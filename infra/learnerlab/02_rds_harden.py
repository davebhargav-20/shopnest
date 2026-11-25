"""Step 2: harden the EXISTING RDS instance (no data is touched, nothing deleted).
 - master password := value from SSM SecureString (value never printed)
 - custom parameter group (MySQL 8.4) with require_secure_transport=1
 - deletion protection on, tags, CloudWatch log exports (best effort)
"""
import time
from botocore.exceptions import ClientError
from common import *

rds, ssm = c("rds"), c("ssm")
PG = "shopnest-mysql84"

def inst():
    return rds.describe_db_instances(DBInstanceIdentifier=RDS_ID)["DBInstances"][0]

while inst()["DBInstanceStatus"] != "available":
    log("waiting for RDS... " + inst()["DBInstanceStatus"]); time.sleep(20)
d = inst()
log(f"RDS available, engine {d['EngineVersion']}")

# parameter group
try:
    rds.create_db_parameter_group(DBParameterGroupName=PG, DBParameterGroupFamily="mysql8.4",
        Description="ShopNest: TLS required", Tags=TAGS); log("parameter group created")
except ClientError as e:
    if e.response["Error"]["Code"] != "DBParameterGroupAlreadyExists": raise
rds.modify_db_parameter_group(DBParameterGroupName=PG, Parameters=[
    {"ParameterName": "require_secure_transport", "ParameterValue": "1", "ApplyMethod": "immediate"}])

pw = ssm.get_parameter(Name=SSM_DB_PASS, WithDecryption=True)["Parameter"]["Value"]
rds.modify_db_instance(DBInstanceIdentifier=RDS_ID, MasterUserPassword=pw, DBParameterGroupName=PG,
                       DeletionProtection=True, ApplyImmediately=True)
log("modify requested (password, param group, deletion protection)")
rds.add_tags_to_resource(ResourceName=d["DBInstanceArn"], Tags=TAGS)
time.sleep(30)
try:
    rds.modify_db_instance(DBInstanceIdentifier=RDS_ID, ApplyImmediately=True,
        CloudwatchLogsExportConfiguration={"EnableLogTypes": ["error", "slowquery"]})
    log("log exports requested")
except ClientError as e:
    log("log exports not possible: " + e.response["Error"]["Code"])
while inst()["DBInstanceStatus"] not in ("available",):
    time.sleep(15)
if inst()["DBParameterGroups"][0]["ParameterApplyStatus"] == "pending-reboot":
    rds.reboot_db_instance(DBInstanceIdentifier=RDS_ID); log("reboot requested to apply parameter group")
    time.sleep(40)
    while inst()["DBInstanceStatus"] != "available":
        time.sleep(15)
d = inst()
log(f"final: status={d['DBInstanceStatus']} public={d['PubliclyAccessible']} enc={d['StorageEncrypted']} "
    f"delprot={d['DeletionProtection']} pg={d['DBParameterGroups'][0]['DBParameterGroupName']}/{d['DBParameterGroups'][0]['ParameterApplyStatus']} "
    f"backup={d['BackupRetentionPeriod']}d logs={d.get('EnabledCloudwatchLogsExports')}")
