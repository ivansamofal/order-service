<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProductRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Product name is required.')]
        #[Assert\Length(min: 1, max: 255)]
        public readonly string $name,

        #[Assert\NotNull(message: 'Price is required.')]
        #[Assert\GreaterThan(value: 0, message: 'Price must be greater than 0.')]
        public readonly float $price,

        #[Assert\NotNull(message: 'Quantity is required.')]
        #[Assert\GreaterThanOrEqual(value: 0, message: 'Quantity must be 0 or greater.')]
        public readonly int $quantity,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            price: isset($data['price']) ? (float) $data['price'] : 0.0,
            quantity: isset($data['quantity']) ? (int) $data['quantity'] : 0,
        );
    }
}
