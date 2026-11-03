# Security review — application handoff changes, 03/11/2026

Read-only review of the completed input, queue, draft, news routing and HR import changes. No code changed during this review. Scope is the local static prototype, not a production backend.

## Entry points and trust boundaries

- Public query parameters enter `TGApplyForm.prefill` (`assets/js/apply-form.js:153`). Vacancy IDs resolve against the static position registry; unknown IDs select an explicitly unassigned application. Query text is assigned to input values/text and never executed.
- Form values enter `TGApplication.create/valid` (`assets/js/application-data.js:61`, `:87`), then `TGPendingApplications.add` (`assets/js/demo-store.js:18`). Phone is normalized; IDs, enums, number ranges and text lengths are bounded. The queue is limited to 2,000 records.
- `sessionStorage` is treated as untrusted at HR import (`assets/js/admin-data.js:314`). Invalid payloads keep the queue and produce an import error. Incoming status/history are constructed by the importer. No arbitrary first vacancy or fabricated birth year is assigned.
- Imported/persisted names, addresses, messages and titles reach escaped template fields in `assets/js/admin.js`. Birth is a validated integer or null, rendered as a dash when absent. IDs used in candidate navigation must pass the ID schema.
- Draft content (`assets/js/apply-form.js:527`, `:537`) is size checked and restored through `.value` on an allowlist of fields. It is removed after handoff. Files are not serialized, and the page asks the user to select the CV again.
- Article query IDs resolve against `news-data.js`; invalid IDs redirect to fixed `404.html` (`assets/js/news-detail.js`). Text is escaped before constructing article/related-link markup. The image URLs are static repository-owned constants.

## Findings

### Informational — same-origin demo storage is not an authentication boundary

**Location:** `assets/js/admin-data.js`, `tools/build-admin.mjs`, generated `/admin/*`.

**Category:** Spoofing / Tampering / Information disclosure / Elevation of privilege.

Anyone using the same browser tab/origin can inspect or modify the local demo data and load the static HR screens. This is an explicitly labeled prototype with no authenticated HR API, no server-side candidate database and no real CV downloads. This review does not certify these static routes for processing real applications.

**Required before production:** authenticated server routes with role checks and validated writes, controlled CV storage, clear data retention and receipt behavior. These services are outside this frontend change.

## Non-findings considered

- Stored/reflected DOM XSS via queue names, source, notes and birth: string output remains escaped; birth must be an integer/null; adversarial queue test rejects malformed records without execution.
- ID substitution: exact registry resolution or an unassigned record; missing article IDs use a fixed local 404 destination. No remote redirect is accepted from the article query.
- False success/loss of provenance: queue write happens before UI success; import writes state before removing the queue, retries deduplicate record IDs. Storage failure tests preserve form input/queue and do not publish an unpersisted candidate.
- CSV formula injection: existing formula-prefix neutralization remains in the export path and its regression suite passes.
- CV execution/file traversal: this frontend only keeps a bounded file name as metadata; it does not evaluate document contents, store file bytes, request filesystem paths or send a CV to a server. Extension/size checks are user feedback, not a promise of server-side file safety.
- Secrets, CSRF, SQL/command injection, SSRF: no new credentials, cookie-authenticated write surface, database query, shell invocation or server-side fetch is introduced by these browser changes.

## Limits

The browser user can edit their own session, history and fixtures; the UI's timeline is a demonstration, not a tamper-proof audit trail. No penetration test, production auth review, external map/QR service security review or real CV upload E2E is claimed.
