# API REST — referência rápida

Base URL (desenvolvimento típico): `http://localhost:8000/api`

Todas as rotas abaixo (exceto `POST /api/login`) exigem header:

```http
Authorization: Bearer {token}
Accept: application/json
```

O token é obtido em `POST /api/login` (corpo JSON: `email`, `password`, opcional `device_name`). Guarde o `token` da resposta; ele não é exibido de novo.

---

## Autenticação

| Método | Caminho   | Corpo                                     | Resposta                              |
| ------ | --------- | ----------------------------------------- | ------------------------------------- |
| POST   | `/login`  | `{ "email", "password", "device_name"? }` | `200` + `token`, `token_type`, `user` |
| POST   | `/logout` | —                                         | `204` (revoga token atual)            |
| GET    | `/user`   | —                                         | `200` + objeto usuário                |

---

## Signatários (`cliente`)

`GET/POST /cliente` · `GET/PUT/PATCH/DELETE /cliente/{id}`

Corpo típico de criação/edição: `name`, `email`, `role`, `sector`, `status` (`active`/`inactive`). E-mail único.

---

## Processos (`processo`)

**Regra de ownership:** `GET /processo` lista apenas processos em que `responsible_user_id` é o **usuário do token**. Em `POST/PUT` o `responsible_user_id` deve ser **o próprio** id do usuário autenticado; operações de leitura/escrita em um processo de outro responsável retornam **403**.

| Método    | Caminho                   | Descrição                               |
| --------- | ------------------------- | --------------------------------------- |
| GET       | `/processo`               | Lista (só seus)                         |
| POST      | `/processo`               | Cria (`status` inicia `pending`)        |
| GET       | `/processo/{id}`          | Detalhe                                 |
| PUT/PATCH | `/processo/{id}`          | Atualiza (valida transição de `status`) |
| DELETE    | `/processo/{id}`          | Remove                                  |
| POST      | `/processo/{id}/document` | `multipart` campo `document`            |
| GET       | `/processo/{id}/document` | Download/stream do arquivo              |

### Signatários do processo

| Método | Caminho                                  |
| ------ | ---------------------------------------- | ---------------------------------------------------------------- |
| GET    | `/processo/{id}/signatarios`             |
| POST   | `/processo/{id}/signatarios`             | JSON: `cliente_id`, `sort_order?`                                |
| POST   | `/processo/{id}/signatarios/sync`        | JSON: `{ "signatarios": [{ "cliente_id", "sort_order" }, ...] }` |
| DELETE | `/processo/{id}/signatarios/{clienteId}` |                                                                  |

### Convites (fila assíncrona — Req. 3)

| Método | Caminho                   | Corpo                                    | Resposta                                                    |
| ------ | ------------------------- | ---------------------------------------- | ----------------------------------------------------------- |
| POST   | `/processo/{id}/convites` | `{ "ttl_hours"? }` (opcional, padrão 72) | **202** — jobs `SendProcessSignatureInviteJob` enfileirados |

É necessário **`php artisan queue:work`** quando `QUEUE_CONNECTION=database` ou `redis`. Cada job cria token (e grava hash + cifra), envia e-mail e audita.

---

## Códigos HTTP usuais

- `200` / `201` sucesso
- `204` sem corpo (logout, delete)
- `202` convites aceitos para processamento assíncrono
- `403` policy / não é o responsável pelo processo
- `404` recurso inexistente
- `422` validação ou transição de status inválida

---

## Status de processo (mínimos do teste)

`pending` → `in_approval` | `canceled`  
`in_approval` → `approved` | `rejected` | `canceled`  
`approved`, `rejected`, `canceled` → sem saída (exceto manter o mesmo valor no PUT)

---

## Exemplo cURL (login + listar processos)

```bash
curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"voce@example.com","password":"senha","device_name":"curl"}'

# use o token retornado:
curl -s http://localhost:8000/api/processo \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"
```
