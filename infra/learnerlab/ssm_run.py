"""Run a shell command on the ShopNest EC2 instance via SSM Run Command and print the output.
usage: python ssm_run.py "<command>"   (never print secrets: do not cat .env)"""
import sys, time
from common import *
ec2, ssm = c("ec2"), c("ssm")
iid = ec2.describe_instances(Filters=[{"Name": "tag:Name", "Values": ["shopnest-web"]},
      {"Name": "instance-state-name", "Values": ["running"]}])["Reservations"][0]["Instances"][0]["InstanceId"]
cid = ssm.send_command(InstanceIds=[iid], DocumentName="AWS-RunShellScript",
      Parameters={"commands": [sys.argv[1]]}, CloudWatchOutputConfig={"CloudWatchOutputEnabled": False})["Command"]["CommandId"]
for _ in range(400):
    time.sleep(3)
    try:
        r = ssm.get_command_invocation(CommandId=cid, InstanceId=iid)
    except Exception:
        continue
    if r["Status"] not in ("Pending", "InProgress", "Delayed"):
        print(r["StandardOutputContent"], r["StandardErrorContent"], "[status: %s]" % r["Status"]); break
