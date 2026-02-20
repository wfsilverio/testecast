<?php
namespace App;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;
use Psr\Container\ContainerInterface;

class Bootstrap
{
    public static function createContainer(): ContainerInterface
    {
        // Configura container Slim e serviços principais
        $settings = ['settings' => ['displayErrorDetails' => true]];
        $container = new \Slim\Container($settings);

        $container['em'] = function () {
            // Doctrine ORM com mapeamento por atributos
            $config = ORMSetup::createAttributeMetadataConfiguration(
                paths: [__DIR__ . '/Entity'],
                isDevMode: true
            );
            // Conexão PostgreSQL via variáveis de ambiente (docker-compose)
            $connectionParams = [
                'dbname'   => getenv('DB_NAME') ?: 'appdb',
                'user'     => getenv('DB_USER') ?: 'appuser',
                'password' => getenv('DB_PASSWORD') ?: 'apppass',
                'host'     => getenv('DB_HOST') ?: 'localhost',
                'port'     => (int)(getenv('DB_PORT') ?: 5432),
                'driver'   => 'pdo_pgsql',
            ];
            $conn = DriverManager::getConnection($connectionParams, $config);
            $em = new EntityManager($conn, $config);

            // Em dev: cria/atualiza schema automaticamente
            if (getenv('AUTO_SCHEMA')) {
                self::ensureSchema($em);
            }

            return $em;
        };

        $container['accountService'] = function ($c) {
            // Serviço de domínio para contas
            return new Service\AccountService($c['em']);
        };

        $container['view'] = function () {
            // View engine Twig
            return new \Slim\Views\Twig(__DIR__ . '/../templates', ['cache' => false]);
        };

        return $container;
    }

    private static function ensureSchema(EntityManager $em): void
    {
        // Atualiza o schema com as entidades de Conta e Transação
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = [
            $em->getClassMetadata(Entity\Account::class),
            $em->getClassMetadata(Entity\Transaction::class),
        ];
        $tool->updateSchema($classes, true);
    }
}
