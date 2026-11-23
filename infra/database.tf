resource "random_password" "db" {
  length           = 24
  special          = true
  override_special = "-_.+"
}

# Password kept encrypted in SSM Parameter Store (never in code or Git)
resource "aws_ssm_parameter" "db_password" {
  name  = "/shopnest/db_password"
  type  = "SecureString"
  value = random_password.db.result
}

resource "aws_db_subnet_group" "main" {
  name       = "shopnest-db-subnets"
  subnet_ids = data.aws_subnets.default.ids
}

resource "aws_db_parameter_group" "mysql" {
  name   = "shopnest-mysql80"
  family = "mysql8.0"

  parameter {
    name  = "require_secure_transport"   # reject unencrypted connections
    value = "1"
  }
  parameter {
    name  = "slow_query_log"
    value = "1"
  }
  parameter {
    name  = "long_query_time"
    value = "2"
  }
}

resource "aws_db_instance" "mysql" {
  identifier            = "shopnest-db"
  engine                = "mysql"
  engine_version        = "8.0"
  instance_class        = var.db_instance_class
  allocated_storage     = 20
  max_allocated_storage = 50 # storage autoscaling
  storage_type          = "gp3"
  storage_encrypted     = true # encryption at rest (KMS)

  db_name  = "ecomdb"
  username = "shopadmin"
  password = random_password.db.result

  multi_az               = var.db_multi_az
  db_subnet_group_name   = aws_db_subnet_group.main.name
  vpc_security_group_ids = [aws_security_group.db.id]
  publicly_accessible    = false
  parameter_group_name   = aws_db_parameter_group.mysql.name

  backup_retention_period    = 7
  backup_window              = "20:00-20:30"     # 01:30-02:00 IST
  maintenance_window         = "sun:21:00-sun:21:30"
  auto_minor_version_upgrade = true
  copy_tags_to_snapshot      = true

  enabled_cloudwatch_logs_exports = ["error", "slowquery"]

  deletion_protection       = var.protect_data
  skip_final_snapshot       = !var.protect_data
  final_snapshot_identifier = var.protect_data ? "shopnest-db-final" : null
}
