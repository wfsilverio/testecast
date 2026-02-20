<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accounts')]
class Account
{
    // Identificador da conta
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Nome do titular
    #[ORM\Column(type: 'string', length: 120)]
    private string $ownerName;

    // Saldo monetário com precisão de 2 casas
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => '0.00'])]
    private string $balance = '0.00';

    // Data de criação
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $ownerName)
    {
        $this->ownerName = $ownerName;
        $this->createdAt = new \DateTimeImmutable('now');
    }

    // Getters/Setters básicos
    public function getId(): ?int { return $this->id; }
    public function getOwnerName(): string { return $this->ownerName; }
    public function getBalance(): string { return $this->balance; }
    public function setBalance(string $balance): void { $this->balance = $balance; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setOwnerName(string $ownerName): void { $this->ownerName = $ownerName; }
}
