<?php
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use App\Entity\Account;
use App\Entity\Transaction;
use App\Service\AccountService;

final class AccountServiceTest extends TestCase
{
    private EntityManager $em;
    private AccountService $svc;

    protected function setUp(): void
    {
        // Configura EntityManager apontando para o mesmo schema do app
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/../src/Entity'],
            isDevMode: true
        );
        $connectionParams = [
            'dbname'   => getenv('DB_NAME') ?: 'appdb',
            'user'     => getenv('DB_USER') ?: 'appuser',
            'password' => getenv('DB_PASSWORD') ?: 'apppass',
            'host'     => getenv('DB_HOST') ?: 'db',
            'port'     => (int)(getenv('DB_PORT') ?: 5432),
            'driver'   => 'pdo_pgsql',
        ];
        $conn = DriverManager::getConnection($connectionParams, $config);
        $this->em = new EntityManager($conn, $config);

        // Cria schema limpo para cada teste
        $tool = new SchemaTool($this->em);
        $classes = [
            $this->em->getClassMetadata(Account::class),
            $this->em->getClassMetadata(Transaction::class),
        ];
        $tool->dropSchema($classes);
        $tool->createSchema($classes);

        $this->svc = new AccountService($this->em);
    }

    public function testCreateAccountInitialBalanceZero(): void
    {
        // Nova conta inicia com saldo 0.00
        $acc = $this->svc->createAccount('Alice');
        $this->assertNotNull($acc->getId());
        $this->assertSame('0.00', $acc->getBalance());
    }

    public function testCreditAndDebit(): void
    {
        // Credita e debita mantendo histórico de transações
        $acc = $this->svc->createAccount('Bob');
        $acc = $this->svc->credit($acc->getId(), '100.00');
        $this->assertSame('100.00', $acc->getBalance());
        $acc = $this->svc->debit($acc->getId(), '40.00');
        $this->assertSame('60.00', $acc->getBalance());
    }

    public function testDebitInsufficientThrows(): void
    {
        // Débito sem saldo deve lançar exceção
        $acc = $this->svc->createAccount('Carol');
        $this->expectException(\RuntimeException::class);
        $this->svc->debit($acc->getId(), '1.00');
    }

    public function testTransferMovesFunds(): void
    {
        // Transferência move fundos entre contas
        $a = $this->svc->createAccount('D1');
        $b = $this->svc->createAccount('D2');
        $this->svc->credit($a->getId(), '50.00');
        $this->svc->transfer($a->getId(), $b->getId(), '20.00');

        $aRef = $this->em->find(Account::class, $a->getId());
        $bRef = $this->em->find(Account::class, $b->getId());
        $this->assertSame('30.00', $aRef->getBalance());
        $this->assertSame('20.00', $bRef->getBalance());
    }
}
