variable "aws_region" {
  description = "AWS region. ap-south-1 = Asia Pacific (Mumbai)"
  type        = string
  default     = "ap-south-1"
}

variable "environment" {
  type    = string
  default = "production"
}

variable "instance_type" {
  description = "EC2 size for the web server"
  type        = string
  default     = "t3.micro"
}

variable "db_instance_class" {
  description = "RDS size for MySQL"
  type        = string
  default     = "db.t4g.micro"
}

variable "db_multi_az" {
  description = "true = standby database in a second AZ (99.95% SLA, about double the DB cost)"
  type        = bool
  default     = false
}

variable "domain_name" {
  description = "Optional domain, e.g. shop.example.com. Empty = plain HTTP on the Elastic IP"
  type        = string
  default     = ""
}

variable "github_repo" {
  description = "GitHub repository allowed to deploy, as owner/name"
  type        = string
}

variable "alert_email" {
  description = "Email that receives CloudWatch alarms and budget alerts"
  type        = string
}

variable "monthly_budget_usd" {
  type    = number
  default = 40
}

variable "protect_data" {
  description = "true = RDS deletion protection + final snapshot. Set false before 'terraform destroy' of a demo"
  type        = bool
  default     = true
}
