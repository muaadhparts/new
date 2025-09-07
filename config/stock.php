<?php

return [
    'remote_disk' => env('STOCK_REMOTE_DISK', 's3'),
    'remote_path' => env('STOCK_REMOTE_PATH', 'stock/stock.dbf'),
    'local_path'  => env('STOCK_LOCAL_PATH',  'stock/import/stock.dbf'),
    'encoding'    => env('STOCK_DBF_ENCODING', 'CP1256'),
    'chunk'       => (int) env('STOCK_IMPORT_CHUNK', 1000),

    // مفاتيح upsert (تتأكد ما يصير تكرار لنفس الصنف في نفس الفرع)
    'unique_by'   => ['fitem', 'fbranch'],

    /**
     * خرائط أسماء الحقول: لو الملف DBF/CSV يستخدم أسماء مختلفة
     * نحدد المرادفات هنا، والمستورد يختار أول الموجود.
     */
    'field_map'   => [
        'fitem'   => ['FITEM', 'ITEM', 'PART', 'PARTNO', 'PNO'],
        'fdesc'   => ['FDESC', 'DESC', 'DESCRIPTION', 'NAME', 'LABEL'],
        'fbranch' => ['FBRANCH', 'BRANCH', 'STORE', 'WAREHOUSE', 'BR'],
        'fqty'    => ['FQTY', 'QTY', 'QTYONHAND', 'ONHAND', 'BALANCE'],
        'fprice'  => ['FPRICE', 'PRICE', 'SELLPRICE', 'RETAIL', 'UNITPRICE'],
    ],
];
