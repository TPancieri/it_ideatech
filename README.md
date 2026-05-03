# Laravel CRUD API — Trilha de Assinatura Digital (Teste Prático)

Este repositório implementa uma API em **Laravel** para cadastro de **signatários**, gestão de **processos digitais**, **upload de documentos**, **associação de signatários**, **convites por e-mail via job assíncrono**, e **aprovação/reprovação por link com token** (com registros de histórico e auditoria). Documentação dos endpoints: [`docs/API_REST.md`](docs/API_REST.md).

> Ajuste host/porta conforme seu Docker ou ambiente local. **Não há usuário/senha fixos:** cadastre-se na página inicial (`/`).

> **Validação local feita em:** Windows 10, PHP 8.5, SQLite, sem Docker

## Rodar com Docker depois do `git clone`

Na pasta do repositório (onde está o `docker-compose.yml`). O container da app chama-se **`crud-app`** (veja `container_name` no compose).

1. **Criar o ficheiro de ambiente** (na raiz do projeto, ao lado do compose):

```bash
cp .env.example .env
```

2. **Editar o `.env`** e garantir pelo menos isto para o compose atual (Postgres no Docker):

```env
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

Opcional para **menos um terminal** (convites e e-mails rodam na hora, sem worker):

```env
QUEUE_CONNECTION=sync
```

Se preferir fila real (`QUEUE_CONNECTION=database`), depois do migrate abra **outro** terminal e deixe a fila a correr: `docker exec -it crud-app php artisan queue:work --tries=1`.

3. **Subir os contentores**:

```bash
docker compose up -d --build
```

4. **Instalar dependências PHP** :

```bash
docker exec -it crud-app composer install
```

5. **Chave da aplicação e base de dados:**

```bash
docker exec -it crud-app php artisan key:generate
docker exec -it crud-app php artisan migrate
docker exec -it crud-app php artisan storage:link
```

(`db:seed` é opcional; não cria utilizador fixo.)

6. **Abrir no browser:** [http://localhost:8000](http://localhost:8000) → **Cadastro** → **Painel**.

7. **Testes (opcional):** `docker exec -it crud-app php artisan test`

**Se algo falhar:** porta `8000` ou `5432` ocupada no host; container `db` ainda a arrancar (espere ~10 s e volte a `migrate`); `DB_HOST` tem que ser **`db`** (nome do serviço na rede Docker), não `127.0.0.1`, quando corre o Artisan **dentro** do `crud-app`.

---

## Requisitos 

- **Docker + Docker Compose**
- Ou PHP **8.3+** + Composer + SQLite ou Postgres, se for correr sem Docker (ver secção mais abaixo).
- **Laravel Sanctum** já vem no `composer.json`; ficheiros publicados e migrations estão no repo.

## Como rodar (Docker — detalhe extra)

Na pasta do projeto:

```bash
docker compose up -d --build
```

### Variáveis de ambiente (resumo)

- **`APP_URL`**: deve coincidir com o que abre no browser (ex.: `http://localhost:8000`) — afecta links em e-mails.
- **Postgres no compose:** `POSTGRES_USER` / `POSTGRES_PASSWORD` / `POSTGRES_DB` estão no `docker-compose.yml`; o `.env` da app deve usar o mesmo utilizador, base e `DB_HOST=db`.
- **Fila:** `QUEUE_CONNECTION=database` exige `queue:work`; com `sync` não precisa de worker em desenvolvimento.
- **E-mail em dev:** `MAIL_MAILER=log` grava convites em `storage/logs/laravel.log`.

### Migrations + seed (opcional)

```bash
docker exec -it crud-app php artisan migrate
docker exec -it crud-app php artisan db:seed
```

O `DatabaseSeeder` não cria utilizador fixo; use o cadastro na web ou `demo:seed-scenario` para massa de dados de teste.

### Massa de dados demo

- **Consola (qualquer ambiente):** `php artisan demo:seed-scenario` cria dezenas de processos com título `[Demo] …` e signatários com e-mail `demo-seed-u{id}-N@example.invalid`, atribuídos ao utilizador indicado. Por omissão apaga antes os dados demo desse utilizador. Exemplos:
    - `php artisan demo:seed-scenario --user=seu@email.com`
    - `php artisan demo:seed-scenario --no-purge` (evita o purge; pode falhar se os e-mails demo já existirem, por causa do `UNIQUE` em `clientes.email`).
- **Painel (só `APP_ENV=local`):** no canto superior do `/painel` há um formulário que chama a mesma lógica (substitui a massa demo da conta autenticada). Não aparece em produção.

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

**Convites:** `POST /api/processo/{id}/convites`, o botão na página de fluxo e o envio ao **criar processo** na web disparam `SendProcessSignatureInviteJob::dispatch(...)`. O job cria o token (hash + cifra), envia o mailable e só completa quando o **worker** processar a fila. Sem worker, os jobs ficam na tabela `jobs` e **não** há e-mail nem linha em `processo_assinatura_tokens` até processar.

Em desenvolvimento você pode usar `QUEUE_CONNECTION=sync` no `.env` para executar jobs no mesmo processo PHP (sem `queue:work`), útil só em máquina local.

### Link público de storage (opcional)

Se você for servir arquivos via `/storage/...`:

```bash
docker exec -it crud-app php artisan storage:link
```
## Como rodar (sem Docker) 

1. **Clonar** e entrar na pasta do projecto.

2. **Criar o `.env` a partir do exemplo** (escolhe o comando do seu sistema):

   | Ambiente | Comando |
   |----------|---------|
   | Windows PowerShell | `Copy-Item .env.example .env` |
   | Windows cmd | `copy .env.example .env` |
   | Linux / macOS | `cp .env.example .env` |

3. **Abrir o `.env`** (o que acabou de ser criado). Para SQLite local, confirma que esta (o `.env.example` já vem assim):

   - `DB_CONNECTION=sqlite`
   - Não precisas de `DB_HOST` / pode deixar essas linhas comentadas.

   Opcional: `APP_URL=http://127.0.0.1:8000` (igual ao endereço do `php artisan serve`).

4. **Criar o folder da base SQLite** (vazio), na pasta `database/`:

   | Ambiente | Comando |
   |----------|---------|
   | PowerShell | `New-Item -ItemType File -Path database\database.sqlite -Force` |
   | cmd | `type nul > database\database.sqlite` |
   | Linux / macOS | `touch database/database.sqlite` |

   (O Laravel usa `database/database.sqlite` por omissão quando `DB_DATABASE` não está definido.)

5. **Instalar dependências e preparar a app:**

   ```bash
   composer install
   php artisan key:generate
   php artisan migrate
   php artisan storage:link
   ```

6. **(Opcional)** `php artisan db:seed` — não cria utilizador fixo.

7. **Subir o servidor:** `php artisan serve` → abre [http://127.0.0.1:8000](http://127.0.0.1:8000) e registra na home.

**Fila:** no `.env.example` actual, `QUEUE_CONNECTION=sync` evita precisar de segundo terminal com `queue:work`. Se mudar para `database`, usa no outro terminal: `php artisan queue:work --tries=1`.


## Testes automatizados

```bash
php artisan test
```

## Export consolidado para BI / datalake 

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

### Datalake

Os arquivos em `datalake/` são pensados para serem **copiados para um storage externo**  e ingeridos por pipelines ou carregados direto em ferramentas de BI.

Campos principais no dataset (CSV/JSONL): identificação do processo (`processo_id`, `titulo`, `categoria`, `status`, timestamps), dados do signatário (`signatario_*`, `sort_order`), convites (`convite_*`, `convites_enviados`), resposta (`tipo_resposta`, `resposta_em`, `tempo_resposta_em_horas`, `justificativa_reprovacao`) e responsável (`responsible_user_*`).

1. **Comando** `php artisan datalake:export` (ver `app/Console/Commands/` + `App\Services\Datalake\ProcessAnalyticsFactExporter`) reconstrói a tabela **`processo_analytics_facts`**: uma linha por par **(processo, signatário)** que já esteve na pivô `cliente_processo`, enriquecida com métricas derivadas (contagem de tokens de convite, tempos, tipo de resposta, etc., conforme o exporter).
2. **Arquivos** `JSONL` e `CSV` são gravados em `storage/app/private/datalake/` (disk `local` no Laravel 11+). Cada linha JSONL é um registro “wide” pronto para ingestão em ferramentas que preferem semi-estruturado; o CSV espelha colunas para análise em planilha ou cargas tabulares.
3. **Uso futuro (BI / lakehouse):** copie os arquivos para um bucket (S3, GCS, ADLS) e configure um job (Airflow, dbt, Spark) para **append** diário; a tabela `processo_analytics_facts` pode ser lida diretamente por Metabase/PowerBI via conexão read-only ao Postgres, ou exportada de novo para Parquet numa fase seguinte (não incluída aqui).
4. **Schedule:** `bootstrap/app.php` agenda `datalake:export` diariamente (03:15). Em produção, o cron do SO deve chamar `php artisan schedule:run` a cada minuto, conforme documentação Laravel.

## Auditoria 

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

### Fluxo de assinatura 

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

## Segurança do fluxo — confidencialidade do token

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

## Dashboard 

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

## Relatórios

Base típica: `http://localhost:8000`

- `GET /relatorios` — índice com links para todos os relatórios abaixo
- `GET /relatorios/status` (+ export `GET /relatorios/status.csv`)
    - quantidade por status + percentual do total (**filtrado ao seu usuário responsável**)
- `GET /relatorios/produtividade-signatarios?from=&to=` (+ export CSV)
    - total de aprovações/reprovações por signatário + tempo médio de resposta (primeira resposta por processo)
- `GET /relatorios/processos-periodo?grain=day|week|month&from=&to=` (+ export CSV)
    - criados por período + concluídos por período (conclusão = primeira transição para `approved/rejected` no histórico)
- `GET /relatorios/reprovacoes?from=&to=` (+ export CSV)
    - processo, signatário, data, justificativa

## Análise de Dados 

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
