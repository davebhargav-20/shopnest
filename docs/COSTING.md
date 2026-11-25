# Costing — ShopNest on AWS

Region us-east-1, USD, month = 730 h. **List-price estimates from AWS documentation; not re-verified against the price-list API.**

| Resource | Unit price | Per hour | Per month |
|---|---|---|---|
| EC2 t3.micro (Linux) | $0.0104/h | 0.0104 | 7.59 |
| EBS gp3 20 GB | $0.08/GB-month | | 1.60 |
| RDS db.t3.micro MySQL single-AZ | $0.017/h | 0.017 | 12.41 |
| RDS gp3 storage 20 GB (+ backup within allocated size) | $0.115/GB-month | | 2.30 |
| Public IPv4 (Elastic IP) | $0.005/h | 0.005 | 3.65 |
| CloudWatch alarms ×5 | $0.10/alarm-month | | 0.50 |
| S3 (<1 GB), SNS, Lambda, SSM, EventBridge, log groups | free tier / pennies | | ≈0.05 |
| **Total 24/7** | | **≈0.037** | **≈28.1 (≈ $0.93/day)** |

| Scenario | Estimate/month |
|---|---|
| Running 24/7 | ≈ $28 |
| Business hours only (10 h × 22 days) | ≈ $10 compute + ≈ $8 always-on storage/IP ≈ $14–18 |
| Demo weekend (2 days 24/7) | ≈ $1.9 + fixed |
| Everything stopped (EBS, RDS storage, snapshots, EIP remain) | ≈ $7–8 |
| Forgotten running | $0.93/day; RDS stopped >7 days restarts automatically |

Not used: NAT gateway (~$33), ALB (~$16 + usage), Multi-AZ RDS (×2), WAF (~$5 + rules), Route 53/ACM (no domain).
With a ~USD 50 lab credit, 24/7 lasts ~53 days; the nightly auto-stop extends this.

**Real consumption:** Cost Explorer showed ≈ USD 0.00 for the earlier month; it lags ~24 h, so today's spend is **not yet known**. Record it here: `Credit remaining on ____ : $____` (Learner Lab page → AWS Details, or Billing → Cost Explorer).
