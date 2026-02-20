# Como rodar o projeto

## Pré‑requisitos
- Docker Desktop instalado e em execução.
- Portas livres: 8080 (app) e 5432 (PostgreSQL).

## Subir a aplicação
1. No diretório do projeto:

```bash
docker compose up -d --build
```

2. Acessos:
- App (Home): http://localhost:8080/
- Healthcheck: http://localhost:8080/health

## Fluxo básico
- Criar conta: Menu “Nova Conta”
- Abrir conta: “Abrir” na listagem
- Operações na conta: Creditar, Debitar e Transferir
- Editar conta: botão “Editar” (altera titular e saldo)

## Variáveis de ambiente (docker-compose.yml)
- DB_HOST=db
- DB_PORT=5432
- DB_NAME=appdb
- DB_USER=appuser
- DB_PASSWORD=apppass
- AUTO_SCHEMA=1 (gera/atualiza o schema automaticamente em dev)

## Testes
Execute os testes unitários (PHPUnit) dentro do container da aplicação:

```bash
docker compose exec app php ./vendor/bin/phpunit
```

## Comandos úteis
- Logs tempo real:
```bash
docker compose logs -f app
docker compose logs -f db
```
- Parar os serviços:
```bash
docker compose down
```
- Resetar banco (apaga volume):
```bash
docker compose down -v
```

## Solução de problemas
- Docker não sobe imagens: abra o Docker Desktop e aguarde “Docker is running”.
- Conflito de portas: altere as portas mapeadas em `docker-compose.yml`.
- Erros de conexão ao banco: verifique os valores de `DB_*` no `docker-compose.yml` e o health do serviço `db`.

## Estrutura (resumo)
- `app/` — código PHP (Slim 3, Doctrine ORM, Twig/Bootstrap)
- `app/public/` — ponto de entrada (index.php) e assets
- `app/templates/` — templates Twig
- `app/tests/` — testes PHPUnit
- `docker-compose.yml` — orquestração de app e Postgres
- `php/Dockerfile` — imagem da aplicação

Pronto! Com o Docker ativo, o comando `docker compose up -d --build` compila e inicia tudo automaticamente.
*** End Patch```} true 予約なし !*** 종료 patch tool input invalid? The tool requires FREEFORM patch syntax. We provided. Let's ensure formatting matches tool grammar: It requires header lines and each added line must be prefixed with " +". We have that. Ends with "*** End Patch\n". We included a trailing code fence accidentally at the end "```} true 予約なし". That was accidental. Need to resend apply_patch properly. Let's redo clean. 
]】
}```  Let's send again clean.  -->
