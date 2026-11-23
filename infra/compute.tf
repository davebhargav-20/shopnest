data "aws_ssm_parameter" "al2023" {
  name = "/aws/service/ami-amazon-linux-latest/al2023-ami-kernel-default-x86_64"
}

locals {
  site_address = var.domain_name != "" ? var.domain_name : ":80"
}

resource "aws_instance" "web" {
  ami                    = data.aws_ssm_parameter.al2023.value
  instance_type          = var.instance_type
  subnet_id              = sort(data.aws_subnets.default.ids)[0]
  vpc_security_group_ids = [aws_security_group.web.id]
  iam_instance_profile   = aws_iam_instance_profile.ec2.name

  metadata_options {
    http_tokens                 = "required" # IMDSv2 only
    http_put_response_hop_limit = 1
  }

  root_block_device {
    volume_type = "gp3"
    volume_size = 20
    encrypted   = true
  }

  user_data = templatefile("${path.module}/user_data.sh.tftpl", {
    aws_region     = var.aws_region
    ecr_image      = aws_ecr_repository.app.repository_url
    db_host        = aws_db_instance.mysql.address
    db_user        = aws_db_instance.mysql.username
    db_name        = aws_db_instance.mysql.db_name
    db_pass_param  = aws_ssm_parameter.db_password.name
    site_address   = local.site_address
    backup_bucket  = aws_s3_bucket.backups.bucket
    compose_b64    = base64encode(file("${path.module}/../deploy/docker-compose.prod.yml"))
    caddyfile_b64  = base64encode(file("${path.module}/../deploy/Caddyfile"))
    deploy_sh_b64  = base64encode(file("${path.module}/../deploy/deploy.sh"))
    backup_sh_b64  = base64encode(file("${path.module}/../deploy/backup.sh"))
  })
  user_data_replace_on_change = true

  tags = { Name = "shopnest-web" }

  depends_on = [aws_cloudwatch_log_group.app, aws_cloudwatch_log_group.proxy]
}

# Fixed public IP that survives stop/start (point your domain's A record here)
resource "aws_eip" "web" {
  instance = aws_instance.web.id
  domain   = "vpc"
  tags     = { Name = "shopnest-web" }
}
