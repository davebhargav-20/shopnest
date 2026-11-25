"""ShopNest auto-stop: stops the tagged EC2 instance and RDS instance every night to protect the
Learner Lab credit, unless the resource has a tag KeepAliveUntil=YYYY-MM-DD that is still in the future.
Env: DRY_RUN=true|false (default true). Only resources tagged Project=ShopNest are ever touched."""
import os, datetime, boto3

DRY = os.environ.get("DRY_RUN", "true").lower() != "false"
today = datetime.date.today()

def protected(tags):
    t = {x["Key"]: x["Value"] for x in tags}
    try:
        return datetime.date.fromisoformat(t.get("KeepAliveUntil", "")) >= today
    except ValueError:
        return False

def handler(event, context):
    out = []
    ec2, rds = boto3.client("ec2"), boto3.client("rds")
    for r in ec2.describe_instances(Filters=[{"Name": "tag:Project", "Values": ["ShopNest"]},
                                             {"Name": "instance-state-name", "Values": ["running"]}])["Reservations"]:
        for i in r["Instances"]:
            if protected(i.get("Tags", [])):
                out.append(f"EC2 {i['InstanceId']}: protected by KeepAliveUntil"); continue
            out.append(f"EC2 {i['InstanceId']}: " + ("DRY_RUN would stop" if DRY else "stopping"))
            if not DRY: ec2.stop_instances(InstanceIds=[i["InstanceId"]])
    for d in rds.describe_db_instances()["DBInstances"]:
        tags = rds.list_tags_for_resource(ResourceName=d["DBInstanceArn"])["TagList"]
        if {x["Key"]: x["Value"] for x in tags}.get("Project") != "ShopNest" or d["DBInstanceStatus"] != "available":
            continue
        if protected(tags):
            out.append(f"RDS {d['DBInstanceIdentifier']}: protected by KeepAliveUntil"); continue
        out.append(f"RDS {d['DBInstanceIdentifier']}: " + ("DRY_RUN would stop" if DRY else "stopping"))
        if not DRY: rds.stop_db_instance(DBInstanceIdentifier=d["DBInstanceIdentifier"])
    print("\n".join(out)); return out
