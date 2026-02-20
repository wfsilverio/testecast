<?php
namespace App\Service;

use App\Entity\Account;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;

class AccountService
{
    // Serviço de domínio para operações em contas com controle transacional
    public function __construct(private EntityManagerInterface $em) {}

    public function createAccount(string $ownerName): Account
    {
        // Cria conta com saldo inicial 0.00
        $account = new Account($ownerName);
        $this->em->persist($account);
        $this->em->flush();
        return $account;
    }

    public function credit(int $accountId, string $amount): Account
    {
        // Bloqueia a linha da conta e credita o valor, registrando transação
        return $this->withLockedAccount($accountId, function (Account $acc) use ($amount) {
            $newBalance = bcadd($acc->getBalance(), $amount, 2);
            $acc->setBalance($newBalance);
            $this->em->persist(new Transaction($acc, Transaction::TYPE_CREDIT, $amount, $newBalance));
            return $acc;
        });
    }

    public function debit(int $accountId, string $amount): Account
    {
        // Bloqueia e valida saldo; impede saldo negativo
        return $this->withLockedAccount($accountId, function (Account $acc) use ($amount) {
            $newBalance = bcsub($acc->getBalance(), $amount, 2);
            if (bccomp($newBalance, '0.00', 2) < 0) {
                throw new \RuntimeException('Saldo insuficiente');
            }
            $acc->setBalance($newBalance);
            $this->em->persist(new Transaction($acc, Transaction::TYPE_DEBIT, $amount, $newBalance));
            return $acc;
        });
    }

    public function transfer(int $fromId, int $toId, string $amount): void
    {
        // Ordena ids para evitar deadlock na aquisição de bloqueios
        if ($fromId === $toId) {
            throw new \InvalidArgumentException('Conta de origem e destino devem ser diferentes');
        }
        $ids = [$fromId, $toId];
        sort($ids);

        $this->em->beginTransaction();
        try {
            // PESSIMISTIC_WRITE garante exclusão mútua durante a transferência
            $first = $this->em->find(Account::class, $ids[0], LockMode::PESSIMISTIC_WRITE);
            $second = $this->em->find(Account::class, $ids[1], LockMode::PESSIMISTIC_WRITE);
            if (!$first || !$second) {
                throw new \RuntimeException('Conta não encontrada');
            }
            $from = $fromId === $first->getId() ? $first : $second;
            $to = $toId === $first->getId() ? $first : $second;

            // Debita origem com validação
            $newFrom = bcsub($from->getBalance(), $amount, 2);
            if (bccomp($newFrom, '0.00', 2) < 0) {
                throw new \RuntimeException('Saldo insuficiente');
            }
            $from->setBalance($newFrom);
            $this->em->persist(new Transaction($from, Transaction::TYPE_TRANSFER_OUT, $amount, $newFrom));

            // Credita destino
            $newTo = bcadd($to->getBalance(), $amount, 2);
            $to->setBalance($newTo);
            $this->em->persist(new Transaction($to, Transaction::TYPE_TRANSFER_IN, $amount, $newTo));

            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function updateAccount(int $id, string $ownerName, string $balance): Account
    {
        // Atualiza nome do titular e saldo diretamente
        return $this->withLockedAccount($id, function (Account $acc) use ($ownerName, $balance) {
            $acc->setOwnerName($ownerName);
            $acc->setBalance($balance);
            return $acc;
        });
    }

    private function withLockedAccount(int $accountId, callable $fn): Account
    {
        // Template method: inicia transação, bloqueia a conta, aplica a função e confirma/rollback
        $this->em->beginTransaction();
        try {
            $acc = $this->em->find(\App\Entity\Account::class, $accountId, LockMode::PESSIMISTIC_WRITE);
            if (!$acc) {
                throw new \RuntimeException('Conta não encontrada');
            }
            $result = $fn($acc);
            $this->em->flush();
            $this->em->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
