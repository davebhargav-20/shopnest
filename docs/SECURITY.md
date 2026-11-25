# Security checklist — ShopNest on AWS

Status: **Done** = implemented and verified · **Partial** · **Not done**.

| # | Control | Status | Evidence | Gap / fix |
|---|---|---|---|---|
| 1 | Only port 80 public; SSH removed; DB port not exposed | Done | `evidence/aws-07-security-proofs.txt` (port probe from internet: 22, 443, 3306, RDS 3306 unreachable) | 443 is allowed by the SG but nothing listens (no domain) |
| 2 | RDS private (`PubliclyAccessible=false`), SG allows 3306 only from `shopnest-web` | Done | audit + console screenshot | — |
| 3 | RDS encrypted at rest, 7-day backups, deletion protection | Done | `evidence/aws-02-rds-harden.txt` | Single-AZ (cost) |
| 4 | TLS enforced to RDS (`require_secure_transport=1`), app uses `DB_SSL=1` with RDS CA | Done | plain connection refused (ERROR 3159); TLS 1.3 `TLS_AES_256_GCM_SHA384` accepted | — |
| 5 | DB password only in SSM Parameter Store (SecureString); not in Git/CI/image/docs | Done | gitleaks green; `docker history` clean | Password is also in `/opt/shopnest/.env` (mode 600) on the server — normal for compose |
| 6 | EC2: IMDSv2 required, hop limit 1, encrypted gp3, no key needed (SSM) | Done | instance metadata options at launch | OS patching is manual (`dnf update`) |
| 7 | S3 backup bucket: SSE, versioning, public access blocked, TLS-only, 30-day lifecycle | Done | `evidence/aws-01-foundation.txt`, screenshots | — |
| 8 | Container: non-root workers, no secrets/infra/tests in image, memory limits | Done | `evidence/local-docker-checks.txt` | Apache master runs as root (normal; workers are `www-data`) |
| 9 | Supply chain in CI: gitleaks, hadolint, Trivy (CRITICAL fails), pinned versions | Done | CI run green | Trivy covers only CRITICAL with fixes available |
| 10 | App headers: nosniff, X-Frame-Options, Referrer-Policy, Permissions-Policy; cookies HttpOnly + SameSite=Lax | Done | `curl -I` of live site | `Secure` cookie flag and HSTS only take effect with HTTPS |
| 11 | Uploads validated, PHP engine off in `images/` | Done (by code review) | `docker/apache-security.conf` | Not penetration-tested |
| 12 | Monitoring: 5 CloudWatch alarms → SNS topic | Partial | alarms exist (screenshot) | **No e-mail subscribed yet**, so no notification test was possible |
| 13 | Cost/credit protection: auto-stop Lambda with `KeepAliveUntil` | Done | dry run + live mode logged | stop is nightly, not idle-based |
| 14 | Least-privilege IAM | Not done | — | Learner Lab only allows the shared `LabRole` |
| 15 | **HTTPS** | **Not done** | — | **No domain: passwords cross the internet unencrypted. Use throw-away demo accounts only.** Fix: domain + Caddy (`SITE_ADDRESS=shop.example.com`) |
| 16 | **Forgot-password weakness** | Known, not changed | `forgot-password.php` | Resets any account (incl. admin) with e-mail + mobile only. Fix: e-mailed one-time token |
| 17 | **Demo credentials** (`admin / admin@123` …) are public in the README | Open | README | Change the admin password before any real use (see RUNBOOK) |
| 18 | WAF, rate limiting, Multi-AZ, CloudTrail trail | Designed only | — | cost / lab limits |
