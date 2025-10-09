# MUAADH EPC

**AI-Assisted OEM/Aftermarket Parts Catalog** with callout-first search, built on Laravel & Livewire.

---

## About MUAADH EPC

MUAADH EPC is a comprehensive Electronic Parts Catalog (EPC) system designed to streamline automotive parts identification and ordering. The platform combines:

- **Callout-First Search**: Visual parts diagrams for intuitive part identification
- **AI-Powered Matching**: Intelligent search algorithms for accurate part recommendations
- **OEM & Aftermarket Support**: Comprehensive coverage of original and compatible parts
- **Multi-Language Support**: Available in English and Arabic (RTL)
- **Modern Tech Stack**: Built with Laravel 10, Livewire, and modern frontend tools

---

## Key Features

### For Customers
- Visual parts lookup with interactive callout diagrams
- Advanced search with AI-assisted part matching
- Multi-vendor marketplace with competitive pricing
- Real-time inventory tracking
- Secure payment integration (MyFatoorah, Stripe, PayPal, etc.)
- Order tracking and delivery management

### For Vendors
- Comprehensive vendor dashboard
- Product catalog management with bulk upload
- Inventory and pricing controls
- Order processing and fulfillment
- Sales analytics and reporting
- Flexible commission structure

### For Administrators
- Complete system administration
- Multi-theme support (MUAADH OEM, Storefront, Minimal)
- Payment gateway configuration
- Shipping and packaging management
- User and vendor management
- Content management system

---

## Technology Stack

- **Backend**: Laravel 10.x, PHP 8.1+
- **Frontend**: Livewire 2.x, Alpine.js, Blade templates
- **Database**: MySQL/MariaDB
- **Payment**: MyFatoorah, Stripe, PayPal, Razorpay, Mollie
- **Storage**: DigitalOcean Spaces (S3-compatible)
- **API Integration**: Tryoto parts data provider

---

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd new

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install && npm run build

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# DB_DATABASE=your_database
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

---

## Configuration

### Environment Variables
Key configuration in `.env`:
- `APP_NAME="MUAADH EPC"`
- Database credentials
- Payment gateway keys (FATOORAH_API_KEY, etc.)
- Storage settings (DO_ACCESS_KEY_ID, DO_SECRET_ACCESS_KEY)
- API tokens (TRYOTO_REFRESH_TOKEN)

### Theme Selection
Available themes (Admin > Home Page Settings):
- **MUAADH OEM**: Professional OEM parts catalog layout
- **MUAADH Storefront**: E-commerce focused design
- **MUAADH Minimal**: Clean, minimalist interface

---

## Project Structure

```
app/
├── Http/Controllers/    # Application controllers
├── Models/             # Eloquent models
├── Actions/            # Business logic actions (Laravel Actions)
└── Helpers/            # Helper functions

resources/
├── views/
│   ├── frontend/       # Customer-facing views
│   ├── admin/          # Admin panel views
│   ├── vendor/         # Vendor dashboard views
│   └── layouts/        # Layout templates
└── lang/               # Translations (EN, AR)

public/
└── assets/             # Static assets (images, CSS, JS)
```

---

## License & Ownership

**Copyright © 2025 MUAADH. All rights reserved.**

This project is proprietary software. While it incorporates open-source components (Laravel, Livewire, etc.) under their respective licenses, the overall application, customizations, and business logic are proprietary to MUAADH.

### Third-Party Components
This application uses the following open-source software:
- Laravel Framework (MIT License)
- Livewire (MIT License)
- Other dependencies listed in `composer.json` and `package.json`

See individual component licenses for their terms.

---

## Support & Contact

For technical support or inquiries about MUAADH EPC, please contact the development team.

**Built with ❤️ for the automotive industry**
