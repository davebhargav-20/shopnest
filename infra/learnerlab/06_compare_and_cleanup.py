"""Compare per-table row counts production vs restored copy, then delete ONLY the temporary copy."""
import base64, subprocess, sys, os
from common import *
rds = c("rds"); T = "shopnest-restore-test"
host = rds.describe_db_instances(DBInstanceIdentifier=T)["DBInstances"][0]["Endpoint"]["Address"]
b = base64.b64encode(open(os.path.join(os.path.dirname(__file__), "compare_counts.sh"), "rb").read()).decode()
out = subprocess.run([sys.executable, os.path.join(os.path.dirname(__file__), "ssm_run.py"), f"echo {b} | base64 -d | bash -s {host}"],
                     capture_output=True, text=True, encoding="utf-8").stdout
print(out)
parts = out.split("== ")
counts = [dict(l.split() for l in p.strip().splitlines()[1:] if len(l.split()) == 2) for p in parts[1:3]]
print(f"ROW COUNTS MATCH: {counts[0] == counts[1]} ({len(counts[0])} tables, {sum(map(int, counts[0].values()))} rows)")
if counts[0] == counts[1] and len(counts[0]) >= 11:
    rds.delete_db_instance(DBInstanceIdentifier=T, SkipFinalSnapshot=True, DeleteAutomatedBackups=True)
    print("temporary restore instance deletion requested (production untouched)")
