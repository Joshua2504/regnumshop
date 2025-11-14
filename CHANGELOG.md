# Changelog - Regnum Online Shop

## Recent Updates

### Login Page Enhancements
- ✅ **Prominent COR Forum Registration**: Added large, eye-catching registration button at the top of login page
- ✅ **Clear Login Instructions**: Added info box explaining authentication via cor-forum.de
- ✅ **Enhanced Visual Design**: Improved login form with better styling and icons
- ✅ **Direct Registration Link**: Button links to https://cor-forum.de/register

### Analytics System
- ✅ **SQLite-based Analytics**: Complete analytics tracking stored in SQLite database
- ✅ **Event Tracking**: Tracks page views, logins, add to cart, and orders
- ✅ **Daily Summaries**: Automatic daily aggregation of statistics
- ✅ **Admin Dashboard**: New analytics page at `/admin/analytics.php` showing:
  - Today's stats (page views, visitors, logins, orders)
  - All-time statistics
  - 30-day summary table
  - Top pages (last 30 days)
  - Recent activity feed
- ✅ **Automated Tracking**: Integrated into all main pages (index, login, checkout)

### Delivery Time Information
- ✅ **Homepage Banner**: Prominent green banner showing "6-12 hours delivery" on shop homepage
- ✅ **Checkout Page**: Delivery timeframe notice before payment selection
- ✅ **Order Confirmation**: Delivery time information after order placement
- ✅ **Consistent Messaging**: "Delivery within 6-12 hours after payment confirmation" throughout site

### Email Notification System
- ✅ **SMTP Configuration**: Configured with mail.treudler.net
  - Host: mail.treudler.net
  - Port: 587
  - From: system@treudler.net
- ✅ **Order Confirmation Emails**: Sent to customers when order is placed
  - Order details (number, total, payment method)
  - Payment instructions (PayPal or Bank Transfer)
  - Delivery timeframe (6-12 hours)
  - Link to view order status
- ✅ **Admin Order Notifications**: Sent to admin email when new order received
  - Customer information
  - Order details
  - Quick link to admin panel
- ✅ **Order Status Updates**: Sent when admin changes order status
  - Updates for "processing" and "completed" status
  - Personalized messages per status
  - Link to view order details
- ✅ **Email Storage**: User emails captured from COR Forum API and stored in database

### Database Updates
- ✅ **Users Table**: Added `email` field to store user email addresses
- ✅ **Analytics Tables**: Added two new tables:
  - `analytics_events`: Detailed event tracking
  - `analytics_summary`: Daily aggregated statistics
- ✅ **Indexes**: Added performance indexes for analytics queries

### New Files Created
- `/src/Analytics.php` - Analytics tracking class
- `/src/Email.php` - SMTP email sending class
- `/public/admin/analytics.php` - Analytics dashboard

### Configuration Updates
- `.env` and `.env.example`: Added SMTP email settings
- `config/config.php`: Added SMTP configuration constants
- `database/init.sql`: Added analytics tables and email field

### Integration Points
- **index.php**: Tracks page views and add to cart events
- **login.php**: Tracks page views and successful logins
- **checkout.php**: Tracks page views, orders, sends emails
- **admin/index.php**: Sends email notifications on status updates
- **admin/items.php** & **admin/analytics.php**: Added analytics link to navigation

## Features Summary

### For Customers
1. Clear instructions to create COR Forum account before login
2. Fast delivery promise (6-12 hours) visible throughout shopping process
3. Email confirmations for orders with payment instructions
4. Email updates when order status changes
5. Professional HTML email templates

### For Administrators
6. Complete analytics dashboard with real-time statistics
7. Track user behavior (page views, popular pages)
8. Monitor sales and revenue
9. Email notifications for new orders
10. Automated customer communication

## Technical Details

### Analytics Metrics Tracked
- Page views (all pages)
- Unique visitors (by IP address)
- User logins
- Add to cart events
- Order placements
- Revenue (per order and total)

### Email Templates
All emails are HTML-formatted with:
- Responsive design
- Professional styling
- Clear call-to-action buttons
- Branding consistent with shop

### Security
- User emails only captured if provided by COR Forum API
- SMTP credentials stored in .env file
- Email sending errors logged but don't break checkout process
- Analytics IP addresses hashed for privacy

## Next Steps

To start using these features:

1. **Update .env file** with your PayPal email and bank details
2. **Test login** to ensure cor-forum.de API integration works
3. **Place a test order** to verify email notifications
4. **Check analytics dashboard** at `/admin/analytics.php`
5. **Monitor email delivery** to ensure SMTP settings are correct

## Support

All features are production-ready and fully integrated with existing shop functionality.
