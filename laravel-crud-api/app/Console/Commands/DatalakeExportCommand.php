<?php

namespace App\Console\Commands;

use App\Services\Datalake\ProcessAnalyticsFactExporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DatalakeExportCommand extends Command
{
    protected $signature = 'datalake:export
                            {--format=jsonl,csv : Comma-separated list: jsonl, csv}
                            {--skip-table : Do not rebuild processo_analytics_facts}
                            {--name= : Optional basename for generated files (without extension)}';

    protected $description = 'Exporta dataset analítico consolidado (Req. 7): arquivos em storage (disk local) + tabela processo_analytics_facts.';

    public function handle(ProcessAnalyticsFactExporter $exporter): int
    {
        $formats = collect(explode(',', (string) $this->option('format')))
            ->map(fn (string $v) => trim($v))
            ->filter()
            ->values()
            ->all();

        foreach ($formats as $f) {
            if (! in_array($f, ['jsonl', 'csv'], true)) {
                $this->error('Formato inválido: '.$f.' (use jsonl e/ou csv)');

                return self::INVALID;
            }
        }

        if ($formats === []) {
            $this->error('Informe ao menos um formato.');

            return self::INVALID;
        }

        $skipTable = (bool) $this->option('skip-table');

        if (! $skipTable) {
            $count = $exporter->syncTable();
            $this->info('Tabela processo_analytics_facts atualizada ('.$count.' linhas).');
        }

        $basename = $this->option('name') ? (string) $this->option('name') : null;
        $paths = $exporter->writeExports($formats, $basename);

        $root = Storage::disk('local')->path('');
        foreach ($paths as $fmt => $rel) {
            $this->line($fmt.': '.$root.$rel);
        }

        return self::SUCCESS;
    }
}
