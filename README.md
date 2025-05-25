# CTF Room Challenge

A web-based platform for managing Capture The Flag (CTF) competitions, allowing judges to score participants and administrators to manage events.

## Features

- User authentication with role-based access (Admin, Judge, Participant)
- Event management system
- Judge scoring interface
- Real-time scoreboard
- Admin panel for system management
- Responsive Bootstrap-based UI

## Setup Instructions

1. **Prerequisites**

   - PHP 7.4 or higher
   - MySQL 5.7 or higher
   - Apache/Nginx web server
   - Composer (for PHP dependencies)

2. **Installation**

   ```bash
   # Clone the repository
   git clone https://github.com/yourusername/ctfroom_challenge.git
   cd ctfroom_challenge

   # Create database
   mysql -u root -p
   CREATE DATABASE ctfroom;
   exit;

   # Import database schema
   mysql -u root -p ctfroom < database.sql
   ```

3. **Configuration**

   - Copy `config/database.example.php` to `config/database.php`
   - Update database credentials in `config/database.php`
   - Ensure web server has write permissions for session handling

4. **Create Test Accounts**
   - Run `create_test_accounts.php` to create demo accounts:
     - Admin: admin@test.com / admin123
     - Judge: judge@test.com / judge123
     - Participant: participant@test.com / participant123

## Database Schema

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'judge', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE judges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('pending', 'active', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE event_judges (
    event_id INT NOT NULL,
    judge_id INT NOT NULL,
    PRIMARY KEY (event_id, judge_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
);

CREATE TABLE scores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    judge_id INT NOT NULL,
    points INT NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_judge_score (event_id, user_id, judge_id)
);
```

## Design Choices

1. **Database Structure**

   - Normalized database design to minimize data redundancy
   - Foreign key constraints to maintain data integrity
   - Unique constraints to prevent duplicate scores
   - Timestamps for audit trails

2. **Authentication System**

   - Role-based access control (RBAC) for different user types
   - Secure password hashing using PHP's password_hash()
   - Session-based authentication with proper security measures

3. **Code Organization**

   - Separation of concerns with dedicated files for different functionalities
   - Centralized authentication logic in auth.php
   - Reusable database connection in config/database.php

4. **Security Measures**
   - Prepared statements to prevent SQL injection
   - Input validation and sanitization
   - CSRF protection through session tokens
   - XSS prevention through proper output escaping

## Future Improvements

1. **Features**

   - Email verification system
   - Password reset functionality
   - Two-factor authentication
   - Real-time notifications
   - Export scores to CSV/PDF
   - Bulk judge assignment
   - Event categories and tags

2. **Technical Improvements**

   - Implement API endpoints for mobile app integration
   - Add unit tests and integration tests
   - Implement caching for better performance
   - Add logging system for debugging
   - Implement rate limiting for API endpoints

3. **UI/UX Improvements**
   - Dark mode support
   - Advanced filtering and search
   - Interactive charts and graphs
   - Mobile-responsive design improvements
   - Accessibility enhancements

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, please open an issue in the GitHub repository or contact the maintainers.
