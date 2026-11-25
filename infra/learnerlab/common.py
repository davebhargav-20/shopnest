"""Shared helpers for the Learner Lab provisioning scripts (boto3).
Credentials are read from the standard AWS_* environment variables only."""
import boto3, botocore

REGION = "us-east-1"
ACCOUNT = boto3.client("sts", region_name=REGION).get_caller_identity()["Account"]
TAGS = [{"Key": "Project", "Value": "ShopNest"}]
TAGS_LC = [{"Key": "Project", "Value": "ShopNest"}]
RDS_ID = "shopnest-db"
BUCKET = f"shopnest-backups-{ACCOUNT}-use1"
TOPIC = "shopnest-alerts"
SSM_DB_PASS = "/shopnest/db_password"

def c(service):
    return boto3.client(service, region_name=REGION)

def log(msg):
    print(msg, flush=True)
