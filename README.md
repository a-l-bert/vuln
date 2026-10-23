# ShopEase (PHP)

A server-rendered shop built in plain PHP + PDO (no framework) for authorized security-training practice.

⚠️ **For local, authorized testing only.** Do not deploy publicly, do not use real data.

## Pages
1. `login.php` — SQL injection, plaintext credentials, and session fixation.
2. `products.php` / `api/search.php` — product browsing and search.
3. `profile.php` — profile display and earned-award/certificate history.
4. `product.php` — product details and validated image display.
5. `admin.php` — admin dashboard for managing products, customer accounts, orders, and awards.

Admin product management accepts an image URL or a local JPG, PNG, GIF, or WebP upload.
Administrators can create customer accounts and manage existing user records; new account
passwords must be at least eight characters. Password storage follows the active lab hashing
switch, so the intentionally enabled weak-hashing lab stores them in plaintext. Public
registration remains disabled. Storefront product cards preserve image aspect ratios and fit
images within each card.

The current code does not implement every vulnerability named by its configuration switches.
See [VULNERABILITIES.md](VULNERABILITIES.md) for the source-verified inventory, limitations,
and flag storage/submission workflow. Use only an isolated local instance with synthetic data.
For developers diagnosing failures or preparing updates, see the
[Developer Operations Guide](docs/DEVELOPER-OPERATIONS-GUIDE.pdf) and its
[editable HTML source](docs/DEVELOPER-OPERATIONS-GUIDE.html).

The named challenge-flag catalog is initialized in the active SQLite or PostgreSQL/Neon
database without resetting existing shop data. A signed-in learner can submit a discovered
flag at `/submit_flag.php` by pasting it or selecting its downloaded plain-text file from
their computer. Valid submissions ask for the learner's name and email, then issue a small PDF
certificate with a unique number. Awards are listed in the Superadmin Hall of Heroes with
the individual learner identity and shared demo account. Successful SQL-injection login
bypasses open the award-claim form automatically; other challenges still require flag
submission. For a login bypass, learners enter their own name and email on that form—not the
shared demo username or another flag. Uploaded files are limited to 16 KB, read as plain text,
and not stored by the app. Other flag submissions are self-reported and do not automatically
detect an exploit. Directly detected login awards use the additive `automatic_awards` table,
separate from the legacy flag-backed achievement table. The `award_claims` registry prevents
the same case-insensitive email from claiming the same challenge twice, even across demo
accounts; it preserves existing award rows and points repeat claims to the original account.
Learner emails are collected for attribution but are not email-verified.

Learners use the ShopEase storefront and its implemented challenge flows directly. See the
[Vulnerability Reference](docs/VULNERABILITY-REFERENCE.pdf) for source-verified challenge
status, concepts, and defensive guidance.

If an award claim fails, the page shows a short reference ID. Use that ID to find the matching
`Award claim failed [ID]` message in the authorized Render logs; do not share database URLs,
cookies, learner contact details, or flag values.

## Setup
Needs PHP 8.x with the `sqlite3`/`pdo_sqlite` extension for local development (commonly
`php-sqlite3` on Debian/Ubuntu). The container also includes `pdo_pgsql` for Neon.
```bash
php -S 127.0.0.1:8081
```
Visit `http://127.0.0.1:8081`. `data/vulnapp.db` is created and seeded automatically on first request; delete it to reset.
To use PostgreSQL instead, set `DATABASE_URL` to a PostgreSQL connection URL; the schema and demo rows are initialized automatically. The seeded catalog includes product photography, and an empty database receives the demo users and products.

To intentionally replace **all** shop records in a PostgreSQL database with the demo users and products, set `DATABASE_URL` to that database and run the reset command from a one-off job:
```bash
php scripts/reset_demo_data.php --confirm
```
This deletes the users, products, orders, comments, reviews, flags, and activity records before reseeding. It refuses to run from a web request or against SQLite. Only use it when replacing the existing data is intended.

Demo logins: `alice/alice123`, `bob/bobpass`, `admin/admin!2024`

Account registration is disabled. Students should use the shared demo accounts above.

Make sure the `uploads/` folder is writable by the PHP process (`chmod 755 uploads` is enough on most local setups).


## Testing locally
```bash
cd vulnapp-php
php -S 127.0.0.1:8081
```
Verify the retired public registration route is unavailable:
```bash
curl -i http://127.0.0.1:8081/register.php
```
The removed route returns `404 Not Found`. Learners use the seeded demo accounts or an account created by an administrator; public self-registration is unavailable.
Delete `vulnapp.db` any time to reset to the seeded demo data.

## Deploying to Render
This is a single PHP service. The included Dockerfile installs PDO support for SQLite and
PostgreSQL and enables the ShopEase-branded access sign-in page. Configure `LAB_AUTH_USER`,
`LAB_AUTH_PASS`, and `SUPERADMIN_KEY` in the Render service environment before exposing the
intentionally vulnerable lab. The access gate remains enabled; the sign-in session is kept in
the browser so the access prompt does not repeat while the session is active.

For durable relational data, set `DATABASE_URL` as a secret environment variable in the Render
service, using a Neon PostgreSQL URL with SSL (`sslmode=require`). When Neon is empty, the app
creates its schema and seeds the demo accounts and product catalog. Product uploads remain in
`uploads/` and need separate durable file storage to survive redeploys.

Render startup does not reset or delete existing Neon data. To intentionally replace it, run the
guarded `php scripts/reset_demo_data.php --confirm` command from an authorized one-off job or
shell session. Do not enable a startup reset for a persistent production database.
