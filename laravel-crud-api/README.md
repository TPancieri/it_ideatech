# Laravel CRUD API — Trilha de Assinatura Digital (Teste Prático)

Este repositório implementa uma API em **Laravel** para cadastro de **signatários**, gestão de **processos digitais**, **upload de documentos**, **associação de signatários**, **convites por e-mail via fila**, e **aprovação/reprovação por link com token** (com registros de histórico e auditoria).

> Observação: este README foi escrito para cobrir os itens típicos de entrega do teste (instalação, migrations/seeders, filas/jobs, como testar o fluxo). Ajuste host/porta conforme seu Docker/local.

## Requisitos

- PHP **8.3+**
- Composer
- Banco: **PostgreSQL** (no Docker deste projeto) ou SQLite (ambiente local/dev)
- Docker + Docker Compose (recomendado)

## Como rodar (Docker — recomendado)

Na pasta do projeto (onde está o `docker-compose.yml`):

```bash
docker compose up -d --build
```

### Variáveis de ambiente

O container normalmente usa `.env` montado no projeto. Pontos importantes:

- **`APP_URL`**: deve bater com o host/porta que você abre no navegador/Postman (ex.: `http://localhost:8000`). Isso impacta URLs geradas em e-mails (`route()` / `url()`).
- **Banco (Postgres no compose típico)**:
    - `DB_CONNECTION=pgsql`
    - `DB_HOST=db` (**somente dentro da rede Docker**)
    - `DB_PORT=5432`
    - credenciais conforme seu `docker-compose.yml`
- **Fila**:
    - `QUEUE_CONNECTION=database` (há migration da tabela `jobs`)
- **E-mail (desenvolvimento)**:
    - `MAIL_MAILER=log` grava e-mails no log (`storage/logs/laravel.log`)

### Migrations + seed

Dentro do container da aplicação (ex.: serviço/container `crud-app`):

```bash
docker exec -it crud-app php artisan migrate
docker exec -it crud-app php artisan db:seed
```

O `DatabaseSeeder` **não cria mais usuário fixo** por padrão: o operador deve **cadastrar-se na página inicial** (`/`) e usar esse usuário como **responsável** (`responsible_user_id`) nos processos. O seeder continua disponível para você adicionar dados opcionais (veja comentários em `database/seeders/DatabaseSeeder.php`).

### Acesso ao sistema (web + autenticação por sessão)

1. Abra a raiz do site (ex.: `http://localhost:8000/`).
2. Use o formulário **Cadastro** (nome, e-mail, senha) ou **Login** se já tiver conta.
3. Após entrar, você cai no **Painel** (`/painel`) com links para Dashboard, Relatórios, Análise, Auditoria e **Signatários** (formulário web do Req. 1, substituindo o fluxo só via Postman para esse cadastro).
4. Rotas web operacionais (dashboard, relatórios, `/analise`, `/auditoria`, signatários) exigem **usuário autenticado**. As rotas públicas de **assinatura por token** (`/assinatura/...`) continuam sem login.

### Worker da fila (obrigatório para processar Jobs)

Em outro terminal:

```bash
docker exec -it crud-app php artisan queue:work --tries=1
```

Sem o worker, jobs ficam na tabela `jobs` e **e-mails de convite não saem**.

### Link público de storage (opcional)

Se você for servir arquivos via `/storage/...`:

```bash
docker exec -it crud-app php artisan storage:link
```

## Como rodar (sem Docker)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

E em outro terminal:

```bash
php artisan queue:work --tries=1
```

## Testes automatizados

```bash
php artisan test
```

## Export consolidado para BI / datalake (Req. 7)

Este projeto inclui uma rotina de **consolidação analítica** que:

- reconstrói a tabela **`processo_analytics_facts`** (uma linha por combinação **processo × signatário** na associação `cliente_processo`);
- gera arquivos **JSON Lines** e **CSV** no disco **`local`** do Laravel.

No Laravel 13 o disk `local` aponta para `storage/app/private`, então os exports ficam em:

- `storage/app/private/datalake/`

**Após puxar o código ou adicionar migrations novas**, rode as migrations no ambiente que aponta para o banco (ex.: `docker exec -it crud-app php artisan migrate`). Sem a tabela `processo_analytics_facts`, o comando `datalake:export` falha ao reconstruir o dataset (use `--skip-table` só se quiser gerar arquivos sem atualizar a tabela).

### Como gerar manualmente

```bash
php artisan datalake:export
```

Opções úteis:

- `--format=jsonl,csv` (padrão: ambos)
- `--skip-table` (gera só os arquivos, sem rebuild da tabela)
- `--name=meu_snapshot` (nome base dos arquivos)

### Agendamento

O projeto registra um schedule diário em `bootstrap/app.php` (03:15) para rodar `datalake:export`. Em produção, use o scheduler do Laravel (`schedule:run`) conforme a documentação.

### Como isso vira “datalake” na prática

Os arquivos em `datalake/` são pensados para serem **copiados para um storage externo** (S3/GCS/Azure Blob) e ingeridos por pipelines (Airflow/DBT/Spark/Batch SQL) ou carregados direto em ferramentas de BI.

Campos principais no dataset (CSV/JSONL): identificação do processo (`processo_id`, `titulo`, `categoria`, `status`, timestamps), dados do signatário (`signatario_*`, `sort_order`), convites (`convite_*`, `convites_enviados`), resposta (`tipo_resposta`, `resposta_em`, `tempo_resposta_em_horas`, `justificativa_reprovacao`) e responsável (`responsible_user_*`).

## Auditoria (Req. 8)

- **Tabela** `auditoria_eventos` (ação, subject/actor polimórficos, `before`/`after`/`meta` em JSON, IP e user-agent).
- **APIs instrumentadas** (além do que já existia no fluxo de assinatura e jobs de convite): cadastro/edição/inativação de signatário; criação/edição/exclusão de processo; alteração de documento; vínculo/sync/remoção de signatários; enfileiramento de convites (`POST /processo/{id}/convites`).
- **Telas**: listagem global com filtros em `GET /auditoria`; no detalhe do processo (`/dashboard/processo/{id}`) continuam os eventos cujo **subject** é aquele processo, com atalho para a listagem filtrada por `processo_id`.

## Modelagem principal (resumo)

### Signatários

Tabela: `clientes` (representa o signatário).

### Processos

Tabela: `processos`

Campos principais: `title`, `description`, `status`, `responsible_user_id` (FK `users.id`), `category`, `created_at` (timestamps), `document_path` (upload).

### Associação processo ↔ signatário

Tabela pivô: `cliente_processo`

- `sort_order`:
    - **0 para todos** ⇒ modo **paralelo** (todos podem responder)
    - **`1,2,3...`** ⇒ modo **sequencial por degraus** (mesmo número ⇒ paralelo dentro do mesmo degrau)

### Upload de documento

- Endpoint dedicado (multipart): ver seção “Endpoints”.
- Arquivo salvo em `storage/app/public/...` com `document_path` relativo ao disk `public`.

### Fluxo de assinatura (Req. 3)

Tabelas:

- `processo_assinatura_tokens`: token **hash** + expiração + consumo
- `processo_respostas`: aprovação/reprovação + IP + user agent + justificativa (reprovação)
- `processo_status_histories`: histórico de mudanças de status do processo
- `auditoria_eventos`: auditoria genérica (ex.: envio de convite)

Política de status (transições) está centralizada em `app/Services/ProcessoStatusPolicy.php`.

## Endpoints (API)

Base típica: `http://localhost:8000/api`

## Dashboard

Base típica: `http://localhost:8000`

- `GET /dashboard`
    - cards com totais por status
    - tempo médio de aprovação (via histórico `approved`)
    - lista de processos **pendentes** há mais de **N** dias (parâmetro `overdue_days`)
    - filtros: `status`, `category`, `signatario_id`, `from`, `to`
    - tabela de processos (até 200 linhas, sem paginação)
- `GET /dashboard/processo/{id}`
    - detalhes + histórico de status + respostas + auditoria

## Relatórios

Base típica: `http://localhost:8000`

- `GET /relatorios/status` (+ export `GET /relatorios/status.csv`)
    - quantidade por status + percentual do total
- `GET /relatorios/produtividade-signatarios?from=&to=` (+ export CSV)
    - total de aprovações/reprovações por signatário + tempo médio de resposta (primeira resposta por processo)
- `GET /relatorios/processos-periodo?grain=day|week|month&from=&to=` (+ export CSV)
    - criados por período + concluídos por período (conclusão = primeira transição para `approved/rejected` no histórico)
- `GET /relatorios/reprovacoes?from=&to=` (+ export CSV)
    - processo, signatário, data, justificativa

## Análise de Dados (Web — Requisito 6)

Base típica: `http://localhost:8000`

- `GET /analise?grain=day|week|month&from=&to=`
    - **Qual o tempo médio de aprovação?**
        - fórmula: média de \(`processo_status_histories.created_at - processos.created_at`\) para eventos com `to_status = approved`
    - **Quais signatários mais aprovam/reprovam?**
        - fonte: `processo_respostas` agregando por `cliente_id` (`tipo=approved` e `tipo=rejected`)
        - tempo médio de resposta: primeira resposta do signatário em cada processo vs `processos.created_at`
    - **Qual categoria possui maior volume?**
        - fonte: `processos` agrupando por `category`
    - **Qual status concentra mais processos atualmente?**
        - fonte: `processos` agrupando por `status`
    - **Quantos processos foram criados/concluídos por período?**
        - criados: `processos.created_at` agrupado por período
        - concluídos: primeira transição para `approved`/`rejected` em `processo_status_histories`

### Signatários

- `GET /cliente`
- `POST /cliente`
- `GET /cliente/{id}`
- `PUT/PATCH /cliente/{id}`
- `DELETE /cliente/{id}` (**inativa** signatário no comportamento atual)

### Processos (CRUD)

- `GET /processo`
- `POST /processo`
- `GET /processo/{id}`
- `PUT/PATCH /processo/{id}`
- `DELETE /processo/{id}`

### Upload de documento do processo

- `POST /processo/{id}/document` (**multipart/form-data**, campo arquivo: `document`)
- `GET /processo/{id}/document` (abre/stream do arquivo salvo; útil para validar upload)

### Signatários do processo (pivô)

- `GET /processo/{id}/signatarios`
- `POST /processo/{id}/signatarios` (JSON: `{ "cliente_id": 1, "sort_order": 0 }`)
- `POST /processo/{id}/signatarios/sync` (substitui lista inteira)
- `DELETE /processo/{id}/signatarios/{clienteId}`

### Convites (enfileira e-mails)

- `POST /processo/{id}/convites`
    - JSON opcional: `{ "ttl_hours": 72 }`

### Fluxo web por token (navegador)

Rotas (fora de `/api`):

- `GET /assinatura/{token}`
- `POST /assinatura/{token}/aprovar`
- `POST /assinatura/{token}/reprovar` (justificativa obrigatória)

## Como testar o fluxo completo

1. Subir app + db + worker da fila
2. `db:seed` (para existir `users.id` do responsável)
3. Criar signatários (`POST /cliente`)
4. Criar processo (`POST /processo`) usando `responsible_user_id` válido
5. Associar signatários ao processo (`POST /processo/{id}/signatarios`)
6. Enviar convites (`POST /processo/{id}/convites`)
7. Pegar URL do e-mail:
    - se `MAIL_MAILER=log`, ler `storage/logs/laravel.log` dentro do container
8. Abrir `GET /assinatura/{token}` e aprovar/reprovar

### Erros comuns

- **500 em `/convites` com controller vazio/corrompido no volume**: verifique se os arquivos PHP não estão `0 bytes` no container.
- **`DB_HOST=db` não resolve fora do Docker**: rode `php artisan` **dentro** do container, ou ajuste host para `127.0.0.1` se rodar artisan no Windows apontando para Postgres publicado.

## Autenticação

O projeto inclui rota padrão:

- `GET /api/user` com `auth:sanctum`

Para o escopo atual do teste, os fluxos principais via Postman foram pensados para validação funcional **sem obrigar UI de login**, mas Sanctum pode ser habilitado/expandido.

## Atual vs Final

Implementado no momento:

- Req. 1 signatários
- Req. 2 processos + upload + associação + status mínimos (via policy de transição)
- Req. 3 convites assíncronos + token + aprovação/reprovação + registros + histórico
- Base de auditoria/histórico
- Req. 4 dashboard + filtros + SLA/atrasados + relatórios (5)
- Req. 6 camada de consultas analíticas + README de indicadores
- Req. 7 export consolidado “datalake-like”
- Req. 8 ampliar auditoria para todas ações relevantes + tela de detalhes

Pendências:

- Altercoes de fluxo e visuais para UX, pagina home e criacao de massa de dados
- Implementacao de diferenciais
