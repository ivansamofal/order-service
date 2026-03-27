<?php

namespace App\Service;

use App\DTO\CreateProductRequest;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Microservices\SharedBundle\Message\ProductMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function create(CreateProductRequest $dto): Product
    {
        $product = new Product($dto->name, $dto->price, $dto->quantity);

        $this->productRepository->save($product, flush: true);

        $this->messageBus->dispatch(new ProductMessage(
            id: (string) $product->getId(),
            name: $product->getName(),
            price: $product->getPrice(),
            quantity: $product->getQuantity(),
        ));

        return $product;
    }

    /**
     * @return Product[]
     */
    public function findAll(): array
    {
        return $this->productRepository->findAllProducts();
    }

    public function findById(string $id): ?Product
    {
        return $this->productRepository->find($id);
    }
}
