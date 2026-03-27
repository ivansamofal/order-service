<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Microservices\SharedBundle\Entity\AbstractProduct;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product extends AbstractProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $name, float $price, int $quantity)
    {
        $this->id = Uuid::v4();
        $this->name = $name;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setName(string $name): static
    {
        parent::setName($name);
        $this->touch();

        return $this;
    }

    public function setPrice(float $price): static
    {
        parent::setPrice($price);
        $this->touch();

        return $this;
    }

    public function setQuantity(int $quantity): static
    {
        parent::setQuantity($quantity);
        $this->touch();

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
        ] + parent::toArray();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
