"""Step 5: disaster-recovery rehearsal. Restores snapshot shopnest-manual-pre-demo into a TEMPORARY instance
(shopnest-restore-test), compares per-table row counts with production from the EC2 host, measures RTO,
then deletes ONLY the temporary instance (no final snapshot). Production is never modified."""
import subprocess, sys, time, os
from common import *
rds = c("rds"); T = "shopnest-restore-test"
prod = rds.describe_db_instances(DBInstanceIdentifier=RDS_ID)["DBInstances"][0]
snap = rds.describe_db_snapshots(DBSnapshotIdentifier="shopnest-manual-pre-demo")["DBSnapshots"][0]
age = (time.time() - snap["SnapshotCreateTime"].timestamp()) / 60
t0 = time.time()
try:
    ex = rds.describe_db_instances(DBInstanceIdentifier=T)["DBInstances"][0]
    log("temporary instance already exists, resuming"); t0 = ex.get("InstanceCreateTime").timestamp() if ex.get("InstanceCreateTime") else t0
except Exception:
    ex = None
if ex is None: rds.restore_db_instance_from_db_snapshot(DBInstanceIdentifier=T, DBSnapshotIdentifier="shopnest-manual-pre-demo",
    DBInstanceClass="db.t3.micro", PubliclyAccessible=False, VpcSecurityGroupIds=["sg-031b26b221d57b91d"],
    DBParameterGroupName="shopnest-mysql84", DBSubnetGroupName=prod["DBSubnetGroup"]["DBSubnetGroupName"],
    MultiAZ=False, DeletionProtection=False, Tags=TAGS)
log("restore started")
while True:
    try:
        d = rds.describe_db_instances(DBInstanceIdentifier=T)["DBInstances"][0]
        if d["DBInstanceStatus"] == "available": break
    except Exception as e: log("retry after transient error")
    time.sleep(20)
rto = (time.time() - t0) / 60
host = d["Endpoint"]["Address"]; log(f"restored instance available after {rto:.1f} min: {host}")
q = ("select table_name from information_schema.tables where table_schema='ecomdb' order by 1")
cmd = ("cd /opt/shopnest; set -a; . ./.env; set +a; export MYSQL_PWD=$DB_PASS; "
       "S='--ssl --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem'; "
       "for H in $DB_HOST " + host + "; do echo \"== $H\"; "
       "for t in $(docker exec -e MYSQL_PWD shopnest-app-1 mysql $S -h $H -u admin -N -e \"" + q + "\"); do "
       "echo \"$t $(docker exec -e MYSQL_PWD shopnest-app-1 mysql $S -h $H -u admin -N -e \\\"select count(*) from ecomdb.$t\\\")\"; done; done")
out = subprocess.run([sys.executable, os.path.join(os.path.dirname(__file__), "ssm_run.py"), cmd], capture_output=True, text=True, encoding="utf-8").stdout
print(out)
parts = out.split("== ")
counts = [dict(l.split() for l in p.strip().splitlines()[1:] if len(l.split()) == 2) for p in parts[1:3]]
log(f"ROW COUNTS MATCH: {counts[0] == counts[1]}  ({len(counts[0])} tables)")
log(f"Snapshot age at restore start: {age:.0f} min (RPO for a snapshot restore = time since snapshot; PITR RPO ~5 min)")
log(f"MEASURED RTO (restore to available): {rto:.1f} min")
rds.delete_db_instance(DBInstanceIdentifier=T, SkipFinalSnapshot=True, DeleteAutomatedBackups=True)
log("temporary restore instance deletion requested (production untouched)")
