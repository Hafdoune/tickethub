<?php

namespace App\Enum;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouvert',
            self::InProgress => 'En cours',
            self::Resolved => 'Résolu',
            self::Closed => 'Fermé',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Closed;
    }
}
