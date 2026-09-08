param(
    [switch]$Apply
)

$ErrorActionPreference = 'Stop'

$repoTheme = Join-Path $PSScriptRoot 'wp-theme\Ya-bao'
$packageTheme = Join-Path $PSScriptRoot 'Ya-bao'

if (Test-Path (Join-Path $repoTheme 'style.css')) {
    $themeRoot = $repoTheme
} elseif (Test-Path (Join-Path $packageTheme 'style.css')) {
    $themeRoot = $packageTheme
} else {
    throw 'Не найдена тема Ya-bao рядом со скриптом (wp-theme\Ya-bao или Ya-bao).'
}

$style = Get-Content -LiteralPath (Join-Path $themeRoot 'style.css') -Raw
if ($style -notmatch 'Theme Name:\s*Я Бао Завари') {
    throw 'Защитная проверка не пройдена: style.css не похож на тему «Я Бао Завари».'
}

$targets = @(
    '.nojekyll',
    '404.html',
    'README.md',
    'about.html',
    'article.html',
    'blog-belyy-chay.html',
    'blog-chaynaya-ceremoniya.html',
    'blog-chto-takoe-gaba.html',
    'blog-chto-takoe-puer.html',
    'blog-chto-takoe-ulun.html',
    'blog-kak-vybrat-kitayskiy-chay.html',
    'blog-kak-zavarivat-chay-prolivami.html',
    'blog-kak-zavarivat-da-hun-pao.html',
    'blog-kak-zavarivat-gaba.html',
    'blog-kak-zavarivat-puer.html',
    'blog-kak-zavarivat-shen-puer.html',
    'blog-kak-zavarivat-shu-puer.html',
    'blog-kak-zavarivat-ulun.html',
    'blog-kuda-shodit-na-kirovke-chelyabinsk.html',
    'blog-kuda-shodit-na-svidanie-v-chelyabinske.html',
    'blog-molochnyy-ulun.html',
    'blog-shen-i-shu-puer-raznitsa.html',
    'blog-smola-puera.html',
    'blog.html',
    'chaynaya-ceremoniya.html',
    'consent.html',
    'contacts.html',
    'event.html',
    'events.html',
    'menu.html',
    'privacy.html',
    'robots.txt',
    'site.webmanifest',
    'sitemap.html',
    'sitemap.xml',
    'cart',
    'checkout',
    'delivery',
    'order-failed',
    'order-success',
    'shop',
    'performance',
    'seo-migration',
    'seo-tech',
    'assets\css\wp-product-parity.css',
    'assets\js\wp-cart-v043.js',
    'assets\js\wp-cart-v044.js',
    'assets\js\wp-product-parity.js',
    'assets\js\wp-shop-card-cart-v047.js',
    'assets\vendor\swiper\swiper-bundle.min.css',
    'assets\vendor\swiper\swiper-bundle.min.js'
)

$existing = @()
foreach ($relative in $targets) {
    $path = Join-Path $themeRoot $relative
    if (Test-Path -LiteralPath $path) {
        $existing += [PSCustomObject]@{ Relative = $relative; Path = $path }
    }
}

Write-Host "Theme: $themeRoot"
Write-Host "Найдено объектов для очистки: $($existing.Count)"
$existing | ForEach-Object { Write-Host " - $($_.Relative)" }

if (-not $Apply) {
    Write-Host ''
    Write-Host 'DRY-RUN: ничего не удалено.' -ForegroundColor Yellow
    Write-Host 'Для применения: .\cleanup-stage67-v0.5.1.ps1 -Apply'
    exit 0
}

foreach ($item in $existing) {
    Remove-Item -LiteralPath $item.Path -Recurse -Force
}

$swiperDir = Join-Path $themeRoot 'assets\vendor\swiper'
if ((Test-Path -LiteralPath $swiperDir) -and -not (Get-ChildItem -LiteralPath $swiperDir -Force | Select-Object -First 1)) {
    Remove-Item -LiteralPath $swiperDir -Force
}

Write-Host "Удалено объектов: $($existing.Count)" -ForegroundColor Green
Write-Host 'Корневой статический фронт репозитория скрипт не затрагивает.' -ForegroundColor Green
