"""Step 3: EC2 (t3.micro, AL2023, encrypted gp3, IMDSv2) + Elastic IP. Idempotent via Name tag.
User-data installs Docker, writes /opt/shopnest/.env from SSM, installs the pull-based deployer timer."""
import base64, pathlib, sys
from common import *

BRANCH = sys.argv[1] if len(sys.argv) > 1 else "main"
ROOT = pathlib.Path(__file__).resolve().parents[2]
b64 = lambda p: base64.b64encode((ROOT / p).read_bytes()).decode()
ec2, rds, ssm = c("ec2"), c("rds"), c("ssm")

for r in ec2.describe_instances(Filters=[{"Name": "tag:Name", "Values": ["shopnest-web"]},
        {"Name": "instance-state-name", "Values": ["pending", "running", "stopped", "stopping"]}])["Reservations"]:
    sys.exit(f"instance already exists: {r['Instances'][0]['InstanceId']}")

db_host = rds.describe_db_instances(DBInstanceIdentifier=RDS_ID)["DBInstances"][0]["Endpoint"]["Address"]
ami = ssm.get_parameter(Name="/aws/service/ami-amazon-linux-latest/al2023-ami-kernel-default-x86_64")["Parameter"]["Value"]
sg = ec2.describe_security_groups(Filters=[{"Name": "group-name", "Values": ["shopnest-web"]}])["SecurityGroups"][0]["GroupId"]

user_data = f"""#!/bin/bash
set -euo pipefail
exec > >(tee /var/log/shopnest-bootstrap.log) 2>&1
dnf install -y docker cronie
systemctl enable --now docker crond
mkdir -p /usr/local/lib/docker/cli-plugins
curl -fsSL https://github.com/docker/compose/releases/download/v2.29.7/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose
chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
echo '/swapfile swap swap defaults 0 0' >> /etc/fstab
mkdir -p /opt/shopnest/uploads /opt/shopnest/src
cd /opt/shopnest
echo '{b64("deploy/pull/docker-compose.prod.yml")}' | base64 -d > docker-compose.prod.yml
echo '{b64("deploy/Caddyfile")}' | base64 -d > Caddyfile
echo '{b64("deploy/backup.sh")}' | base64 -d > backup.sh
echo '{b64("deploy/pull/pull-deploy.sh")}' | base64 -d > pull-deploy.sh
chmod 750 backup.sh pull-deploy.sh
DB_PASS=$(aws ssm get-parameter --region {REGION} --name {SSM_DB_PASS} --with-decryption --query Parameter.Value --output text)
cat > .env <<ENV
AWS_REGION={REGION}
APP_TAG=none
DB_HOST={db_host}
DB_USER=admin
DB_PASS=$DB_PASS
DB_NAME=ecomdb
SITE_ADDRESS=:80
BACKUP_BUCKET={BUCKET}
ENV
chmod 600 .env
printf 'AUTO_DEPLOY=true\nREPO=davebhargav-20/shopnest\nBRANCH={BRANCH}\n' > auto-deploy.conf
cat > /etc/systemd/system/shopnest-deploy.service <<UNIT
[Unit]
Description=ShopNest pull-based deploy
[Service]
Type=oneshot
ExecStart=/opt/shopnest/pull-deploy.sh
UNIT
cat > /etc/systemd/system/shopnest-deploy.timer <<UNIT
[Unit]
Description=Check GitHub for a CI-verified ShopNest release every 2 minutes
[Timer]
OnBootSec=30
OnUnitActiveSec=120
[Install]
WantedBy=timers.target
UNIT
systemctl daemon-reload && systemctl enable --now shopnest-deploy.timer
echo '0 21 * * * root /opt/shopnest/backup.sh >> /var/log/shopnest-backup.log 2>&1' > /etc/cron.d/shopnest-backup
systemctl start shopnest-deploy.service || true
echo BOOTSTRAP_DONE
"""

res = ec2.run_instances(ImageId=ami, InstanceType="t3.micro", MinCount=1, MaxCount=1,
    SecurityGroupIds=[sg], IamInstanceProfile={"Name": "LabInstanceProfile"}, UserData=user_data,
    BlockDeviceMappings=[{"DeviceName": "/dev/xvda", "Ebs": {"VolumeSize": 20, "VolumeType": "gp3", "Encrypted": True, "DeleteOnTermination": True}}],
    MetadataOptions={"HttpTokens": "required", "HttpPutResponseHopLimit": 1, "HttpEndpoint": "enabled"},
    TagSpecifications=[{"ResourceType": rt, "Tags": TAGS + [{"Key": "Name", "Value": "shopnest-web"}]} for rt in ("instance", "volume")])
iid = res["Instances"][0]["InstanceId"]; log(f"launched {iid}")
ec2.get_waiter("instance_running").wait(InstanceIds=[iid])
alloc = ec2.allocate_address(Domain="vpc", TagSpecifications=[{"ResourceType": "elastic-ip", "Tags": TAGS + [{"Key": "Name", "Value": "shopnest-eip"}]}])
ec2.associate_address(InstanceId=iid, AllocationId=alloc["AllocationId"])
log(f"Elastic IP {alloc['PublicIp']} associated; site: http://{alloc['PublicIp']}/")
