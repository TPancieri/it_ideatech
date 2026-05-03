# Laravel CRUD API — Trilha de Assinatura Digital (Teste Prático)

Este repositório implementa uma API em **Laravel** para cadastro de **signatários**, gestão de **processos digitais**, **upload de documentos**, **associação de signatários**, **convites por e-mail via job assíncrono** (`SendProcessSignatureInviteJob` enfileirado; atende o item **3.2** do PDF do teste), e **aprovação/reprovação por link com token** (com registros de histórico e auditoria). Documentação dos endpoints: [`docs/API_REST.md`](docs/API_REST.md).

> Observação: este README foi escrito para cobrir os itens típicos de entrega do teste (instalação, migrations/seeders, filas/jobs, como testar o fluxo). Ajuste host/porta conforme seu Docker/local.

## Requisitos

- PHP **8.3+**
- Composer
- Banco: **PostgreSQL** (no Docker deste projeto) ou SQLite (ambiente local/dev)
- Docker + Docker Compose (recomendado)
- **Laravel Sanctum** (dependência PHP listada no `composer.json`; não é um serviço separado — veja a nota abaixo)

### Sanctum no seu ambiente local

Quando você **atualiza o repositório** (pull) neste estado do projeto:

1. Rode **`composer install`** na pasta do projeto (ou `composer update` se estiver gerenciando versões). Isso baixa o Sanctum porque ele já está no `composer.lock` — **não é obrigatório** repetir `composer require laravel/sanctum` no dia a dia.
2. Rode **`php artisan migrate`** para criar a tabela `personal_access_tokens` (migration já versionada em `database/migrations/`).
3. **`php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`** só é necessário se você estiver num **clone antigo** que ainda **não** tenha os arquivos publicados (`config/sanctum.php` + migration de tokens). Neste repositório esses arquivos **já foram incluídos**; quem só dá `git pull` + `composer install` + `migrate` não precisa publicar de novo.

## Avaliador: primeiro uso após clonar do GitHub

1. **Clone** o repositório e entre na pasta: `git clone …` e `cd laravel-crud-api`.
2. **Docker (recomendado):** na raiz do projeto, `docker compose up -d --build`. Ajuste o nome do serviço da app no `docker-compose.yml` se for diferente de `crud-app` (os comandos abaixo usam `crud-app` como exemplo).
3. **`.env`:** o compose costuma montar o `.env` da sua máquina. Se não existir, copie `cp .env.example .env` **no host** e rode `php artisan key:generate` dentro do container. Confira `APP_URL` (ex.: `http://localhost:8000`), credenciais de **DB** alinhadas ao `docker-compose.yml`, `QUEUE_CONNECTION=database` (ou `sync` só para testar sem worker) e `MAIL_MAILER=log` para ver convites no log.
4. **Migrations + link de storage:**  
   `docker exec -it crud-app php artisan migrate`  
   `docker exec -it crud-app php artisan storage:link` (upload de documentos / URLs públicas).
5. **Fila:** em terminal separado, `docker exec -it crud-app php artisan queue:work --tries=1` — **obrigatório** para processar convites se `QUEUE_CONNECTION` não for `sync`.
6. **Abrir** `APP_URL` no navegador → **cadastro** na home → **Painel** → criar signatários e processos. Não há usuário fixo no seeder; use o formulário de cadastro.
7. **Testes:** `docker exec -it crud-app php artisan test`.
8. **Se algo falhar:** confira se a porta do host não conflita, se o container `db` está healthy, se `DB_HOST` dentro do container é o nome do serviço (`db`) e não `127.0.0.1`, e se o worker da fila está ativo ao testar e-mails de convite.

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
3. Após entrar, você cai no **Painel** (`/painel`) com links para Dashboard, Relatórios, Análise, Auditoria, **Signatários** e **Processos** (formulários web que substituem parte do fluxo só via Postman).
4. Rotas web operacionais (dashboard, relatórios, `/analise`, `/auditoria`, signatários, processos) exigem **usuário autenticado**. As rotas públicas de **assinatura por token** (`/assinatura/...`) continuam sem login.

### Worker da fila (**obrigatório** para convites com `QUEUE_CONNECTION=database|redis`)

Em outro terminal:

```bash
docker exec -it crud-app php artisan queue:work --tries=1
```

**Convites (Req. 3.2 — assíncrono):** `POST /api/processo/{id}/convites`, o botão na página de fluxo e o envio ao **criar processo** na web disparam `SendProcessSignatureInviteJob::dispatch(...)`. O job cria o token (hash + cifra), envia o mailable e só completa quando o **worker** processar a fila. Sem worker, os jobs ficam na tabela `jobs` e **não** há e-mail nem linha em `processo_assinatura_tokens` até processar.

Em desenvolvimento você pode usar `QUEUE_CONNECTION=sync` no `.env` para executar jobs no mesmo processo PHP (sem `queue:work`), útil só em máquina local.

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

E em outro terminal (**necessário** para convites se `QUEUE_CONNECTION` não for `sync`):

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

### Requisito 7 — o que o datalake faz “por baixo dos panos”

1. **Comando** `php artisan datalake:export` (ver `app/Console/Commands/` + `App\Services\Datalake\ProcessAnalyticsFactExporter`) reconstrói a tabela **`processo_analytics_facts`**: uma linha por par **(processo, signatário)** que já esteve na pivô `cliente_processo`, enriquecida com métricas derivadas (contagem de tokens de convite, tempos, tipo de resposta, etc., conforme o exporter).
2. **Arquivos** `JSONL` e `CSV` são gravados em `storage/app/private/datalake/` (disk `local` no Laravel 11+). Cada linha JSONL é um registro “wide” pronto para ingestão em ferramentas que preferem semi-estruturado; o CSV espelha colunas para análise em planilha ou cargas tabulares.
3. **Uso futuro (BI / lakehouse):** copie os arquivos para um bucket (S3, GCS, ADLS) e configure um job (Airflow, dbt, Spark) para **append** diário; a tabela `processo_analytics_facts` pode ser lida diretamente por Metabase/PowerBI via conexão read-only ao Postgres, ou exportada de novo para Parquet numa fase seguinte (não incluída aqui).
4. **Schedule:** `bootstrap/app.php` agenda `datalake:export` diariamente (03:15). Em produção, o cron do SO deve chamar `php artisan schedule:run` a cada minuto, conforme documentação Laravel.

## Auditoria (Req. 8)

- **Tabela** `auditoria_eventos` (ação, subject/actor polimórficos, `before`/`after`/`meta` em JSON, IP e user-agent).
- **APIs instrumentadas** (além do que já existia no fluxo de assinatura e convites): cadastro/edição/inativação de signatário; criação/edição/exclusão de processo; alteração de documento; vínculo/sync/remoção de signatários; **enfileiramento** de convites (`POST /processo/{id}/convites` → `processo.convites_enfileirados`). Na web do fluxo de assinatura: `processo.link_assinatura_gerado`, `processo.link_assinatura_revelado`, `processo.signatarios_sincronizados`, etc.
- **Telas**: listagem global com filtros em `GET /auditoria`; no detalhe do processo (`/dashboard/processo/{id}`) continuam os eventos cujo **subject** é aquele processo, com atalho para a listagem filtrada por `processo_id`.

## Modelagem principal (resumo)

### Signatários

Tabela: `clientes` (no seu projeto, “Cliente” representa o signatário).

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

- `processo_assinatura_tokens`: `token_hash` (SHA-256 do token em claro, para validação sem guardar o segredo repetível), **`invite_plain_ciphertext`** (opcional; ver [Segurança do fluxo: convites e links](#segurança-do-fluxo-convites-e-links-req-3)), `expires_at`, `consumed_at`
- `processo_respostas`: aprovação/reprovação + IP + user agent + justificativa (reprovação)
- `processo_status_histories`: histórico de mudanças de status do processo
- `auditoria_eventos`: auditoria genérica (ex.: envio de convite, revelação de link pelo responsável)

Política de status (transições) está centralizada em `app/Services/ProcessoStatusPolicy.php`.

**Web (operador autenticado)** — visibilidade do fluxo sem depender só do Postman:

- `GET /fluxo-assinatura` — lista processos em que você é o **responsável**
- `GET /processos/{id}/fluxo-assinatura` — **reenviar convites** (enfileira jobs), **gerar link manual**, **ajustar `sort_order`** (paralelo vs sequencial), tabela de tokens e respostas
- `POST /processos/{id}/fluxo-assinatura/convites` — mesmo efeito do `POST /api/processo/{id}/convites` (**202**, jobs na fila)
- `POST /processos/{id}/fluxo-assinatura/link` — emite novo token e exibe a URL **uma vez** na tela
- `POST /processos/{id}/fluxo-assinatura/tokens/{assinaturaToken}/revelar` — recupera a URL de um convite **válido** cujo token foi persistido com cifra (ver segurança abaixo)
- `POST /processos/{id}/fluxo-assinatura/ordem` — sincroniza ordem dos signatários (equivalente conceitual ao `sync` da API)

Implementação principal: `app/Http/Controllers/FluxoAssinaturaWebController.php`, `app/Services/ProcessSigningTokenService.php`, migration `2026_05_03_210000_add_invite_plain_ciphertext_to_processo_assinatura_tokens_table.php`.

## Endpoints (API)

Base típica: `http://localhost:8000/api`

### Autenticação (Laravel Sanctum — token pessoal)

1. Crie um usuário pela **web** (`/` → cadastro) ou em testes com `User::factory()`.
2. `POST /api/login` (JSON): `email`, `password`, opcional `device_name` (rótulo do token, ex.: `postman`).
3. A resposta devolve `token` (guarde imediatamente; não é mostrado de novo) e `token_type: Bearer`.
4. Nas demais rotas, envie o header **`Authorization: Bearer {token}`**.
5. `POST /api/logout` com o mesmo header revoga o token corrente.

**Rota pública na API:** somente `POST /api/login`. Todo o restante (incluindo `GET /api/user`) exige `auth:sanctum`.

### Telas web (operador, após login)

- `GET /painel` — atalhos (inclui card **Fluxo de assinatura** para Req. 3)
- `GET /signatarios`, `GET /signatarios/create`, … — cadastro de signatários (Req. 1)
- `GET /processos`, `GET /processos/criar`, **`GET /processos/{id}/editar`**, **`PUT /processos/{id}`**, **`DELETE /processos/{id}`** — **CRUD web** de processos (Req. 2) só para o responsável; na listagem há **Fluxo**, **Editar** e **Excluir**. Ao criar com signatários, **convites são enfileirados por padrão** (como `POST /api/processo/{id}/convites`); dá para marcar _Não enviar convites agora_. **Tokens** aparecem após o worker processar os jobs (ou imediatamente com `QUEUE_CONNECTION=sync`).
- Fluxo de assinatura (Req. 3): ver bullets em [Fluxo de assinatura (Req. 3)](#fluxo-de-assinatura-req-3) (rotas `/fluxo-assinatura` e `/processos/{id}/fluxo-assinatura`)

## Segurança do fluxo: convites e links (Req. 3)

Esta seção descreve como o projeto atende **boas práticas** comuns em documentos de requisitos de segurança para assinatura digital (confidencialidade do segredo, controle de acesso, rastreabilidade e auditoria). Ajuste a redação formal ao **seu PDF de requisitos** (nomenclatura de LGPD, ISO 27001, classificação de dados, retenção, etc., quando aplicável).

### Confidencialidade do token

- **Em trânsito:** o signatário recebe uma URL opaca (`/assinatura/{token}`). Em **produção**, exija **HTTPS** para que o token não trafegue em claro na rede.
- **Em repouso (banco):** o sistema guarda apenas **`token_hash`** (SHA-256) para comparar o token recebido na URL **sem** armazenar o valor em claro nesse campo.
- **Recuperação controlada pelo operador:** para o responsável poder **reexibir o mesmo link** de um convite (sem reenviar e-mail), cada emissão grava também **`invite_plain_ciphertext`**, produzido com `Illuminate\Support\Facades\Crypt::encryptString()` (AES-256-CBC + MAC no padrão do Laravel). Ou seja: **não é texto claro no banco**, mas **é reversível** quem tiver a **`APP_KEY`** e acesso ao banco — trate backups e credenciais de DB com o mesmo rigor que a chave da aplicação.
- O atributo `invite_plain_ciphertext` está em **`$hidden`** no model `ProcessoAssinaturaToken` para não vazar em serialização JSON acidental.
- **Tokens criados antes** da migration da coluna `invite_plain_ciphertext` **não** podem ser recuperados pela UI; use **Gerar link manual** ou novo convite.

### Autenticação e autorização

- **Página pública de assinatura** (`/assinatura/...`): não usa sessão do operador; a **autorização** é o portador do **token** (modelo “quem tem o link”). Por isso TTL (`expires_at`) e **consumo único** (`consumed_at`) reduzem janela de abuso.
- **Recuperação / gestão do fluxo na web:** rotas sob `auth` + verificação de **`responsible_user_id`** — só o **usuário responsável** pelo processo acessa convites, ordem dos signatários, **Exibir link** e geração manual.
- **API** (`/api/...`): `auth:sanctum` + **`ProcessoPolicy`** (`app/Policies/ProcessoPolicy.php`): listagem e operações de processo/signatários/convites/documento só para o **responsável**; `responsible_user_id` no `POST/PUT` deve ser o próprio usuário do token (validação + 403).

### Integridade e disponibilidade do fluxo

- Validação do token via hash; rejeição se expirado ou já consumido.
- **Convites (assíncrono):** `SendProcessSignatureInviteJob` implementa `ShouldQueue` e é disparado com `dispatch()`. Dentro do job, o e-mail é enviado com `Mail::send` (mailable **não** enfileirado de novo, para evitar fila dupla). Garanta **worker** ativo ou `QUEUE_CONNECTION=sync` em dev.

### Rastreabilidade e auditoria (alinhado a “não repúdio” operacional)

- **`processo_respostas`** guarda **IP** e **user-agent** na resposta do signatário (evidência técnica complementar; não substitui certificado ICP-Brasil se o requisito legal for esse nível).
- **`auditoria_eventos`** registra ações sensíveis: envio de convite, enfileiramento, geração manual de link, **revelação de link** (`processo.link_assinatura_revelado`), sincronização de signatários, etc., com actor (usuário ou signatário conforme o caso) e metadados.

### Boas práticas operacionais (checklist de produção)

- Proteger **`.env`** / **`APP_KEY`** (rotação invalida cifras antigas — planeje reemissão de convites se rotacionar chave).
- Restringir acesso ao banco e a backups; cifrados em repouso no storage de backup quando possível.
- Considerar **rate limiting** nas rotas públicas de assinatura e em `POST /api/login` (mencionado também em “Próximo melhor passo”).

## Dashboard (Web — Requisito 4)

Base típica: `http://localhost:8000`

- `GET /dashboard`
    - cards com totais por status (**apenas processos em que você é o responsável**)
    - gráficos (Chart.js): rosca e barras por status
    - tempo médio de aprovação (via histórico `approved`)
    - lista de processos **pendentes** há mais de **N** dias (parâmetro `overdue_days`)
    - filtros: `status`, `category`, `signatario_id`, `from`, `to`
    - tabela de processos (até **200** linhas, sem paginação)
- `GET /dashboard/processo/{id}`
    - detalhes + histórico de status + respostas + auditoria

## Relatórios (Web — Requisito 5)

Base típica: `http://localhost:8000`

- `GET /relatorios/status` (+ export `GET /relatorios/status.csv`)
    - quantidade por status + percentual do total (**filtrado ao seu usuário responsável**)
- `GET /relatorios/produtividade-signatarios?from=&to=` (+ export CSV)
    - total de aprovações/reprovações por signatário + tempo médio de resposta (primeira resposta por processo)
- `GET /relatorios/processos-periodo?grain=day|week|month&from=&to=` (+ export CSV)
    - criados por período + concluídos por período (conclusão = primeira transição para `approved/rejected` no histórico)
- `GET /relatorios/reprovacoes?from=&to=` (+ export CSV)
    - processo, signatário, data, justificativa

## Análise de Dados (Web — Requisito 6)

Base típica: `http://localhost:8000`

- `GET /analise?grain=day|week|month&from=&to=` (dados **apenas dos seus processos** como responsável)
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

Todas exigem header **`Authorization: Bearer {token}`** (exceto login).

- `GET /cliente`
- `POST /cliente`
- `GET /cliente/{id}`
- `PUT/PATCH /cliente/{id}`
- `DELETE /cliente/{id}` (**inativa** signatário no comportamento atual)

### Processos (CRUD)

Requer **`Authorization: Bearer {token}`**.

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

### Convites (fila assíncrona)

- `POST /processo/{id}/convites`
    - JSON opcional: `{ "ttl_hours": 72 }`
    - Resposta **HTTP 202** — jobs enfileirados; corpo `message` + `signatarios`. Requer **`queue:work`** (ou `QUEUE_CONNECTION=sync` em dev).

### Fluxo web por token (navegador)

Rotas (fora de `/api`):

- `GET /assinatura/{token}`
- `POST /assinatura/{token}/aprovar`
- `POST /assinatura/{token}/reprovar` (justificativa obrigatória)

## Como testar o fluxo completo (sanity check)

1. Subir app + db + **worker da fila** (ou `QUEUE_CONNECTION=sync` só em dev)
2. **Registrar-se em `/`** (ou usar API com `User::factory` em ambiente de teste) para existir um `users.id` de responsável
3. Criar signatários pela web em **`/signatarios`** ou via `POST /api/cliente` (com **Bearer** após `POST /api/login`)
4. Criar processo pela web em **`/processos/criar`** ou via `POST /api/processo` com `responsible_user_id` **igual** ao `id` do usuário retornado no login (senão **422/403**)
5. Associar signatários ao processo (`POST /processo/{id}/signatarios`) se não tiver marcado na web
6. Enviar convites (`POST /processo/{id}/convites` **ou** criar processo com signatários na web **ou** botão na página de fluxo) — resposta **202**; aguarde o worker processar
7. Obter a URL de assinatura:
    - **Recomendado (operador logado):** `GET /processos/{id}/fluxo-assinatura` → na tabela de tokens, **Exibir link** (token válido e emitido após a migration com `invite_plain_ciphertext`), ou **Gerar link manual** para um novo token.
    - **Alternativa (dev):** se `MAIL_MAILER=log`, o corpo do e-mail também aparece em `storage/logs/laravel.log` dentro do container.
8. Abrir `GET /assinatura/{token}` e aprovar/reprovar

### Erros comuns

- **500 em `/convites` com controller vazio/corrompido no volume**: verifique se os arquivos PHP não estão `0 bytes` no container.
- **`DB_HOST=db` não resolve fora do Docker**: rode `php artisan` **dentro** do container, ou ajuste host para `127.0.0.1` se rodar artisan no Windows apontando para Postgres publicado.

## Autenticação

- **Web (operador)**: login e cadastro na raiz `/` (sessão `web`). Painel em `/painel`. Logout pelo botão **Sair** nas telas.
- **API REST** (`/api/...`): **`auth:sanctum`** em todas as rotas exceto `POST /api/login`. Use o token Bearer retornado pelo login em todas as chamadas (Postman: aba **Authorization → Bearer Token** ou header manual).
- **`GET /api/user`**: retorna o usuário dono do token (requer header `Authorization`).

## Implementado

- Req. **1** signatários
- Req. **2** processos + **CRUD API e web** + upload + associação + status mínimos + **Laravel Policy** (`ProcessoPolicy`) restringindo ao responsável
- Req. **3** convites **assíncronos** (job em fila, item 3.2 do PDF) + token + aprovação/reprovação + registros + histórico + **telas web de fluxo** (`/fluxo-assinatura`, recuperação controlada de link com cifra + auditoria; ver [Segurança do fluxo: convites e links](#segurança-do-fluxo-convites-e-links-req-3))
- Req. **4** dashboard web (indicadores + **gráficos** Chart.js) + detalhe de processo
- Req. **5** relatórios web + CSV (**escopo ao responsável logado**)
- Req. **6** página `/analise` + camada de consultas analíticas (snapshot **escopo ao responsável**)
- Req. **7** export consolidado “datalake-like” (`datalake:export` + tabela `processo_analytics_facts`)
- Req. **8** auditoria ampliada nas rotas da API + listagem web `/auditoria` (filtros e paginação)
