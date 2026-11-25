# ShopNest on AWS — Phase 1 Audit (read-only)

Date: 2026-11-25 · Account: 891377286919 · Role: `voclabs` → **AWS Academy Learner Lab**
Scope: repository `davebhargav-20/shopnest` (main, 1 commit `69d3638`) and the AWS account. Nothing was created, changed or deleted.

Status tags: **Verified** · **Implemented but not tested** · **Designed only** · **Not done**

## 1. Account type

| Check | Result |
|---|---|
| Caller ARN | `assumed-role/voclabs/<student>` → Learner Lab |
| `ap-south-1` (Terraform's region) | **Refused** (UnauthorizedOperation / AccessDenied on every service) |
| `us-east-1`, `us-west-2` | Accessible |
| Custom IAM / OIDC provider | Not creatable (only `LabRole`, `LabInstanceProfile` exist; 0 OIDC providers) |
| Cost Explorer | **Works** (unlike a typical Learner Lab); Nov-2026 spend so far ≈ USD 0.00 |
| Budgets | Not usable |

Consequence: `infra/*.tf` and the GitHub OIDC workflow **cannot work as written** (region, IAM role, OIDC, Budget).

## 2. Inventory vs. repository

| Item | Code in repo | Exists in AWS | Working | Evidence |
|---|---|---|---|---|
| Region | ap-south-1 | us-east-1 holds the existing resources | n/a | region sweep |
| EC2 instance | `compute.tf` | **None** (any region) | — | `describe_instances` empty |
| Elastic IP | `compute.tf` | None | — | empty |
| RDS MySQL | `database.tf` (8.0, deletion protection, TLS-only, log exports) | **`shopnest-db` exists** in us-east-1: MySQL **8.4.11**, db.t3.micro, 20 GB gp3, encrypted, private, single-AZ, 7-day backups, **status: stopped**, default param group, deletion protection **off**, no CloudWatch log exports, no tags | Not running; contents unknown | `describe_db_instances` |
| RDS snapshot | automated backups | 1 automated snapshot `rds:shopnest-db-2026-11-23-19-59` (encrypted) | Available | `describe_db_snapshots` |
| Security group web | `network.tf` | `shopnest-web` sg-0b85…: 80, 443 and **22 open to 0.0.0.0/0** | Exists | `describe_security_groups` |
| Security group DB | `network.tf` | `shopnest-db` sg-031b…: 3306 only from shopnest-web | Matches design | same |
| ECR | `storage.tf` | None | — | empty |
| S3 backup bucket | `storage.tf` | None (account has 0 buckets) | — | `list_buckets` |
| SSM Parameter Store | `database.tf` | None | — | empty |
| IAM role/profile | `iam.tf` | Only Lab-provided `LabRole` / `LabInstanceProfile` | Reusable | `list_roles` |
| CloudWatch log groups/alarms | `monitoring.tf` | No ShopNest groups, 0 alarms | — | empty |
| SNS topic | `monitoring.tf` | None for ShopNest | — | only Lab's `RedshiftSNS` |
| Lambda/EventBridge auto-stop | not in repo | None (only Lab system rules) | — | — |
| CloudTrail trail | — | No trail (event history is on by default) | — | — |
| Route 53 / ACM | — | None | — | — |
| Terraform state | local | **No state exists anywhere** | — | no `.tfstate` |

The existing `shopnest-db` and the two security groups appear to be an earlier manual/console attempt. They are reusable and **must be preserved**.

## 3. Repository findings

| Area | Finding | Tag |
|---|---|---|
| Content | Dockerfile, compose, deploy scripts, Terraform, workflow, tests, `health.php`, `docker/*` all present as described | Verified (read) |
| Local folder | `c:\xampp\htdocs\shopnest` is an **older copy** (no Docker/infra/CI files) and is not a git repo; the full code is only on GitHub | Verified |
| CI history | 1 run, **failed**: `lint` ✅, `test` ✅ (e2e passed in CI), `build` ❌ at "Set up job", `deploy` skipped | Verified (public API) |
| Local Docker run / e2e | Docker Desktop was **not running**; not yet done | Not done |
| Secrets in repo | grep for AKIA/ASIA/private keys/aws_secret: none; no `.env`, `.tfstate`, `.tfvars` | Verified |
| DB config | Environment variables with TLS option (`DB_SSL=1`) — good | Verified (read) |
| Sessions/cookies | `HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS; strict mode on | Verified (read) |
| Proxy handling | `remoteip` + `X-Forwarded-Proto` handled in Apache conf | Verified (read) |
| Uploads | `/opt/shopnest/uploads` bind mount, PHP engine disabled in `images/` | Designed only |
| Entrypoint import | Imports `ecomdb.sql` only if table `tbladmin` is absent (never over existing data) | Verified (read) |
| Secrets in `entrypoint.sh` | DB password passed via `MYSQL_PWD` env | Acceptable |
| `Dockerfile` | Container runs Apache (drops to `www-data`); RDS CA downloaded at build; `.dockerignore` excludes `.git`, `infra`, `tests` but **not** `deploy/` copies (removed later in the image) | Verified (read) |
| `deploy.sh` | Uses `aws ecr` + EC2 role; has auto-rollback; `docker image prune` limited to images (not volumes) | Verified (read) |
| `backup.sh` | Dumps DB + uploads to S3; **never run** | Implemented but not tested |
| Observed-risk: forgot-password | Resets password with email + mobile only, and applies to **admin** too → account takeover if those are known | Known weakness (flag only) |
| Observed-risk: demo accounts | README publishes demo logins incl. admin | Must be treated as throw-away |
| Public repo | Contains account structure but no secrets | Info |

## 4. Gaps

1. No compute, registry, backup bucket, secrets store, monitoring or alerting exists.
2. Terraform/OIDC design is incompatible with Learner Lab; the CI `build` job fails.
3. Existing RDS is stopped, runs MySQL 8.4 (repo targets 8.0 — app compatibility must be tested), has no tags, deletion protection off.
4. `shopnest-web` allows SSH from the whole internet.
5. Nothing proves the app runs on AWS; nothing was tested locally yet in this audit.

## 5. Risks

- Learner Lab credentials expire; the lab can be reset and wipe resources → off-account backups needed.
- A stopped RDS **auto-restarts after 7 days** and then bills.
- HTTP-only (no domain) → passwords cross the internet unencrypted.
- Public IP changes on each EC2 stop/start unless an Elastic IP is used.
- MySQL 8.4 vs 8.0: `mysql_native_password` is disabled by default in 8.4; PHP 8.3 mysqli uses caching_sha2 — expected to work, but must be verified.

## 6. Decisions needed from the owner

See the questions in the chat: provisioning method, CI/CD style, HTTPS/domain, fixed IP, report details, instance sizes. Also: permission to **start** the stopped RDS (≈ USD 0.017/h) and to **tighten** the SSH rule on `shopnest-web`.
