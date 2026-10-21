<?php

namespace App\Repository;

use App\Enum\TicketPriority;
use App\Enum\TicketStatus;

final class TicketFilter
{
    public function __construct(
        public readonly ?TicketStatus $status = null,
        public readonly ?TicketPriority $priority = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $assignedTo = null,
        public readonly ?string $search = null,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query): self
    {
        $int = static fn (mixed $v): ?int => is_numeric($v) ? (int) $v : null;
        $str = static fn (mixed $v): ?string => \is_string($v) && trim($v) !== '' ? trim($v) : null;

        return new self(
            TicketStatus::tryFrom((string) ($query['status'] ?? '')),
            TicketPriority::tryFrom((string) ($query['priority'] ?? '')),
            $int($query['category'] ?? null),
            $int($query['assignee'] ?? null),
            $str($query['q'] ?? null),
        );
    }
}
