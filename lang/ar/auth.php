<?php

declare(strict_types=1);

return [
    'invalid_credentials' => 'رقم الهاتف أو كلمة المرور غير صحيحة.',
    'too_many_attempts' => 'محاولات كثيرة. أعد المحاولة بعد :seconds ثانية.',
    'login_successful' => 'تم تسجيل الدخول بنجاح.',
    'logout_successful' => 'تم تسجيل الخروج بنجاح.',
    'registration_successful' => 'تم إنشاء الحساب بنجاح.',
    'token_refreshed' => 'تم تجديد الجلسة.',
    'invalid_token' => 'لا يمكن استخدام هذا الرمز هنا.',
    'invalid_refresh_token' => 'رمز التجديد غير صالح.',
    'device_required' => 'مطلوب معرّف الجهاز لتسجيل الدخول.',
    'invalid_device' => 'جهاز غير معروف.',
    'invalid_reset_token' => 'رمز إعادة تعيين كلمة المرور غير صالح.',
    'otp_sent' => 'تم إرسال رمز التحقق.',
    'otp_rate_limit' => 'تم طلب رموز كثيرة. أعد المحاولة بعد :seconds ثانية.',
    'otp_verify_rate_limit' => 'محاولات كثيرة. أعد المحاولة بعد :seconds ثانية.',
    'current_password_incorrect' => 'كلمة المرور الحالية غير صحيحة.',
    'password_updated_successfully' => 'تم تحديث كلمة المرور بنجاح.',
    'password_reset_successful' => 'تمت إعادة تعيين كلمة المرور بنجاح.',

    'errors' => [
        'does_not_have_password' => 'لا توجد كلمة مرور لهذا الحساب.',
        'otp_delivery_failed' => 'تعذّر إرسال رمز التحقق.',
        'otp_storage_failed' => 'تعذّر إصدار رمز التحقق.',
        'otp_expired' => 'انتهت صلاحية رمز التحقق.',
        'otp_does_not_match' => 'رمز التحقق الذي أدخلته غير صحيح.',
        'phone_already_registered' => 'يوجد حساب مسجّل بهذا الرقم.',
        'invalid_registration_token' => 'انتهت صلاحية التحقق. ابدأ من جديد.',
    ],
];
