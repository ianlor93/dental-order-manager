# Order Manager

**English** · [Italiano](README.it.md)

Order Manager is a web application for **digital dental laboratories** that manufacture **custom-made medical devices** under the EU Medical Device Regulation (**MDR 2017/745**). It covers the whole life of an order — from the prescription sent by a dental practice to production, shipping and long-term archiving — and builds the regulatory and privacy obligations (MDR, GDPR) into the workflow instead of leaving them on paper.

It was designed for, and ran in production at, an Italian digital dental laboratory. This repository is a **sanitized reference release**: the laboratory's identity, credentials, infrastructure details and commercial features have been removed, and everything that identifies an operator is now configuration.

---

## What it does

**Orders and production**
- **Direct file exchange between practice and laboratory.** Scans, projects and production files travel between the two on the laboratory's own servers, without external file-transfer services or third-party cloud storage. Clinical data stays under the laboratory's control, and the list of sub-processors stays short. A reverse proxy or CDN placed in front of the server, if used, still handles the traffic in transit.
- Prescriptions from dental practices, with resumable chunked uploads of CAD/CAM files and antivirus scanning (ClamAV).
- Orders move across dedicated storage areas: intake, workbench, finishing and shipping, and a long-term MDR archive.
- PDF documents generated per order: request sheet, production report, delivery note, shipping label, patient information, and the **statement for custom-made devices** (MDR Annex XIII).
- Logistics (delivery notes) and an accounting area for invoicing the laboratory's work, with monthly summaries.

**Regulatory compliance (MDR)**
- Post-market surveillance: incident reporting, review status, and the deadline for notifying the competent authority, with a dedicated PMS report.
- Long-term archive with access auditing and retention reporting.

**Privacy and legal (GDPR)**
- Versioned legal texts (terms of service, specific-approval clauses, privacy notice, data processing agreement). Acceptances are recorded per document, with version and content hash.
- The legal texts are templates: operator data (name, VAT number, address, competent court…) is injected from configuration at render time.
- GDPR data subject rights: data export (ZIP + PDF summary), rectification requests, and a deletion workflow (see *Known limitations*).
- Public list of sub-processors, as HTML and JSON.
- Personal data is stripped from application logs.

**Accounts and integration**
- Single sign-on from **Nextcloud** through signed JWT tokens (ES256), verified against a configured public key.
- Self-registration funnel: sign-up from a public landing page, email verification, manual approval, and provisioning of the Nextcloud account over a separate signed channel.
- Per-practice storage levels that set the user's Nextcloud quota, and monthly credits consumed by a companion CAD application published separately.
- Transactional emails built on a single master template, with an offline preview command (`php artisan mail:preview`).
- Graduated rate limiting on public and authenticated routes.

---

## Architecture

- **Laravel 12** (PHP 8.2 or later; developed and verified on PHP 8.4).
- **MySQL/MariaDB**, shared with Nextcloud: users come from the Nextcloud user table, and the application's own tables live alongside it.
- **Nextcloud** is the identity provider and the file space for practices. The integration relies on Nextcloud apps that are **not part of this release** (the *External Sites* JWT handshake, plus two companion apps for gatekeeping and user provisioning).
- **dompdf** for PDF generation, **ClamAV** for upload scanning, SMTP for email.
- Designed to run in Docker behind a reverse proxy.

Some technical identifiers (table prefixes, CSS classes, a notification class) keep the original internal prefix `enamel`. They are names inside the code, not references to a live service.

### Using it without Nextcloud

The order, document, MDR and GDPR logic does not depend on Nextcloud: all of the application's own data lives in its own tables (`oc_enamel_*`). The coupling is concentrated in a few points:

- **Authentication.** A single middleware (`NextcloudAuthBridge`) accepts the signed token from Nextcloud. There is no local login.
- **Users.** The `User` model reads the Nextcloud users table (`oc_users`). Two migrations reference it, and a few controllers and models read user data (such as the display name) from the Nextcloud tables.
- **Storage quota.** A practice's storage level is written into Nextcloud's preferences (`StudioController`, `ResetStudioCredits`).
- **Registration.** Account provisioning and access gating go through Nextcloud companion apps (`ProvisioningController`, `GateController`).
- **Embedding.** A middleware allows the application to be shown inside Nextcloud in an iframe (`AllowNextcloudFrame`).

Replacing these points would make Order Manager standalone. That means Laravel's own authentication or an OpenID Connect provider (Keycloak, Authentik, Microsoft Entra, Google…), a local users table, and removing or adapting the quota and provisioning channels. This adaptation has **not** been done or tested in this release.

---

## Installation

> Order Manager is not a standalone application: it expects a Nextcloud instance and the companion apps described above. This release is meant first of all to be **read and studied**. Running it end to end requires rebuilding that environment.

```bash
composer install --no-dev
cp .env.example .env
php artisan key:generate
# edit .env, then:
php artisan config:cache
php artisan migrate
```

The migrations assume the Nextcloud schema already exists in the same database.

`.env.example` documents every setting. The ones you cannot leave empty in practice:

| Variable | If left empty |
|---|---|
| `NEXTCLOUD_URL` | links and buttons in emails point nowhere |
| `NEXTCLOUD_SSO_PUBKEY_B64` | **nobody can sign in** (fail-closed) |
| `NC_PROVISIONING_PUBKEY_B64` | user provisioning is disabled (fail-closed) |
| `LAB_MANUFACTURER_NAME`, `LAB_EMAIL` | the legal texts show empty fields |
| `LANDING_URL`, `LANDING_ORIGINS` | the landing page sign-up form is rejected |
| `LEGAL_VERSION` | must match the version stated in the legal texts |

Both public keys are PEM files encoded in base64 on a single line (`base64 -w0 key.pem`).

---

## Known limitations

- **GDPR deletion is shipped disabled** (`ProcessGdprDeletions`, `ENABLED = false`). Before publication it was fixed to use the correct storage disks and to verify the deletion before logging it. It still locates an order's folder by the stored `directory_name`, which can differ from the actual folder name on disk (`Order::resolveDirectoryOnDisk()` is not used). Review this before enabling it.
- **The specific-approval clauses** (Italian Civil Code, articles 1341–1342) cannot be downloaded from the user profile.
- **There is no automated test suite.** `tests/` only contains the Laravel stubs.
- **Dependencies are newer than the ones that ran in production.** They were updated to clear every known security advisory. The release was verified as described below, but this exact combination has not run in production.
- **The JWT library is vendored** (`app/Support/Firebase/JWT/`, with a rewritten namespace). Composer reports that these classes do not follow PSR-4 and skips them; the authentication middleware loads them with its own autoloader.
- **Infrastructure assumptions:** trusted proxies are set to the Docker subnet `172.18.0.0/16` in `bootstrap/app.php`, and the storage disks keep the names of the original deployment. Their paths are configurable.
- **The legal texts are written for Italian law.** They are templates, not legal advice: have them reviewed before using them.

---

## How this release was prepared

Before publication the code went through a structured review:

- the laboratory's identity, hostnames, personal data, credentials and embedded keys were removed, and every operator-specific value was moved to configuration;
- the payment integration and the price list were removed;
- dead code was removed, and some pre-existing defects found along the way were fixed: a PDF template that could not compile, a route pointing to a missing method, and the storage paths of the GDPR deletion command.

The final state was checked in a clean container:
- syntax of every PHP file;
- every route resolving to an existing controller method;
- compilation of every Blade view;
- rendering of every email template;
- PDF generation;
- rendering of the four legal texts with all placeholders resolved;
- the SSO token check: accepted with the right key, rejected with a wrong key, rejected when no key is configured.

`composer audit` reports no known vulnerabilities.

---

## Author

Designed and directed by **Lorenzo Ianniello**.

The code was developed with **Claude Code** (Anthropic) under his direction. The requirements, the architecture, the regulatory and privacy design, and the verification of the result are his work.

---

## License

Released under the **GNU Affero General Public License v3.0 or later** — see [`LICENSE`](LICENSE). If you run a modified version as a network service, the AGPL requires you to make your source code available to its users.

Third-party material included in this repository (the JWT library, the Ubuntu font family, the Laravel skeleton) keeps its own license: see [`THIRD-PARTY-NOTICES.md`](THIRD-PARTY-NOTICES.md).
