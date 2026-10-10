<?php

declare(strict_types=1);

namespace App\DTO;

use Tusk\Validation\Constraint\Length;
use Tusk\Validation\Constraint\NotBlank;

final readonly class ProductInput
{
    public function __construct(
        #[NotBlank]
        #[Length(max: 120)]
        public string $name,
        #[NotBlank]
        #[Length(max: 40)]
        public string $sku,
    ) {}
}
