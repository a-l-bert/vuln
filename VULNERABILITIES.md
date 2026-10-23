# ShopEase PHP lab: verified vulnerability inventory

**Scope:** source review of this PHP training application, not a penetration test. Use only a
local, isolated lab with synthetic data and accounts you are authorized to test. Do not use the
public Render deployment, real customer data, or production credentials for exercises.

## Important status

The vulnerability switches in `config.php` are not a reliable checklist of working exercises.
There are 69 defined `VULN_*` switches: 63 are `true` and six are `false`. Only a subset of
enabled switches is connected to application behavior, and some refer only to hints or obsolete
settings. The findings below distinguish code that was confirmed from labels that are only
configured. This is a source-based inventory, not a claim that every toggle or every possible
bug was dynamically tested.

The current source also differs from older versions of this guide: for example, product image
uploads are validated and renamed, checkout recalculates prices from the database, and product
comments/reviews are not implemented on the product page. The old exploit examples have
therefore been removed rather than presented as working instructions.

## Confirmed vulnerable behavior

| Severity | Area | Confirmed behavior | Main source |
|---|---|---|---|
| Critical | Remember-me deserialization | A client cookie is decoded and unserialized with classes enabled; resulting fields can set session identity/role. The `Logger` destructor writes to a path and content held in object properties, creating a dangerous file-write gadget. | `includes/session_check.php`, `config.php` |
| High | SQL injection | Search, login, and User-Agent logging concatenate request-controlled values into SQL. Search remains injectable even though matching is case-insensitive. | `api/search.php`, `login.php`, `includes/log_ua.php` |
| High | Admin access and include | A URL-path quirk skips the admin check; the admin page also includes a page path derived from request input without an allowlist. The exact path behavior depends on the web server. | `admin.php` |
| High | Order IDOR / privacy disclosure | Order lookup can be based only on an order ID, returning customer email, shipping address, items, total, and status without verifying ownership. | `order_success.php` |
| High | Receipt path traversal | A caller-supplied filename is appended to `uploads/` and read without checking that the resolved path stays inside that directory. | `download_receipt.php` |
| High | Diagnostic disclosure | The debug page exposes `phpinfo()`. PHP warnings and errors are logged server-side but not displayed in responses. The Docker startup applies a deployment access gate, which must continue to protect the lab. | `debug.php`, `config.php`, `Dockerfile` |
| Medium | Profile-edit IDOR | The edit handler skips its ownership check when `VULN_IDOR_PROFILE_EDIT` is true, and updates the requested account's balance. Reachability depends on the surrounding admin-page routing. | `pages/edit_user.php`, `admin.php` |
| Medium | SSRF / local resource fetch | The admin image-fetch handler passes the submitted URL to `file_get_contents()` without destination restrictions. | `pages/fetch_remote_image.php` |
| Medium | Plaintext passwords and session fixation | Demo seeding stores plaintext passwords; successful login does not rotate the session ID under the current configuration. Public registration and admin user creation are disabled. | `config.php`, `login.php` |
| Medium | CSRF and unsafe state-changing requests | State changes lack CSRF tokens. Order approval and cart removal use GET requests. | `pages/orders.php`, `cart.php`, `checkout.php` |
| Low | Missing upper quantity bound | The legacy quantity-update module has no upper-bound check when the integer-overflow toggle is on. The main cart flow enforces a lower bound of one, but no upper limit. | `pages/update_cart_quantity.php`, `cart.php` |
| Low | Missing browser security headers | The shared header omits common security headers with `VULN_MISSING_HEADERS` enabled. | `includes/header.php` |
| Low | Backup file in document root | `config.php.bak` is present in the web root. Whether it can be fetched depends on the web server and deployment access gate; treat it as sensitive and remove it from deploy artifacts. | `config.php.bak`, `Dockerfile` |

Severity indicates the potential impact in a lab context, not whether the behavior is reachable
from the public Internet. The Docker deployment access gate is part of the deployment boundary;
keep it enabled and do not treat these intentionally vulnerable pages as production-ready.

## Toggle inventory

All values below reflect the current definitions in `config.php`. “Enabled” means only that the
constant is `true`; it does **not** confirm an implemented or reachable challenge.

### Authentication, sessions, and SQL

- Enabled: `VULN_SQLI_PRODUCT_SEARCH`, `VULN_HEADER_SQLI`, `VULN_SQLI_LOGIN`,
  `VULN_WEAK_PASSWORD_HASHING`, `VULN_NO_RATE_LIMITING`, `VULN_PREDICTABLE_SESSIONS`,
  `VULN_SESSION_FIXATION`, `VULN_USERNAME_ENUMERATION`, `VULN_INSECURE_DESERIALIZATION`,
  `VULN_WEAK_REMEMBER_ME`, `VULN_PREDICTABLE_RESET_TOKEN`, `VULN_SQL_COMMENT_FILTER_BYPASS`.
- Confirmed in current PHP code: search/login/logging SQL injection, untrusted remember-cookie
  deserialization, plaintext passwords, and session fixation.
- Not demonstrated: rate limiting, predictable session IDs, predictable password-reset tokens,
  or a SQL comment-filter bypass exercise. Username enumeration does not distinguish ordinary
  failed logins, though SQL errors may be disclosed.

### Access control and business logic

- Enabled: `VULN_IDOR_ORDER_VIEW`, `VULN_PATH_TRAVERSAL_INCLUDE`,
  `VULN_BROKEN_ACCESS_CONTROL`, `VULN_IDOR_PROFILE_EDIT`,
  `VULN_PRICE_QUANTITY_TAMPERING`, `VULN_MASS_ASSIGNMENT_CHECKOUT`,
  `VULN_RACE_CONDITION`, `VULN_RACE_MULTI_ENDPOINT`, `VULN_RACE_SINGLE_PACKET`,
  `VULN_RACE_DOUBLE_SPEND`, `VULN_RACE_VOUCHER_OVERRUN`, `VULN_WORKFLOW_STEP_SKIP`,
  `VULN_ROUNDING_ERROR`, `VULN_INTEGER_OVERFLOW`, `VULN_NEGATIVE_REFUND`,
  `VULN_LOYALTY_ABUSE`, `VULN_HORIZONTAL_PRIV_ESC`, `VULN_VERTICAL_PRIV_ESC`,
  `VULN_HTTP_METHOD_SWAP`, `VULN_URL_QUIRKS`, `VULN_SPOOFABLE_ACCESS_CONTROL`,
  `VULN_OPEN_REDIRECT`, `VULN_HTTP_VERB_TAMPERING`, `VULN_UNICODE_NORMALIZATION`,
  `VULN_NULL_BYTE_INJECTION`, `VULN_CASE_SENSITIVITY_BYPASS`, `VULN_TRAILING_DOT_SLASH`.
- Confirmed: order IDOR, profile edit IDOR, URL-quirk admin-check bypass, unvalidated local
  include, and missing upper bound on the legacy quantity-update handler.
- Not demonstrated: checkout price tampering or mass assignment; checkout uses database prices
  and session cart data. The current search change intentionally makes product-name matching
  case-insensitive, so it is not a case-sensitivity-bypass exercise.
- `VULN_PATH_TRAVERSAL_INCLUDE` and `VULN_BROKEN_ACCESS_CONTROL` are not the switches governing
  the observed admin include and URL-quirk behavior. `VULN_HTTP_VERB_TAMPERING` has a check in
  a helper that no current page calls.
- The unsafe remember-cookie `unserialize()` is controlled by `VULN_WEAK_REMEMBER_ME`;
  `VULN_INSECURE_DESERIALIZATION` is enabled but unused.

### Browser/client-side and CSRF

- Enabled: `VULN_REFLECTED_XSS`, `VULN_STORED_XSS`, `VULN_CLICKJACKING_DRAG`,
  `VULN_CSRF`, `VULN_CSRF_METHOD_OVERRIDE`, `VULN_CSRF_TOKEN_LEAK`,
  `VULN_CSRF_FAIL_OPEN`, `VULN_DOM_CLOBBERING`, `VULN_CLIENT_PROTOTYPE_POLLUTION`,
  `VULN_POSTMESSAGE_NO_ORIGIN`, `VULN_UNSAFE_EVAL`, `VULN_CLIENT_SIDE_VAL_TRUST`.
- Confirmed: missing CSRF defenses on selected state-changing operations.
- Not demonstrated: reflected/stored XSS or the listed JavaScript/client-side challenge
  behaviors in the current PHP pages. The older product review/comment examples do not match
  current `product.php`.

### Files, network, and information exposure

- Enabled: `VULN_UNRESTRICTED_FILE_UPLOAD`, `VULN_PATH_TRAVERSAL_DOWNLOAD`, `VULN_SSRF`,
  `VULN_EXPOSED_SOURCE_MAP`, `VULN_COOKIE_DEBUG_MODE`, `VULN_AUTOCOMPLETE_ENABLED`,
  `VULN_MISSING_CACHE_CONTROL`, `VULN_VERBOSE_API_JSON`,
  `VULN_EXPOSED_BACKUPS`, `VULN_HARDCODED_CREDS`, `VULN_DEBUG_ENDPOINTS`,
  `VULN_MISSING_HEADERS`.
- Confirmed: receipt path traversal, SSRF handler, debug/config disclosure,
  missing security headers, and the presence of a backup file.
- PHP warnings and errors are logged server-side and are not rendered into browser responses.
- Not confirmed as an unrestricted upload: the product image helper validates image content,
  permits a small image MIME allowlist, and generates a new filename.
- Source maps, cookie debug mode, autocomplete, verbose API JSON, and hardcoded-credential
  behaviors were not demonstrated in the current modules.

### Remaining configured toggles

- Enabled but not implemented: `VULN_ROUNDING_ERROR`.
- Disabled: `VULN_COMMAND_INJECTION`, `VULN_VERBOSE_ERRORS`, `VULN_XXE_IMPORT`,
  `VULN_PARAMETER_POLLUTION`, `VULN_COUPON_STACKING`, `VULN_REDOS`.
- No current application behavior was confirmed for command injection; do not enable it or
  interpret its name as an implemented challenge.
- The four disabled toggles above are explicitly defined so their modules do not fail with an
  undefined-constant error. The import, discount, and validation modules are incomplete and
  should not be treated as verified exercises.

### Toggle-to-code audit result

A source scan of the current PHP files found that not all configured vulnerabilities are real,
reachable challenges. Several enabled constants are never read outside their definitions; some
are named only by the hint page or the older settings page. `VULN_HTTP_VERB_TAMPERING` is read
inside an access-control helper that no current route calls. The configured names do not
automatically enable or validate challenge behavior.

Code paths currently contain the following confirmed behaviors: product-search and login SQL
injection, User-Agent SQL injection, weak/plaintext demo passwords, session fixation, IDOR on
order viewing and profile editing, an admin URL-quirk access check, receipt path traversal,
unrestricted remote fetching (SSRF), unsafe remember-cookie deserialization, a diagnostic
`phpinfo()` endpoint, missing security headers, and an unbounded quantity in the legacy
quantity-update module. These findings are subject to the reachability caveats above; in
particular, web-server path handling affects the admin URL quirk.

The following are not verified as working challenges in the current PHP app: rate limiting,
predictable session IDs or reset tokens, username enumeration, command injection, XSS, browser
or client-side JavaScript issues, checkout price tampering/mass assignment, race conditions,
coupon/discount abuse, unrestricted image upload, and the other configured toggles not mapped
to an implemented code path. The four disabled import/discount/validation toggles are incomplete
modules, not working challenges. Several hint-page suggestions also describe exercises that
are not present; do not rely on hints as proof of implementation.

This confirms that the statement “every vulnerability named in `config.php` works” is **false**.
The app intentionally retains only the vulnerable code paths documented above; implementing
every unverified toggle would require a separate scope decision.

## Flags: storage, submission, and availability

### Where they are stored

- Startup adds the named challenge catalog to the active database's `flags` table once. This
  works with SQLite and PostgreSQL/Neon and does not clear or reset shop records. Each catalog
  entry receives a generated persistent flag; legacy entries are retained. The same database
  must be used to discover and submit a flag.
- The catalog contains named challenge entries, including repeated legacy vulnerability IDs
  where the inventory maps more than one challenge to that ID. Names, not IDs alone, distinguish
  those challenges.
- A valid flag submission by a signed-in learner is recorded in `hall_of_fame` with a stable
  demo-account reference, learner-entered name and email, unique certificate number, flag
  reference, and timestamp. Existing achievement rows are preserved; missing certificate
  numbers are generated during additive schema initialization.
- Automatically detected login SQL-injection awards are stored separately in
  `automatic_awards`, so their recording does not depend on legacy `hall_of_fame` columns or
  the challenge-flag catalog.
- The additive `award_claims` registry reserves each challenge and case-insensitive learner
  email globally across demo accounts. Existing award rows are retained; when historical
  duplicates exist, the earliest award is the canonical one for subsequent claims. The email
  is not independently verified.
- By default, SQLite uses `data/vulnapp.db` (or `$APP_DATA_DIR/vulnapp.db`). If `DATABASE_URL`
  is configured, the app uses that PostgreSQL database instead. Neon is initialized on normal
  application startup; no shop-data reset is required for the flag catalog.

### How a learner claims a flag

1. Work only through the authorized local lab instance and its exercises. Identify a flag
   intentionally associated with the challenge you completed. The catalog provides persistent
   challenge flags; it does not automatically detect or prove that an exploit occurred.
2. Sign in to the lab and open **Submit Flag** (`/submit_flag.php`).
3. Paste the exact flag or upload its downloaded plain-text file (up to 16 KB). The upload is
   read for validation and is not stored on the server. After validation, enter the individual
   learner's name and email. Awards show the shared demo account, learner identity, unique
   certificate number, vulnerability type, and discovery time in the Superadmin **Hall of
   Heroes**. Re-submitting the same challenge for the same learner email reuses the original
   award across demo accounts (email comparison is case-insensitive); no duplicate certificate
   is created.
4. Login SQL injection is detected when the vulnerable query authenticates a learner who would
   not authenticate with an exact username/password check. That success opens the same identity
   claim form. This award is stored in `automatic_awards`, independently of legacy
   `hall_of_fame` constraints and challenge-flag catalog rows. Learners enter their personal
   name and email there, not another flag or the shared username. Other challenge types still
   require flag submission; automatic exploit detection is not implemented for every catalog
   entry. After a claim is saved, the page confirms the discovery and links to its PDF
   certificate. The learner profile lists individual identity and certificate links.
   `generate_cert.php` authorizes by certificate number and signed-in demo account.

Flags are stored in the database, so do not publish their values in documentation or expose
them in learner-facing catalog pages. A fresh deployment creates catalog values automatically;
existing shop and achievement rows are not reset.

### Safe Lab simulation certificates

- Every configured `VULN_*` switch has a contained simulation on `/safe_labs.php`. After the
  simulation's synthetic check passes, a signed-in learner can enter their name and email to
  claim a uniquely numbered certificate for that specific simulation.
- Simulation certificates are stored in `automatic_awards` with distinct `SAFE-LAB-*` challenge
  identifiers. Repeat claims for the same simulation and case-insensitive learner email reuse
  the existing certificate; different simulations receive separate certificates.
- These certificates confirm only that a fixed, in-memory simulation completed. They are not
  challenge flags, proof of exploitation, or evidence that the corresponding application
  vulnerability is enabled or reachable. The separate discovered-flag workflow continues to
  represent the named challenge catalog.
- Certificate downloads are authorized by the signed-in demo account, just like other
  automatic awards. The learner profile lists simulation certificates with the rest of that
  account's awards.
- The one-page landscape PDF uses the same ShopEase certificate layout as other awards and
  labels simulations “For completing”; flag/detected awards are labeled “For discovering.”
- Existing SQLite and PostgreSQL schemas receive missing `automatic_awards` and
  `award_claims` columns additively. Existing award records are preserved. If an award claim
  fails, use the short reference ID shown on the page to locate `Award claim failed [ID]` in
  authorized application logs; never expose database URLs, cookies, learner contact data, or
  flag values in a bug report.

### Flag workflow caveats

- A valid flag submission records a claimed completion; it is not independent proof that the
  learner exploited the associated vulnerability. Login SQL injection is automatically
  detected, but the learner still self-identifies when claiming the award.
- The catalog identifies intended challenge topics, but it does not make an unimplemented
  vulnerability toggle into a working exercise. See the source-verified inventory above.
- The Hall of Heroes retains the learner's name, email, shared-account name, and unique
  certificate number so the record remains attributable if the demo account is later removed.

## Safe ways to use this guide

- Run the lab only on localhost or an isolated disposable environment, with synthetic records.
- The PHP app requires the deployment access-gate variables and a `SUPERADMIN_KEY`; keep the
  Render instance protected and do not use it for exercises.
- Start with the confirmed issues above. A configured toggle that is not mapped to code is not
  a working challenge.
- This inventory is a point-in-time source review. Re-audit after changes to code, deployment,
  PHP version, or web-server routing.
