import pymysql
import re


# إعداد الاتصال بقاعدة البيانات
connection = pymysql.connect(
    host='127.0.0.1',
    user='root',
    database='autoparts',
    charset='utf8mb4',
)
create_tables_sql = [
    """<user pasted SQL code here>""",
]




]

with connection.cursor() as cursor:
    for i, statement in enumerate(create_tables_sql):
        # استخراج اسم الجدول
        match = re.search(r'CREATE TABLE IF NOT EXISTS\s+`?(\w+)`?', statement, re.IGNORECASE)
        table_name = match.group(1) if match else f"Table_{i+1}"

        # استخراج الأعمدة
        raw_columns = re.findall(r'\n\s*`?(\w+)`?\s+[\w\(\)\'",]+', statement)
        excluded_keywords = [
            'KEY', 'INDEX', 'PRIMARY', 'FOREIGN', 'UNIQUE',
            'REFERENCES', 'AUTO_INCREMENT', 'ON', 'CREATE'
        ]
        real_columns = [col for col in raw_columns if col.upper() not in excluded_keywords]

        # استخراج الفهارس
        indexes = re.findall(r'INDEX\s+\w+\s*\(([^)]+)\)', statement)

        # استخراج المفاتيح الخارجية
        foreign_keys = re.findall(
            r'FOREIGN KEY\s*\((\w+)\)\s+REFERENCES\s+(\w+)\s*\((\w+)\)', statement, re.IGNORECASE
        )

        # طباعة معلومات الجدول
        print(f"\n⏳ Executing table: {table_name}")
        print(f'✅ Table: {table_name}')
        print('      🧱 Columns:')
        for col in real_columns:
            print(f'      - {col}')
        print('      🔎 Indexes:')
        for idx in indexes:
            print(f'      - {idx.strip()}')
        print('      🔗 Foreign Keys:')
        for fk_col, ref_table, ref_col in foreign_keys:
            print(f'      - {fk_col} ➜ {ref_table}({ref_col})')

        # تنفيذ إنشاء الجدول
        try:
            cursor.execute(statement)
            print(f'✅ Table created: {table_name}')
        except pymysql.err.OperationalError as e:
            print(f'⚠️ Skipping {table_name} due to error: {e}')
        except Exception as e:
            print(f'❌ Unexpected error in {table_name}: {e}')

    connection.commit()

connection.close()
print('\n✅ All tables processed successfully.')