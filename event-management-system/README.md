# Event Management System

A comprehensive event management system built with PHP, MySQL, JavaScript, and CSS. The system allows users to create events, book venues, purchase tickets, and provides administrators with tools to manage events, venues, and analyze bookings.

## Features

### User Features
- User registration and authentication
- Create and manage events
- Book venues for events
- Purchase tickets for events
- View event history and bookings

### Admin Features
- Approve/reject event submissions
- Manage venues (add/edit/delete)
- Manage users
- View booking analytics with visual charts
- Monitor ticket sales and revenue

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser
- SMTP server for email notifications

## Installation

1. Clone the repository to your web server directory:
```bash
git clone https://github.com/yourusername/event-management-system.git
```

2. Create a MySQL database and import the schema:
```bash
mysql -u your_username -p your_database_name < sql/database.sql
```

3. Configure the database connection in `config/db.php`:
```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'your_database_name');
```

4. Set up your web server to point to the project directory.

5. Configure your SMTP settings for email notifications (if required).

6. Access the application through your web browser.

## Default Admin Account

- Username: admin
- Password: admin123

## Directory Structure

```
event-management-system/
│
├── config/
│   └── db.php                # Database configuration
│
├── assets/
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   ├── js/
│   │   └── script.js         # JavaScript functions
│   └── images/               # Image assets
│
├── auth/
│   ├── login.php             # Login page
│   ├── register.php          # Registration page
│   ├── logout.php            # Logout handler
│   └── auth_check.php        # Authentication functions
│
├── user/
│   ├── dashboard.php         # User dashboard
│   ├── create_event.php      # Event creation
│   ├── book_venue.php        # Venue booking
│   ├── book_ticket.php       # Ticket booking
│   └── view_events.php       # Event listing
│
├── admin/
│   ├── dashboard.php         # Admin dashboard
│   ├── approve_event.php     # Event approval
│   ├── manage_users.php      # User management
│   ├── manage_tickets.php    # Ticket management
│   └── manage_venues.php     # Venue management
│
├── includes/
│   ├── header.php           # Common header
│   └── footer.php           # Common footer
│
└── sql/
    └── database.sql         # Database schema
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements for SQL queries
- Input validation and sanitization
- Session-based authentication
- Role-based access control

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the GitHub repository or contact the maintainers. 