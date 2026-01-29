لضمان استدامة النظام المعماري النظيف

1. إنشاء معايير ووثائق معمارية
1.1 توثيق النمط المعماري
يجب إنشاء مستند يشرح النمط المعماري المتبع في المشروع، ليكون مرجعاً لجميع المطورين:
المحتوى المقترح:
تدفق البيانات: View → Controller → Service → Model
قاعدة ذهبية: لا Business Logic في Views أو Controllers، فقط في Services
استخدام DTOs: جميع البيانات المرسلة للـ Views أو API يجب أن تكون DTOs، وليس Models مباشرة
Constructor Injection: استخدام Dependency Injection بدلاً من app() helper
الموقع المقترح: docs/architecture/clean-architecture-guide.md
1.2 إنشاء Coding Standards
إنشاء ملف CONTRIBUTING.md في جذر المشروع يحتوي على:
قواعد تسمية الملفات والـ Classes
متى يتم إنشاء Service جديد
متى يتم إنشاء DTO جديد
كيفية التعامل مع الـ Events
أمثلة على الكود الصحيح والخاطئ
2. تطبيق Automated Testing
2.1 Unit Tests للـ Services
الآن بعد عزل الـ Business Logic في الـ Services، يجب كتابة Unit Tests لها:
الأولويات:
CatalogItemCardDTOBuilder - اختبار جميع الـ methods
PriceFormatterService - اختبار تحويل العملات والتنسيق
MerchantCatalogService - اختبار الفلاتر والترتيب
CatalogItemApiService - اختبار جميع الـ API operations
الفائدة: منع الأخطاء عند تعديل الكود مستقبلاً، والتأكد من أن التغييرات لا تكسر الوظائف الموجودة.
2.2 Integration Tests للـ Controllers
اختبار تكامل الـ Controllers مع الـ Services، للتأكد من أن التدفق الكامل يعمل بشكل صحيح.
2.3 Feature Tests للـ API Endpoints
اختبار جميع الـ API Endpoints للتأكد من أنها ترجع البيانات بالهيكل الصحيح (DTOs).
3. إعداد Code Quality Tools
3.1 PHP CS Fixer
تثبيت وإعداد PHP CS Fixer لضمان اتساق تنسيق الكود:
Bash
composer require --dev friendsofphp/php-cs-fixer
الفائدة: تنسيق تلقائي للكود حسب معايير PSR-12.
3.2 PHPStan أو Psalm
تثبيت أداة Static Analysis للكشف عن الأخطاء قبل التشغيل:
Bash
composer require --dev phpstan/phpstan
الفائدة: اكتشاف الأخطاء المحتملة (مثل استدعاء methods غير موجودة) قبل الوصول للـ Production.
3.3 Laravel Pint
أداة تنسيق الكود الرسمية من Laravel:
Bash
composer require --dev laravel/pint
الفائدة: تنسيق سريع وسهل حسب معايير Laravel.
4. إعداد CI/CD Pipeline
4.1 GitHub Actions
إنشاء Workflow يقوم تلقائياً بـ:
تشغيل جميع الـ Tests عند كل Push
تشغيل PHP CS Fixer للتأكد من تنسيق الكود
تشغيل PHPStan للتأكد من عدم وجود أخطاء
مثال على Workflow:
YAML
name: Laravel Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
      - name: Run PHPStan
        run: ./vendor/bin/phpstan analyse
الفائدة: منع دمج كود يحتوي على أخطاء أو لا يتبع المعايير.
5. مراجعة الكود (Code Review)
5.1 إنشاء Pull Request Template
إنشاء ملف .github/pull_request_template.md يحتوي على:
وصف التغييرات
هل تم اتباع النمط المعماري؟
هل تم كتابة Tests؟
هل تم تحديث الوثائق؟
5.2 قواعد المراجعة
لا يتم دمج أي PR بدون مراجعة: على الأقل مطور واحد آخر يجب أن يراجع الكود
التحقق من اتباع Clean Architecture: هل الـ Business Logic في الـ Service؟
التحقق من استخدام DTOs: هل الـ Controller يمرر DTOs للـ View؟
6. مراجعة دورية للديون التقنية
6.1 مراجعة 
دوال أو ملفات غير مستخدمة
تكرار في الكود (Code Duplication)
أجزاء تحتاج لإعادة هيكلة
6.2 استخدام أدوات تحليل الديون التقنية
7. تحسينات معمارية إضافية
7.1 إنشاء PriceFormatterService (TODO من المراجعة السابقة)
نقل منطق CatalogItem::convertPrice() إلى Service منفصل:
PHP
app/Domain/Commerce/Services/PriceFormatterService.php
الفائدة: عزل منطق تحويل الأسعار عن الـ Model، واستخدامه في أي مكان عبر Dependency Injection.
7.2 إنشاء Repository Pattern 
