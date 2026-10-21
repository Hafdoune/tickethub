<?php

namespace App\Dto;

use App\Enum\TicketPriority;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateTicketPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 5, max: 150)]
        public readonly string $title = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 10)]
        public readonly string $description = '',

        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly ?int $categoryId = null,

        public readonly TicketPriority $priority = TicketPriority::Medium,
    ) {
    }
}
