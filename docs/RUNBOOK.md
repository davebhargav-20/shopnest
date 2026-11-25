# Runbook — ShopNest on AWS (us-east-1, Learner Lab)

Live site: **http://52.23.26.212/** (Elastic IP, health: `/health.php`). Instance `i-08fcd02f43c346252`, RDS `shopnest-db`, bucket `shopnest-backups-891377286919-use1`.
All commands below need fresh Learner Lab credentials exported in the shell (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_SESSION_TOKEN`) and Python with boto3. Helper: `python infra/learnerlab/ssm_run.py "<command>"` runs a command on the server through SSM.

| Task | How |
|---|---|
| Is it up? | `curl http://52.23.26.212/health.php` → `{"status":"ok","db":"up","version":"<commit>"}` |
| Start after a stop | Console → EC2 start; RDS start (can take ~5–10 min). The Elastic IP stays the same; containers restart by themselves (`restart: unless-stopped`) |
| Stop (save credit) | The Lambda `shopnest-autostop` does it nightly (00:00 IST) **unless** tag `KeepAliveUntil` (EC2 and RDS) is today or later. Currently `2026-12-02`. Change the tag to extend |
| Deploy now | Merge/push to the deploy branch; the server checks every 2 min. Force: `ssm_run.py "systemctl start shopnest-deploy.service"` |
| Pause deploys | `ssm_run.py "echo AUTO_DEPLOY=false > /opt/shopnest/auto-deploy.conf"` (set `BRANCH=` line again if you recreate the file: `printf 'AUTO_DEPLOY=false\nREPO=davebhargav-20/shopnest\nBRANCH=aws/cloud-deploy\n'`) |
| After merging the PR | set `BRANCH=main` in `/opt/shopnest/auto-deploy.conf` |
| Deploy history | `ssm_run.py "tail -20 /opt/shopnest/deploy-history.log"` |
| Logs | CloudWatch Logs groups `/shopnest/app`, `/shopnest/proxy` |
| Manual backup | `ssm_run.py "/opt/shopnest/backup.sh"` (also nightly 02:30 IST by cron) |
| Restore | see `BACKUP_AND_DR.md` |
| Reset the admin password | `ssm_run.py "cd /opt/shopnest; set -a; . ./.env; set +a; H=\$(docker exec shopnest-app-1 php -r 'echo password_hash(\"NEW_PASSWORD\", PASSWORD_DEFAULT);'); docker exec -e MYSQL_PWD=\$DB_PASS shopnest-app-1 mysql --ssl --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem -h \$DB_HOST -u admin ecomdb -e \"UPDATE tbladmin SET Password='\$H' WHERE UserName='admin'\""` (replace NEW_PASSWORD) |
| Demo checklist | site healthy · `KeepAliveUntil` covers the demo day · `AUTO_DEPLOY=false` just before the demo · logins in 3 browsers · Actions page open · console tabs open |
| Clean-up after the course (owner runs, destructive) | terminate EC2 `i-08fcd02f43c346252`; release the Elastic IP; disable deletion protection then delete RDS `shopnest-db`; empty and delete the S3 bucket; delete Lambda/EventBridge/alarms/SNS/log groups/SSM parameter. **First copy the backups from `C:\Users\hp\shopnest-backups\`** |
