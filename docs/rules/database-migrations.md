# Database Schema & Migrations Rules

## Folder Structure

```
database/
├── migrations/     # ← ALL database changes go here (Laravel migrations)
└── schema/         # ← REFERENCE ONLY (SQL exports for documentation)
```

## `database/schema/` - READ-ONLY REFERENCE

**DO NOT:**
- Use schema files to create tables
- Import SQL files into database
- Modify schema files manually

**DO:**
- Read schema files to understand table structure
- Reference column names, types, and indexes
- Use for documentation purposes

## `database/migrations/` - ALL CHANGES HERE

**ALL database modifications MUST use Laravel migrations:**

```bash
# Add new table
php artisan make:migration create_table_name_table

# Add column to existing table
php artisan make:migration add_column_to_table_name

# Modify column
php artisan make:migration modify_column_in_table_name

# Run migrations
php artisan migrate
```

## Migration Examples

**Adding a new column:**
```php
Schema::table('purchases', function (Blueprint $table) {
    $table->string('new_column')->nullable()->after('existing_column');
});
```

**Creating a new table:**
```php
Schema::create('new_table', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

## Database Safety Rules

### ABSOLUTELY FORBIDDEN (even if user requests it):
- DROP DATABASE
- DROP TABLE (with data)
- TRUNCATE TABLE
- DELETE FROM table (without WHERE)

### When renaming/replacing tables:
1. Create the new table structure
2. Migrate data from old table to new table
3. Rename old table with `_old` suffix (e.g., coupons -> coupons_old)
4. NEVER delete the _old table - keep it for safety/rollback

**Example:**
```sql
-- Step 1: Create new table
CREATE TABLE discount_codes (...);

-- Step 2: Migrate data
INSERT INTO discount_codes SELECT * FROM coupons;

-- Step 3: Rename old table (don't delete!)
RENAME TABLE coupons TO coupons_old;
```

## Table Rename Methodology

For complete table rename instructions, see: **docs/standards/TABLE_RENAME_METHODOLOGY.md**

The methodology covers all 9 steps:
1. Database Migration
2. Model Updates
3. Controller Updates
4. Helper Function Updates
5. Route Updates
6. Session Key Updates
7. View Updates
8. JavaScript Updates
9. Permission & Translation Updates
