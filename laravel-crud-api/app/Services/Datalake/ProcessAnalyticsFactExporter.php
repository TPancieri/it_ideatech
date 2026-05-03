<?php

namespace App\Services\Datalake;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ProcessAnalyticsFactExporter
{
    /**
     * @return list<array<string,mixed>>
     */
    public function buildRows(): array
    {
        $latestResposta = DB::table('processo_respostas')
            ->selectRaw('processo_id')
            ->selectRaw('cliente_id')
            ->selectRaw('MAX(id) as id')
            ->groupBy('processo_id', 'cliente_id');

        $rows = DB::query()
            ->from('cliente_processo as cp')
            ->join('processos as p', 'p.id', '=', 'cp.processo_id')
            ->join('users as u', 'u.id', '=', 'p.responsible_user_id')
            ->join('clientes as c', 'c.id', '=', 'cp.cliente_id')
            ->leftJoinSub($latestResposta, 'lr', function ($join): void {
                $join
                    ->on('lr.processo_id', '=', 'cp.processo_id')
                    ->on('lr.cliente_id', '=', 'cp.cliente_id');
            })
            ->leftJoin('processo_respostas as pr', 'pr.id', '=', 'lr.id')
            ->leftJoin('processo_assinatura_tokens as pat', function ($join): void {
                $join
                    ->on('pat.processo_id', '=', 'cp.processo_id')
                    ->on('pat.cliente_id', '=', 'cp.cliente_id');
            })
            ->select([
                'cp.processo_id',
                'cp.cliente_id',
                'cp.sort_order',

                'p.title as processo_title',
                'p.category as processo_category',
                'p.status as processo_status',
                'p.created_at as processo_created_at',
                'p.updated_at as processo_updated_at',
                'p.document_path as document_path',

                'p.responsible_user_id as responsible_user_id',
                'u.email as responsible_user_email',

                'c.name as signatario_nome',
                'c.email as signatario_email',
                'c.role as signatario_funcao',
                'c.sector as signatario_setor',
                'c.status as signatario_status',

                'pr.tipo as tipo_resposta',
                'pr.justificativa as justificativa_reprovacao',
                'pr.created_at as resposta_em',

                DB::raw('MIN(pat.created_at) as convite_primeiro_envio_em'),
                DB::raw('MAX(pat.created_at) as convite_ultimo_envio_em'),
                DB::raw('COUNT(pat.id) as convites_enviados'),
            ])
            ->groupBy([
                'cp.processo_id',
                'cp.cliente_id',
                'cp.sort_order',

                'p.title',
                'p.category',
                'p.status',
                'p.created_at',
                'p.updated_at',
                'p.document_path',

                'p.responsible_user_id',
                'u.email',

                'c.name',
                'c.email',
                'c.role',
                'c.sector',
                'c.status',

                'pr.tipo',
                'pr.justificativa',
                'pr.created_at',
            ])
            ->orderBy('cp.processo_id')
            ->orderBy('cp.sort_order')
            ->orderBy('cp.cliente_id')
            ->get();

        $out = [];

        foreach ($rows as $row) {
            $createdAt = $this->parseOptionalCarbon($row->processo_created_at);
            $answeredAt = $this->parseOptionalCarbon($row->resposta_em);

            $hours = null;
            if ($createdAt && $answeredAt) {
                $hours = round($answeredAt->diffInSeconds($createdAt) / 3600, 3);
            }

            $out[] = [
                'processo_id' => (int) $row->processo_id,
                'titulo' => (string) $row->processo_title,
                'categoria' => (string) $row->processo_category,
                'status' => (string) $row->processo_status,
                'document_path' => $row->document_path !== null ? (string) $row->document_path : null,

                'responsible_user_id' => (int) $row->responsible_user_id,
                'responsible_user_email' => $row->responsible_user_email !== null ? (string) $row->responsible_user_email : null,

                'processo_created_at' => $this->optionalIso($createdAt),
                'processo_updated_at' => $this->optionalIso($this->parseOptionalCarbon($row->processo_updated_at)),

                'signatario_id' => (int) $row->cliente_id,
                'signatario_nome' => (string) $row->signatario_nome,
                'signatario_email' => (string) $row->signatario_email,
                'signatario_funcao' => $row->signatario_funcao !== null ? (string) $row->signatario_funcao : null,
                'signatario_setor' => $row->signatario_setor !== null ? (string) $row->signatario_setor : null,
                'signatario_status' => $row->signatario_status !== null ? (string) $row->signatario_status : null,
                'sort_order' => (int) $row->sort_order,

                'convite_primeiro_envio_em' => $this->optionalIso($this->parseOptionalCarbon($row->convite_primeiro_envio_em ?? null)),
                'convite_ultimo_envio_em' => $this->optionalIso($this->parseOptionalCarbon($row->convite_ultimo_envio_em ?? null)),
                'convites_enviados' => (int) ($row->convites_enviados ?? 0),

                'tipo_resposta' => $row->tipo_resposta !== null ? (string) $row->tipo_resposta : null,
                'resposta_em' => $this->optionalIso($answeredAt),
                'tempo_resposta_em_horas' => $hours,
                'justificativa_reprovacao' => $row->justificativa_reprovacao !== null ? (string) $row->justificativa_reprovacao : null,
            ];
        }

        return $out;
    }

    public function syncTable(): int
    {
        $rows = $this->buildRows();

        DB::transaction(function () use ($rows): void {
            DB::table('processo_analytics_facts')->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                $now = now();

                $payload = array_map(static function (array $r) use ($now): array {
                    return [
                        'processo_id' => $r['processo_id'],
                        'processo_title' => $r['titulo'],
                        'processo_category' => $r['categoria'],
                        'processo_status' => $r['status'],
                        'processo_created_at' => $r['processo_created_at'],
                        'processo_updated_at' => $r['processo_updated_at'],
                        'document_path' => $r['document_path'],
                        'responsible_user_id' => $r['responsible_user_id'],
                        'responsible_user_email' => $r['responsible_user_email'],
                        'signatario_id' => $r['signatario_id'],
                        'signatario_nome' => $r['signatario_nome'],
                        'signatario_email' => $r['signatario_email'],
                        'signatario_funcao' => $r['signatario_funcao'],
                        'signatario_setor' => $r['signatario_setor'],
                        'signatario_status' => $r['signatario_status'],
                        'sort_order' => $r['sort_order'],
                        'convite_primeiro_envio_em' => $r['convite_primeiro_envio_em'],
                        'convite_ultimo_envio_em' => $r['convite_ultimo_envio_em'],
                        'convites_enviados' => $r['convites_enviados'],
                        'tipo_resposta' => $r['tipo_resposta'],
                        'resposta_em' => $r['resposta_em'],
                        'tempo_resposta_horas' => $r['tempo_resposta_em_horas'],
                        'justificativa_reprovacao' => $r['justificativa_reprovacao'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $chunk);

                DB::table('processo_analytics_facts')->insert($payload);
            }
        });

        return count($rows);
    }

    /**
     * @param  list<'jsonl'|'csv'>  $formats
     * @return array<string,string> disk-relative paths keyed by format
     */
    public function writeExports(array $formats, ?string $basename = null): array
    {
        $basename ??= 'process_analytics_facts_'.now()->format('Ymd_His');

        $disk = Storage::disk('local');
        $dir = 'datalake';

        if (! $disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $rows = $this->buildRows();
        $paths = [];

        if (in_array('jsonl', $formats, true)) {
            $rel = $dir.'/'.$basename.'.jsonl';
            $disk->put($rel, '');
            $disk->append($rel, implode("\n", array_map(static fn (array $r): string => json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $rows)));
            if ($rows !== []) {
                $disk->append($rel, "\n");
            }
            $paths['jsonl'] = $rel;
        }

        if (in_array('csv', $formats, true)) {
            $rel = $dir.'/'.$basename.'.csv';
            $handle = fopen('php://temp', 'r+');
            if ($handle === false) {
                throw new \RuntimeException('Failed to open temp stream for CSV.');
            }

            if ($rows !== []) {
                fputcsv($handle, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
            } else {
                fputcsv($handle, [
                    'processo_id',
                    'titulo',
                    'categoria',
                    'status',
                ]);
            }

            rewind($handle);
            $csv = stream_get_contents($handle) ?: '';
            fclose($handle);

            $disk->put($rel, $csv);
            $paths['csv'] = $rel;
        }

        return $paths;
    }

    private function parseOptionalCarbon(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::parse($value->format('Y-m-d H:i:s.u'), $value->getTimezone());
        }

        return Carbon::parse((string) $value);
    }

    private function optionalIso(?CarbonInterface $dt): ?string
    {
        if (! $dt) {
            return null;
        }

        return $dt->toIso8601String();
    }
}
