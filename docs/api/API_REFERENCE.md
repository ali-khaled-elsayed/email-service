# Email Service Platform — API Reference

Complete reference for the REST API. All authenticated endpoints require an application key. Field tables mark **Required**, **Optional**, or **Nullable**.

**Base URL:** `http://localhost:8000` (or your `APP_URL`)

**API prefix:** `/api`

---

## Table of contents

1. [Authentication](#authentication)
2. [Common headers](#common-headers)
3. [Rate limiting](#rate-limiting)
4. [Standard responses & errors](#standard-responses--errors)
5. [Shared request fields (send / schedule)](#shared-request-fields-send--schedule)
6. [POST /api/emails/send](#post-apiemailssend)
7. [POST /api/emails/schedule](#post-apiemailsschedule)
8. [POST /api/emails/bulk](#post-apiemailsbulk)
9. [GET /api/emails/{id}](#get-apiemailsid)
10. [POST /api/emails/{id}/retry](#post-apiemailsidretry)
11. [POST /api/emails/{id}/cancel](#post-apiemailsidcancel)
12. [GET /api/providers/health](#get-apiprovidershealth)
13. [GET /api/metrics](#get-apimetrics)
14. [Tracking endpoints (no API key)](#tracking-endpoints-no-api-key)
15. [Enums & status values](#enums--status-values)
16. [Seeded test credentials](#seeded-test-credentials)

---

## Provider resolution (per application)

The API **never** accepts a `provider` field. When you send email, the service picks a provider using the **Application** linked to your `X-APP-KEY`:

| Priority | Source | Admin field |
|----------|--------|-------------|
| 1 | Routing rule for email `type` | Settings → routing by type (e.g. `transactional` → `smtp_primary`) |
| 2 | Default provider | Application → Default provider |
| 3 | Fallback provider | Used on retries (Application → Fallback provider) |
| 4 | Global weighted pool | If no app provider is available |

Configure applications in **Filament → Applications → Provider routing**.

---

## Authentication

| Header       | Required | Description                                      |
|-------------|----------|--------------------------------------------------|
| `X-APP-KEY` | **Yes**  | Application key issued per tenant (Filament → Applications) |

Missing or invalid key:

```json
{
  "success": false,
  "message": "X-APP-KEY header is required."
}
```

HTTP `401`

```json
{
  "success": false,
  "message": "Invalid or inactive application key."
}
```

HTTP `401`

---

## Common headers

| Header           | Required | Value                    |
|-----------------|----------|--------------------------|
| `Content-Type`  | **Yes** (POST) | `application/json` |
| `Accept`        | Optional | `application/json`       |
| `X-APP-KEY`     | **Yes**  | Your application key     |

---

## Rate limiting

- Default: **120 requests per minute** per application (config: `EMAIL_API_RATE_LIMIT`).
- When exceeded: HTTP `429 Too Many Requests` (Laravel throttle).

---

## Standard responses & errors

### Success envelope (most endpoints)

```json
{
  "success": true,
  "message": "...",
  ...
}
```

### Validation error (HTTP 422)

Laravel validation format:

```json
{
  "message": "The to field is required. (and 1 more error)",
  "errors": {
    "to": ["The to field is required."],
    "subject": ["The subject field is required when template is not present."]
  }
}
```

### Not found (HTTP 404)

```json
{
  "success": false,
  "message": "Email not found."
}
```

### Server / business rule errors (HTTP 500)

e.g. cancel/retry when status does not allow it:

```json
{
  "message": "Email cannot be cancelled in current status: sent",
  ...
}
```

---

## Shared request fields (send / schedule)

Used by **Send** and **Schedule** (`SendEmailRequest`).

| Field             | Type              | Required | Nullable | Notes |
|-------------------|-------------------|----------|----------|-------|
| `to`              | `string[]`        | **Yes**  | No       | Min 1 email. Each item must be valid email. |
| `cc`              | `string[]`        | No       | —        | Optional. Each item valid email. |
| `bcc`             | `string[]`        | No       | —        | Optional. Each item valid email. |
| `subject`         | `string`          | Conditional | No   | **Required** unless `template` is provided. Max 998 chars. |
| `html`            | `string`          | Conditional | No   | **Required** unless `template` OR `text` is provided. |
| `text`            | `string`          | No       | —        | Plain-text body. Can satisfy content rule instead of `html`. |
| `priority`        | `string`          | No       | —        | Default: `default`. Values: `high`, `default`, `low`, `bulk`. |
| `type`            | `string`          | No       | —        | Default: `transactional`. Values: `transactional`, `marketing`, `notification`, `system`. |
| `scheduled_at`    | `string` (ISO 8601 date) | No | **Yes** | See endpoint notes. |

> **Provider routing:** The mail provider is **not** set in the API. It is resolved from the authenticated **Application** (default provider, fallback, and per-`type` routing rules in the admin panel).
| `attachments`     | `object[]`        | No       | —        | See [Attachments](#attachments). |
| `meta`            | `object`          | No       | —        | Arbitrary JSON metadata stored on the log. |
| `idempotency_key` | `string`          | No       | Yes      | Max 255. Duplicate key returns existing log (no duplicate send). |
| `template`        | `string`          | No       | Yes      | Template **slug** for this application. |
| `template_data`   | `object`          | No       | —        | Variables for `{{name}}` placeholders in template. |

### Attachments

Each item in `attachments[]`:

| Field     | Type     | Required | Nullable | Notes |
|-----------|----------|----------|----------|-------|
| `name`    | `string` | **Yes** (if `attachments` present) | No | Filename |
| `content` | `string` | No       | —        | Base64-encoded file content |
| `path`    | `string` | No       | —        | Storage path (with `content` uploads to disk) |
| `mime`    | `string` | No       | Yes      | MIME type (stored when provided) |

Provide either:
- `name` + `content` (base64), optionally `path` as hint, or
- `path` pointing to an existing stored file.

Max size per file: `EMAIL_ATTACHMENT_MAX_KB` (default **10240 KB**).

---

## POST /api/emails/send

Queue a single email for immediate (or delayed) delivery.

| | |
|---|---|
| **URL** | `POST /api/emails/send` |
| **Auth** | `X-APP-KEY` |
| **Success** | HTTP `202 Accepted` |

### `scheduled_at` on send

| Value | Behavior |
|-------|----------|
| Omitted / `null` | Queued immediately |
| Future datetime  | Status `scheduled`, job delayed until that time |
| Past datetime    | Treated as immediate queue |

### Minimal example (HTML only)

**Request**

```http
POST /api/emails/send HTTP/1.1
Host: localhost:8000
X-APP-KEY: construction_app
Content-Type: application/json
```

```json
{
  "to": ["customer@example.com"],
  "subject": "Welcome",
  "html": "<h1>Hello</h1><p>Thanks for signing up.</p>"
}
```

**Response** `202`

```json
{
  "success": true,
  "message": "Email queued successfully",
  "email_log_id": 42
}
```

### Full example (all optional fields)

```json
{
  "to": ["user@example.com", "manager@example.com"],
  "cc": ["archive@example.com"],
  "bcc": null,
  "subject": "Invoice #1042",
  "html": "<h1>Invoice</h1><p>Please find details below.</p>",
  "text": "Invoice\nPlease find details below.",
  "priority": "high",
  "type": "transactional",
  "scheduled_at": null,
  "attachments": [
    {
      "name": "invoice.pdf",
      "content": "JVBERi0xLjQKJeLjz9MKMy...",
      "mime": "application/pdf"
    }
  ],
  "meta": {
    "invoice_id": 1042,
    "user_id": 7,
    "source": "billing-service"
  },
  "idempotency_key": "invoice-1042-2026-05-18",
  "template": null,
  "template_data": null
}
```

### Template example (no subject/html required)

Uses seeded template `invoice_created`:

```json
{
  "to": ["ahmed@example.com"],
  "template": "invoice_created",
  "template_data": {
    "name": "Ahmed",
    "invoice_id": "INV-9921"
  },
  "priority": "default",
  "type": "notification",
  "meta": {
    "department": "finance"
  }
}
```

Rendered subject: `Invoice #INV-9921 Created`

### Idempotency example

Second request with same `idempotency_key` returns **same** `email_log_id` without creating a new row:

```json
{
  "to": ["test@example.com"],
  "subject": "Test",
  "html": "<p>Test</p>",
  "idempotency_key": "unique-key-123"
}
```

### cURL

```bash
curl -X POST "http://localhost:8000/api/emails/send" \
  -H "X-APP-KEY: construction_app" \
  -H "Content-Type: application/json" \
  -d '{
    "to": ["user@example.com"],
    "subject": "Invoice Created",
    "html": "<h1>Hello</h1><p>Your invoice was created.</p>",
    "priority": "high",
    "type": "transactional",
    "meta": { "invoice_id": 55 }
  }'
```

---

## POST /api/emails/schedule

Schedule an email for future delivery. Uses the **same body** as [send](#post-apiemailssend).

| | |
|---|---|
| **URL** | `POST /api/emails/schedule` |
| **Auth** | `X-APP-KEY` |
| **Success** | HTTP `202 Accepted` |

### `scheduled_at` — **required**

| Field          | Required | Nullable |
|----------------|----------|----------|
| `scheduled_at` | **Yes**  | No       |

Must be a **future** ISO 8601 datetime. If omitted, the server throws an error (`scheduled_at is required for scheduled emails`).

### Example

```json
{
  "to": ["reminder@example.com"],
  "subject": "Payment due tomorrow",
  "html": "<p>Your payment is due on May 19.</p>",
  "scheduled_at": "2026-05-19T09:00:00+00:00",
  "priority": "low",
  "type": "notification",
  "meta": {
    "subscription_id": 88
  }
}
```

**Response** `202`

```json
{
  "success": true,
  "message": "Email scheduled successfully",
  "email_log_id": 43
}
```

### cURL

```bash
curl -X POST "http://localhost:8000/api/emails/schedule" \
  -H "X-APP-KEY: construction_app" \
  -H "Content-Type: application/json" \
  -d '{
    "to": ["user@example.com"],
    "subject": "Reminder",
    "html": "<p>Scheduled reminder</p>",
    "scheduled_at": "2026-05-20T10:00:00Z"
  }'
```

---

## POST /api/emails/bulk

Send the same content to many recipients. Each recipient gets a separate `email_log_id`. Priority is always forced to `bulk`.

| | |
|---|---|
| **URL** | `POST /api/emails/bulk` |
| **Auth** | `X-APP-KEY` |
| **Success** | HTTP `202 Accepted` |

### Request fields

| Field         | Type       | Required | Nullable | Notes |
|---------------|------------|----------|----------|-------|
| `recipients`  | `array`    | **Yes**  | No       | Min 1 item. See below. |
| `subject`     | `string`   | Conditional | No    | Required unless `template` provided. |
| `html`        | `string`   | Conditional | No    | Required unless `template` provided. |
| `template`    | `string`   | No       | Yes      | Template slug |
| `type`        | `string`   | No       | —        | `transactional`, `marketing`, `notification`, `system` |
| `meta`        | `object`   | No       | —        | Base metadata merged per recipient |

### `recipients[]` item shapes

**Form A — email string only**

```json
"recipients": ["a@example.com", "b@example.com"]
```

**Form B — object (recommended)**

| Field       | Type     | Required | Nullable | Notes |
|-------------|----------|----------|----------|-------|
| `email`     | `string` | **Yes**  | No       | Valid email |
| `meta`      | `object` | No       | —        | Merged into email log `meta` |
| `variables` | `object` | No       | —        | Merged into `template_data` for that recipient |

### Minimal example

```json
{
  "recipients": [
    { "email": "user1@example.com" },
    { "email": "user2@example.com" }
  ],
  "subject": "Newsletter May 2026",
  "html": "<h1>News</h1><p>Monthly update.</p>",
  "type": "marketing"
}
```

### Full example (per-recipient template variables)

```json
{
  "recipients": [
    {
      "email": "alice@example.com",
      "variables": { "name": "Alice", "invoice_id": "A-100" },
      "meta": { "segment": "premium" }
    },
    {
      "email": "bob@example.com",
      "variables": { "name": "Bob", "invoice_id": "B-200" },
      "meta": { "segment": "standard" }
    }
  ],
  "template": "invoice_created",
  "type": "transactional",
  "meta": {
    "campaign": "may-invoices"
  }
}
```

**Response** `202`

```json
{
  "success": true,
  "message": "Bulk emails queued successfully",
  "email_log_ids": [44, 45],
  "count": 2
}
```

### cURL

```bash
curl -X POST "http://localhost:8000/api/emails/bulk" \
  -H "X-APP-KEY: construction_app" \
  -H "Content-Type: application/json" \
  -d '{
    "recipients": [
      { "email": "a@example.com", "variables": { "name": "A" } },
      { "email": "b@example.com", "variables": { "name": "B" } }
    ],
    "subject": "Hello",
    "html": "<p>Hi {{name}}</p>",
    "type": "marketing"
  }'
```

---

## GET /api/emails/{id}

Get status and details for one email log. Only logs belonging to the authenticated application are returned.

| | |
|---|---|
| **URL** | `GET /api/emails/{id}` |
| **Auth** | `X-APP-KEY` |
| **Success** | HTTP `200 OK` |

### Path parameters

| Name | Type    | Required |
|------|---------|----------|
| `id` | integer | **Yes**  |

### Example

```http
GET /api/emails/42 HTTP/1.1
X-APP-KEY: construction_app
```

**Response** `200`

```json
{
  "success": true,
  "data": {
    "id": 42,
    "status": "sent",
    "priority": "high",
    "type": "transactional",
    "subject": "Invoice Created",
    "to": ["customer@example.com"],
    "cc": [],
    "bcc": [],
    "provider_id": 1,
    "retry_count": 0,
    "error_message": null,
    "scheduled_at": null,
    "sent_at": "2026-05-18T14:32:10+00:00",
    "failed_at": null,
    "meta": {
      "invoice_id": 55
    },
    "timelines": [
      {
        "old_status": "queued",
        "new_status": "processing",
        "message": null,
        "created_at": "2026-05-18T14:32:05+00:00"
      },
      {
        "old_status": "sending",
        "new_status": "sent",
        "message": "Email sent successfully",
        "created_at": "2026-05-18T14:32:10+00:00"
      }
    ],
    "created_at": "2026-05-18T14:32:00+00:00"
  }
}
```

### Not found

```json
{
  "success": false,
  "message": "Email not found."
}
```

HTTP `404` — wrong id or email belongs to another application.

### cURL

```bash
curl "http://localhost:8000/api/emails/42" \
  -H "X-APP-KEY: construction_app"
```

---

## POST /api/emails/{id}/retry

Manually queue a retry for a failed/bounced email.

| | |
|---|---|
| **URL** | `POST /api/emails/{id}/retry` |
| **Auth** | `X-APP-KEY` |
| **Success** | HTTP `200 OK` |

### Allowed statuses

Only emails in status **`failed`** or **`bounced`** can be retried.

### Example

```http
POST /api/emails/42/retry HTTP/1.1
X-APP-KEY: construction_app
```

**Response** `200`

```json
{
  "success": true,
  "message": "Email retry queued",
  "email_log_id": 42
}
```

### Error (wrong status)

If status is e.g. `sent`, server returns HTTP `500` with message like:

`Email cannot be retried in current status.`

### cURL

```bash
curl -X POST "http://localhost:8000/api/emails/42/retry" \
  -H "X-APP-KEY: construction_app"
```

---

## POST /api/emails/{id}/cancel

Cancel a pending, queued, or scheduled email.

| | |
|---|---|
| **URL** | `POST /api/emails/{id}/cancel` |
| **Auth** | `X-APP-KEY` |
| **Success** | HTTP `200 OK` |

### Allowed statuses

`pending`, `queued`, `scheduled`

### Example

**Response** `200`

```json
{
  "success": true,
  "message": "Email cancelled successfully"
}
```

### Error (wrong status)

If status is e.g. `sent`:

`Email cannot be cancelled in current status: sent`

HTTP `500`

### cURL

```bash
curl -X POST "http://localhost:8000/api/emails/42/cancel" \
  -H "X-APP-KEY: construction_app"
```

---

## GET /api/providers/health

List all configured providers and their health/quota snapshot. **Global** list (not filtered by application).

| | |
|---|---|
| **URL** | `GET /api/providers/health` |
| **Auth** | `X-APP-KEY` |
| **Success** | HTTP `200 OK` |

### Example response

```json
{
  "success": true,
  "providers": [
    {
      "id": 1,
      "name": "SMTP Primary",
      "slug": "smtp_primary",
      "health_status": "healthy",
      "available": true,
      "quota_used": 150,
      "quota_limit": 50000,
      "last_check": "2026-05-18T14:00:00+00:00"
    },
    {
      "id": 2,
      "name": "SMTP Fallback",
      "slug": "smtp_fallback",
      "health_status": "healthy",
      "available": true,
      "quota_used": 0,
      "quota_limit": 10000,
      "last_check": null
    }
  ]
}
```

### Field reference (`providers[]`)

| Field           | Type           | Nullable | Description |
|----------------|----------------|----------|-------------|
| `id`           | integer        | No       | Provider ID |
| `name`         | string         | No       | Display name |
| `slug`         | string         | No       | Provider identifier (configured on Application in admin) |
| `health_status`| string         | No       | `healthy`, `degraded`, `unhealthy`, `unknown` |
| `available`    | boolean        | No       | `true` if healthy or degraded |
| `quota_used`   | integer        | No       | Emails sent against quota |
| `quota_limit`  | integer        | No       | Max quota |
| `last_check`   | string (ISO8601) | **Yes** | Last health check time |

### cURL

```bash
curl "http://localhost:8000/api/providers/health" \
  -H "X-APP-KEY: construction_app"
```

---

## GET /api/metrics

Dashboard metrics for **today**, scoped to the authenticated application.

| | |
|---|---|
| **URL** | `GET /api/metrics` |
| **Auth** | `X-APP-KEY` |
| **Success** | HTTP `200 OK` |

### Example response

```json
{
  "success": true,
  "metrics": {
    "sent_today": 120,
    "failed_today": 3,
    "queued": 15,
    "retry_count": 2,
    "delivery_rate": 97.56
  }
}
```

### Field reference (`metrics`)

| Field           | Type    | Nullable | Description |
|----------------|---------|----------|-------------|
| `sent_today`    | integer | No       | Sent count today (this app) |
| `failed_today`  | integer | No       | Failed count today |
| `queued`        | integer | No       | Logs in `pending`, `queued`, or `processing` (all apps in DB) |
| `retry_count`   | integer | No       | Logs in `retrying` status |
| `delivery_rate` | float   | No       | `sent / (sent + failed) * 100` for today, or `100.0` if none |

### cURL

```bash
curl "http://localhost:8000/api/metrics" \
  -H "X-APP-KEY: construction_app"
```

---

## Tracking endpoints (no API key)

Used inside HTML emails (tracking pixel / wrapped links). Protected by Laravel **signed URLs** middleware.

| Method | URL | Auth |
|--------|-----|------|
| GET | `/api/track/open/{emailLog}` | Signed URL |
| GET | `/api/track/click/{emailLog}?url={base64}` | Signed URL |

### Open tracking

Returns a 1×1 transparent GIF. Updates log status to `opened`.

```http
GET /api/track/open/42
```

### Click tracking

Redirects to decoded URL. Updates status to `clicked`.

| Query | Required | Description |
|-------|----------|-------------|
| `url` | **Yes**  | Base64-encoded target URL |

```http
GET /api/track/click/42?url=aHR0cHM6Ly9leGFtcGxlLmNvbS9vZmZlcg==
```

(`aHR0cHM6Ly9leGFtcGxlLmNvbS9vZmZlcg==` → `https://example.com/offer`)

---

## Enums & status values

### Email status (`data.status`)

| Value        | Terminal | Can cancel | Can retry |
|-------------|----------|------------|-----------|
| `pending`   | No       | Yes        | No        |
| `queued`    | No       | Yes        | No        |
| `processing`| No       | No         | No        |
| `sending`   | No       | No         | No        |
| `scheduled` | No       | Yes        | No        |
| `retrying`  | No       | No         | No        |
| `sent`      | Yes      | No         | No        |
| `delivered` | Yes      | No         | No        |
| `opened`    | Yes      | No         | No        |
| `clicked`   | Yes      | No         | No        |
| `bounced`   | Yes      | No         | **Yes**   |
| `failed`    | Yes      | No         | **Yes**   |
| `cancelled` | Yes      | No         | No        |
| `rejected`  | Yes      | No         | No        |

### Priority

`high` → `emails-high` queue  
`default` → `emails-default`  
`low` → `emails-low`  
`bulk` → `emails-bulk` (forced for bulk API)

### Type

`transactional`, `marketing`, `notification`, `system`

---

## Seeded test credentials

After `php artisan migrate --seed`:

| Item | Value |
|------|-------|
| Application name | Construction App |
| `X-APP-KEY` | `construction_app` |
| Default provider slug | `smtp_primary` |
| Fallback provider slug | `smtp_fallback` |
| Sample template slug | `invoice_created` |
| Template variables | `name`, `invoice_id` |

### Quick test flow

```bash
# 1. Send
curl -s -X POST "http://localhost:8000/api/emails/send" \
  -H "X-APP-KEY: construction_app" \
  -H "Content-Type: application/json" \
  -d '{"to":["test@example.com"],"subject":"Test","html":"<p>Hi</p>"}'

# 2. Check status (replace 1 with email_log_id from step 1)
curl -s "http://localhost:8000/api/emails/1" \
  -H "X-APP-KEY: construction_app"

# 3. Metrics
curl -s "http://localhost:8000/api/metrics" \
  -H "X-APP-KEY: construction_app"
```

---

## HTTP status summary

| Code | When |
|------|------|
| `200` | GET status, retry, cancel, health, metrics |
| `202` | Send, schedule, bulk accepted |
| `401` | Missing/invalid `X-APP-KEY` |
| `404` | Email log not found |
| `422` | Validation failed |
| `429` | Rate limit exceeded |
| `500` | Cancel/retry not allowed; schedule without `scheduled_at`; unhandled errors |

---

## Related files

- OpenAPI (minimal): [`openapi.yaml`](./openapi.yaml)
- Postman collection: [`../postman/Email-Service-API.postman_collection.json`](../postman/Email-Service-API.postman_collection.json)
- Route definitions: [`../../routes/api.php`](../../routes/api.php)
