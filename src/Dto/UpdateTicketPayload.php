<?php

namespace App\Dto;

use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTicketPayload
{
    public function __construct(
        #[Assert\Length(min: 5, max: 150)]
        public readonly ?string $title = null,

        #[Assert\Length(min: 10)]
        public readonly ?string $description = null,

        #[Assert\Positive]
        public readonly ?int $categoryId = null,

        public readonly ?TicketPriority $priority = null,

        public readonly ?TicketStatus $status = null,

        #[Assert\PositiveOrZero]
        public readonly ?int $assigneeId = null,
    ) {
    }

    public function changesContent(): bool
    {
        return $this->title !== null || $this->description !== null || $this->categoryId !== null || $this->priority !== null;
    }

    public function changesWorkflow(): bool
    {
        return $this->status !== null || $this->assigneeId !== null;
    }
}
