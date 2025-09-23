# Copilot Instructions for AI Agents

## Project Overview
This is a Laravel-based web application with a modular structure. The codebase includes custom business logic, integrations, and frontend assets. Key directories:
- `app/`: Main application logic (Actions, Models, Services, Helpers, etc.)
- `routes/`: Route definitions for web, API, console, and Livewire
- `resources/views/`: Blade templates for frontend rendering
- `public/`: Public assets and entry point (`index.php`)
- `config/`: Application and service configuration
- `database/`: Migrations, seeders, and factories

## Architecture & Patterns
- **Action Classes**: Business logic is often encapsulated in `app/Actions/` (e.g., `DownloadStock.php`, `ImportStock.php`).
- **Service Classes**: Integrations and reusable logic are in `app/Services/` and `app/Classes/`.
- **Helpers & Traits**: Shared logic is in `app/Helpers/` and `app/Traits/`.
- **Livewire**: Real-time UI components are in `app/Livewire/` and registered in `routes/livewire.php`.
- **Config-Driven**: Many behaviors are controlled via `config/*.php` files.

## Workflows
- **Development**: Use `php artisan serve` to run the local server. Frontend assets are managed with Vite (`vite.config.js`).
- **Testing**: Run tests with `php artisan test` or `vendor/bin/phpunit`.
- **Migrations**: Use `php artisan migrate` for database schema changes.
- **Composer**: Manage PHP dependencies with Composer (`composer install`, `composer update`).

## Conventions
- **Naming**: Follows Laravel conventions for controllers, models, and migrations. Custom logic is grouped by domain (e.g., stock import/export).
- **Environment**: Sensitive settings are in `.env` (not committed). Use `config/*.php` for defaults.
- **Frontend**: JS/CSS assets are in `resources/` and built to `public/assets/`.
- **External Integrations**: Payment and mail integrations are in `app/Classes/` and configured via `config/services.php` and related config files.

## Integration Points
- **Payments**: Custom payment logic in `app/Classes/` (e.g., `Instamojo.php`, `GeniusMailer.php`).
- **Mail**: Mailer logic in `app/Classes/GeniusMailer.php` and configured in `config/mail.php`.
- **Livewire**: Used for dynamic frontend features.

## Examples
- To add a new payment provider, create a class in `app/Classes/`, update `config/services.php`, and wire up routes/controllers as needed.
- To add a new background job, create a job in `app/Jobs/` and dispatch via Laravel's queue system.

## References
- See `README.md` for general Laravel info.
- See `phpunit.xml` for test configuration.
- See `vite.config.js` for frontend build setup.

---

*Update this file as project conventions evolve. Focus on actionable, project-specific guidance for AI agents.*
