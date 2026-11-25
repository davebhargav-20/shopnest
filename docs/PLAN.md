# ShopNest on AWS — Phase 2 Plan (awaiting owner approval)

Decisions taken: boto3 provisioning reusing existing resources · pull-based CD · HTTP only (no domain) · Elastic IP for the demo period · region **us-east-1** · Learner Lab (`LabInstanceProfile`, no custom IAM).

## 1. Architecture

```
Users ──HTTP :80──► EC2 t3.micro (Docker: Caddy → PHP-Apache app) ──3306 TLS──► RDS MySQL 8.4 (existing shopnest-db, private)
                       │ uploads → /opt/shopnest/uploads (EBS gp3, encrypted)
                       ├ CloudWatch Logs (awslogs) + alarms ──► SNS ──► e-mail
                       ├ SSM (Run Command / Session Manager, no SSH) · SSM Parameter Store (DB password, SecureString)
                       ├ S3 backup bucket (SSE, versioned, public-blocked, TLS-only, lifecycle)
                       └ EventBridge → Lambda "auto-stop" (idle/nightly cap, KeepAliveUntil tag)  [uses LabRole]
CI/CD: GitHub Actions (lint → test → build → Trivy → push image to GHCR)
       EC2 systemd timer: poll GitHub → CI passed for that SHA? → pull → health check → swap → auto-rollback
```

## 2. Services, names, sizes (all tagged `Project=ShopNest`)

| # | Service | Resource | Reuse/new | Why |
|---|---|---|---|---|
| 1 | EC2 + EBS | `shopnest-web`, AL2023, t3.micro, 20 GB gp3 encrypted, IMDSv2, hop limit 1 | new | Runs the containers |
| 2 | VPC/SG | default VPC; SG `shopnest-web` (80/443; **close port 22**, use SSM) and `shopnest-db` | reuse, 1 rule change | Network isolation |
| 3 | RDS | `shopnest-db` MySQL 8.4.11 db.t3.micro, private, encrypted, 7-day backups | **reuse** (start it; enable deletion protection, TLS-only param group, log exports, tags) | Database |
| 4 | IAM | `LabInstanceProfile` / `LabRole` | reuse | Lab allows nothing else |
| 5 | SSM | Run Command, Parameter Store `/shopnest/db_password` SecureString | new | No open SSH, no secrets in files |
| 6 | Registry | **GHCR** (ECR kept as optional design) | new | Pull-based CD needs no AWS keys in GitHub |
| 7 | S3 | `shopnest-backups-891377286919-use1` | new | Real nightly DB dump + uploads |
| 8 | CloudWatch + SNS | log groups `/shopnest/app`, `/shopnest/proxy`; alarms EC2 CPU, EC2 status, RDS CPU, RDS free storage, health check; topic `shopnest-alerts` | new | Monitoring + e-mail alerts (you must click the confirm link) |
| 9 | Elastic IP | 1 EIP for the demo | new | Stable URL |
| 10 | Route 53/ACM | — | **not used** (no domain) | designed only |
| 11 | Lambda + EventBridge | `shopnest-autostop`, Python 256 MB, DRY_RUN first | new | Protects the USD 50 credit |
| 12 | Budgets | — | **not possible** in Learner Lab; use Cost Explorer (works) | |
| 13 | CloudTrail | event history only | existing | audit trail |

Not used (documented): NAT gateway, ALB, Multi-AZ, WAF, Auto Scaling — cost vs. benefit for a class demo.

## 3. Cost estimate (us-east-1, list price, **estimate; from AWS documentation, not re-verified against the price-list API**)

| Resource | Unit price | Per hour | Per month (730 h) |
|---|---|---|---|
| EC2 t3.micro | $0.0104/h | 0.0104 | 7.59 |
| EBS gp3 20 GB | $0.08/GB-mo | — | 1.60 |
| RDS db.t3.micro MySQL | $0.017/h | 0.017 | 12.41 |
| RDS storage gp3 20 GB | $0.115/GB-mo | — | 2.30 |
| Public IPv4 / Elastic IP | $0.005/h | 0.005 | 3.65 |
| CloudWatch alarms ×5 | $0.10/alarm | — | 0.50 |
| S3 (≤1 GB), SNS, Lambda, SSM | negligible / free tier | — | ≈0.05 |
| **Total, 24/7** | | **≈0.037** | **≈28.1** (≈ $0.93/day) |

Scenarios: 24/7 ≈ $28/mo · business hours (10 h × 22 d) ≈ $10 + fixed ≈ $14 · everything stopped ≈ $5/mo (EBS, RDS storage, EIP) · RDS auto-restarts after 7 days stopped. With the ~$50 credit, 24/7 lasts ≈ 53 days; auto-stop extends this. **Real spend must be read from Cost Explorer / the Lab credit page** (a line is left for you in `COSTING.md`).

## 4. Order of implementation (after your approval)

1. Fork-safe working copy: clone repo to `c:\xampp\htdocs\shopnest-aws`, branch `aws/cloud-deploy`; copy `docs/`.
2. Local Docker build + e2e (needs Docker Desktop started) → evidence.
3. Start RDS; check it contains the schema/data (read-only); manual snapshot; tighten SG port 22.
4. SSM SecureString, S3 bucket, SNS, log groups.
5. EC2 + EIP (user-data installs Docker, writes `.env` from SSM, installs deploy timer).
6. App up; verify `/health.php`, TLS to RDS, upload persistence across restart.
7. CI changed for GHCR; pull-based deployer; prove deploy / refused-on-CI-fail / rollback.
8. Alarms, Lambda auto-stop (dry run → live), backup run, restore rehearsal, load test on a **copy**.
9. Console screenshots, docs, report PDF, SLA PDF, PR.

## 5. Risks and rollback

| Risk | Mitigation / rollback |
|---|---|
| MySQL 8.4 vs app | Test early; if incompatible, ask before creating a separate 8.0 instance (extra cost) |
| Credentials expire mid-way | Scripts are idempotent; ask for fresh keys |
| Cost overrun | Auto-stop, tags, daily Cost Explorer check |
| Bad deploy | Previous image tag kept; automatic rollback; `AUTO_DEPLOY=false` freeze |
| Anything destructive | Never without your explicit OK, shown first |

## 6. Test plan

Local e2e (68 checks), CI run links, health/TLS/privacy checks (`nmap`-style port test of 3306 from outside), upload persistence, alarm test via SNS publish, backup upload + restore row-count comparison with measured RTO/RPO, load test 50/100 users on a DB copy.
