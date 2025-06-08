# Pinoy Flask Shop

A full-featured e-commerce web application built with PHP, MySQL, and Bootstrap. This project demonstrates a complete online shopping system with both customer and admin interfaces.

## Features

### Customer Features
- User Authentication
  - Registration with email verification
  - Login/Logout functionality
  - Password reset via email
  - Profile management
- Shopping Experience
  - Product browsing and search
  - Category filtering
  - Shopping cart management
  - Checkout process
- Order Management
  - Order placement
  - Order history
  - Order status tracking
  - Order cancellation
- Account Management
  - Profile updates
  - Address management
  - Password changes

### Admin Features
- Dashboard
  - Sales overview
  - Recent orders
  - User statistics
- Product Management
  - Add/Edit/Delete products
  - Product image upload
  - Stock management
  - Category management
- Order Management
  - View all orders
  - Update order status
  - Order details
- User Management
  - View all users
  - User verification
  - User deactivation/deletion
- Settings
  - Profile settings
  - Password management

## Technical Features
- Responsive design using Bootstrap 5
- Modern UI with monochrome theme
- Font Awesome icons
- Google Fonts (Poppins)
- Secure password handling
- SQL injection prevention
- Input validation
- Session management
- File upload handling

## Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependencies)
- SMTP server for email functionality

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/Pinoy-flaskSHOP.git
```

2. Set up the database:
- Create a new MySQL database
- Import the database schema from `setup_database.php`

3. Configure the application:
- Copy `config/database.example.php` to `config/database.php`
- Update database credentials in `config/database.php`
- Configure email settings in `config/email.php`

4. Install dependencies:
```bash
composer install
```

5. Set up the web server:
- Point your web server to the project directory
- Ensure the `uploads` directory is writable

6. Create an admin account:
- Run `create_admin.php` to set up the initial admin user

## Directory Structure
```
Pinoy-flaskSHOP/
├── admin/              # Admin panel files
├── assets/            # CSS, JS, and images
├── config/            # Configuration files
├── includes/          # Common PHP includes
├── uploads/           # Uploaded product images
├── vendor/            # Composer dependencies
└── *.php             # Main application files
```

## Security Features
- Password hashing using PHP's password_hash()
- Email verification for new accounts
- SQL injection prevention using prepared statements
- XSS protection through output escaping
- CSRF protection in forms
- Secure session handling

## Contributing
This is a school project, but suggestions and improvements are welcome. Please feel free to submit issues or pull requests.

## License
This project is created for educational purposes.

## Credits
- Bootstrap 5 for the frontend framework
- Font Awesome for icons
- Google Fonts for typography
- PHP and MySQL for backend functionality

## Author
Kathrina Krizel Loria - HCI Final School Project

## Acknowledgments
- Thanks to all the open-source projects that made this possible
- Special thanks to the PHP and MySQL communities for their excellent documentation 
