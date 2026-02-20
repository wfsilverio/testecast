<?php
namespace App\Controller;

use App\Service\AccountService;
use App\Entity\Account;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

class AccountController
{
    // Controller HTTP com páginas de listagem, detalhes e ações de conta
    public function __construct(private \Psr\Container\ContainerInterface $c) {}

    private function em(): EntityManagerInterface { return $this->c['em']; }           // EntityManager
    private function view(): Twig { return $this->c['view']; }                         // Twig view
    private function svc(): AccountService { return $this->c['accountService']; }      // Serviço de domínio

    public function home(Request $req, Response $res)
    {
        $accounts = $this->em()->getRepository(Account::class)->findBy([], ['id' => 'ASC']);
        $total = '0.00';
        foreach ($accounts as $a) {
            $total = bcadd($total, $a->getBalance(), 2);
        }
        return $this->view()->render($res, 'home.twig', ['accounts' => $accounts, 'totalBalance' => $total]);
    }

    public function newAccountForm(Request $req, Response $res)
    {
        // Página de criação de conta
        return $this->view()->render($res, 'admin/new_account.twig');
    }

    public function createAccount(Request $req, Response $res)
    {
        // Cria a conta e redireciona para a página da conta
        $data = $req->getParsedBody();
        $owner = trim($data['owner'] ?? '');
        if ($owner === '') {
            return $res->withStatus(400)->write('Nome do titular é obrigatório');
        }
        $acc = $this->svc()->createAccount($owner);
        return $res->withHeader('Location', '/accounts/' . $acc->getId())->withStatus(302);
    }

    public function showAccount(Request $req, Response $res, array $args)
    {
        // Exibe saldo e extrato
        $id = (int)$args['id'];
        $acc = $this->em()->find(Account::class, $id);
        if (!$acc) {
            return $res->withStatus(404)->write('Conta não encontrada');
        }
        $txs = $this->em()->getRepository(Transaction::class)->findBy(['account' => $acc], ['id' => 'DESC']);
        return $this->view()->render($res, 'account/show.twig', ['account' => $acc, 'txs' => $txs]);
    }

    public function credit(Request $req, Response $res, array $args)
    {
        // Ação de crédito seguida de redirecionamento
        $id = (int)$args['id'];
        $amount = $this->normalizeAmount($req->getParsedBody()['amount'] ?? '0');
        $this->svc()->credit($id, $amount);
        return $res->withHeader('Location', '/accounts/' . $id)->withStatus(302);
    }

    public function debit(Request $req, Response $res, array $args)
    {
        // Ação de débito; redireciona com erro=saldo quando insuficiente
        $id = (int)$args['id'];
        $amount = $this->normalizeAmount($req->getParsedBody()['amount'] ?? '0');
        try {
            $this->svc()->debit($id, $amount);
        } catch (\RuntimeException $e) {
            return $res->withHeader('Location', '/accounts/' . $id . '?error=saldo')->withStatus(302);
        }
        return $res->withHeader('Location', '/accounts/' . $id)->withStatus(302);
    }

    public function transfer(Request $req, Response $res)
    {
        // Faz a transferência entre contas de forma atômica
        $data = $req->getParsedBody();
        $from = (int)($data['from'] ?? 0);
        $to = (int)($data['to'] ?? 0);
        $amount = $this->normalizeAmount($data['amount'] ?? '0');
        try {
            $this->svc()->transfer($from, $to, $amount);
        } catch (\Throwable $e) {
            return $res->withHeader('Location', '/accounts/' . $from . '?error=transfer')->withStatus(302);
        }
        return $res->withHeader('Location', '/accounts/' . $from)->withStatus(302);
    }

    public function editAccountForm(Request $req, Response $res, array $args)
    {
        $id = (int)$args['id'];
        $acc = $this->em()->find(Account::class, $id);
        if (!$acc) {
            return $res->withStatus(404)->write('Conta não encontrada');
        }
        return $this->view()->render($res, 'admin/edit_account.twig', ['account' => $acc]);
    }

    public function updateAccount(Request $req, Response $res, array $args)
    {
        $id = (int)$args['id'];
        $data = $req->getParsedBody();
        $owner = trim($data['owner'] ?? '');
        $balance = $this->normalizeAmount($data['balance'] ?? '0');
        if ($owner === '') {
            return $res->withStatus(400)->write('Nome do titular é obrigatório');
        }
        $this->svc()->updateAccount($id, $owner, $balance);
        return $res->withHeader('Location', '/accounts/' . $id)->withStatus(302);
    }

    private function normalizeAmount(string $raw): string
    {
        // Converte "1.234,56" para "1234.56" com 2 casas
        $v = str_replace(['.', ','], ['', '.'], trim($raw));
        if ($v === '') $v = '0';
        return number_format((float)$v, 2, '.', '');
    }
}
