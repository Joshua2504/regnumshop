# Quick Start Guide

## Get Up and Running in 3 Steps

### 1. Configure Payment Details

Edit [.env](.env) and update:

```bash
# Your PayPal email for receiving payments
PAYPAL_EMAIL=your-paypal-email@example.com

# Your bank details for bank transfers
BANK_NAME=Your Bank Name
BANK_ACCOUNT_HOLDER=Your Name
BANK_IBAN=DE89370400440532013000
BANK_BIC=COBADEFFXXX

# IMPORTANT: Change admin password!
ADMIN_PASSWORD=your-secure-password
```

### 2. Start the Shop

```bash
docker-compose up -d
```

### 3. Access Your Shop

- **Shop Frontend**: http://localhost:8080
- **Admin Panel**: http://localhost:8080/admin
  - Default username: `admin`
  - Default password: `admin123` (change this in `.env`!)

## What's Next?

### Add Your Items

1. Go to http://localhost:8080/admin
2. Login with admin credentials
3. Click "Items" in the navigation
4. Click "Add New Item"
5. Fill in:
   - Name (e.g., "1000 Gold Coins")
   - Description
   - Price in EUR
   - Image URL (use any public image URL)
   - Stock quantity
6. Check "Active" to make it visible
7. Click "Create Item"

### Test the Shop

1. Go to http://localhost:8080
2. Click "Login"
3. Use your cor-forum.de credentials
4. Add items to cart
5. Proceed to checkout
6. Choose payment method
7. View order in "My Orders"

### Manage Orders

1. Go to http://localhost:8080/admin
2. Click "Orders"
3. Click "Manage" on any order
4. Update payment status when payment received
5. Update order status as you process it
6. Add notes if needed

## Common Tasks

### View Logs
```bash
docker-compose logs -f
```

### Stop the Shop
```bash
docker-compose down
```

### Restart After Config Changes
```bash
docker-compose restart
```

### Reset Database
```bash
docker-compose down
rm database/shop.db
docker-compose up -d
```

## Default Sample Items

The shop comes with 5 sample items:
- Gold Pack - 1000 (€9.99)
- Gold Pack - 5000 (€39.99)
- Gold Pack - 10000 (€69.99)
- Premium Mount (€24.99)
- Rare Weapon Skin (€14.99)

You can edit or delete these from the admin panel.

## Need Help?

Check the full [README.md](README.md) for detailed documentation.

---

**Your shop is ready! Start selling Regnum Online items now!**
