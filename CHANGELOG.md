# Changelog

## [Unreleased]
- Optional human-verification challenge on public submissions, off unless configured
- Cloudflare Turnstile ships as the first challenge provider; others plug in behind a challenge contract
- A challenge provider that cannot be reached answers 503 rather than rejecting the submitter
- The challenge token is verified and discarded instead of being stored with the submission

## [0.1.0] - 2026-07-23
- Polymorphic form collection: a Form definition and its submissions, each scoped to any owning model via an owner morph
- Public, throttled submission endpoint that resolves a form by key and records the submission
- Pluggable, delivery-agnostic destinations (mail, webhook, Mautic, Smartpings) behind a destination contract, selectable per form
- Spam protection: honeypot fields, a minimum submit time, and per-minute rate limiting
- Typed submission columns (name, email, phone, subject, message) alongside an arbitrary payload, plus a delivery log
- A FormSubmittedEvent the host can bridge to its own workflows
- Config-driven throughout: destinations, protection thresholds, and the response formatter
