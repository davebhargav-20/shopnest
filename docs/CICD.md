# CI/CD pipeline — ShopNest (Learner Lab design)

## Why pull-based CD
AWS Academy Learner Lab cannot create an OIDC provider or IAM roles, and its credentials expire every few hours, so GitHub cannot deploy *into* AWS safely. Instead the **server pulls**: GitHub holds **no AWS secret at all**.

```
 push ─► GitHub Actions CI ──────────────────────────────┐
        lint (php -l, gitleaks, terraform fmt/validate)   │  result is public:
        test (MariaDB service, tests/e2e.py, 68 checks)   │  workflow run for commit SHA
        build (hadolint, docker build, Trivy CRITICAL,    │
               container smoke test: healthy, www-data)   ▼
 EC2 systemd timer (every 2 min): pull-deploy.sh
   1 latest commit of REPO@BRANCH         2 CI run for THAT sha == completed:success ? (else SKIP, fail-closed)
   3 build image on the server from that commit   4 swap containers, restart proxy
   5 health check (version must equal new tag) ──ok──► DEPLOYED, log line
                                              └─fail─► rollback to previous tag, mark SHA bad, log line
```

## Safety properties — all **Verified (live, 2026-11-25)** — evidence in `docs/evidence/cd-proof-abc-deploy-history.txt`
| Property | Result |
|---|---|
| (a) green CI deploys automatically; `/health.php` version changes | `1e6e07449b44` → `aa34984d808c` (BUILD 18:10:01, DEPLOYED 18:10:10) |
| (b) a commit that fails CI is **not** deployed | PHP syntax error commit `0aeeb7349ae4`: CI failed; server logged `SKIP … completed:failure`; live version unchanged |
| (c) a release that passes CI but is unhealthy in production is rolled back | commit `37706c3ee1f8` (fails only when `DB_SSL=1`): `UNHEALTHY → ROLLING BACK` at 18:20:07, `ROLLED BACK (healthy)` at 18:20:14. Bad SHA is remembered and never retried |
| While CI is still running nothing is deployed | `SKIP … in_progress` lines |
| Every action is recorded | `/opt/shopnest/deploy-history.log` |
| Freeze during demo/exam | `echo AUTO_DEPLOY=false > /opt/shopnest/auto-deploy.conf` — **Implemented, not tested live** |

## Other facts
- Bug found on the way: the original workflow referenced `aquasecurity/trivy-action@0.28.0`, a tag that no longer exists, so the original build job could never start. Trivy now runs from its pinned Docker image (0.76.2).
- Windows git converted `docker/entrypoint.sh` to CRLF and crashed the container; `.gitattributes` now forces LF.
- Terraform is **never** applied from CI.

## Honest limits
- The deploy branch is configured in `/opt/shopnest/auto-deploy.conf`: it is `aws/cloud-deploy` until the PR is merged; after merging set `BRANCH=main`.
- Image is built on the t3.micro (slower than pulling a registry image, but needs no registry credentials). Trivy scans the CI build of the same Dockerfile and commit, not the server's build.
- Unauthenticated GitHub API: 60 requests/hour per IP; the 2-minute poll uses ~60–90/hour when commits are in flight, so a rate-limit delay is possible (fails safe: no deploy).
- Rollback has a window of about 2 minutes in which the new release is serving unhealthy responses (30 × 4 s health checks).
- The old ECR/OIDC design remains in `infra/*.tf` and `deploy/deploy.sh` as a *Designed only* alternative for a normal AWS account.
