# API Routes Reference

## Web API endpoints (`routes/web.php`)

- `/api/search/part` - Part number search
- `/api/search/vin` - VIN decode/search
- `/api/vehicle/suggestions` - Vehicle search autocomplete
- `/modal/catalog-item/{key}` - Catalog item quick view modal

## REST API (`routes/api.php`)

- `/api/specs/*` - Specification filtering
- `/api/catalog-item/alternatives/{sku}` - Catalog item alternatives
- `/api/catalog-item/compatibility/{sku}` - Fitment data
- `/api/shipping/tryoto/*` - Shipping options
- `/api/user/*` - User authentication and profile
- `/api/front/purchasetrack` - Purchase tracking
