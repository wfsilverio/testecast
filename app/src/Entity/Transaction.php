<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'transactions')]
class Transaction
{
    // Tipos de transação
    public const TYPE_CREDIT = 'CREDIT';
    public const TYPE_DEBIT = 'DEBIT';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';

    // Identificador da transação
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Conta relacionada (FK)
    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    // Tipo
    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    // Valor movimentado
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $amount;

    // Saldo após a operação
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $balanceAfter;

    // Data/hora
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Account $account, string $type, string $amount, string $balanceAfter)
    {
        $this->account = $account;
        $this->type = $type;
        $this->amount = $amount;
        $this->balanceAfter = $balanceAfter;
        $this->createdAt = new \DateTimeImmutable('now');
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getAccount(): Account { return $this->account; }
    public function getType(): string { return $this->type; }
    public function getAmount(): string { return $this->amount; }
    public function getBalanceAfter(): string { return $this->balanceAfter; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
