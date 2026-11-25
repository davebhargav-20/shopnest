# Testing — ShopNest on AWS

| Test | Result | Status |
|---|---|---|
| Local Docker build + run, health `healthy` | OK; image 816 MB; Apache workers run as `www-data`; no infra/tests/.git/.env in image | Verified (`evidence/local-docker-checks.txt`) |
| Functional e2e (customer, seller, admin) locally | **68 PASS, 0 FAILS** | Verified (`evidence/local-e2e.txt`) |
| CI on GitHub (lint, e2e, hadolint, build, Trivy, container smoke) | green on commits `1e6e074`, `aa34984`, `73bf5b0`; red on the broken commit by design | Verified |
| Live site `/health.php` | `status ok, db up` | Verified |
| Port exposure from the internet | only 80 open | Verified |
| RDS TLS enforcement | plain connection refused; TLS 1.3 accepted | Verified |
| CD: auto-deploy / refuse failing CI / auto-rollback | all three observed live | Verified |
| Backup to S3 + copy to PC | ok | Verified |
| Restore into a new instance | available in 7.3 min | Verified (row counts **not** compared) |
| Auto-stop Lambda | dry run correct (both resources protected); live mode enabled; **never actually stopped anything** | Partly verified |

## Load test (throw-away copy, load generated on the same t3.micro with ApacheBench; 1,500 requests per run)
| Concurrency | Page | Req/s | p50 | p95 | p99 | Failed | Avg CPU busy |
|---|---|---|---|---|---|---|---|
| 50 | `/index.php` | 231 | 203 ms | 327 ms | 441 ms | 0 | 44 % |
| 100 | `/product-details.php?id=1` | 371 | 268 ms | 303 ms | 319 ms | 0 | 28 % |
| 100 | `/index.php` | 49 | 229 ms | 10,024 ms | 19,637 ms | 83 non-2xx | 7 % |
| 50 and 100 | `/shop.php`; 50 `/product-details` | **no data** (ApacheBench produced no output) | | | | | |

**Interpretation (honest):** the result is **inconclusive**. The failures at 100 users with low CPU suggest a limit other than CPU (t3.micro burst credits, the 512 MB container limit or Apache workers), but this was not investigated. Four of six runs gave no usable numbers. The test does not include login, cart or checkout flows, network latency or the RDS database.

## NOT tested
Image-upload persistence across a restart · HTTPS · SNS e-mail delivery (no subscriber) · auto-stop actually stopping · cron backup firing · restored-data row counts · load test with full customer flows · penetration test · long-term uptime.
