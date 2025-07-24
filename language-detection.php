<?php
// Language Detection and Redirect Logic
function detectUserLanguage() {
    // Check URL path first
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('/^\/(ar|fr)\//', $path, $matches)) {
        return $matches[1];
    }
    
    // Check cookie preference
    if (isset($_COOKIE['preferred_language'])) {
        return $_COOKIE['preferred_language'];
    }
    
    // Check Accept-Language header
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    
    // Priority order for Morocco
    if (strpos($acceptLanguage, 'ar') !== false) {
        return 'ar';
    }
    if (strpos($acceptLanguage, 'fr') !== false) {
        return 'fr';
    }
    
    // Default to English
    return 'en';
}

function getLocalizedContent($key, $language = 'en') {
    $translations = [
        'en' => [
            'site_title' => 'Free AI Image Vectorizer | Convert JPG PNG to SVG Online',
            'site_description' => 'Transform images to crisp SVG vectors instantly. Free AI-powered tool for designers, print shops & students.',
            'upload_heading' => 'Upload Your Image',
            'features_heading' => 'Why Choose VectorizeAI?',
            'how_it_works' => 'How It Works',
            'start_free' => 'Start Free',
            'upload_instructions' => 'Drop your image here or click to browse'
        ],
        'ar' => [
            'site_title' => 'محول الصور إلى فيكتور مجاني | تحويل JPG PNG إلى SVG',
            'site_description' => 'حول صورك إلى فيكتور SVG عالي الجودة فوراً. أداة مجانية بالذكاء الاصطناعي للمصممين ومحلات الطباعة والطلاب.',
            'upload_heading' => 'ارفع صورتك',
            'features_heading' => 'لماذا تختار VectorizeAI؟',
            'how_it_works' => 'كيف يعمل',
            'start_free' => 'ابدأ مجاناً',
            'upload_instructions' => 'اسحب صورتك هنا أو انقر للتصفح'
        ],
        'fr' => [
            'site_title' => 'Vectoriseur d\'Images IA Gratuit | Convertir JPG PNG en SVG',
            'site_description' => 'Transformez vos images en vecteurs SVG nets instantanément. Outil gratuit alimenté par IA pour designers, imprimeries et étudiants.',
            'upload_heading' => 'Téléchargez Votre Image',
            'features_heading' => 'Pourquoi Choisir VectorizeAI?',
            'how_it_works' => 'Comment Ça Marche',
            'start_free' => 'Commencer Gratuitement',
            'upload_instructions' => 'Déposez votre image ici ou cliquez pour parcourir'
        ]
    ];
    
    return $translations[$language][$key] ?? $translations['en'][$key];
}

// Set language preference cookie
function setLanguagePreference($language) {
    setcookie('preferred_language', $language, time() + (86400 * 30), '/'); // 30 days
}

// Generate hreflang tags
function generateHreflangTags($currentPath = '/') {
    $languages = [
        'en' => 'https://vectorizeai.com' . $currentPath,
        'ar-MA' => 'https://vectorizeai.com/ar' . $currentPath,
        'fr-MA' => 'https://vectorizeai.com/fr' . $currentPath
    ];
    
    $hreflangTags = '';
    foreach ($languages as $lang => $url) {
        $hreflangTags .= '<link rel="alternate" hreflang="' . $lang . '" href="' . $url . '">' . "\n";
    }
    
    // Add x-default
    $hreflangTags .= '<link rel="alternate" hreflang="x-default" href="https://vectorizeai.com' . $currentPath . '">' . "\n";
    
    return $hreflangTags;
}

// Usage example
$currentLanguage = detectUserLanguage();
$pageTitle = getLocalizedContent('site_title', $currentLanguage);
$pageDescription = getLocalizedContent('site_description', $currentLanguage);
?>
