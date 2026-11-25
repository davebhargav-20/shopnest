"""Step 1: foundation resources. Idempotent; never deletes anything.
 - starts the existing RDS instance (if stopped)
 - S3 backup bucket (SSE, versioning, public-access block, TLS-only, lifecycle)
 - SNS topic, CloudWatch log groups, SSM SecureString for the DB password
"""
import json, secrets, string
from botocore.exceptions import ClientError
from common import *

rds, s3, sns, logs, ssm = c("rds"), c("s3"), c("sns"), c("logs"), c("ssm")

# --- RDS: start if stopped
st = rds.describe_db_instances(DBInstanceIdentifier=RDS_ID)["DBInstances"][0]["DBInstanceStatus"]
log(f"RDS status: {st}")
if st == "stopped":
    rds.start_db_instance(DBInstanceIdentifier=RDS_ID)
    log("RDS start requested")

# --- S3
try:
    s3.head_bucket(Bucket=BUCKET); log("bucket exists")
except ClientError:
    s3.create_bucket(Bucket=BUCKET); log("bucket created")
s3.put_bucket_tagging(Bucket=BUCKET, Tagging={"TagSet": TAGS})
s3.put_public_access_block(Bucket=BUCKET, PublicAccessBlockConfiguration=dict(
    BlockPublicAcls=True, IgnorePublicAcls=True, BlockPublicPolicy=True, RestrictPublicBuckets=True))
s3.put_bucket_encryption(Bucket=BUCKET, ServerSideEncryptionConfiguration={"Rules": [
    {"ApplyServerSideEncryptionByDefault": {"SSEAlgorithm": "AES256"}, "BucketKeyEnabled": True}]})
s3.put_bucket_versioning(Bucket=BUCKET, VersioningConfiguration={"Status": "Enabled"})
s3.put_bucket_policy(Bucket=BUCKET, Policy=json.dumps({"Version": "2012-10-17", "Statement": [{
    "Sid": "DenyInsecureTransport", "Effect": "Deny", "Principal": "*", "Action": "s3:*",
    "Resource": [f"arn:aws:s3:::{BUCKET}", f"arn:aws:s3:::{BUCKET}/*"],
    "Condition": {"Bool": {"aws:SecureTransport": "false"}}}]}))
s3.put_bucket_lifecycle_configuration(Bucket=BUCKET, LifecycleConfiguration={"Rules": [
    {"ID": "expire-old-backups", "Status": "Enabled", "Filter": {"Prefix": ""},
     "Expiration": {"Days": 30}, "NoncurrentVersionExpiration": {"NoncurrentDays": 7},
     "AbortIncompleteMultipartUpload": {"DaysAfterInitiation": 3}}]})
log("bucket hardened")

# --- SNS
arn = sns.create_topic(Name=TOPIC, Tags=TAGS)["TopicArn"]
log(f"SNS topic: {arn}")

# --- CloudWatch log groups
for g in ("/shopnest/app", "/shopnest/proxy", "/shopnest/deploy"):
    try:
        logs.create_log_group(logGroupName=g, tags={"Project": "ShopNest"}); log(f"log group {g} created")
    except ClientError as e:
        if e.response["Error"]["Code"] != "ResourceAlreadyExistsException": raise
    logs.put_retention_policy(logGroupName=g, retentionInDays=14)

# --- SSM SecureString for DB password (created once; value never printed)
try:
    ssm.get_parameter(Name=SSM_DB_PASS); log("SSM parameter exists (value untouched)")
except ClientError:
    pw = "".join(secrets.choice(string.ascii_letters + string.digits) for _ in range(28))
    ssm.put_parameter(Name=SSM_DB_PASS, Value=pw, Type="SecureString", Tags=TAGS,
                      Description="ShopNest RDS master password")
    log("SSM parameter created")
