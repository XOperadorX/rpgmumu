## Quick context for AI contributors

This is a small, procedural PHP web game (Mumu RPG) served with XAMPP/Apache and using Microsoft SQL Server via the `sqlsrv` PHP extension.

Key files to read first:
- `db.php` — establishes MS SQL Server connection using `sqlsrv_connect` (credentials live here).
- `functions.php` — shared helpers (e.g., `safeQuery($conn, $sql, $params)`, `getPlayerID()`, `formatMoney()`).
- `index.php`, `dashboard.php`, `login.php`, `register.php` — entry points showing session-based auth (`$_SESSION['PlayerID']`).
- `check_ban.php` — common access check included by pages.
- `*.php` in repo root — many single-action scripts (e.g., `plantar.php`, `comprar_semente.php`, `mercado_ajax.php`).

Architecture & patterns
- Procedural PHP: pages and endpoints are plain PHP files that `include` shared files (`db.php`, `functions.php`, etc.). Avoid assuming a framework.
- Database: MS SQL Server (sqlsrv). Queries use `sqlsrv_query()` directly; helper `safeQuery()` wraps query+fetch. Prefer keeping parameterized calls when possible: e.g. `sqlsrv_query($conn, $sql, [$param])` or `safeQuery($conn, $sql, $params)`.
- Auth: session-driven. Most code checks `$_SESSION['PlayerID']` (see `getPlayerID()` in `functions.php`). Logins set session keys directly; use the same session keys when modifying auth flows.
- AJAX & real-time: Many files are AJAX endpoints (`*_ajax.php`) and there are SSE endpoints (e.g., `historico_sse.php`). These return JSON or stream text/event-stream — preserve headers and minimal output.

Conventions to follow
- Filenames: lowercase, underscore-separated, single-purpose scripts (e.g., `equip_item_ajax.php`, `inventario_loja.php`). When adding a new endpoint, follow the existing name pattern and keep behavior single-responsibility.
- DB access: use `db.php` connection, then `safeQuery()` or `sqlsrv_query()` with parameter arrays. Avoid interpolating user input directly into SQL.
- Error handling: many files call `die()` or echo errors; be conservative when changing these flows — preserve user-facing messages and existing early-exit semantics.

Integration points & external services
- MS SQL Server configured in `db.php` (server/DB/credentials). When running locally, ensure `sqlsrv` PHP extension is enabled and a compatible SQL Server instance is available.
- Payment/recarga and external callbacks live in scripts like `recarga.php`, `recarga_update.php` — inspect before changing transactional flows.
- DNS/automation artifacts (e.g., `duckdns.bat`) may be present but are non-critical for code changes.

Developer workflows (discovered)
- Local dev: run under XAMPP/Apache on Windows and open `http://localhost/.../index.php`. Confirm `sqlsrv` extension and correct DB credentials in `db.php`.
- Quick checks: to validate changes, log in with a test account, then exercise the relevant script (e.g., plant/colher/comprar) via the UI or by calling the endpoint directly.

Search tips & useful grep targets
- Find DB usage: `grep -R "sqlsrv_" -n .`
- Find AJAX endpoints: `grep -R "_ajax.php" -n .` or list `*_ajax.php` files.
- Find entry points that require auth: `grep -R "PlayerID" -n .`

First safe edits for contributors
- Small bugfixes in helper functions (`functions.php`) and centralizing error messages are low-risk.
- For any DB schema or credential change, update `db.php` and notify maintainers — credentials are stored in that file.

If anything in this file is unclear or you'd like me to add examples (e.g., a short checklist for making a new AJAX endpoint), tell me which area to expand.
