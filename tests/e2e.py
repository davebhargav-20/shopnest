#!/usr/bin/env python3
# End-to-end test of ShopNest: customer, seller and admin flows (run against a fresh ecomdb).
#   BASE_URL=http://127.0.0.1:8080 python3 tests/e2e.py
import re, sys, http.cookiejar, urllib.request, urllib.parse
import os
B=os.environ.get('BASE_URL','http://127.0.0.1:8080').rstrip('/')+'/'
class S:
    def __init__(s):
        s.cj=http.cookiejar.CookieJar()
        s.op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(s.cj))
    def get(s,p,ref=None):
        r=urllib.request.Request(B+p, headers={'Referer':ref} if ref else {})
        resp=s.op.open(r); return resp.geturl(), resp.read().decode()
    def post(s,p,data,ref=None):
        _,h=s.get(p) if not ref else s.get(ref)
        tok=re.search(r'name="csrf" value="([0-9a-f]+)"',h).group(1)
        data=dict(data); data['csrf']=tok
        body=urllib.parse.urlencode(data, doseq=True).encode()
        r=urllib.request.Request(B+p, data=body, headers={'Referer':B+(ref or p)})
        resp=s.op.open(r); return resp.geturl(), resp.read().decode()
def check(cond,msg):
    print(('PASS ' if cond else 'FAIL ')+msg)
    if not cond: global fails; fails+=1
fails=0
def bad(h): return re.search(r'(Warning|Fatal error|Notice|Deprecated)</b>|Uncaught',h)

# ---- customer
c=S()
u,h=c.post('cart.php',{'action':'add','pid':'7','qty':'2'},ref='product-details.php?id=7')
check('added to your cart' in h, 'guest add to cart')
u,h=c.get('checkout.php'); check('login.php' in u, 'checkout requires login')
u,h=c.post('login.php',{'user_role':'customer','login_id':'rahul@gmail.com','password':'wrong'})
check('Invalid' in h,'wrong password rejected')
u,h=c.post('login.php',{'user_role':'customer','login_id':'rahul@gmail.com','password':'user@123','next':'/checkout.php'})
check(u.endswith('checkout.php'),'customer login -> next=checkout, got '+u)
check('cart-count">2' in h,'cart kept after login')
u,h=c.post('checkout.php',{'name':'Rahul','mobile':'8989898989','address':'Addr','city':'Delhi','state':'Delhi','pincode':'110091','payment':'COD'})
check('order-success.php' in u and 'Order Placed' in h,'place order')
no=re.search(r'(SN\d{13})',h).group(1)
oid=re.search(r'order-details.php\?id=(\d+)',h).group(1)
u,h=c.get('customer/order-details.php?id='+oid)
check(no in h and 'Cancel Order' in h and not bad(h),'order details visible')
u,h=c.post('customer/order-details.php?id='+oid,{'action':'cancel','all':'1'})
check('item(s) cancelled' in h and 'Cancelled' in h,'customer cancel order')
for p in ['customer/dashboard.php','customer/my-orders.php','customer/wishlist.php','customer/profile.php','customer/change-password.php']:
    u,h=c.get(p); check(not bad(h) and 'panel-content' in h, 'page '+p)
u,h=c.post('wishlist-action.php',{'pid':'9','action':'add'},ref='shop.php')
check('Added to your wishlist' in h,'wishlist add')
u,h=c.get('customer/order-details.php?id=1')
check('Review' in h,'review button for delivered item')
u,h=c.post('product-details.php?id=1',{'review':'1','rating':'4','comment':'Updated review'})
check('review has been saved' in h,'review update')
u,h=c.get('customer/order-details.php?id=2'); check('my-orders' in u,'cannot view another customer order')
u,h=c.get('admin/dashboard.php'); check('admin/' not in u,'customer blocked from admin -> '+u)
u,h=c.post('customer/profile.php',{'fullname':'Rahul Saxena','mobile':'8989898989','gender':'Male','address':'H-456 Mayur Vihar Phase 1','city':'New Delhi','state':'Delhi','pincode':'110091'})
check('Profile updated' in h,'profile update')
u,h=c.post('customer/change-password.php',{'current':'user@123','newpass':'user@123','confirm':'user@123'})
check('changed successfully' in h,'change password')
u,h=c.post('contact.php',{'name':'T','email':'t@t.com','message':'hello'})
check('Thanks for contacting' in h,'contact form')

# ---- new customer signup + stock check
n=S()
u,h=n.post('signup.php',{'fullname':'Test User','email':'test@x.com','mobile':'9000000001','password':'abc123','cpassword':'abc123'})
check('account has been created' in h,'customer signup')
u,h=n.post('cart.php',{'action':'add','pid':'18'},ref='shop.php')
check('out of stock' in h,'out of stock blocked')
u,h=n.post('cart.php',{'action':'add','pid':'15','qty':'10'},ref='shop.php')
check('Only 3 unit' in h,'qty capped to stock')
u,h=n.post('checkout.php',{'name':'Test','mobile':'9000000001','address':'A','city':'B','state':'C','pincode':'123456','payment':'Online'})
check('Order Placed' in h,'online order placed (3 x formal shoes)')
no2=re.search(r'(SN\d{13})',h).group(1)

# ---- seller
s=S()
u,h=s.post('login.php',{'user_role':'seller','login_id':'arjun@shopnest.com','password':'seller@123'})
check('waiting for admin approval' in h,'pending seller blocked')
u,h=s.post('login.php',{'user_role':'seller','login_id':'meera@shopnest.com','password':'seller@123'})
check('seller/dashboard.php' in u and not bad(h),'seller login')
for p in ['seller/manage-products.php','seller/add-product.php','seller/orders.php','seller/orders.php?status=Pending','seller/sales-report.php','seller/seller-profile.php','seller/edit-product.php?id=10','seller/change-password.php']:
    u,h=s.get(p); check(not bad(h) and 'panel-content' in h,'page '+p)
u,h=s.get('seller/orders.php?q='+no2); check(no2 in h,'seller sees new order')
ooid=re.search(r'order-details.php\?id=(\d+)',h).group(1)
u,h=s.get('seller/order-details.php?id='+ooid)
iid=re.search(r'name="item_id" value="(\d+)"',h).group(1)
for st in ['Confirmed','Shipped','Delivered']:
    u,h=s.post('seller/order-details.php?id='+ooid,{'action':'status','item_id':iid,'status':st,'remark':'via test'})
    check('updated to '+st in h,'seller set '+st)
u,h=s.get('seller/order-details.php?id=4'); check('orders.php' in u,'seller cannot open other seller order')
u,h=s.get('seller/edit-product.php?id=1'); check('manage-products' in u,'seller cannot edit other seller product')
u,h=s.post('seller/edit-product.php?id=10',{'name':"Men's Cotton Casual Shirt",'brand':'Peter England','category':'3','status':'Active','price':'2000','mrp':'1799','stock':'50','description':'x'})
check('cannot be more than MRP' in h,'price>MRP validation')

# ---- admin
a=S()
u,h=a.post('login.php',{'user_role':'admin','login_id':'admin','password':'admin@123'})
check('admin/dashboard.php' in u and not bad(h),'admin login')
for p in ['admin/manage-categories.php','admin/manage-products.php','admin/all-orders.php','admin/all-orders.php?status=Delivered','admin/between-dates-reports.php?from=2026-01-01&to=2026-12-31','admin/manage-sellers.php','admin/view-seller.php?id=1','admin/registered-customers.php','admin/view-customer.php?id=1','admin/enquiries.php','admin/about-us.php','admin/contact-us.php','admin/admin-profile.php','admin/change-password.php','admin/order-details.php?id=1']:
    u,h=a.get(p); check(not bad(h) and 'panel-content' in h,'page '+p)
u,h=a.post('admin/manage-sellers.php',{'sid':'3','status':'Active'}); check('approved' in h,'approve seller')
u,h=a.post('admin/manage-categories.php',{'action':'add','name':'Books','description':'All books','icon':'fa-book'}); check('Category added' in h,'add category')
u,h=a.post('admin/manage-categories.php',{'action':'delete','id':'1'}); check('has products' in h,'protect category with products')
u,h=a.post('admin/manage-products.php',{'action':'toggle_status','pid':'2'}); check('status changed' in h,'toggle product')
u,h=a.get('shop.php?q=Redmi'); check('No products match' in h,'inactive product hidden')
a.post('admin/manage-products.php',{'action':'toggle_status','pid':'2'})
u,h=a.post('admin/order-details.php?id=4',{'action':'status','item_id':'6','status':'Confirmed','remark':''}); check('Item status updated' in h,'admin update item')
u,h=a.post('admin/enquiries.php',{'action':'read','id':'1'}); check('Marked as read' in h,'enquiry read')
u,h=a.get('admin/between-dates-reports.php?from=2026-01-01&to=2026-12-31'); check('Sales Amount' in h,'report')
# approved seller can now login
s3=S(); u,h=s3.post('login.php',{'user_role':'seller','login_id':'arjun@shopnest.com','password':'seller@123'}); check('seller/dashboard.php' in u,'approved seller login')
# csrf
try:
    urllib.request.urlopen(urllib.request.Request(B+'contact.php',data=b'name=x&email=a@b.c&message=y')); check(False,'csrf')
except urllib.error.HTTPError as e: check(e.code==400,'csrf rejected')
print('FAILS',fails)
sys.exit(1 if fails else 0)
