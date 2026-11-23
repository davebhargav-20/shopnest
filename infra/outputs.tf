output "site_url" {
  value = var.domain_name != "" ? "https://${var.domain_name}" : "http://${aws_eip.web.public_ip}"
}
output "public_ip"          { value = aws_eip.web.public_ip }
output "instance_id"        { value = aws_instance.web.id }
output "ecr_repository_url" { value = aws_ecr_repository.app.repository_url }
output "github_role_arn"    { value = aws_iam_role.github_deploy.arn }
output "rds_endpoint"       { value = aws_db_instance.mysql.address }
output "backup_bucket"      { value = aws_s3_bucket.backups.bucket }
