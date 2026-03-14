# ArcVault Intake Triage (Laravel Form)

This project implements the Valsoft ArcVault intake assessment as a Laravel web form and processing pipeline.

## What it does

- Accepts inbound requests from `Email`, `Web Form`, or `Support Portal`
- Classifies each request into:
  - `Bug Report`
  - `Feature Request`
  - `Billing Issue`
  - `Technical Question`
  - `Incident/Outage`
- Assigns priority, confidence score, extracted identifiers, urgency signal
- Applies routing logic to destination queues
- Applies escalation logic:
  - confidence `< 70`
  - outage language
  - billing discrepancy `> $500`
- Stores all results in `intake_records`
- Exposes structured JSON output

## Run locally

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Open: `http://127.0.0.1:8000`

## Process the required five synthetic inputs

- Web UI: click `Process 5 Synthetic Samples`
- CLI:

```bash
php artisan intake:process-samples
```

## Structured output

- JSON endpoint: `GET /intake/records.json`

## Optional LLM mode

By default, classification uses deterministic rule-based logic.  
If LLM credentials are configured, the app attempts live LLM classification first, then falls back to rules on failure.

```env
LLM_PROVIDER=openai
LLM_API_KEY=your_key_here
LLM_MODEL=gpt-4o-mini
```

For Groq free tier:

```env
LLM_PROVIDER=groq
LLM_API_KEY=your_groq_key_here
LLM_MODEL=llama-3.3-70b-versatile
# Optional override:
# LLM_BASE_URL=https://api.groq.com/openai/v1
```

## Test

```bash
php artisan test
```
