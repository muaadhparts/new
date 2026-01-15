<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration لإنشاء قوالب البريد الإلكتروني الأساسية
 *
 * القوالب:
 * 1. welcome_customer - ترحيب بالعميل الجديد
 * 2. welcome_merchant - ترحيب بالتاجر الجديد
 * 3. purchase_confirmed - تأكيد الطلب للعميل
 * 4. purchase_merchant_notify - إشعار التاجر بطلب جديد
 * 5. merchant_trusted - قبول توثيق التاجر
 * 6. trust_badge_request - طلب توثيق من التاجر
 */
return new class extends Migration
{
    public function up(): void
    {
        // حذف القوالب القديمة إن وجدت
        DB::table('comms_blueprints')->truncate();

        // إدراج القوالب الجديدة
        DB::table('comms_blueprints')->insert([
            [
                'email_type' => 'welcome_customer',
                'email_subject' => 'مرحباً بك في {website_name}',
                'email_body' => $this->getWelcomeCustomerTemplate(),
                'status' => 1,
            ],
            [
                'email_type' => 'welcome_merchant',
                'email_subject' => 'مرحباً بك كتاجر في {website_name}',
                'email_body' => $this->getWelcomeMerchantTemplate(),
                'status' => 1,
            ],
            [
                'email_type' => 'purchase_confirmed',
                'email_subject' => 'تأكيد طلبك #{order_number} - {website_name}',
                'email_body' => $this->getPurchaseConfirmedTemplate(),
                'status' => 1,
            ],
            [
                'email_type' => 'purchase_merchant_notify',
                'email_subject' => 'طلب جديد #{order_number} - {website_name}',
                'email_body' => $this->getPurchaseMerchantNotifyTemplate(),
                'status' => 1,
            ],
            [
                'email_type' => 'merchant_trusted',
                'email_subject' => 'تهانينا! تم توثيق حسابك - {website_name}',
                'email_body' => $this->getMerchantTrustedTemplate(),
                'status' => 1,
            ],
            [
                'email_type' => 'trust_badge_request',
                'email_subject' => 'طلب توثيق جديد - {website_name}',
                'email_body' => $this->getTrustBadgeRequestTemplate(),
                'status' => 1,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('comms_blueprints')->whereIn('email_type', [
            'welcome_customer',
            'welcome_merchant',
            'purchase_confirmed',
            'purchase_merchant_notify',
            'merchant_trusted',
            'trust_badge_request',
        ])->delete();
    }

    private function getWelcomeCustomerTemplate(): string
    {
        return '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head><body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);"><tr><td style="background:linear-gradient(135deg,#006c35 0%,#004d26 100%);padding:30px;text-align:center;"><h1 style="color:#ffffff;margin:0;font-size:28px;">مرحباً بك في {website_name}</h1></td></tr><tr><td style="padding:40px 30px;"><h2 style="color:#333;margin:0 0 20px;font-size:22px;">أهلاً {customer_name}،</h2><p style="color:#555;font-size:16px;line-height:1.8;margin:0 0 20px;">شكراً لانضمامك إلينا! نحن سعداء بوجودك معنا.</p><p style="color:#555;font-size:16px;line-height:1.8;margin:0 0 20px;">يمكنك الآن تصفح كتالوج قطع الغيار والطلب من أفضل التجار المعتمدين.</p></td></tr><tr><td style="background-color:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #eee;"><p style="color:#888;font-size:14px;margin:0;">{website_name} - جميع الحقوق محفوظة</p></td></tr></table></td></tr></table></body></html>';
    }

    private function getWelcomeMerchantTemplate(): string
    {
        return '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head><body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);"><tr><td style="background:linear-gradient(135deg,#1a5f7a 0%,#134b5f 100%);padding:30px;text-align:center;"><h1 style="color:#ffffff;margin:0;font-size:28px;">مرحباً بك كتاجر</h1></td></tr><tr><td style="padding:40px 30px;"><h2 style="color:#333;margin:0 0 20px;font-size:22px;">أهلاً {customer_name}،</h2><p style="color:#555;font-size:16px;line-height:1.8;margin:0 0 20px;">شكراً لتسجيلك كتاجر في {website_name}!</p><div style="background-color:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:15px;margin:20px 0;"><p style="color:#856404;font-size:14px;margin:0;"><strong>حسابك قيد المراجعة</strong><br>لتفعيل حسابك بالكامل، يرجى رفع مستندات التوثيق من لوحة التحكم.</p></div><p style="color:#555;font-size:16px;line-height:1.8;margin:0 0 20px;">بعد التوثيق ستتمكن من إضافة منتجاتك واستقبال الطلبات.</p></td></tr><tr><td style="background-color:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #eee;"><p style="color:#888;font-size:14px;margin:0;">{website_name} - جميع الحقوق محفوظة</p></td></tr></table></td></tr></table></body></html>';
    }

    private function getPurchaseConfirmedTemplate(): string
    {
        return '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head><body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);"><tr><td style="background:linear-gradient(135deg,#28a745 0%,#1e7e34 100%);padding:30px;text-align:center;"><div style="font-size:50px;margin-bottom:10px;">✓</div><h1 style="color:#ffffff;margin:0;font-size:24px;">تم تأكيد طلبك</h1></td></tr><tr><td style="padding:40px 30px;"><h2 style="color:#333;margin:0 0 20px;font-size:20px;">شكراً {customer_name}،</h2><p style="color:#555;font-size:16px;line-height:1.8;margin:0 0 20px;">تم استلام طلبك بنجاح وجاري معالجته.</p><div style="background-color:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;"><table width="100%" cellpadding="8" cellspacing="0"><tr><td style="color:#666;font-size:14px;">رقم الطلب:</td><td style="color:#333;font-size:16px;font-weight:bold;text-align:left;">#{order_number}</td></tr><tr><td style="color:#666;font-size:14px;">المبلغ الإجمالي:</td><td style="color:#28a745;font-size:18px;font-weight:bold;text-align:left;">{order_amount}</td></tr></table></div><p style="color:#555;font-size:15px;line-height:1.8;margin:20px 0;">سيتم إرسال إشعار آخر عند شحن طلبك.</p></td></tr><tr><td style="background-color:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #eee;"><p style="color:#888;font-size:14px;margin:0;">{website_name} - جميع الحقوق محفوظة</p></td></tr></table></td></tr></table></body></html>';
    }

    private function getPurchaseMerchantNotifyTemplate(): string
    {
        return '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head><body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);"><tr><td style="background:linear-gradient(135deg,#ff6b35 0%,#e55a2b 100%);padding:30px;text-align:center;"><h1 style="color:#ffffff;margin:0;font-size:24px;">طلب جديد!</h1></td></tr><tr><td style="padding:40px 30px;"><h2 style="color:#333;margin:0 0 20px;font-size:20px;">مرحباً،</h2><p style="color:#555;font-size:16px;line-height:1.8;margin:0 0 20px;">لديك طلب جديد يحتاج إلى معالجة.</p><div style="background-color:#fff8f0;border:2px solid #ff6b35;border-radius:8px;padding:20px;margin:20px 0;"><table width="100%" cellpadding="8" cellspacing="0"><tr><td style="color:#666;font-size:14px;">رقم الطلب:</td><td style="color:#333;font-size:16px;font-weight:bold;text-align:left;">#{order_number}</td></tr><tr><td style="color:#666;font-size:14px;">العميل:</td><td style="color:#333;font-size:16px;text-align:left;">{customer_name}</td></tr><tr><td style="color:#666;font-size:14px;">المبلغ:</td><td style="color:#ff6b35;font-size:18px;font-weight:bold;text-align:left;">{order_amount}</td></tr></table></div></td></tr><tr><td style="background-color:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #eee;"><p style="color:#888;font-size:14px;margin:0;">{website_name} - جميع الحقوق محفوظة</p></td></tr></table></td></tr></table></body></html>';
    }

    private function getMerchantTrustedTemplate(): string
    {
        return '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head><body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);"><tr><td style="background:linear-gradient(135deg,#ffc107 0%,#e0a800 100%);padding:30px;text-align:center;"><h1 style="color:#333;margin:0;font-size:26px;">تهانينا! تم توثيق حسابك</h1></td></tr><tr><td style="padding:40px 30px;"><h2 style="color:#333;margin:0 0 20px;font-size:20px;">مرحباً {customer_name}،</h2><p style="color:#555;font-size:16px;line-height:1.8;margin:0 0 20px;">يسعدنا إبلاغك بأن حسابك التجاري قد تم توثيقه بنجاح!</p><div style="background-color:#d4edda;border:1px solid #28a745;border-radius:8px;padding:20px;margin:20px 0;text-align:center;"><p style="color:#155724;font-size:16px;font-weight:bold;margin:0;">أنت الآن تاجر موثق ✓</p></div><p style="color:#555;font-size:16px;line-height:1.8;margin:0 0 20px;">يمكنك الآن إضافة منتجاتك واستقبال الطلبات.</p></td></tr><tr><td style="background-color:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #eee;"><p style="color:#888;font-size:14px;margin:0;">{website_name} - جميع الحقوق محفوظة</p></td></tr></table></td></tr></table></body></html>';
    }

    private function getTrustBadgeRequestTemplate(): string
    {
        return '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head><body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);"><tr><td style="background:linear-gradient(135deg,#6c757d 0%,#545b62 100%);padding:30px;text-align:center;"><h1 style="color:#ffffff;margin:0;font-size:24px;">طلب توثيق جديد</h1></td></tr><tr><td style="padding:40px 30px;"><h2 style="color:#333;margin:0 0 20px;font-size:20px;">مرحباً،</h2><p style="color:#555;font-size:16px;line-height:1.8;margin:0 0 20px;">تم استلام طلب توثيق جديد من تاجر يحتاج إلى مراجعة.</p><div style="background-color:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;"><table width="100%" cellpadding="8" cellspacing="0"><tr><td style="color:#666;font-size:14px;">اسم التاجر:</td><td style="color:#333;font-size:16px;font-weight:bold;text-align:left;">{customer_name}</td></tr></table></div><p style="color:#555;font-size:15px;line-height:1.8;margin:20px 0;">يرجى مراجعة المستندات المرفقة واتخاذ الإجراء المناسب.</p></td></tr><tr><td style="background-color:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #eee;"><p style="color:#888;font-size:14px;margin:0;">{website_name} - لوحة تحكم المشغل</p></td></tr></table></td></tr></table></body></html>';
    }
};
