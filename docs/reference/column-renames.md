# Column Renames History (2026-01-08)

## Renamed Columns

| Table | Old Column | New Column |
|-------|------------|------------|
| `purchases` | `order_note` | `purchase_note` |
| `purchases` | `riders` | `couriers` |
| `delivery_couriers` | `order_amount` | `purchase_amount` |
| `rewards` | `order_amount` | `purchase_amount` |
| `users` | `admin_commission` | `operator_commission` |

## Renamed Indexes & Constraints

| Table | Old Name | New Name |
|-------|----------|----------|
| `catalog_item_clicks` | `product_clicks_merchant_product_id_index` | `catalog_item_clicks_merchant_item_id_index` |
| `merchant_items` | `mi_product_type` | `mi_item_type` |
| `merchant_credentials` | `vendor_service_key_env_unique` | `merchant_service_key_env_unique` |
| `merchant_credentials` | `vendor_credentials_user_id_index` | `merchant_credentials_user_id_index` |
| `merchant_credentials` | `vendor_credentials_service_name_index` | `merchant_credentials_service_name_index` |
| `merchant_credentials` | `vendor_credentials_user_id_foreign` | `merchant_credentials_user_id_foreign` |
| `merchant_stock_updates` | `vendor_stock_updates_user_id_index` | `merchant_stock_updates_user_id_index` |
| `merchant_stock_updates` | `vendor_stock_updates_status_index` | `merchant_stock_updates_status_index` |
| `merchant_stock_updates` | `vendor_stock_updates_update_type_index` | `merchant_stock_updates_update_type_index` |
| `merchant_stock_updates` | `vendor_stock_updates_user_id_foreign` | `merchant_stock_updates_user_id_foreign` |

## Migration Files

```
database/migrations/2026_01_08_100001_rename_legacy_columns_to_new_names.php
database/migrations/2026_01_08_100002_rename_legacy_indexes_to_new_names.php
```

Run migrations: `php artisan migrate`
