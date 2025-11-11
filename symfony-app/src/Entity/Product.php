<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'tblProductData')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ]
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'intProductDataId', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'strProductName', type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $productName = '';

    #[ORM\Column(name: 'strProductDesc', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $productDesc = '';

    #[ORM\Column(name: 'strProductCode', type: Types::STRING, length: 10, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    private string $productCode = '';

    #[ORM\Column(name: 'dtmAdded', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $added = null;

    #[ORM\Column(name: 'dtmDiscontinued', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $discontinued = null;

    #[ORM\Column(name: 'stmTimestamp', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(name: 'stock', type: Types::INTEGER, nullable: true, options: ['default' => 0])]
    private ?int $stock = 0;

    #[ORM\Column(name: 'price', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => 0])]
    private ?string $price = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): self
    {
        $this->productName = $productName;
        return $this;
    }

    public function getProductDesc(): string
    {
        return $this->productDesc;
    }

    public function setProductDesc(string $productDesc): self
    {
        $this->productDesc = $productDesc;
        return $this;
    }

    public function getProductCode(): string
    {
        return $this->productCode;
    }

    public function setProductCode(string $productCode): self
    {
        $this->productCode = $productCode;
        return $this;
    }

    public function getAdded(): ?\DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(?\DateTimeInterface $added): self
    {
        $this->added = $added;
        return $this;
    }

    public function getDiscontinued(): ?\DateTimeInterface
    {
        return $this->discontinued;
    }

    public function setDiscontinued(?\DateTimeInterface $discontinued): self
    {
        $this->discontinued = $discontinued;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;
        return $this;
    }
}

