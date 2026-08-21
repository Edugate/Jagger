# CodeIgniter 3 → 4 Migration Tracker

## Why strangler-fig, not a rewrite

Jagger has 67 controllers, 39 Doctrine models, 37 libraries, and 131 views on CodeIgniter 3
(see `docs/AUDIT.md`). Rewriting all of it to CodeIgniter 4 correctly — especially the
SAML/Shibboleth login flow in `application/controllers/Auth.php` and
`application/libraries/Jauth.php`, which is genuinely security-sensitive — needs a real
LAMP+MySQL+Shibboleth-SP environment to test against at each step. That environment doesn't
exist in the sandbox this migration started in (no `php`/`composer`/`mysql` available — verified
in `docs/AUDIT.md` §6), so nothing beyond `php -l` syntax-checking was possible here.

Given that, this migrates one module at a time, both frameworks running in the same deployment
throughout, instead of attempting a big-bang rewrite that can't be verified until the very end:

- `index.php` (repo root) is still the single front controller Apache/PHP-FPM points at.
- It reads `app4_routes.php` — an explicit allow-list of URI prefixes — and dispatches matching
  requests into `app4/` (CodeIgniter 4). Everything else falls through to the legacy
  `application/` (CodeIgniter 3), completely unchanged.
- A request is never "half handled" by both: the allow-list check happens before either
  framework boots.

## First thing to do with real PHP/Composer access

`app4/public/index.php` and `app4/app/Config/Paths.php` were hand-written against CI4's
documented bootstrap sequence without being able to run `composer install` and actually boot the
app anywhere (`docs/AUDIT.md` §6). Before migrating anything else: `composer install
--working-dir=app4`, hit `/dashboard` while logged out, confirm it renders instead of fatal-erroring,
*then* start migrating more controllers. If the bootstrap needs fixing, fix it here rather than
working around it per-controller.

## How to migrate the next controller

1. Pick the next `Not started` row below (roughly in dependency order — things `Dashboard`/other
   controllers depend on first).
2. Port its logic into a new class under `app4/app/Controllers/`, extending `BaseController`
   (`app4/app/Controllers/BaseController.php`) — it already gives you `$this->em` (Doctrine,
   pointed at the *same* `application/models` entity classes, not a duplicate copy) and
   `$this->legacySession`/`$this->isLoggedIn()` (reads the CI3-created session; see the long
   comment in `BaseController::bridgeLegacySession()` for the assumption it depends on —
   **re-verify that assumption before migrating anything that touches session state**, since a
   wrong assumption there is an auth bug, not a display bug).
3. Add the route to `app4/app/Config/Routes.php`.
4. Add the matching prefix to `app4_routes.php` (repo root). These two lists must always agree —
   a mismatch means either a 404 (route missing) or the legacy controller for that prefix
   becoming permanently unreachable (route added without ever falling through).
5. Update the status table below.
6. Get it reviewed and exercised against a real staging DB/webserver before merging — this
   project's own CI (`.github/workflows/`) only syntax-checks and boots the app, it does not
   click through business logic.
7. Once every controller in a given legacy directory (`manage/`, `providers/`, ...) is migrated,
   the corresponding `application/controllers/...` files can be deleted in the same PR.

Prefer migrating a **read-only** controller before a state-changing one — see `Dashboard` below
for why that ordering matters for risk, not just convenience.

## Status

Legend: ✅ migrated · 🚧 in progress · ⬜ not started

| Controller | Status | Notes |
|---|---|---|
| `Dashboard` (front page / logged-in dashboard only) | 🚧 | `app4/app/Controllers/Dashboard.php`. Read-only reference slice; widgets/stats not ported. |
| `Auth` (logout only) | 🚧 | `app4/app/Controllers/Auth.php`. Login (Shibboleth attrs, 2FA, auto-register — ~550 lines in the CI3 version) intentionally **not** ported yet; needs a live IdP to verify against. |
| `Authenticate` | ⬜ | |
| `Ajax` | ⬜ | |
| `Arp` | ⬜ | |
| `attributes/Attributes` | ⬜ | |
| `Disco` | ⬜ | |
| `Eds` | ⬜ | |
| `federations/*` (Fedactions, Fedregistration, Fvalidator, Manage) | ⬜ | 4 controllers |
| `Gworkers` | ⬜ | Also depends on the M2 `dragonmantank/cron-expression` API fix already applied to the CI3 controller |
| `Jaggerstatus` | ⬜ | Good second candidate: read-only, no auth dependency |
| `manage/*` (Accessmanage, Arpsexcl, Attributepolicy, Attribute_policyajax, Attrrequirement, Ec, Entityedit, Entitystate, Fedcategory, Fededit, Fvalidatoredit, Importer, Joinfed, Leavefed, Logomngmt, Mailtemplates, Premoval, Regpolicy, Spage, Statdefs, Statistics, Translator, Userprofile, Users) | ⬜ | 24 controllers — the bulk of the admin UI; migrate after `Auth` login is fully ported, since all of these require a logged-in admin |
| `Mdq` / `Mdqworker` | ⬜ | Signing itself (`Mdqsigner.php`) is framework-agnostic pure PHP already — low risk once queued |
| `Metadata` / `Metadatalocations` | ⬜ | |
| `Msigner` | ⬜ | |
| `notifications/Invitations`, `notifications/Subscriber` | ⬜ | |
| `Oidcauth` | ⬜ | |
| `P` | ⬜ | Serves the real site home (`$route['home']`) — migrate together with `Dashboard`'s front-page path since they overlap |
| `Providerregistration` | ⬜ | |
| `providers/*` (Detail, Idp_list, Providers_list, Sp_list) | ⬜ | 4 controllers |
| `reports/*` (Awaiting, Awaitinglist, Idpmatrix, Queueactions, Spmatrix, Timelines, View_attribute_matrix) | ⬜ | 7 controllers, all read-heavy — good migration order after Dashboard/Jaggerstatus |
| `Setup` | ⬜ | First-run only; low priority |
| `smanage/Reports`, `smanage/Sysprefs`, `smanage/Taskscheduler` | ⬜ | `Taskscheduler` already has the cron-expression API fix applied |
| `tools/Addontools`, `tools/Syncwrk`, `tools/Sync_metadata` | ⬜ | |
| `Update` | ⬜ | |

67 controllers total; 2 in progress (partially), 65 not started.

## Libraries/models

Not duplicated per-controller here: Doctrine models (`application/models`) are reused as-is by
both frameworks via `BaseController::buildEntityManager()` — they are not migrated or copied,
only pointed at from `app4/`. CI3-specific libraries (session helpers, `MY_Form_validation`,
etc.) get ported only when the controller that needs them is migrated, not ahead of time.
