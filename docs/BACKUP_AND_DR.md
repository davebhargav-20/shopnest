# Backup and disaster recovery — ShopNest

## What data exists and how it is protected
| Data | Where | Protection | Status |
|---|---|---|---|
| Database (users, products, orders) | RDS `shopnest-db` (encrypted gp3, private) | Automated backups 7 days (point-in-time), manual snapshot `shopnest-manual-pre-demo`, deletion protection | Verified |
| Nightly dump + uploads | S3 `shopnest-backups-891377286919-use1` (SSE, versioned, public-blocked, TLS-only, 30-day expiry) | `backup.sh` cron 02:30 IST | **One manual run verified** (db 5.4 KB, uploads 3 KB); the cron schedule itself was not observed firing |
| Off-account copy | `C:\Users\hp\shopnest-backups\` on the owner's PC | manual copy | Verified (copied 2026-11-25) |
| Product images | EBS `/opt/shopnest/uploads` | in the uploads tarball only | upload persistence across a container restart **not tested** |
| Code | GitHub | git history | Verified |
| Infrastructure | `infra/learnerlab/*.py` scripts (repeatable) | git | Implemented |

## Restore rehearsal (2026-11-25)
- Method: restore snapshot `shopnest-manual-pre-demo` into a temporary instance `shopnest-restore-test` (db.t3.micro, private, same SG).
- **Measured RTO: 7.3 minutes** (from creating the restore to the instance being `available`). Verified.
- **Row-count comparison with production: NOT done.** The comparison step failed on a shell-quoting bug in my script, and AWS credentials then expired. The corrected script is `infra/learnerlab/06_compare_and_cleanup.py`. The temporary copy must be deleted (it bills about USD 0.017/hour).
- RPO: point-in-time restore is documented by AWS as ~5 minutes (not measured here); restoring the nightly dump loses up to 24 hours; the manual snapshot loses everything after it.

## Procedures
| Situation | Action |
|---|---|
| Server down | Start/reboot the EC2 instance; containers restart by themselves; check `/health.php`. If the instance is lost, re-run `infra/learnerlab/03_compute.py` (same RDS, same SSM password) — about 6 minutes |
| Bad deploy | Automatic rollback to the previous image (verified, ~2 min). Manual: `AUTO_DEPLOY=false`, then fix and push |
| DB corruption / bad data | RDS point-in-time restore to a new instance, compare, then repoint `DB_HOST` in `/opt/shopnest/.env` and `docker compose up -d --force-recreate app` |
| Accidental deletion of RDS | Deletion protection is on; restore from the manual snapshot |
| Lab reset / account loss | Everything in AWS may be wiped. Re-create with the scripts, load `db-*.sql.gz` from the PC copy (`zcat db.sql.gz | mysql ... ecomdb`), unpack `uploads-*.tgz` into `/opt/shopnest/uploads` |
