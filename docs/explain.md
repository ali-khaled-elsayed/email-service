Email Service API — Full Guide with Examples
Base URL: http://localhost:8000/api
Auth header (required on all endpoints below except tracking):

X-APP-KEY: construction_app
Content-Type: application/json
After php artisan migrate --seed, use app key construction_app.

Provider routing (important)
You do not send provider in the API. The mail provider is chosen from your Application in the admin panel:

Routing rule for email type (e.g. transactional → smtp_primary)
Application default provider
Fallback provider on retries
Global weighted pool (last resort)
Use type in the request so the correct rule applies.

1. POST /api/emails/send — Queue one email
Queues a single email for delivery (now or later via scheduled_at).

Minimal example
Request

POST /api/emails/send
X-APP-KEY: construction_app
Content-Type: application/json
{
  "to": ["customer@example.com"],
  "subject": "Welcome",
  "html": "<h1>Hello</h1><p>Thanks for signing up.</p>"
}
Response 202

{
  "success": true,
  "message": "Email queued successfully",
  "email_log_id": 42
}
Full example (optional fields)
{
  "to": ["user@example.com", "manager@example.com"],
  "cc": ["archive@example.com"],
  "bcc": null,
  "subject": "Invoice #1042",
  "html": "<h1>Invoice</h1><p>Details below.</p>",
  "text": "Invoice - Details below.",
  "priority": "high",
  "type": "transactional",
  "scheduled_at": null,
  "meta": {
    "invoice_id": 1042,
    "user_id": 7
  },
  "idempotency_key": "invoice-1042-2026-05-18"
}
Field	Required?	Notes
to	Yes	Array of emails
subject	If no template	Max 998 chars
html	If no template and no text	HTML body
text	No	Plain text alternative
cc, bcc	No	Arrays of emails
priority	No	high, default, low, bulk (default: default)
type	No	transactional, marketing, notification, system — drives provider routing
scheduled_at	No	Future date → delayed send
template + template_data	No	Use stored template instead of subject/html
meta	No	Custom JSON stored on the log
idempotency_key	No	Same key twice = same log, no duplicate
attachments	No	[{ "name": "file.pdf", "content": "<base64>" }]
Template example
{
  "to": ["ahmed@example.com"],
  "template": "invoice_created",
  "template_data": {
    "name": "Ahmed",
    "invoice_id": "INV-9921"
  },
  "type": "notification"
}
Rendered subject: Invoice #INV-9921 Created (from seeded template).

cURL
curl -X POST "http://localhost:8000/api/emails/send" \
  -H "X-APP-KEY: construction_app" \
  -H "Content-Type: application/json" \
  -d '{"to":["user@example.com"],"subject":"Test","html":"<p>Hi</p>","type":"transactional"}'
2. POST /api/emails/schedule — Schedule for later
Same body as send, but scheduled_at is required (future ISO 8601).

Request

{
  "to": ["reminder@example.com"],
  "subject": "Payment due tomorrow",
  "html": "<p>Your payment is due on May 19.</p>",
  "scheduled_at": "2026-12-31T09:00:00+00:00",
  "priority": "low",
  "type": "notification",
  "meta": { "subscription_id": 88 }
}
Response 202

{
  "success": true,
  "message": "Email scheduled successfully",
  "email_log_id": 43
}
3. POST /api/emails/bulk — Many recipients
One email log per recipient. Priority is always bulk.

Request

{
  "recipients": [
    {
      "email": "alice@example.com",
      "variables": { "name": "Alice", "invoice_id": "A-100" },
      "meta": { "segment": "premium" }
    },
    {
      "email": "bob@example.com",
      "variables": { "name": "Bob", "invoice_id": "B-200" }
    }
  ],
  "template": "invoice_created",
  "type": "transactional",
  "meta": { "campaign": "may-invoices" }
}
Or simple list:

{
  "recipients": ["a@example.com", "b@example.com"],
  "subject": "Newsletter",
  "html": "<p>Monthly update</p>",
  "type": "marketing"
}
Response 202

{
  "success": true,
  "message": "Bulk emails queued successfully",
  "email_log_ids": [44, 45],
  "count": 2
}
4. GET /api/emails/{id} — Get status
Request

GET /api/emails/42
X-APP-KEY: construction_app
Response 200

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
    "meta": { "invoice_id": 55 },
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
provider_id comes from Application routing, not from the send request.

Not found 404

{
  "success": false,
  "message": "Email not found."
}
5. POST /api/emails/{id}/retry — Manual retry
Re-queues a failed email.

Allowed statuses: failed, bounced

Request

POST /api/emails/42/retry
X-APP-KEY: construction_app
Response 200

{
  "success": true,
  "message": "Email retry queued",
  "email_log_id": 42
}
6. POST /api/emails/{id}/cancel — Cancel before send
Allowed statuses: pending, queued, scheduled

Request

POST /api/emails/42/cancel
X-APP-KEY: construction_app
Response 200

{
  "success": true,
  "message": "Email cancelled successfully"
}
7. GET /api/providers/health — Provider status
Lists all mail providers (for monitoring). Does not select provider on send.

Request

GET /api/providers/health
X-APP-KEY: construction_app
Response 200

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
8. GET /api/metrics — Today’s stats (your app)
Request

GET /api/metrics
X-APP-KEY: construction_app
Response 200

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
Tracking (no API key)
Used inside HTML emails; URLs must be signed by Laravel.

Endpoint	Purpose
GET /api/track/open/{id}	1×1 pixel → marks opened
GET /api/track/click/{id}?url={base64}	Redirect → marks clicked
Example click URL (target = https://example.com/offer):

GET /api/track/click/42?url=aHR0cHM6Ly9leGFtcGxlLmNvbS9vZmZlcg==
Typical workflow
1. POST /api/emails/send          → email_log_id: 42
2. GET  /api/emails/42            → poll until status = sent | failed
3. POST /api/emails/42/retry      (only if failed)
4. GET  /api/metrics              → dashboard numbers
HTTP status codes
Code	Meaning
202	Send / schedule / bulk accepted
200	Get status, retry, cancel, health, metrics
401	Missing or invalid X-APP-KEY
404	Email not found
422	Validation error (missing to, etc.)
429	Rate limit (default 120/min)
Validation error example 422:

{
  "message": "The to field is required.",
  "errors": {
    "to": ["The to field is required."]
  }
}
Email statuses
Status	Meaning
pending, queued, scheduled	Waiting — can cancel
processing, sending, retrying	In progress
sent, delivered, opened, clicked	Success (terminal)
failed, bounced	Error — can retry
cancelled, rejected	Stopped (terminal)
Docs & Postman
Resource	Path
Full reference	docs/api/API_REFERENCE.md
Postman (all requests + tests)	docs/postman/Email-Service-API-Testing.postman_collection.json
Postman (quick)	docs/postman/Email-Service-API.postman_collection.json
Environment	docs/postman/Email-Service-Local.postman_environment.json
Note: Run php artisan queue:work so queued emails actually send after API calls return 202.
php artisan queue:work database --queue=emails-high,emails-default,emails-low,emails-bulk,emails-retry
