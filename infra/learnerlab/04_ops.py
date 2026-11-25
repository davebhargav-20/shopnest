"""Step 4: close SSH to the world, CloudWatch alarms -> SNS, Lambda auto-stop + EventBridge nightly rule.
Usage: python 04_ops.py [KeepAliveUntil YYYY-MM-DD]"""
import io, sys, zipfile, pathlib, time
from botocore.exceptions import ClientError
from common import *

KEEP = sys.argv[1] if len(sys.argv) > 1 else "2026-12-02"
ec2, cw, sns, lam, ev, rds = c("ec2"), c("cloudwatch"), c("sns"), c("lambda"), c("events"), c("rds")
topic = f"arn:aws:sns:{REGION}:{ACCOUNT}:{TOPIC}"
sg = ec2.describe_security_groups(Filters=[{"Name": "group-name", "Values": ["shopnest-web"]}])["SecurityGroups"][0]["GroupId"]
iid = ec2.describe_instances(Filters=[{"Name": "tag:Name", "Values": ["shopnest-web"]}, {"Name": "instance-state-name", "Values": ["running"]}])["Reservations"][0]["Instances"][0]["InstanceId"]

# 1. SSH closed (SSM is used instead)
try:
    ec2.revoke_security_group_ingress(GroupId=sg, IpPermissions=[{"IpProtocol": "tcp", "FromPort": 22, "ToPort": 22, "IpRanges": [{"CidrIp": "0.0.0.0/0"}]}])
    log("SSH (22) rule removed from shopnest-web")
except ClientError as e:
    log("SSH rule: " + e.response["Error"]["Code"])
ec2.create_tags(Resources=[sg, "sg-031b26b221d57b91d"], Tags=TAGS)

# 2. Alarms
def alarm(name, ns, metric, dim, stat, thr, op, period=300, ev_=1, desc=""):
    cw.put_metric_alarm(AlarmName=name, AlarmDescription=desc, Namespace=ns, MetricName=metric, Dimensions=dim,
        Statistic=stat, Period=period, EvaluationPeriods=ev_, Threshold=thr, ComparisonOperator=op,
        TreatMissingData="notBreaching", AlarmActions=[topic], OKActions=[topic], Tags=TAGS)
    log("alarm " + name)
ei = [{"Name": "InstanceId", "Value": iid}]; ri = [{"Name": "DBInstanceIdentifier", "Value": RDS_ID}]
alarm("shopnest-ec2-cpu-high", "AWS/EC2", "CPUUtilization", ei, "Average", 80, "GreaterThanThreshold", ev_=2)
alarm("shopnest-ec2-status-check-failed", "AWS/EC2", "StatusCheckFailed", ei, "Maximum", 1, "GreaterThanOrEqualToThreshold", 60, 2)
alarm("shopnest-rds-cpu-high", "AWS/RDS", "CPUUtilization", ri, "Average", 80, "GreaterThanThreshold", ev_=2)
alarm("shopnest-rds-storage-low", "AWS/RDS", "FreeStorageSpace", ri, "Minimum", 2 * 1024**3, "LessThanThreshold")
alarm("shopnest-rds-connections-high", "AWS/RDS", "DatabaseConnections", ri, "Maximum", 50, "GreaterThanThreshold")

# 3. KeepAliveUntil tags protect the demo from auto-stop
ec2.create_tags(Resources=[iid], Tags=[{"Key": "KeepAliveUntil", "Value": KEEP}])
arn = rds.describe_db_instances(DBInstanceIdentifier=RDS_ID)["DBInstances"][0]["DBInstanceArn"]
rds.add_tags_to_resource(ResourceName=arn, Tags=[{"Key": "KeepAliveUntil", "Value": KEEP}])
log(f"KeepAliveUntil={KEEP} set on EC2 and RDS")

# 4. Lambda + EventBridge
buf = io.BytesIO()
with zipfile.ZipFile(buf, "w", zipfile.ZIP_DEFLATED) as z:
    z.write(pathlib.Path(__file__).parent / "lambda" / "autostop.py", "autostop.py")
role = f"arn:aws:iam::{ACCOUNT}:role/LabRole"
try:
    lam.create_function(FunctionName="shopnest-autostop", Runtime="python3.12", Role=role, Handler="autostop.handler",
        Code={"ZipFile": buf.getvalue()}, Timeout=60, MemorySize=256, Environment={"Variables": {"DRY_RUN": "true"}}, Tags={"Project": "ShopNest"})
    log("lambda created (DRY_RUN=true)")
except ClientError as e:
    if e.response["Error"]["Code"] != "ResourceConflictException": raise
    lam.update_function_code(FunctionName="shopnest-autostop", ZipFile=buf.getvalue()); log("lambda code updated")
fn = lam.get_function(FunctionName="shopnest-autostop")["Configuration"]["FunctionArn"]
ev.put_rule(Name="shopnest-autostop-nightly", ScheduleExpression="cron(30 18 * * ? *)", State="ENABLED",
            Description="Stop ShopNest EC2/RDS nightly at 00:00 IST unless KeepAliveUntil protects them", Tags=TAGS)
try:
    lam.add_permission(FunctionName="shopnest-autostop", StatementId="eventbridge-nightly", Action="lambda:InvokeFunction",
        Principal="events.amazonaws.com", SourceArn=ev.describe_rule(Name="shopnest-autostop-nightly")["Arn"])
except ClientError as e:
    if e.response["Error"]["Code"] != "ResourceConflictException": raise
ev.put_targets(Rule="shopnest-autostop-nightly", Targets=[{"Id": "1", "Arn": fn}])
log("EventBridge rule shopnest-autostop-nightly -> lambda (cron 18:30 UTC)")
time.sleep(5)
r = lam.invoke(FunctionName="shopnest-autostop", LogType="Tail")
import base64; log("DRY-RUN invoke result:\n" + base64.b64decode(r["LogResult"]).decode()[-600:])
