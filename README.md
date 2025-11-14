# Regnum Online Shop

A simple PHP-based online shop for Regnum Online in-game items. Users can login with their cor-forum.de account, browse items, make purchases via PayPal or bank transfer, and view their order history.

## Features

- **User Authentication**: Login using cor-forum.de API credentials
- **Item Browsing**: View available in-game items with images and prices
- **Shopping Cart**: Add items to cart and manage quantities
- **Checkout**: Place orders with PayPal or Bank Transfer payment options
- **Order History**: Users can view their past orders and status
- **Admin Panel**: Manage orders and items (CRUD operations)
- **Responsive Design**: Built with Bootstrap 5 for mobile-friendly experience
- **Docker Support**: Easy deployment with Docker Compose

## Technology Stack

- **Backend**: PHP 8.3 with SQLite database
- **Frontend**: Bootstrap 5, vanilla JavaScript
- **Server**: Nginx + PHP-FPM
- **Deployment**: Docker & Docker Compose

## Prerequisites

- Docker and Docker Compose installed
- cor-forum.de API access (API key configured)

## Installation

1. **Clone or download this repository**

2. **Configure environment variables**

   Edit the `.env` file and update the following:
   ```
   # Payment Settings
   PAYPAL_EMAIL=your-paypal-email@example.com

   # Bank Transfer Details
   BANK_NAME=Your Bank Name
   BANK_ACCOUNT_HOLDER=Your Name
   BANK_IBAN=DE89370400440532013000
   BANK_BIC=COBADEFFXXX

   # Admin Panel (change this password!)
   ADMIN_USERNAME=admin
   ADMIN_PASSWORD=admin123
   ```

3. **Start the Docker containers**

   ```bash
   docker-compose up -d
   ```

4. **Access the shop**

   Open your browser and navigate to:
   - Shop: http://localhost:8080
   - Admin Panel: http://localhost:8080/admin

## Default Credentials

### Admin Panel
- **Username**: admin
- **Password**: admin123

**IMPORTANT**: Change the admin password in the `.env` file after first login!

### User Login
Users login with their cor-forum.de credentials via the API.

## Directory Structure

```
regnumshop/
├── docker-compose.yml          # Docker stack configuration
├── Dockerfile                  # PHP container configuration
├── nginx.conf                  # Nginx web server configuration
├── .env                        # Environment variables
├── .env.example                # Environment template
├── config/
│   └── config.php             # Application configuration
├── database/
│   ├── init.sql               # Database schema
│   └── shop.db                # SQLite database (auto-created)
├── public/                     # Web root
│   ├── index.php              # Shop homepage
│   ├── login.php              # User login
│   ├── logout.php             # User logout
│   ├── cart.php               # Shopping cart
│   ├── checkout.php           # Checkout page
│   ├── orders.php             # User order history
│   ├── admin/                 # Admin panel
│   │   ├── index.php          # Order management
│   │   ├── items.php          # Item management
│   │   └── logout.php         # Admin logout
│   └── assets/
│       ├── css/style.css      # Custom styles
│       └── js/main.js         # JavaScript utilities
└── src/                       # PHP classes
    ├── Database.php           # Database connection
    ├── Auth.php               # Authentication
    ├── Session.php            # Session management
    ├── Item.php               # Item model
    ├── Order.php              # Order model
    ├── Cart.php               # Shopping cart
    └── helpers.php            # Helper functions
```

## Usage

### For Customers

1. **Browse Items**: Visit the shop homepage to see available items
2. **Login**: Click "Login" and use your cor-forum.de credentials
3. **Add to Cart**: Select items and add them to your cart
4. **Checkout**: Proceed to checkout and choose payment method
5. **Payment**: Follow the payment instructions (PayPal or Bank Transfer)
6. **Track Orders**: View your order status in "My Orders"

### For Administrators

1. **Login**: Go to http://localhost:8080/admin and login
2. **Manage Orders**:
   - View all orders
   - Update payment status (pending/paid/failed)
   - Update order status (pending/processing/completed/cancelled)
   - Add notes to orders
3. **Manage Items**:
   - Add new items with name, description, price, image URL, and stock
   - Edit existing items
   - Delete items
   - Toggle active/inactive status

## Database Schema

### Tables

- **users**: Store user information from cor-forum.de
- **sessions**: User session management
- **items**: Store items for sale
- **orders**: Store order information
- **order_items**: Store items in each order
- **admin_users**: Admin user accounts

## Payment Workflow

1. User places an order
2. Order is created with status "pending"
3. User receives payment instructions:
   - **PayPal**: Send payment to configured PayPal email
   - **Bank Transfer**: Transfer to configured bank account
4. Admin receives payment and updates order status
5. Admin marks payment as "paid" and order as "processing"
6. After delivery, admin marks order as "completed"

## Security Features

- SQL injection prevention (prepared statements)
- XSS protection (output escaping)
- CSRF tokens for forms
- Session hijacking prevention
- Secure password handling for admin
- Database file access blocked via Nginx

## Customization

### Adding Items

1. Login to admin panel
2. Go to "Items" section
3. Click "Add New Item"
4. Fill in details (use image URLs from external sources)
5. Set stock and price
6. Activate the item

### Changing Payment Details

Edit the `.env` file:
```
PAYPAL_EMAIL=your-new-email@example.com
BANK_IBAN=YOUR_NEW_IBAN
```

Restart the containers:
```bash
docker-compose restart
```

## Troubleshooting

### Database Issues

If the database is corrupted, delete and recreate:
```bash
docker-compose down
rm database/shop.db
docker-compose up -d
```

### Permission Issues

Ensure the database directory is writable:
```bash
chmod 755 database/
```

### Can't Login

1. Check that the cor-forum.de API is accessible
2. Verify the API key in `.env` is correct
3. Check Docker logs: `docker-compose logs php`

### Admin Panel Not Loading

1. Check you're using correct credentials (default: admin/admin123)
2. Clear browser cookies/cache
3. Check `.env` file has correct admin credentials

## Development

### Stopping the Shop

```bash
docker-compose down
```

### Viewing Logs

```bash
docker-compose logs -f
```

### Rebuilding Containers

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## Production Deployment

Before deploying to production:

1. **Change admin password** in `.env`
2. **Update payment details** with real PayPal email and bank info
3. **Disable error display** in `config/config.php`:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```
4. **Use HTTPS**: Configure SSL certificate in Nginx
5. **Set secure session cookies** in Dockerfile:
   ```
   session.cookie_secure = 1
   ```
6. **Regular backups** of `database/shop.db`
7. **Update COR API key** if needed

## License

This project is provided as-is for use with Regnum Online.

## Support

For issues or questions, please check:
- Docker logs: `docker-compose logs`
- Database: Verify `database/shop.db` exists
- API connectivity: Test cor-forum.de API access

---

**Built for Regnum Online Community**
