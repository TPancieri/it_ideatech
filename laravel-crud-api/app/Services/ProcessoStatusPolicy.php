<?php

namespace App\Services;

final class ProcessoStatusPolicy
{
    public const STATUSES = [
        'pending',
        'in_approval',
        'approved',
        'rejected',
        'canceled',
    ];

    /**
     * Defines allowed transitions between statuses.
     *
     * Keep this aligned with the PDF minimum statuses for "processos digitais".
     */
    private const TRANSITIONS = [
        'pending' => ['in_approval', 'canceled'],
        'in_approval' => ['approved', 'rejected', 'canceled'],
        'approved' => [],
        'rejected' => [],
        'canceled' => [],
    ];

    public static function isKnown(string $status): bool
    {
        return array_key_exists($status, self::TRANSITIONS);
    }

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function validateTransition(string $from, string $to): ?string
    {
        if (! self::isKnown($from) || ! self::isKnown($to)) {
            return 'Status inválido.';
        }

        if ($from === $to) {
            return null;
        }

        if (! self::canTransition($from, $to)) {
            return 'Transição de status não permitida.';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function statusesAllowedForForm(string $current): array
    {
        $next = self::TRANSITIONS[$current] ?? [];

        return array_values(array_unique(array_merge([$current], $next)));
    }
}
