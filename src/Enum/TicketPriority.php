<?php

namespace App\Enum;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Medium => 'Normale',
            self::High => 'Haute',
            self::Urgent => 'Urgente',
        };
    }
}
