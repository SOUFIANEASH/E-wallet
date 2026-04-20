# PayVault – E-Wallet PHP/MySQL

## Project structure
```
ewallet/
├── index.php               → Redirects to login or dashboard
├── dashboard.php           → Balance, stats, recent transactions
├── transfer.php            → Send money to another user
├── add_funds.php           → Record income or expense
├── transactions.php        → Full history with filters & pagination
├── categories.php          → Manage income/expense categories
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/
│   ├── db.php              → PDO connection
│   ├── auth.php            → Session helpers
│   └── layout.php          → Shared layout/header/footer
├── assets/
│   └── style.css
└── sql/
    └── ewallet.sql         → Database schema
```

## Setup (XAMPP / WAMP / MAMP / Laragon)

### 1. Import the database
- Open phpMyAdmin → New → Create database `ewallet`
- Import `sql/ewallet.sql`

### 2. Place the project
- Copy the `ewallet/` folder to your server root:
  - XAMPP:   `C:\xampp\htdocs\ewallet\`
  - WAMP:    `C:\wamp64\www\ewallet\`
  - MAMP:    `/Applications/MAMP/htdocs/ewallet/`
  - Laragon: `C:\laragon\www\ewallet\`

### 3. Configure database credentials
Edit `config/db.php`:
```php
define('DB_USER', 'root');
define('DB_PASS', '');  // your MySQL password
```

### 4. Open in browser
```
http://localhost/ewallet/
```

### 5. Register your first account
Go to `/auth/register.php`, create an account — default categories are auto-created.

---

## Features
- ✅ User registration & login (bcrypt passwords)
- ✅ Dashboard with balance, monthly stats
- ✅ Add income / expense with categories
- ✅ Send money to other users (atomic DB transactions)
- ✅ Transaction history with filters + pagination
- ✅ Category management (add, edit, delete, color)
- ✅ Flash messages, form validation
- ✅ PDO prepared statements (SQL injection safe)
- ✅ Session-based authentication

## Security notes
- All queries use PDO prepared statements
- Passwords hashed with `password_hash(PASSWORD_DEFAULT)`
- All user input is `htmlspecialchars()` escaped in output
- Transfer uses `beginTransaction()` + `rollBack()` for atomicity
- Category ownership validated before any mutation
