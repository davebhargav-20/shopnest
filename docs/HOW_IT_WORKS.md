# How ShopNest on AWS works — study guide

Everything here describes what is actually deployed (region us-east-1, AWS Academy Learner Lab).
Live site: http://52.23.26.212/ · Code: github.com/davebhargav-20/shopnest (branch `aws/cloud-deploy`).

---

## 1. The big picture in one paragraph

A visitor opens the Elastic IP. The request goes to **EC2**, where **Caddy** (a web proxy in a Docker container) hands it to the **PHP-Apache app** (another container). The app reads and writes data in **RDS MySQL** over an encrypted connection. The app's logs go to **CloudWatch**; five **alarms** watch the server and database. A nightly script dumps the database to **S3**. **Systems Manager** lets us run commands without SSH and stores the database password. A **Lambda** (triggered by **EventBridge**) stops the server and database every night to save credit. **GitHub Actions** tests every change, and the server itself deploys a change only after the tests passed.

---

## 2. Each service: what it does here and exactly how it was configured

### EC2 (the server) — `infra/learnerlab/03_compute.py`
- Name `shopnest-web`, type **t3.micro** (2 vCPU burst, 1 GB RAM), image **Amazon Linux 2023**.
- Disk: **20 GB gp3, encrypted**, deleted with the instance.
- **IMDSv2 required**, hop limit 1 (stops stolen-credential tricks through the metadata service).
- IAM: the lab's `LabInstanceProfile` (role `LabRole`) — the only role the lab allows. It lets the server read SSM, write CloudWatch logs and use S3.
- Security group `shopnest-web`. No key pair is needed.
- Tags: `Project=ShopNest`, `KeepAliveUntil=2026-12-02`.
- **User-data (runs once at first boot):** installs Docker and cron, installs Docker Compose, adds a 2 GB swap file (the 1 GB RAM is tight when building images), creates `/opt/shopnest`, writes the compose file, Caddyfile, `backup.sh` and `pull-deploy.sh`, **reads the DB password from SSM Parameter Store** and writes `/opt/shopnest/.env` (mode 600), writes `auto-deploy.conf`, creates the systemd timer, adds the nightly backup cron, and starts the first deploy.

### EBS (the disk)
The 20 GB encrypted gp3 volume holds the OS, Docker images and `/opt/shopnest/uploads` (product pictures, mounted into the app container so they survive container replacement).

### Elastic IP — `52.23.26.212`
A normal public IP changes whenever the server stops. The Elastic IP stays fixed, so the URL never changes. It costs about $0.005/hour even while stopped.

### Security groups (firewall)
| Group | Rule |
|---|---|
| `shopnest-web` | inbound **80** and **443** from anywhere. **Port 22 (SSH) was removed** — administration uses SSM. |
| `shopnest-db` | inbound **3306 only from `shopnest-web`** — nothing on the internet can reach the database. |

### VPC
The account's default VPC (six subnets, one per availability zone). The server and the database subnet group live in it. No NAT gateway or load balancer (cost).

### RDS (the database) — `02_rds_harden.py`
The instance `shopnest-db` already existed (MySQL 8.4.11, db.t3.micro, 20 GB gp3, encrypted, private, single-AZ, 7-day backups). I **kept it and added**:
- master password set from the SSM secret (so no one has to know it),
- a custom **parameter group `shopnest-mysql84` with `require_secure_transport=1`** → plain connections are refused (proved: `ERROR 3159`), TLS connections work (TLS 1.3),
- **deletion protection on**, tags, CloudWatch log exports (error and slow-query),
- a **manual snapshot** `shopnest-manual-pre-demo`.
The app connects with `DB_SSL=1` and the Amazon RDS CA bundle baked into the Docker image, so the server certificate is verified. On first run the container imports `ecomdb.sql` only if the table `tbladmin` doesn't exist, so existing data is never overwritten.

### S3 (backups) — `01_foundation.py`
Bucket `shopnest-backups-891377286919-use1`: **encryption (SSE-S3)**, **versioning**, **Block Public Access on**, a **bucket policy that denies non-TLS requests**, and a **lifecycle rule** (expire after 30 days, old versions after 7). `backup.sh` dumps the database with `mysqldump` over TLS, archives the uploads folder, and uploads both. It runs from cron at 21:00 UTC (02:30 IST). One run was verified, and the dump was also copied to your PC.

### Systems Manager (SSM)
- **Run Command:** lets my scripts (`ssm_run.py`) and the console run commands on the server with no SSH. The server shows as an Online managed node.
- **Parameter Store:** `/shopnest/db_password` is a **SecureString** (encrypted). The server reads it at boot; it is never stored in GitHub, code or the image.

### CloudWatch
- **Logs:** log groups `/shopnest/app` and `/shopnest/proxy`, fed by Docker's `awslogs` driver; retention 14 days.
- **Alarms (5), all notify SNS:** EC2 CPU > 80%, EC2 status-check failed, RDS CPU > 80%, RDS free storage < 2 GB, RDS connections > 50.

### SNS
Topic `shopnest-alerts` receives the alarm messages. **No e-mail is subscribed yet**, so alerts are not delivered until you subscribe one.

### Lambda + EventBridge (auto-stop) — `04_ops.py`, `lambda/autostop.py`
- Lambda `shopnest-autostop`: Python 3.12, 256 MB, 60 s timeout, role `LabRole`, `DRY_RUN=false`.
- Logic: find EC2 and RDS tagged `Project=ShopNest` that are running; **skip any with `KeepAliveUntil` ≥ today**; stop the rest.
- EventBridge rule `shopnest-autostop-nightly`: `cron(30 18 * * ? *)` = 18:30 UTC = midnight IST, target = the Lambda.
- Why: a forgotten server + database costs about $0.93/day; the lab credit is limited. Today both are protected until 2 Dec 2026.

### CloudTrail
Event history (on by default) records every API call (for example my `SendCommand`s). No separate trail was created.

### Cost Explorer
Used to read spend. It lags about 24 hours, so today's cost isn't visible yet.

---

## 3. How each workflow works

**A. A visitor request.** Browser → Elastic IP :80 → security group allows → Caddy container → PHP app container → (TLS) → RDS → page returned. Images come from `/opt/shopnest/uploads`.

**B. Secret handling.** The password lives only in SSM. At boot the server fetches it into `/opt/shopnest/.env` (readable by root only), Docker Compose passes it to the app as an environment variable, and the app uses it to connect. GitHub and the image never contain it.

**C. Provisioning (how it was built).** Run in order with the lab credentials in environment variables: `01_foundation.py` (S3, SNS, log groups, SSM secret, starts RDS) → `02_rds_harden.py` → `03_compute.py` (EC2 + Elastic IP) → `04_ops.py` (close SSH, alarms, Lambda, schedule). The scripts are idempotent (safe to re-run) and nothing was deleted. Terraform exists in `infra/*.tf` but the lab forbids what it needs (region ap-south-1, IAM, OIDC), so it is design-only.

**D. Backup.** cron → `backup.sh` → `mysqldump` (TLS) + tar of uploads → upload to S3 (encrypted, versioned) → old files expire after 30 days. RDS also keeps automatic 7-day backups.

**E. Monitoring.** Container logs → CloudWatch Logs. Metrics → alarms → SNS topic → (e-mail once subscribed).

**F. Auto-stop.** EventBridge (midnight IST) → Lambda → checks tags → stops unprotected EC2 and RDS.

**G. Disaster recovery.** Snapshot/automatic backup → "restore to new instance" (measured **7.3 minutes**) → repoint `DB_HOST`. Server lost → re-run `03_compute.py` (about 6 minutes). Bad release → automatic rollback (below). Lab reset → rebuild with the scripts and load the dump from your PC.

---

## 4. The CI/CD pipeline: how it is configured

### Why this design
CI/CD usually lets GitHub log in to AWS. The Learner Lab can't create an OIDC provider or IAM roles, and its keys expire in hours. So **the server pulls**: GitHub holds **no AWS secret at all**.

### Part 1 — CI on GitHub Actions (`.github/workflows/ci-cd.yml`)
- **Triggers:** push to `main` or any `aws/**` branch, pull requests, manual run. Permissions are read-only; runs on the same branch are queued, not cancelled.
- **Job `lint`:** checks out the code, sets up PHP 8.3, runs `php -l` on every PHP file, runs **gitleaks** (secret scan), and runs Terraform fmt/validate.
- **Job `test`** (needs lint): starts a **MariaDB 11 service**, loads `ecomdb.sql`, starts PHP's built-in server, runs `tests/e2e.py` (**68 checks** across customer, seller and admin), then greps the PHP log for errors.
- **Job `build`** (needs test): **hadolint** (Dockerfile lint) → `docker build` with `APP_VERSION` = first 12 characters of the commit → **Trivy 0.76.2** scan that fails on CRITICAL vulnerabilities (database from `mirror.gcr.io`) → **container smoke test** (start the image with a database, `/health.php` must say ok, workers must run as `www-data`, no infra/tests files inside).
- It uses **no cloud credentials** and no secrets except GitHub's built-in token for gitleaks.

### Part 2 — CD on the server (`deploy/pull/pull-deploy.sh`)
- A **systemd timer** (`shopnest-deploy.timer`, 30 s after boot then every 120 s) runs the service `shopnest-deploy.service`.
- Settings are in `/opt/shopnest/auto-deploy.conf`: `AUTO_DEPLOY` (true/false), `REPO`, `BRANCH` (currently `aws/cloud-deploy`; set to `main` after the PR is merged).
- Each run:
  1. Stops if `AUTO_DEPLOY=false` (freeze). A lock file prevents two deploys at once.
  2. Asks the GitHub API for the newest commit on the branch; stops if it is already deployed or known bad.
  3. **CI gate:** asks GitHub for the workflow run of exactly that commit. Only `completed:success` continues; anything else (running, failed, none) is a logged **SKIP** — it fails closed.
  4. Downloads that commit's source, **builds the Docker image on the server**, tags it with the commit id.
  5. Updates `APP_TAG`, runs `docker compose up -d`, restarts the proxy (Caddy caches the app's address).
  6. **Health check:** up to 30 × 4 s, `/health.php` must report the **new version**. Success → log `DEPLOYED`, remember the commit.
  7. Failure → log `UNHEALTHY`, **roll back** to the previous image, remember the bad commit so it is never retried, log `ROLLED BACK`.
- Every action is appended to `/opt/shopnest/deploy-history.log`.

### What I proved live (25 Nov 2026)
| Test | Result |
|---|---|
| Normal change | CI green → deployed automatically; version `1e6e07449b44` → `aa34984d808c` |
| Commit with a PHP syntax error | CI red → server logged `SKIP … completed:failure`; live version unchanged |
| Commit that passes CI but fails only in production (`DB_SSL=1`) | `UNHEALTHY` 18:20:07 → `ROLLED BACK (healthy)` 18:20:14 |

### Problems found and fixed while building it
- The original workflow used `aquasecurity/trivy-action@0.28.0`, a tag that no longer exists, so its build job could never start. Trivy now runs from its pinned Docker image.
- Windows converted `entrypoint.sh` to CRLF line endings and the container crashed; `.gitattributes` now forces LF.
- The original pipeline pushed to ECR with an OIDC role; both are impossible in the lab, so it was replaced by the pull-based design.

### Honest limits
The server builds the image itself (slower than pulling from a registry, and Trivy scans the CI build of the same commit rather than the server build). The deploy branch is a server setting. Unauthenticated GitHub API calls are limited to 60/hour per IP (it fails safe). About 2 minutes of unhealthy traffic can occur before a rollback completes. `AUTO_DEPLOY=false` was written but not tested.

---

## 5. Short answers for your professor
- **Why a pull-based pipeline?** The lab can't give GitHub AWS access and the keys expire; this way no AWS secret sits in GitHub.
- **What stops a bad commit going live?** The server refuses any commit whose CI run didn't succeed, and rolls back any release that fails its health check.
- **Why is the database safe?** Private subnet and firewall rule, encrypted disk, TLS required, deletion protection, 7-day backups plus S3 dumps.
- **Why is there no SSH?** Systems Manager does the same job without an open port.
- **What does it cost?** About $0.93/day if left running, about $28/month; the nightly Lambda lowers that.
- **What is missing?** HTTPS (no domain), alert e-mail not subscribed, forgot-password is weak, the load test was limited.
