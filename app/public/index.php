<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
use Slim\App;
use App\Bootstrap;
use App\Controller\AccountController;

require __DIR__ . '/../vendor/autoload.php';

// Cria container e aplica no Slim 3
$container = Bootstrap::createContainer();

$app = new App($container);

// Endpoint simples de healthcheck
$app->get('/health', function ($request, $response) {
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Rotas da aplicação
$app->get('/', AccountController::class . ':home');
$app->get('/admin/accounts/new', AccountController::class . ':newAccountForm');
$app->post('/admin/accounts', AccountController::class . ':createAccount');
$app->get('/admin/accounts/{id}/edit', AccountController::class . ':editAccountForm');
$app->post('/admin/accounts/{id}', AccountController::class . ':updateAccount');

$app->get('/accounts/{id}', AccountController::class . ':showAccount');
$app->post('/accounts/{id}/credit', AccountController::class . ':credit');
$app->post('/accounts/{id}/debit', AccountController::class . ':debit');
$app->post('/accounts/transfer', AccountController::class . ':transfer');

$app->run();
