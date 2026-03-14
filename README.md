# ArcVault Intake Triage (Laravel)

AI intake and triage workflow built for the ArcVault assessment scenario.

It ingests unstructured support messages, classifies and enriches them with an LLM, applies routing and escalation rules, stores structured records, and exposes JSON output.

## Core capabilities

- Ingestion from `Email`, `Web Form`, or `Support Portal`
- Classification output:
  - `category`
  - `priority`
  - `confidence_score`
- Enrichment output:
  - `core_issue`
  - `identifiers`
  - `urgency_signal`
- Routing queues:
  - `Engineering`
  - `Product`
  - `Billing`
  - `IT/Security`
- Escalation override to `Escalation Queue` when criteria are met
- Persistent storage in `intake_records`
- JSON export endpoint for downstream systems
- UI support for search, filters, and table pagination (`5` rows/page)

## Escalation rules

A record is escalated if any condition is true:

- confidence `< 70`
- outage language detected (for example `outage`, `down for all users`, `multiple users affected`)
- billing discrepancy `> $500`

## Requirements

- PHP `8.2+` (tested with PHP `8.4`)
- Composer
- MySQL (or SQLite if preferred)

## Quick start

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Open: `http://127.0.0.1:8000`

## Database setup

This project is commonly run with MySQL + phpMyAdmin.

Example `.env` values:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arcvault_intake
DB_USERNAME=root
DB_PASSWORD=
```

If you change `.env`, run:

```bash
php artisan config:clear
```

## LLM provider configuration

The app supports OpenAI-compatible providers via `.env`.

### OpenAI

```env
LLM_PROVIDER=openai
LLM_API_KEY=your_openai_key
LLM_MODEL=gpt-4o-mini
```

### Groq

```env
LLM_PROVIDER=groq
LLM_API_KEY=your_groq_key
LLM_MODEL=llama-3.3-70b-versatile
LLM_BASE_URL=
```

Notes:

- If LLM is unavailable, the app automatically falls back to deterministic rules.
- In outputs, `model_used=rules-fallback` means fallback was used.

## Run the required five synthetic inputs

### From UI

- Click `Process 5 Synthetic Samples`

### From CLI

```bash
php artisan intake:process-samples
```

## Structured output

- Endpoint: `GET /intake/records.json`
- Includes:
  - `category`, `priority`, `confidence_score`
  - `identifiers`, `routing_queue`
  - `escalation_flag`, `escalation_reasons`
  - `human_summary`, `model_used`

## Main routes

- `GET /` -> intake UI
- `POST /intake` -> process single request
- `POST /intake/process-samples` -> process 5 synthetic samples
- `GET /intake/records.json` -> structured JSON output

## Testing

```bash
php artisan test
```

## Troubleshooting

### `No application encryption key has been specified`

```bash
php artisan key:generate --force
php artisan config:clear
```

### `rules-fallback` appears for all records

- Check `LLM_PROVIDER`, `LLM_API_KEY`, `LLM_MODEL` in `.env`
- Run `php artisan config:clear`
- Check `storage/logs/laravel.log` for provider error codes

### Migration/state reset

```bash
php artisan migrate:fresh
php artisan intake:process-samples
```

## Security

- Never commit real API keys in `.env` or `.env.example`
- Rotate any key that was ever pasted into logs/chat

## API key access

For API key access or project credentials, contact: `moetassem.wehbe.01@gmail.com`.
