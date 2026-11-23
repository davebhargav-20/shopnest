# ShopNest — Multi-Vendor E-Commerce Website (PHP + MySQL)

Built in the same style as the CRMS project: plain PHP + MySQL (mysqli), one folder per role,
a shared `includes/` folder and a phpMyAdmin SQL dump.

| CRMS                         | ShopNest                       |
|------------------------------|--------------------------------|
| `admin/`                     | `admin/` (admin panel)         |
| `company/` (recruiter)       | `seller/` (shop owners)        |
| `user/` (candidate)          | `customer/` (buyers)           |
| `crmsdb.sql`                 | `ecomdb.sql`                   |
| `tblvacancy`                 | `tblproduct`                   |
| `tblapplyjob`                | `tblorders` + `tblorderitems`  |
| `tblcompany` / `tbluser`     | `tblseller` / `tblcustomer`    |

## Setup on XAMPP (5 minutes)

1. Copy the `shopnest` folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open **http://localhost/phpmyadmin** → **Import** → choose `ecomdb.sql` → **Go**.
   (The file creates the `ecomdb` database itself.)
4. Open **http://localhost/shopnest/**

If your MySQL root user has a password, change it in `includes/dbconnection.php`.
Fonts and icons are bundled in `assets/vendor/`, so the site works without internet.

## Demo logins

| Role     | Login (on `login.php`)  | Password     |
|----------|-------------------------|--------------|
| Admin    | `admin`                 | `admin@123`  |
| Seller   | `ravi@shopnest.com`     | `seller@123` |
| Seller   | `meera@shopnest.com`    | `seller@123` |
| Customer | `rahul@gmail.com`       | `user@123`   |

`arjun@shopnest.com` is a seller waiting for approval. Approve it from **Admin → Sellers**.
Other sample customers (`farha@gmail.com`, `jain@gmail.com`, `ginni@gmail.com`) also use `user@123`.

## Features

**Storefront (root folder)**
- Home page with categories, featured products, deals and new arrivals
- Shop page with search, category filter, price range, in-stock filter, sorting and pagination
- Product page with stock status, Add to Cart / Buy Now, wishlist, ratings and reviews, and similar products
- Session cart (works for guests and is kept after login), quantity update and remove
- Checkout with address and Cash on Delivery or Online payment (demo). Stock is checked and reduced inside a database transaction.
- Customer sign-up, one login page for all three roles, forgot password, About and Contact pages

**Customer panel (`customer/`)**
- Dashboard, order history, order details with a tracking timeline
- Cancel items until the seller confirms them (stock is restored automatically)
- Wishlist, profile with photo and default address, change password

**Seller panel (`seller/`)**
- Seller registration (needs admin approval)
- Dashboard with a 7-day sales chart, low-stock list and recent orders
- Add, edit and delete products with image upload, plus quick stock update
- Orders filtered by status. Each item moves through Pending → Confirmed → Shipped → Delivered (or Cancelled), with a remark.
- Sales report between two dates (printable), shop profile with logo, change password

**Admin panel (`admin/`)**
- Dashboard with counts, monthly sales chart and top sellers
- Categories: add, edit and delete (with Font Awesome icons)
- All products: activate or deactivate, mark as featured, delete, and filter by seller or category
- All orders: filter and search, update any item's status, update payment status
- Sellers: approve, reject, block or unblock, and view their details and sales
- Customers list with order history
- Contact enquiries (mark as read, delete), edit the About Us and Contact Us pages
- Sales report between two dates, admin profile, change password

## How orders work

An order can contain products from several sellers. Each row in `tblorderitems` stores its own
`Status` and `SellerID`, so every seller manages only their own items. The order's overall status
(Pending, Processing, Shipped, Delivered, Partially Delivered, Cancelled) is worked out from its items.
- A COD order is marked **Paid** automatically when all of its items are delivered.
- An online-paid order is marked **Refunded** when all of its items are cancelled.

## Database tables (`ecomdb`)

`tbladmin`, `tblseller`, `tblcustomer`, `tblcategory`, `tblproduct`, `tblorders`, `tblorderitems`,
`tblwishlist`, `tblreview`, `tblpage`, `tblenquiry`. Foreign keys link products to sellers and categories,
orders to customers, and order items to orders and products.

## Security notes (improvements over CRMS)
- Passwords use `password_hash()` / `password_verify()` (bcrypt) instead of `md5()`.
- Every query uses prepared statements (the `db_query()` helper in `includes/functions.php`).
- Every form has a CSRF token, and all output is escaped with `e()`.
- Uploads are checked to be real images (JPG, PNG, GIF or WEBP, up to 2 MB) and get random file names.
- Forgot password checks the registered email together with the mobile number, as most college projects do.
  For production, replace this with an email OTP (CRMS already has PHPMailer in `user/vendor/`).
- "Online payment" is a demo. To take real payments, connect Razorpay or another payment gateway in `checkout.php`.

## Folder structure

```
shopnest/
├── index.php, shop.php, product-details.php, cart.php, checkout.php, order-success.php
├── login.php, signup.php, forgot-password.php, logout.php, about.php, contact.php, wishlist-action.php
├── ecomdb.sql
├── includes/   init.php, dbconnection.php, functions.php, header.php, footer.php,
│               panel-header.php, panel-footer.php, product-card.php, order-view.php,
│               sales-report.php, change-password-form.php
├── admin/      dashboard, manage-categories, manage-products, all-orders, order-details,
│               manage-sellers, view-seller, registered-customers, view-customer, enquiries,
│               about-us, contact-us, between-dates-reports, admin-profile, change-password, logout
├── seller/     seller-signup, dashboard, add-product, edit-product, manage-products, orders,
│               order-details, sales-report, seller-profile, change-password, logout
├── customer/   dashboard, my-orders, order-details, wishlist, profile, change-password, logout
├── assets/     css/style.css, vendor/ (fonts + Font Awesome)
└── images/     products/, sellers/, customers/ (uploads go here)
```

---

## Cloud deployment (AWS, ap-south-1 Mumbai)

The project ships ready for the cloud. The full report (costing, CI/CD, security, SLA) is in the
"ShopNest — Cloud Deployment Project Report" doc; this is the short version.

| Path | What it is |
|------|------------|
| `Dockerfile`, `docker/` | PHP 8.3 + Apache image, security headers, first-run import of `ecomdb.sql` |
| `docker-compose.yml` | Run locally with Docker: `docker compose up --build` → http://localhost:8080 |
| `deploy/` | Production compose file (app + Caddy HTTPS proxy), `deploy.sh` with auto-rollback, nightly `backup.sh` |
| `infra/` | Terraform: EC2, Elastic IP, RDS MySQL, ECR, S3, IAM (GitHub OIDC), CloudWatch alarms, Budget |
| `.github/workflows/ci-cd.yml` | Lint → test (68 e2e checks) → build + Trivy scan → push to ECR → deploy via SSM → smoke test |
| `tests/e2e.py` | End-to-end tests: `BASE_URL=http://127.0.0.1:8080 python3 tests/e2e.py` |
| `health.php` | Health endpoint: `{"status":"ok","db":"up"}` |

Database settings now come from environment variables (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`,
`DB_SSL`); with none set it still uses XAMPP's `localhost` / `root` / no password.

**Go live in short:**
1. Push this folder to a new GitHub repo (`main` branch).
2. `cd infra && cp terraform.tfvars.example terraform.tfvars` → set `github_repo`, `alert_email`.
3. `terraform init && terraform apply` (region ap-south-1; about 10 minutes).
4. In GitHub → Settings → Secrets and variables → Actions → **Variables**, add
   `AWS_ROLE_ARN`, `EC2_INSTANCE_ID`, `SITE_URL` from `terraform output`.
5. Run the CI/CD workflow. When it is green, open `SITE_URL`.
6. After grading: set `protect_data = false`, `terraform apply`, `terraform destroy`.

Estimated cost of the starter setup: about **$31 a month** (≈ ₹2,770 + GST); new AWS accounts get up to $200 in Free Tier credits.
