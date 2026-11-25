import pathlib
from lib import *

OUT = pathlib.Path(__file__).parent
P = "[STUDENT NAME(S)] &nbsp;|&nbsp; [ENROLLMENT NO.(S)] &nbsp;|&nbsp; [COURSE CODE] &nbsp;|&nbsp; [UNIVERSITY]"


def svc(img, name, desc, shot_caption):
    return f'<div class="svc"><h2>{name}</h2><p>{desc}</p>' + figure(img, shot_caption.replace('Figure: ', ''), "10.3cm") + '</div>'


body = f"""
<style>h1{{page-break-before:auto;margin-top:26px}} h1.pb{{page-break-before:always}} .svc{{page-break-inside:avoid}} h2{{page-break-after:avoid}} h1{{page-break-after:avoid}}</style>
<div class="cover"><h1>ShopNest on AWS</h1>
<p style="font-size:15pt">Hosting a multi-vendor e-commerce website on Amazon Web Services<br>Cloud Computing Project Report</p>
<p style="margin-top:30mm">{P}</p>
<p>Cloud platform: Amazon Web Services (AWS Academy Learner Lab), region US East (N. Virginia)<br>
Live website: http://52.23.26.212/<br>
Source code: https://github.com/davebhargav-20/shopnest<br>
Date: 26 November 2026</p></div>

<h1 class="pb" style="margin-top:0">Contents</h1>
<ol style="line-height:1.4">
<li>Project overview</li><li>Application flow</li><li>System architecture</li><li>AWS services used</li>
<li>Containerisation with Docker</li><li>CI/CD pipeline</li><li>Cost estimate</li><li>Cloud security</li>
<li>Backup and recovery</li><li>Service level agreement (summary)</li><li>Testing and results</li><li>Limitations and future scope</li><li>Conclusion</li></ol>
<p>The aim of the project is to host the ShopNest web application online on a cloud platform and to cover cloud provisioning, use of cloud services, costing, the design of a CI/CD pipeline, cloud security and an SLA.</p>

<h1>1. Project overview</h1>
<p>ShopNest is a multi-vendor online shopping website. Many sellers can sell their products on one site, and customers can buy from several sellers in a single order. The website is written in PHP 8 with a MySQL database and has three types of users, each with its own panel.</p>
{table(["User","What the user can do"],[
 ["Customer","Browse, search, filter and sort products; add items to a cart (a guest cart is kept after login); save a wishlist; check out with Cash on Delivery or a demo online payment; track the order (Pending, Confirmed, Shipped, Delivered); cancel an order (stock is restored); rate and review a product after delivery."],
 ["Seller","Register a shop (the admin must approve it); add, edit and delete products with images; receive orders and update the status of each item; view a sales report between two dates."],
 ["Admin","Approve, reject or block sellers; manage categories and products; see all orders and customers; read enquiries; edit the About and Contact pages; view reports."]])}
{table(["Part","Technology"],[["Back end","PHP 8.3 with mysqli, prepared statements, bcrypt password hashing, CSRF tokens"],["Database","MySQL 8.4 (Amazon RDS), 11 tables with sample data"],["Front end","HTML, CSS, JavaScript, Font Awesome, responsive dark theme"],["Packaging","Docker image (PHP 8.3 + Apache) and Caddy web proxy"],["Hosting","Amazon Web Services, region us-east-1"],["Automation","GitHub Actions (testing and scanning), automatic deployment on the server"],["Other features","Free shipping above Rs. 999; image uploads limited to JPG, PNG, GIF, WEBP up to 2 MB with random file names; a health page (/health.php) that reports whether the application and database work"]])}


<h1 class="pb" style="margin-top:0">2. Application flow</h1>
{fig_svg(flow, "Flow of the three user roles. All roles use the same database; one order can contain items from more than one seller.")}
<ol>
<li>A seller registers a shop. The admin approves the seller.</li>
<li>The approved seller adds products with images and prices.</li>
<li>A customer browses products, adds them to the cart and places the order (Cash on Delivery or demo payment).</li>
<li>The order is split by seller. Each seller confirms and ships their own items and updates the status.</li>
<li>The customer sees the status change to Delivered and can then rate and review the product.</li>
<li>The admin watches all orders, customers and reports, and can block a seller if needed.</li></ol>
{figure("L3-login.png","Login page of the live website on AWS. One login page serves all three roles.", "10cm")}

<h1 class="pb" style="margin-top:0">3. System architecture</h1>
{fig_svg(arch, "Architecture of ShopNest on AWS.")}
<p>A visitor opens the site through the public Elastic IP address. The request reaches the EC2 server, where the Caddy proxy passes it to the PHP application running in a Docker container. The application talks to the MySQL database on Amazon RDS over an encrypted (TLS) connection. The database is private and cannot be reached from the internet. Container logs go to CloudWatch, which also watches five alarms. Database dumps are stored in an encrypted S3 bucket. The server is managed through Systems Manager, so no SSH port is open, and a Lambda function stops the resources every night to save credit.</p>
{table(["Decision","Reason"],[["One small EC2 server","Enough for a class project and keeps the cost low"],["Managed database (RDS)","AWS handles backups, encryption and patching"],["No load balancer, NAT gateway or Multi-AZ","These would add USD 15 to 35 per month each; not needed for one server"],["No domain name","So the site uses plain HTTP (see Limitations)"],["Server builds the image itself after CI passes","No AWS password has to be stored in GitHub"]])}

<h1 class="pb" style="margin-top:0">4. AWS services used</h1>
<p>The table lists every AWS service used in the project. A short description and a console screenshot of each service follow.</p>
{table(["AWS service","What it does in ShopNest"],[
 ["Amazon EC2","The virtual server that runs the website containers"],["Amazon EBS","The disk of the server; also stores uploaded product images"],["Elastic IP","A fixed public address for the website"],
 ["Amazon VPC and Security Groups","The private network and the firewall rules"],["Amazon RDS (MySQL)","The managed database that stores users, products and orders"],["Amazon S3","Encrypted storage for database backups"],
 ["AWS Systems Manager","Runs commands on the server without SSH and keeps the database password safe"],["Amazon CloudWatch","Collects logs and raises alarms"],["Amazon SNS","Sends alarm notifications"],
 ["AWS Lambda and Amazon EventBridge","Stop the server and database every night to save credit"],["AWS CloudTrail","Records who did what in the account"]])}
{svc("01-ec2-instance.png","Amazon EC2","EC2 provides virtual servers in the cloud. ShopNest runs on one t3.micro server (1 GB memory) with Amazon Linux 2023. The server runs Docker, and the Docker containers run the web proxy and the PHP application.","Figure: EC2 instance shopnest-web, running, with Elastic IP 52.23.26.212.")}
{svc("03-ebs-volume.png","Amazon EBS","EBS gives the server a disk. The 20 GB gp3 volume is encrypted and holds the operating system, the Docker images and the folder with uploaded product pictures.","Figure: the encrypted 20 GB EBS volume attached to the server.")}
{svc("04-elastic-ip.png","Elastic IP","A normal public IP changes whenever the server is stopped. An Elastic IP stays the same, so the website address 52.23.26.212 does not change.","Figure: Elastic IP shopnest-eip associated with the server.")}
{svc("02-ec2-security-group.png","Security Groups (firewall)","A security group decides which network traffic is allowed. The web server only accepts web traffic (HTTP and HTTPS). SSH was closed, and the database accepts connections only from the web server.","Figure: inbound rules of the shopnest-web security group.")}
{svc("07-vpc-subnets.png","Amazon VPC","A VPC is the private network in which the resources live. ShopNest uses the default VPC of the account, which has six subnets, one in each availability zone.","Figure: subnets of the VPC used by the server and the database.")}
{svc("05-rds-instance.png","Amazon RDS","RDS runs the MySQL database as a managed service. The database shopnest-db (MySQL 8.4, db.t3.micro) is encrypted, is not accessible from the internet, keeps automatic backups for 7 days, has deletion protection on, and accepts only TLS-encrypted connections.","Figure: RDS database shopnest-db, status Available.")}
{svc("06-rds-snapshots.png","RDS snapshots","A snapshot is a saved copy of the database. A manual snapshot was taken before the demo and was used to test restoring the database into a new instance.","Figure: manual snapshot shopnest-manual-pre-demo.")}
{svc("08-s3-bucket-properties.png","Amazon S3","S3 stores files. The bucket shopnest-backups holds nightly database dumps and a copy of the uploaded images. It has versioning and encryption turned on, and old backups expire after 30 days.","Figure: S3 bucket properties (versioning enabled, encrypted).")}
{svc("09-s3-bucket-permissions.png","S3 permissions","Public access to the bucket is completely blocked, and a bucket policy rejects any request that does not use an encrypted connection.","Figure: Block Public Access is on and the TLS-only policy.")}
{svc("10-s3-backup-objects.png","Backups in S3","The backup script dumps the database, compresses it and uploads it to the bucket. One copy was also saved on a local computer in case the cloud account is lost.","Figure: database backup file stored in the db/ folder of the bucket.")}
{svc("11-ssm-managed-nodes.png","AWS Systems Manager","Systems Manager lets us run commands on the server from the console or from scripts without opening SSH. The server shows as an online managed node.","Figure: the server as a managed node (Online).")}
{svc("12-ssm-parameter-store.png","Parameter Store","Parameter Store keeps secrets in encrypted form. The database password is stored there as a SecureString and the server reads it at start-up, so the password is not kept in the code or in GitHub.","Figure: parameter /shopnest/db_password (SecureString).")}
{svc("13-cloudwatch-alarms.png","Amazon CloudWatch alarms","CloudWatch watches the health of the resources. Five alarms were created: server CPU, server status check, database CPU, database free storage and database connections.","Figure: the five CloudWatch alarms, all in OK state.")}
{svc("14-cloudwatch-log-group-app.png","CloudWatch Logs","The application writes its logs to CloudWatch, so they can be read from the console without logging in to the server. Logs are kept for two weeks.","Figure: log group /shopnest/app receiving the application logs.")}
{svc("15-sns-topic.png","Amazon SNS","SNS sends notifications. The topic shopnest-alerts is connected to the alarms; an e-mail address has to be subscribed to receive the messages.","Figure: SNS topic shopnest-alerts.")}
{svc("16-lambda-autostop.png","AWS Lambda","Lambda runs small pieces of code without a server. The function shopnest-autostop stops the server and the database at night so that the lab credit is not wasted. A tag, KeepAliveUntil, keeps them running during the demo days.","Figure: Lambda function shopnest-autostop.")}
{svc("17-eventbridge-rule.png","Amazon EventBridge","EventBridge is the scheduler. The rule shopnest-autostop-nightly calls the Lambda function every day at midnight Indian time (18:30 UTC).","Figure: EventBridge schedule rule for the nightly stop.")}
{svc("18-cloudtrail-event-history.png","AWS CloudTrail","CloudTrail records every action taken in the AWS account, such as running a command or creating a resource. It is useful for auditing and security checks.","Figure: CloudTrail event history.")}

<h1>5. Containerisation with Docker</h1>
<p>The website is packaged as a Docker image so that it runs the same way on a laptop and on AWS. The image is based on php:8.3-apache and includes the MySQL extension, the Amazon RDS certificate bundle for encrypted database connections, production PHP settings (errors are logged, not shown to visitors) and security headers. A health check calls <code>/health.php</code> every 30 seconds.</p>
<ul><li>On first start the container creates the database tables only if they do not exist, so existing data is never overwritten.</li>
<li>Uploaded product pictures are stored on a folder of the server disk, outside the container.</li>
<li>Docker Compose starts the application with the Caddy proxy; the containers restart automatically after a reboot and have memory limits (512 MB and 128 MB).</li>
<li>The image contains no passwords and no infrastructure or test files. The application workers run as the unprivileged user www-data.</li></ul>
<p>The same image was built and tested on a local computer with Docker Compose: the application started healthy and all 68 automated functional checks (customer, seller and admin) passed.</p>

<h1>6. CI/CD pipeline</h1>
{fig_svg(cicd, "CI/CD pipeline of ShopNest.")}
<p><b>CI (continuous integration)</b> runs on GitHub Actions for every push: it checks the PHP syntax, scans for leaked secrets, runs the 68 functional tests against a temporary database, lints the Dockerfile, builds the image, scans it for vulnerabilities with Trivy and starts the container as a final test.</p>
<p><b>CD (continuous deployment)</b> is pull-based. Every two minutes the server asks GitHub for the newest commit and checks that the CI run for exactly that commit succeeded. Only then does it build the new image, replace the running containers and check <code>/health.php</code>. If the new version is not healthy, the server returns to the previous version automatically. Every step is written to a deployment log.</p>
<p>A pull-based design was chosen because the AWS Academy lab does not allow the roles needed for GitHub to log in to AWS, and its keys expire every few hours. With this design no AWS password is stored in GitHub.</p>
{table(["Test performed on the live server","Result"],[
 ["A normal change is pushed and CI passes","The server deployed it automatically; the version on /health.php changed from 1e6e07449b44 to aa34984d808c"],
 ["A commit with a deliberate PHP error is pushed","CI failed and the server refused to deploy it; the running version stayed the same"],
 ["A release that passes CI but fails in production","The server detected the failed health check and went back to the previous version in about seven seconds after detection"]])}

<h1>7. Cost estimate</h1>
<p>The costs below are estimates based on the AWS list prices for us-east-1 (730 hours per month). The real consumption has to be read from the Billing console.</p>
{table(["Resource","Price","Per month (USD)"],[["EC2 t3.micro","$0.0104 per hour","7.59"],["EBS 20 GB gp3","$0.08 per GB-month","1.60"],["RDS db.t3.micro (MySQL, single zone)","$0.017 per hour","12.41"],["RDS storage 20 GB","$0.115 per GB-month","2.30"],["Elastic IP (public IPv4)","$0.005 per hour","3.65"],["CloudWatch alarms (5)","$0.10 each","0.50"],["S3, SNS, Lambda, Systems Manager, EventBridge","free tier or a few cents","0.05"],["<b>Total if running all day, every day</b>","<b>about $0.037 per hour</b>","<b>about 28 (about $0.93 per day)</b>"]])}
{table(["Usage pattern","Approximate cost per month"],[["Running 24 hours a day","about $28"],["Business hours only (10 h, 22 days)","about $14 to $18"],["Two-day demo","about $2 plus the fixed items"],["Everything stopped (disk, database storage and IP remain)","about $7 to $8"]])}
<p>Options considered and rejected because of cost: NAT gateway (about $33 per month), Application Load Balancer (about $16 per month), Multi-AZ database (double the database cost) and AWS WAF. With a lab credit of about USD 50 the project can run for around 50 days non-stop, and the nightly auto-stop makes the credit last longer.</p>

<h1>8. Cloud security</h1>
{table(["Area","What was done"],[
 ["Network","Only web traffic is allowed to the server. SSH is closed; the server is managed with Systems Manager. The database is private and accepts connections only from the web server."],
 ["Data in transit","The connection from the application to the database always uses TLS; the database refuses unencrypted connections."],
 ["Data at rest","The server disk, the database and the backup bucket are encrypted."],
 ["Secrets","The database password is kept in Parameter Store (encrypted). The code and GitHub contain no passwords; a secret scanner runs on every push."],
 ["Server","The metadata service requires tokens (IMDSv2); the application runs as an unprivileged user."],
 ["Backups","Automatic database backups (7 days), manual snapshot, nightly dumps to S3 with versioning, public access blocked."],
 ["Application","Passwords are hashed with bcrypt, forms use CSRF tokens, queries use prepared statements, uploads are checked for type and size and cannot run PHP, and security headers are sent."],
 ["Supply chain","Every build is scanned for vulnerabilities (Trivy) and for leaked secrets (gitleaks)."],
 ["Monitoring","CloudWatch alarms and logs; CloudTrail records account activity."]])}
<p>Known security gaps, listed in the Limitations section: the website uses HTTP because there is no domain name, the forgot-password page accepts only e-mail and mobile number, and the demo accounts are published in the README.</p>

<h1>9. Backup and recovery</h1>
{table(["What","How it is protected"],[["Database","Automatic RDS backups for 7 days with point-in-time restore, a manual snapshot, and deletion protection"],["Backup files","Nightly dump and image archive uploaded to the encrypted S3 bucket"],["Copy outside AWS","A database dump was downloaded to a local computer"],["Code","GitHub"],["Servers","Created by repeatable scripts in the repository (about six minutes to rebuild)"]])}
<p>A restore test was carried out: the manual snapshot was restored into a new temporary database, which became available in <b>7.3 minutes</b>. This is the measured recovery time. If the server fails it is recreated with the scripts and connects to the same database. If a release is bad, the previous version is restored automatically.</p>

<h1>10. Service level agreement (summary)</h1>
<p>AWS publishes its own commitments: 99.5% monthly availability for a single EC2 instance and for a single-zone RDS database, 99.99% for the EC2 region and 99.95% for Lambda. The compensation is service credits, which are not available in the lab account. ShopNest's own targets and the measured values are given in the separate document <b>ShopNest_SLA.pdf</b>.</p>
{table(["Item","Target","Measured"],[["New server serving traffic after launch","under 10 minutes","about 6 minutes"],["Recovery from a bad release","under 5 minutes","about 2 minutes to detect, 7 seconds to roll back"],["Database restore time","under 15 minutes","7.3 minutes"]])}
<p>Long-term availability has not been measured because the system has been running for only a short time.</p>

<h1>11. Testing and results</h1>
{table(["Test","Result"],[["Functional tests (customer, seller, admin flows)","68 of 68 passed on the local build"],["Automated CI on GitHub","lint, tests, image build, Trivy scan and container test passed on the deployed commits"],["Website on AWS","http://52.23.26.212/health.php returns status ok and database up"],["Network exposure","from the internet only port 80 answers; SSH and database ports are closed"],["Database encryption","a connection without TLS is refused; a TLS connection works"],["Automatic deployment, refusal of a failing commit, automatic rollback","all three demonstrated on the live server"],["Backup","database dump uploaded to S3 and copied to a local computer"],["Restore","database restored into a new instance in 7.3 minutes"]])}
<h2>Load test</h2>
<p>A short load test was run against a separate copy of the database on the same small server with 1,500 requests per run.</p>
{table(["Visitors at the same time","Page","Requests per second","Median","95th percentile","Failed"],[["50","Home page","231","203 ms","327 ms","0"],["100","Product page","371","268 ms","303 ms","0"],["100","Home page","49","229 ms","10 s","83"]])}
<p>The server handled 50 simultaneous visitors without errors. At 100 visitors the home page started to fail on this very small server; a larger server or a load balancer would be needed for more traffic.</p>

<h1>12. Limitations and future scope</h1>
<ul>
<li><b>HTTP only.</b> No domain name was available, so the site has no HTTPS certificate and passwords are sent without encryption. Adding a domain and pointing it to the Elastic IP would enable automatic HTTPS in the existing Caddy proxy. Only demo accounts should be used until then.</li>
<li><b>Alert e-mail.</b> The alarm topic exists, but an e-mail address still has to be subscribed to receive notifications.</li>
<li><b>Forgot password.</b> The reset only asks for e-mail and mobile number; an e-mailed one-time link would be safer.</li>
<li><b>Single server and single-zone database.</b> A failure causes a few minutes of downtime. A load balancer, Auto Scaling and a Multi-AZ database would remove this but cost more.</li>
<li><b>Load test.</b> It covered browsing pages only and was run from the same server; a test from outside AWS with login and checkout is future work.</li>
<li><b>Lab environment.</b> The AWS Academy lab has temporary credentials, no custom IAM roles and may be reset, so a backup copy is kept outside AWS. A normal AWS account would allow least-privilege roles and Terraform.</li>
<li><b>Product images</b> could be moved to S3 and delivered through CloudFront.</li></ul>

<h1>13. Conclusion</h1>
<p>The ShopNest web application is now hosted online on AWS. It runs in Docker on EC2, uses a private, encrypted RDS database, stores backups in S3, is monitored with CloudWatch, is managed through Systems Manager and saves credit with a nightly Lambda stop. A CI/CD pipeline tests and scans every change, and the server deploys only verified versions and rolls back automatically if a release is unhealthy. The project covers cloud provisioning, service usage, costing, CI/CD design, security and an SLA, and lists clearly what can be improved next.</p>
"""
(OUT / "report.html").write_text(page("ShopNest Cloud Report", body), encoding="utf-8")

sla = f"""
<h1 class="first" style="font-size:18pt">ShopNest on AWS: Service Level Agreement (SLA)</h1>
<p style="font-size:9pt">{P} &nbsp;|&nbsp; 26 November 2026 &nbsp;|&nbsp; Region us-east-1 &nbsp;|&nbsp; http://52.23.26.212/</p>
<h2>1. What is an SLA?</h2>
<p>A Service Level Agreement states how available a service should be, how quickly it recovers from a problem and what is not covered. AWS has its own SLAs for its services; ShopNest sets simple targets on top of them for a class-project demonstration.</p>
<h2>2. What AWS promises (taken from AWS documentation)</h2>
{table(["Service","AWS commitment","Remedy"],[["EC2, single instance","99.5% per month","service credits"],["EC2, region level","99.99%","service credits"],["RDS, single zone","99.5%","service credits"],["RDS, Multi-AZ (not used)","99.95%","service credits"],["Lambda","99.95%","service credits"]])}
<p style="font-size:9pt">Service credits are not available in the AWS Academy lab account.</p>
<h2>3. ShopNest targets and measured results</h2>
{table(["Item","Target","Result"],[
 ["Availability during demo and exam hours","99% (about 7.3 hours per month, or 36 seconds per hour, of downtime)","Not measured over a long period yet"],
 ["New server serving traffic after launch","under 10 minutes","about 6 minutes"],
 ["Recovery from a bad release","under 5 minutes","about 2 minutes to detect, 7 seconds to roll back"],
 ["Database restore time (RTO)","under 15 minutes","7.3 minutes"],
 ["Maximum data loss (RPO)","about 5 minutes with point-in-time restore; up to 24 hours when restoring the nightly dump","Based on AWS documentation"],
 ["Capacity","50 visitors at the same time","50 visitors served without errors (231 requests per second on the home page)"]])}
<h2>4. How the targets are met</h2>
{table(["Mechanism","Purpose"],[["Container restart policy and health check","Recover from application crashes"],["Automatic rollback in the deployment script","Recover from a bad release"],["RDS automatic backups, manual snapshot, S3 dumps and a local copy","Protect and restore data"],["CloudWatch alarms and SNS","Detect problems and notify"],["Elastic IP, Systems Manager and scripted re-creation","Rebuild the server quickly"],["Nightly auto-stop with a KeepAliveUntil tag","Save credit; the demo period is protected"]])}
<h2>5. Exclusions</h2>
<p>The SLA does not cover: expiry or reset of the AWS Academy lab; an AWS regional outage; problems in the user's own network; resources stopped by the nightly auto-stop after the KeepAliveUntil date; periods when deployments are deliberately frozen; misuse of credentials or tags; and the lack of encryption between the browser and the website, because the site uses HTTP.</p>
<p style="font-size:9pt">No uptime percentage is claimed: the system has run for a short time only, so long-term availability has not been measured.</p>
"""
(OUT / "sla.html").write_text(page("ShopNest SLA", sla), encoding="utf-8")
print("written; figures:", fig_n[0])
