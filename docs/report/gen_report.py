import pathlib
from lib import *

OUT = pathlib.Path(__file__).parent
P = "[STUDENT NAME(S)] &nbsp;|&nbsp; [ENROLLMENT NO.(S)] &nbsp;|&nbsp; [COURSE CODE] &nbsp;|&nbsp; [UNIVERSITY]"
V, I, D, N = tag("V"), tag("I"), tag("D"), tag("N")

body = f"""
<div class="cover"><h1>ShopNest on AWS</h1>
<p style="font-size:15pt">Cloud Deployment Project Report<br>Cloud generation, services, CI/CD, costing, security and SLA</p>
<p style="margin-top:30mm">{P}</p>
<p>Cloud: Amazon Web Services (AWS Academy Learner Lab), region us-east-1 (N. Virginia)<br>
Live site: http://52.23.26.212/ (HTTP only, demo accounts)<br>
Source code: https://github.com/davebhargav-20/shopnest (branch <code>aws/cloud-deploy</code>)<br>Date: 26 November 2026</p>
<p class="note" style="margin:30mm 20mm 0 20mm">Every statement is tagged: {V} {I} {D} {N}. Console screenshots show the AWS account ID and user name, as required.</p></div>

<h1>Summary</h1>
<p>ShopNest, a multi-vendor e-commerce website (PHP 8 + MySQL), was hosted on AWS. A Docker container behind a Caddy proxy runs on an EC2 instance and talks over TLS to a private, encrypted RDS MySQL database. Backups go to an encrypted S3 bucket, logs and alarms go to CloudWatch, and a Lambda stops the resources every night to protect the lab credit. A GitHub Actions pipeline tests, builds and scans every commit, and the server deploys a commit only after CI has passed for exactly that commit, with automatic rollback.</p>
{table(["Requirement","Result"],[
 ["Web application hosted online and attached to the cloud", V+" http://52.23.26.212/ answers; <code>/health.php</code> reports db up"],
 ["Cloud generation / provisioning", V+" repeatable boto3 scripts in <code>infra/learnerlab/</code> (Terraform kept as design only)"],
 ["Cloud service usage", V+" EC2, EBS, VPC/SG, RDS, S3, SSM, CloudWatch, SNS, Lambda, EventBridge, CloudTrail"],
 ["CI/CD", V+" three live proofs: auto-deploy, failed CI refused, unhealthy release rolled back"],
 ["Costing", V+" estimate about USD 28/month if run 24/7; real spend not yet known"],
 ["Security", V+" network, TLS to DB, encryption. <b>Gaps:</b> HTTP only, weak forgot-password, no least-privilege IAM"],
 ["SLA", V+" separate PDF; no long-term uptime data exists yet"]])}
<div class="note"><b>Honest limits.</b> The load test was inconclusive, the restored database copy was not compared row by row, no e-mail is subscribed to the alert topic, HTTPS was not possible (no domain), and the AWS credentials expired before final clean-up.</div>

<h1>1. Project overview</h1>
<p>ShopNest is a multi-vendor marketplace with three roles. <b>Customers</b> browse, search, filter and sort products, keep a cart (a guest cart survives login), use a wishlist, check out with Cash on Delivery or a demo online payment, track orders (Pending, Confirmed, Shipped, Delivered), cancel (stock is restored) and review products after delivery. <b>Sellers</b> register a shop that the admin must approve, manage products with image upload, receive orders, update item status and view a sales report. The <b>admin</b> approves, rejects or blocks sellers, manages categories, products, orders, customers and enquiries, edits the About and Contact pages and runs reports.</p>
{table(["Layer","Technology"],[["Application","PHP 8.3, mysqli with prepared statements, bcrypt password hashes, CSRF tokens, validated image uploads (JPG/PNG/GIF/WEBP up to 2 MB)"],["Database","MySQL (RDS 8.4.11), 11 tables, demo data"],["Front end","HTML, CSS, JavaScript, Font Awesome, responsive dark UI"],["Packaging","Docker (php:8.3-apache), Docker Compose, Caddy 2 reverse proxy"],["Cloud","AWS us-east-1; CI on GitHub Actions"]])}
<h2>2. Application workflow</h2>
{fig_svg(flow, "Role workflows. All roles share one database; one order can contain items from several sellers.")}
{figure("L3-login.png","Live login page on AWS. One login serves all three roles, chosen with the radio buttons. Captured from http://52.23.26.212/login.php.", "10.5cm")}

<h1>3. Architecture</h1>
{fig_svg(arch, "Final architecture. Only port 80 is public; the database is reachable only from the web security group; operations go through SSM, so no SSH port is open.")}
<h2>Design rationale</h2>
<p>One small EC2 instance keeps cost low and is enough for a class demo. The database is a managed RDS instance so that backups, encryption and patching are handled by AWS. The instance is administered through Systems Manager, so SSH is closed. The application image is built on the server from a commit that already passed CI, so no registry credential and no AWS secret are stored in GitHub.</p>
<h2>Services deliberately not used</h2>
{table(["Service","Why not"],[["NAT gateway (about USD 33/month), ALB (about USD 16/month)","not needed for one instance; cost"],["Multi-AZ RDS","doubles database cost; availability target is class-demo level"],["WAF, Auto Scaling, CloudFront","cost; designed only"],["Route 53 / ACM / HTTPS","no domain available"],["IAM roles / OIDC / AWS Budgets","not allowed in AWS Academy Learner Lab"],["ECR registry","replaced by building on the server; kept as design in <code>infra/*.tf</code>"]])}

<h1>4. Cloud generation (provisioning)</h1>
<p>The account is an <b>AWS Academy Learner Lab</b> (role <code>voclabs</code>): region <code>ap-south-1</code> (used by the original Terraform) is refused, and IAM roles and OIDC providers cannot be created. The original Terraform therefore could not be applied. The infrastructure was created with small, repeatable boto3 scripts that reuse what already existed, and nothing was deleted.</p>
{table(["Script","Creates / changes","Status"],[
 ["<code>01_foundation.py</code>","S3 backup bucket (SSE, versioning, public-access block, TLS-only, 30-day lifecycle), SNS topic, 3 log groups, SSM SecureString for the DB password, starts the existing RDS",V],
 ["<code>02_rds_harden.py</code>","Existing RDS: password from SSM, TLS-only parameter group, deletion protection, tags, log exports (data untouched)",V],
 ["<code>03_compute.py</code>","EC2 t3.micro (AL2023, encrypted gp3, IMDSv2), Elastic IP, bootstrap of Docker and the deploy timer",V],
 ["<code>04_ops.py</code>","Closes SSH, 5 CloudWatch alarms, <code>KeepAliveUntil</code> tags, Lambda auto-stop, EventBridge schedule",V],
 ["<code>05/06 restore scripts</code>","Disaster-recovery rehearsal with a temporary RDS copy",I]])}
<p>All resources carry the tag <code>Project=ShopNest</code>. The scripts are idempotent and read AWS credentials only from environment variables. Existing resources that were reused: RDS <code>shopnest-db</code> (MySQL 8.4, created earlier by a team member) and security groups <code>shopnest-web</code> and <code>shopnest-db</code>.</p>
<div class="note">Issues found and fixed on the way: the original CI build job could never start because <code>trivy-action@0.28.0</code> no longer exists; Windows git converted <code>entrypoint.sh</code> to CRLF line endings, which crashed the container (fixed with <code>.gitattributes</code>).</div>

<h1>5. AWS services used</h1>
{table(["Service","Role in ShopNest","Approx. cost/month (USD)"],[
 ["EC2 t3.micro","runs Docker (Caddy + app)","7.59"],["EBS gp3 20 GB (encrypted)","OS, uploads","1.60"],["VPC / security groups","network isolation (default VPC)","free"],
 ["RDS MySQL db.t3.micro","database, private, encrypted, TLS-only","12.41 + 2.30 storage"],["Elastic IP","stable public address","3.65"],["S3","encrypted backups","about 0.05"],
 ["Systems Manager","Run Command (no SSH), Parameter Store secret","free"],["CloudWatch + SNS","logs, 5 alarms, alert topic","0.50"],["Lambda + EventBridge","nightly auto-stop","about 0"],["CloudTrail (event history)","audit trail of API calls","free"]])}
<p>Total about USD 28/month if everything runs 24/7 (list-price estimate, see section 8).</p>
{figure("01-ec2-instance.png","<b>EC2.</b> Instance <code>shopnest-web</code> (t3.micro) is running with Elastic IP 52.23.26.212, IAM role LabRole and IMDSv2 required. It hosts the Docker containers. "+V)}
{figure("02-ec2-security-group.png","<b>Security group <code>shopnest-web</code>.</b> Only HTTP and HTTPS rules remain; the SSH rule that existed was removed. Systems Manager is used for administration instead. "+V)}
{figure("03-ebs-volume.png","<b>EBS.</b> A 20 GB gp3 volume holds the operating system, Docker images and the uploads folder. It is created encrypted. "+V)}
{figure("04-elastic-ip.png","<b>Elastic IP.</b> <code>shopnest-eip</code> keeps the public address stable across stop and start. It costs about USD 0.005 per hour, even when the instance is stopped. "+V)}
{figure("05-rds-instance.png","<b>RDS.</b> <code>shopnest-db</code>, MySQL community edition, db.t3.micro in us-east-1a, status Available. The database is not publicly accessible and is only reachable from the web security group. "+V)}
{figure("06-rds-snapshots.png","<b>RDS snapshots.</b> The manual snapshot <code>shopnest-manual-pre-demo</code> (MySQL 8.4.11) was taken before risky work and was used for the restore rehearsal. Automated backups are kept for 7 days. "+V)}
{figure("07-vpc-subnets.png","<b>VPC.</b> The default VPC with six subnets, one per availability zone, hosts both the instance and the database subnet group. No NAT gateway is used. "+V)}
{figure("08-s3-bucket-properties.png","<b>S3 properties.</b> Bucket <code>shopnest-backups-891377286919-use1</code> has versioning enabled and a Project tag; default encryption is SSE-S3. "+V)}
{figure("09-s3-bucket-permissions.png","<b>S3 permissions.</b> Block Public Access is on, and the bucket policy denies any request that does not use TLS (<code>aws:SecureTransport = false</code>). "+V)}
{figure("10-s3-backup-objects.png","<b>S3 objects.</b> The database dump produced by <code>backup.sh</code> (about 5 KB, gzip) in the <code>db/</code> prefix. A copy was also saved on the owner's PC. "+V)}
{figure("11-ssm-managed-nodes.png","<b>Systems Manager.</b> The instance is a managed node with ping status Online, which allows deployments and checks through Run Command without any open SSH port. "+V)}
{figure("12-ssm-parameter-store.png","<b>Parameter Store.</b> <code>/shopnest/db_password</code> is a SecureString; only its name is shown. The EC2 instance reads it at boot to build the container environment. "+V)}
{figure("13-cloudwatch-alarms.png","<b>CloudWatch alarms.</b> Five alarms (EC2 CPU and status check, RDS CPU, free storage, connections) are in state OK and have an action to the SNS topic. "+V)}
{figure("14-cloudwatch-log-group-app.png","<b>CloudWatch Logs.</b> Log group <code>/shopnest/app</code> with stream <code>app</code> receives the container logs through the awslogs driver; retention is two weeks. "+V)}
{figure("15-sns-topic.png","<b>SNS.</b> Topic <code>shopnest-alerts</code> exists, but <b>no subscription</b> is configured yet, so no alert e-mail can be delivered. Notification test: "+N)}
{figure("16-lambda-autostop.png","<b>Lambda.</b> <code>shopnest-autostop</code> (Python, 256 MB) stops tagged EC2 and RDS resources unless a <code>KeepAliveUntil</code> tag protects them. A dry run listed both as protected "+V+"; actual stopping: "+I)}
{figure("17-eventbridge-rule.png","<b>EventBridge.</b> Rule <code>shopnest-autostop-nightly</code> runs at 18:30 UTC (midnight IST) and calls the Lambda. The next trigger dates are listed. "+V)}
{figure("18-cloudtrail-event-history.png","<b>CloudTrail.</b> Event history (always on) records the API calls made during the build, such as SendCommand and CreateLogStream. No organisation trail was created. "+V)}

<h1>6. Dockerization</h1>
<p>The image is based on <code>php:8.3-apache</code> with mysqli, the Amazon RDS CA bundle, production <code>php.ini</code> (errors logged, not displayed) and Apache security headers. A <code>HEALTHCHECK</code> calls <code>/health.php</code>. The entrypoint imports <code>ecomdb.sql</code> only when the table <code>tbladmin</code> does not exist, so it never overwrites data.</p>
{table(["Check","Result"],[["Build and run locally with Docker Compose","healthy; image 816 MB "+V],["68 functional checks (customer, seller, admin)","68 PASS, 0 FAILS "+V],["Apache workers run as <code>www-data</code>","yes (master process is root, as usual) "+V],["No infra, tests, .git or .env inside the image","confirmed "+V],["No secret in image history","confirmed "+V],["Uploads on a host folder (EBS), PHP disabled in <code>images/</code>","configured "+I+" (persistence across restart not tested)"],["Production limits and restart policy","512 MB app, 128 MB proxy, <code>restart: unless-stopped</code> "+V]])}

<h1>7. CI/CD pipeline</h1>
{fig_svg(cicd, "Pipeline. GitHub Actions only tests and scans; the EC2 server polls GitHub, checks that CI succeeded for the exact commit, builds, swaps and health-checks, and rolls back on failure.")}
<p><b>Why pull-based.</b> The Learner Lab cannot create an OIDC provider, and its credentials expire every few hours, so GitHub cannot deploy into AWS. The server pulls instead, and GitHub stores no AWS secret.</p>
{table(["Stage","What it does"],[["Lint","PHP syntax check, gitleaks secret scan, Terraform fmt/validate"],["Test","MariaDB service, <code>tests/e2e.py</code> (68 checks)"],["Build","hadolint, docker build, Trivy 0.76.2 (fail on CRITICAL), container smoke test (healthy, www-data, no infra in image)"],["Gate","server asks GitHub: run for this commit is <code>completed:success</code>? otherwise skip (fail closed)"],["Deploy","build on the server, swap containers, restart proxy, health check must show the new version"],["Rollback","on failed health check restore the previous image and remember the bad commit"]])}
<h2>Live evidence (26 Nov 2026, server log)</h2>
{table(["Proof","Observed"],[
 ["(a) green CI deploys automatically","version changed <code>1e6e07449b44</code> to <code>aa34984d808c</code> "+V],
 ["(b) failing CI is not deployed","broken commit <code>0aeeb73</code>: <code>SKIP ... completed:failure</code>; live version unchanged "+V],
 ["(c) unhealthy release is rolled back","commit <code>37706c3</code> (fails only in production): <code>UNHEALTHY</code> 18:20:07, <code>ROLLED BACK (healthy)</code> 18:20:14 "+V],
 ["Freeze with <code>AUTO_DEPLOY=false</code>",I]])}
<div class="note"><b>Limits.</b> The deploy branch is a server setting (<code>aws/cloud-deploy</code> until the pull request is merged, then <code>main</code>). The image is built on a 1 GB instance, and Trivy scans the CI build of the same commit rather than the server build. Unauthenticated GitHub API calls are limited to 60 per hour per IP. About two minutes of unhealthy traffic are possible before a rollback completes. <b>No GitHub Actions screenshot was captured</b>; the evidence is the public run history at github.com/davebhargav-20/shopnest/actions and the server log in <code>docs/evidence/</code>.</div>

<h1>8. Costing</h1>
<p>Region us-east-1, USD, 730 hours per month. Prices are <b>list-price estimates from AWS documentation, not re-verified</b> against the price-list API.</p>
{table(["Resource","Unit price","Per month"],[["EC2 t3.micro","$0.0104/h","7.59"],["EBS gp3 20 GB","$0.08/GB-month","1.60"],["RDS db.t3.micro single-AZ","$0.017/h","12.41"],["RDS storage 20 GB","$0.115/GB-month","2.30"],["Public IPv4 / Elastic IP","$0.005/h","3.65"],["CloudWatch alarms x5","$0.10 each","0.50"],["S3, SNS, Lambda, SSM, EventBridge","free tier / pennies","0.05"],["<b>Total 24/7</b>","<b>about $0.037/h</b>","<b>about 28.1 (0.93 per day)</b>"]])}
{table(["Scenario","Estimate/month"],[["24/7","about $28"],["Business hours (10 h x 22 days)","about $14 to $18"],["Demo weekend (2 days)","about $1.9 plus fixed items"],["Everything stopped (EBS, RDS storage, snapshots, Elastic IP remain)","about $7 to $8"],["Forgotten running","$0.93 per day; RDS stopped for 7 days restarts automatically"]])}
<p>Rejected options: NAT gateway (about $33), ALB (about $16 plus usage), Multi-AZ RDS (double), WAF. With a USD 50 credit, 24/7 operation lasts about 53 days; the nightly auto-stop extends it. <b>Actual consumption:</b> Cost Explorer showed about USD 0.00 for the earlier period and lags 24 hours, so today's real spend is unknown. Record it: Credit remaining on ____ : $____.</p>

<h1>9. Cloud security</h1>
{table(["Control","Status","Evidence / gap"],[
 ["Only port 80 public; SSH closed; DB port not exposed",V,"port probe from the internet: 22, 443, 3306 and the RDS endpoint unreachable"],
 ["RDS private, encrypted, 7-day backups, deletion protection",V,"console and API"],
 ["TLS enforced to RDS",V,"plain connection refused (ERROR 3159); TLS 1.3 accepted"],
 ["DB password only in SSM Parameter Store",V,"also in <code>/opt/shopnest/.env</code> (mode 600) on the server"],
 ["EC2 IMDSv2, encrypted volume, no key pair",V,"OS patching is manual"],
 ["S3 hardening (SSE, versioning, public block, TLS-only, lifecycle)",V,"figures on S3"],
 ["CI scans: gitleaks, hadolint, Trivy",V,"CRITICAL only"],
 ["Security headers, HttpOnly + SameSite cookies",V,"<code>Secure</code> flag needs HTTPS"],
 ["Alarms to SNS e-mail",N,"no subscriber, so no notification was tested"],
 ["Least-privilege IAM",N,"Learner Lab allows only LabRole"],
 ["<b>HTTPS</b>",N,"<b>no domain: passwords cross the internet unencrypted; use demo accounts only</b>"],
 ["Forgot-password flow","known weakness","resets any account, including admin, with e-mail + mobile only"],
 ["Public demo credentials in README","open","change the admin password before real use"]])}

<h1 class="first" style="margin-top:14px">10. Backup and disaster recovery</h1>
{table(["Item","Result"],[["Automated RDS backups","7 days, point-in-time "+V],["Manual snapshot","<code>shopnest-manual-pre-demo</code> "+V],["Backup to S3 (database dump + uploads)","one run verified; nightly cron not observed "+V],["Copy outside AWS","saved on the owner's PC "+V],["Restore into a new instance","available after <b>7.3 minutes</b> (measured RTO) "+V],["Row counts compared with production","script failed, credentials expired "+N],["RPO","point-in-time about 5 minutes (AWS documentation, not measured); nightly dump up to 24 h"]])}
<p>Procedures for server loss, bad deploy, database corruption and lab reset are in <code>docs/BACKUP_AND_DR.md</code>. The temporary restore instance still has to be deleted.</p>

<h1 class="first" style="margin-top:14px">11. SLA</h1>
<p>The full SLA is in <b>ShopNest_SLA.pdf</b>. Summary: AWS documents 99.5 % for a single EC2 instance and for single-AZ RDS (credits only, and not available in the Learner Lab). ShopNest sets targets only for demo hours; the measured results are a restore time of 7.3 minutes, a rollback within about 2 minutes of a bad release and a fresh server serving traffic about 6 minutes after launch. <b>No long-term uptime has been measured.</b></p>

<h1>12. Testing and performance</h1>
{table(["Test","Result","Status"],[["Local e2e","68 PASS, 0 FAILS",V],["CI on GitHub","green on 3 commits, red on the deliberate failure",V],["Live health, ports, TLS","as in section 9",V],["CD proofs a, b, c","all observed",V],["Auto-stop Lambda","dry run only; never stopped anything",I]])}
<h2>Load test (throw-away copy, load generated on the same t3.micro, 1,500 requests per run)</h2>
{table(["Users","Page","Req/s","p50","p95","p99","Failed","CPU"],[["50","/index.php","231","203 ms","327 ms","441 ms","0","44 %"],["100","/product-details.php?id=1","371","268 ms","303 ms","319 ms","0","28 %"],["100","/index.php","49","229 ms","10,024 ms","19,637 ms","83","7 %"],["other runs","/shop.php etc.","no data","","","","",""]])}
<div class="note">The load test is <b>inconclusive</b>: several runs produced no usable numbers and the 100-user homepage run failed 83 requests at low CPU, which points to a limit other than CPU (burst credits, memory or Apache workers) that was not investigated. Login, cart and checkout flows, network latency and RDS were not part of it. <b>Not tested:</b> HTTPS, upload persistence after restart, SNS e-mail, cron backup, auto-stop in live mode, penetration testing, long-term uptime.</div>

<h1>13. Limitations and future work</h1>
<ul><li>HTTP only. Add a domain, point an A record to the Elastic IP, and set <code>SITE_ADDRESS</code> to the domain so Caddy obtains a certificate.</li>
<li>Replace the e-mail + mobile forgot-password flow with a one-time e-mailed token.</li><li>Subscribe an e-mail to the alert topic and test it.</li>
<li>Single instance and single-AZ database: a failure means minutes of downtime. Multi-AZ and an ALB are the upgrade path.</li>
<li>Move product images to S3 and re-run the load test with full customer flows from outside AWS.</li>
<li>Learner Lab limits: resources may be wiped and credentials are temporary. Keep the off-account backup.</li>
<li>Complete the pending row-count comparison and delete the temporary restore instance.</li></ul>
<h2>14. Conclusion</h2>
<p>ShopNest runs on AWS with a private, encrypted, TLS-only database, a hardened network, backups, monitoring, cost control and a CI/CD pipeline whose safety properties were demonstrated live. Several items are explicitly incomplete (HTTPS, alert e-mail, load test, restore data comparison), and they are listed rather than hidden.</p>

<h1>Appendix A. Glossary, reproduction and viva questions</h1>
{table(["Term","Meaning"],[["EC2","virtual server"],["RDS","managed database"],["SSM","Systems Manager: run commands and keep secrets without SSH"],["CI/CD","automatic testing and deployment"],["RTO / RPO","time to recover / data that can be lost"],["Pull-based CD","the server fetches and deploys, instead of being pushed to"]])}
<p><b>Reproduce:</b> export Learner Lab credentials, then run <code>infra/learnerlab/01_foundation.py</code> to <code>04_ops.py</code> in order; <code>03_compute.py aws/cloud-deploy</code> launches the server. Local: <code>docker compose up -d --build</code>, then <code>python tests/e2e.py</code>. The runbook and demo guide are in <code>docs/</code>.</p>
{table(["Likely question","Short answer"],[["Why not Terraform?","the lab forbids IAM/OIDC and ap-south-1; Terraform is kept as the design"],["Why pull-based CD?","credentials expire in hours; no AWS secret stored in GitHub"],["Is it secure?","mostly; the main gaps are HTTP only and the weak forgot-password flow"],["What if the server dies?","re-run the scripts (about 6 minutes); data is in RDS and the backups"],["What did not work?","load test inconclusive; restored-data comparison not completed"]])}
"""
(OUT / "report.html").write_text(page("ShopNest Cloud Report", body), encoding="utf-8")

sla = f"""
<h1 class="first" style="font-size:18pt">ShopNest on AWS: Service Level Agreement (SLA)</h1>
<p style="font-size:9pt">{P} &nbsp;|&nbsp; 26 November 2026 &nbsp;|&nbsp; Region us-east-1 &nbsp;|&nbsp; http://52.23.26.212/</p>
<h2>1. What is an SLA?</h2>
<p>An SLA says how available a service should be, how fast it recovers, and what is excluded. AWS has its own SLAs for its services; ShopNest sets simpler targets on top of them for a class-project demo.</p>
<h2>2. What AWS promises (AWS documentation, not re-verified here)</h2>
{table(["Service","AWS commitment","Remedy"],[["EC2 single instance","99.5 % monthly","service credits"],["EC2 region-level","99.99 %","service credits"],["RDS single-AZ","99.5 %","service credits"],["RDS Multi-AZ (not used)","99.95 %","service credits"],["Lambda","99.95 %","service credits"]])}
<p style="font-size:9pt">Credits are not available in the AWS Academy Learner Lab.</p>
<h2>3. ShopNest targets and measured results</h2>
{table(["Item","Target","Measured (26 Nov 2026)","Status"],[
 ["Availability in demo/exam hours","99 % (about 7.3 h/month, 36 s per hour of downtime)","<b>no long-term data yet</b>",N],
 ["New server serving traffic after launch","under 10 minutes","about 6 minutes (launch 17:57 UTC, healthy by 18:02 UTC)",V],
 ["Bad release recovery (rollback)","under 5 minutes","about 2 minutes detection + 7 seconds rollback",V],
 ["Database restore time (RTO)","under 15 minutes","7.3 minutes (snapshot to available)",V],
 ["Data loss (RPO)","point-in-time about 5 min; nightly dump 24 h","not measured (AWS documentation)",D],
 ["Load capacity","50 simultaneous visitors","inconclusive test; 231 req/s at 50 users on the home page, 0 failures",I]])}
<h2>4. How the targets are met</h2>
{table(["Mechanism","Purpose"],[["Container restart policy, health check","recover from application crashes"],["Automatic rollback in the deploy script","bad release recovery (verified)"],["RDS automated backups + manual snapshot + S3 dumps + PC copy","data protection and restore"],["CloudWatch alarms to SNS","detection (no e-mail subscribed yet, so not yet effective)"],["Elastic IP + SSM + scripted re-creation","fast rebuild of the server"],["Nightly auto-stop unless <code>KeepAliveUntil</code>","cost control; demo protected until 2 Dec 2026"]])}
<h2>5. Exclusions</h2>
<p>AWS Academy lab expiry or reset; AWS regional outage; the user's own network; resources stopped by the auto-stop after the <code>KeepAliveUntil</code> date; planned maintenance and demos with deploys frozen; misuse of tags or credentials; HTTP-only transport (no encryption in transit between browser and site).</p>
<p style="font-size:9pt"><b>Honesty note:</b> no uptime percentage is claimed. Availability has not been measured over time, because the system had run for only hours at the time of writing.</p>
"""
(OUT / "sla.html").write_text(page("ShopNest SLA", sla), encoding="utf-8")
print("html written; figures:", fig_n[0])
