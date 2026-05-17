# Personal Finance Management System

A comprehensive web-based personal finance management application built with Native PHP and MySQL.  
This application helps users manage personal finances efficiently, including expense tracking, budgeting, investment monitoring, installment management, and emergency fund planning.

---

## Features

### Core Financial Management
- Income and expense tracking
- Multiple account management (cash, bank, e-wallet)
- Transaction categorization
- Budget planning by category
- Transfer tracking between accounts

### Portfolio & Asset Management
- Asset tracking for stocks, crypto, gold, and mutual funds
- Buy and sell transaction recording
- Asset price monitoring
- Portfolio overview

### Financial Planning
- Emergency fund target management
- Deposit and withdrawal history
- Priority level settings

### Installment Management
- Installment tracking
- Payment scheduling
- Late payment penalty calculation
- Installment payment history

### Reporting & Analytics
- Monthly financial reports
- Excel export
- PDF export
- Transaction filtering

---

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP Native |
| Database | MySQL |
| Frontend | HTML5, CSS3, JavaScript |
| Styling | Custom CSS |
| Export | Excel & PDF Libraries |

---

## Project Structure

```bash
keuangan/
├── dashboard.php
├── login.php
├── register.php
├── logout.php
├── index.php
│
├── assets/
│   ├── css/
│   └── images/
│
├── classes/
│   ├── Database.php
│   ├── User.php
│   ├── Transaction.php
│   ├── Account.php
│   ├── Category.php
│   └── Asset.php
│
├── config/
│   ├── config.php
│   ├── database.php
│   └── session.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── functions.php
│
└── pages/
    ├── transactions/
    ├── accounts/
    ├── categories/
    ├── emergency_fund/
    ├── installments/
    ├── budgets/
    ├── assets/
    └── reports/
```

---

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache / Nginx / XAMPP

### Steps

#### 1. Clone Repository

```bash
git clone https://github.com/penuliscode/keuangan.git
```

#### 2. Move to Project Directory

```bash
cd keuangan
```

#### 3. Configure Database

Create a new MySQL database and update database credentials in:

```bash
config/database.php
```

#### 4. Import Database Schema

Import the SQL schema into your MySQL database.

#### 5. Run Application

Using PHP built-in server:

```bash
php -S localhost:8000
```

Open in browser:

```bash
http://localhost:8000
```

---

## Modules Overview

| Module | Description |
|--------|-------------|
| Dashboard | Financial summary |
| Transactions | Manage income & expenses |
| Accounts | Manage wallets and bank accounts |
| Categories | Transaction categorization |
| Budgets | Monthly budget planning |
| Assets | Investment portfolio tracking |
| Emergency Fund | Emergency savings management |
| Installments | Debt and installment tracking |
| Reports | Financial report generation |

---

## Security Features

- Password hashing using bcrypt
- Session-based authentication
- Prepared statements for SQL injection prevention
- XSS protection
- CSRF protection

---

## Future Enhancements

- Interactive dashboard charts
- Email notification reminders
- Responsive mobile interface
- Multi-currency support
- CSV/Excel import
- Investment analytics
- REST API integration

---

## Author

**Fadhil Cahya Hilmi**

GitHub: `@hilmicy`

---

## License

This project is licensed under the MIT License.  
You are free to use, modify, and distribute this software in accordance with the license terms.

See the [License.md](License.md) file for more information.

---

## Support

If you find this project useful, consider giving it a star on GitHub.
