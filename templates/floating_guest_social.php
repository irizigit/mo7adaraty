<?php
// التحقق من بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// يظهر فقط للزوار؛ إذا كان مسجلاً يتم إيقاف التنفيذ فوراً
if (isset($_SESSION['user_id'])) {
    return;
}

$base_url = '/mo7adaraty/';
$assets_dir = $base_url . 'assets/';

// مصفوفة الروابط والأيقونات بالأسماء المعدلة
$guest_social_links = [
    [
        'title' => 'واتساب',
        'icon'  => 'whatsap.png',
        'url'   => 'https://wa.me/2',
        'url'   => 'https://wa.me/2',
    ],
    [
        'title' => 'إنستغرام',
        'icon'  => 'insta.png',
        'url'   => 'https://instagram.com/your_username',
    ],
    [
        'title' => 'تيليجرام',
        'icon'  => 'telegram.png',
        'url'   => 'https://t.me/your_channel',
    ],
    [
        'title' => 'يوتيوب',
        'icon'  => 'yotub.png',
        'url'   => 'https://youtube.com/@your_channel',
    ],
    [
        'title' => 'البريد الإلكتروني',
        'icon'  => 'gmail.png',
        'url'   => 'mailto:contact@mo7adaraty.com',
    ],
];
?>

<style>
/* شريط التواصل العائم للزوار في أقصى اليسار */
.guest-floating-social {
    position: fixed;
    bottom: 30px;
    left: 20px;
    right: auto;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: center;
}

.guest-social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    min-width: 50px;
    min-height: 50px;
    max-width: 50px;
    max-height: 50px;
    border-radius: 14px;
    background: transparent;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.25s ease;
    text-decoration: none;
    position: relative;
    user-select: none;
}

.guest-social-link:hover {
    transform: scale(1.15) translateY(-3px);
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.25));
}

.guest-social-img {
    width: 50px !important;
    height: 50px !important;
    min-width: 50px !important;
    min-height: 50px !important;
    max-width: 50px !important;
    max-height: 50px !important;
    object-fit: contain !important;
    display: block !important;
    pointer-events: none;
}

/* تلميح اسم المنصة يظهر يمين الأيقونة عند التمرير */
.guest-social-link::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 62px;
    right: auto;
    top: 50%;
    transform: translateY(-50%) scale(0.9);
    background: rgba(15, 23, 42, 0.9);
    backdrop-filter: blur(4px);
    color: #ffffff;
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    font-family: 'Tajawal', sans-serif;
}

.guest-social-link:hover::after {
    opacity: 1;
    transform: translateY(-50%) scale(1);
}

@media (max-width: 768px) {
    .guest-floating-social {
        bottom: 20px;
        left: 15px;
        right: auto;
        gap: 10px;
    }
}
</style>

<div class="guest-floating-social" id="guestFloatingSocial">
    <?php foreach ($guest_social_links as $item): ?>
        <a href="<?= htmlspecialchars($item['url']) ?>" 
           target="_blank" 
           rel="noopener noreferrer" 
           class="guest-social-link" 
           data-tooltip="<?= htmlspecialchars($item['title']) ?>" 
           title="<?= htmlspecialchars($item['title']) ?>">
            <img src="<?= htmlspecialchars($assets_dir . $item['icon']) ?>" 
                 alt="<?= htmlspecialchars($item['title']) ?>" 
                 class="guest-social-img" 
                 onerror="this.onerror=null; this.src='<?= $base_url ?>assets/images/<?= htmlspecialchars($item['icon']) ?>';">
        </a>
    <?php endforeach; ?>
</div>