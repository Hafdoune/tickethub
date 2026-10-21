<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CommentPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 5000)]
        public readonly string $content = '',
    ) {
    }
}
