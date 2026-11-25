# Demo guide (about 10 minutes)

Before: make sure EC2 + RDS are running (`KeepAliveUntil` covers the day), then freeze deploys: `AUTO_DEPLOY=false` (RUNBOOK).
1. **Live site** http://52.23.26.212/ — three browsers: customer `rahul@gmail.com / user@123`, seller `ravi@shopnest.com / seller@123`, admin `admin / admin@123`. Customer places a COD order; seller updates its status; admin sees it.
2. **Health:** `/health.php` — shows the deployed commit and DB status.
3. **GitHub Actions:** lint → test → build/scan; show the green run and the red run (deliberate broken commit).
4. **Server deploy log** (`docs/evidence/cd-proof-abc-deploy-history.txt`): BUILD/DEPLOYED, SKIP when CI failed, UNHEALTHY → ROLLED BACK.
5. **AWS console tour** (one sentence each): EC2 (runs Docker), Security group (only port 80), RDS (private, encrypted, TLS-only), S3 (backups), SSM (no SSH), CloudWatch (alarms, logs), Lambda + EventBridge (nightly auto-stop), CloudTrail (audit).
6. **Cost:** ≈ $0.93/day if left running; rejected NAT/ALB/Multi-AZ because of price.
7. **SLA:** `ShopNest_SLA.pdf`; say clearly there is no long-term uptime data.

## Likely questions
- *Why not Terraform?* The lab forbids IAM/OIDC and ap-south-1; the Terraform stays as the design for a normal account.
- *Why pull-based CD?* Credentials expire in hours, so no AWS secret is stored in GitHub; the server verifies CI itself.
- *Is it secure?* Mostly; the big gap is **HTTP only** (no domain) and the weak forgot-password flow (SECURITY.md).
- *What if the server dies?* Re-run the scripts (~6 min), data from RDS or the S3/PC backups.
- *What did not work?* The load test was inconclusive, and the row-count check on the restored DB was not completed.
