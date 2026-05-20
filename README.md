# Personal Finance Management System

A comprehensive web-based personal finance management application built with Native PHP and MySQL.  
This application helps users manage personal finances efficiently, including expense tracking, budgeting, investment monitoring, installment management, and emergency fund planning.

---

##  Recent Updates (Major Redesign)

- **Modern UI/UX**: Completely redesigned interface with a sleek, minimalist responsive sidebar and 'Plus Jakarta Sans' typography.
- **Enhanced Reporting**: Comprehensive financial reports integrating transactions, emergency funds, installments, and budgets.
- **Multi-Currency Support**: Real-time exchange rate conversion for assets and accounts (USD, EUR, etc.) via external API.
- **Advanced Analytics**: Expense prediction for the next month using Simple Linear Regression (Machine Learning) and automated saving advice.
- **Enforced Crypto Workflow**: Specialized buy/sell flow for crypto requiring USDT, with automated USDT balance deduction.
- **Dependency Management**: Now using **Composer** for better library management.
- **Advanced Export**: Improved PDF export using `dompdf` and Excel export functionality.

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
- Asset price monitoring (IHSG, Gold, DXY, Crypto, Fiat)
- **Interactive Charts**: Real-time TradingView integration for IHSG, Gold, and Crypto (Binance source)
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
- PDF export (powered by `dompdf`)
- Transaction filtering and summaries
- **Advanced Analytics**: Expense prediction based on historical data (Linear Regression)
- **Multi-Currency**: Automatic conversion for foreign assets and bank accounts
- **Automated Portfolio**: Automatic balance deduction for cross-asset transactions (e.g., Crypto-USDT)

---

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP Native 8.x |
| Database | MySQL |
| Frontend | HTML5, CSS3 (Vanilla), JavaScript |
| Styling | Custom Modern CSS (Responsive) |
| Dependencies | Composer |
| Libraries | dompdf, TCPDF, phpspreadsheet |

---

## Project Structure

```bash
keuangan/
├── vendor/             # Composer dependencies (ignored by Git)
├── composer.json       # Project dependencies
├── dashboard.php       # Main dashboard
├── login.php           # Authentication
├── register.php
├── config/             # System configuration
├── classes/            # Business logic (OOP)
├── includes/           # Layout components (Header, Sidebar, Footer)
└── pages/              # Module-specific pages
```

---

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- [Composer](https://getcomposer.org/) (Required)
- Apache / Nginx / Laragon / XAMPP

### Steps

#### 1. Clone Repository
```bash
git clone https://github.com/HilmiCy/personal-finance.git
cd personal-finance
```

#### 2. Install Dependencies
This project uses Composer to manage libraries. Run the following command:
```bash
composer install
```

#### 3. Configure Database
Update your database credentials in:
`config/database.php`

#### 4. Import Database Schema
Import the provided SQL schema into your MySQL database.

#### 5. Run Application
Open the project via your local server (e.g., `http://localhost/keuangan`) or use PHP built-in server:
```bash
php -S localhost:8000
```

---

## Security Features
- Password hashing using `password_hash()` (bcrypt)
- Session-based authentication
- Prepared statements for SQL injection prevention
- Input sanitization for XSS protection

---

## Author
**Fadhil Cahya Hilmi**  
GitHub: [@HilmiCy](https://github.com/HilmiCy)

---

## License
This project is licensed under the MIT License. See [License.md](License.md) for details.
