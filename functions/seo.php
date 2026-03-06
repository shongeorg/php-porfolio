<?php
/**
 * SEO Helper Functions
 * Generate SEO meta tags from seo.json
 */

/**
 * Load SEO data from JSON file
 * @return array
 */
function seoLoadData(): array
{
    $seoFile = DATA_PATH . 'seo.json';
    
    if (!file_exists($seoFile)) {
        return seoGetDefaultData();
    }
    
    $jsonContent = file_get_contents($seoFile);
    $data = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('SEO JSON Error: ' . json_last_error_msg());
        return seoGetDefaultData();
    }
    
    return $data;
}

/**
 * Get default SEO data as fallback
 * @return array
 */
function seoGetDefaultData(): array
{
    return [
        'meta' => [
            'default' => [
                'title' => 'Portfolio',
                'description' => 'Моє портфоліо',
                'keywords' => 'portfolio, php, web',
                'robots' => 'index, follow',
                'author' => 'Admin',
                'themeColor' => '#4f46e5'
            ]
        ]
    ];
}

/**
 * Get SEO data for current page
 * @param string $page Current page name
 * @return array
 */
function seoGetForPage(string $page): array
{
    $seo = seoLoadData();
    $page = $page === 'portfolio' ? 'portfolio' : $page;
    
    // Get page-specific SEO or use default
    $pageSeo = $seo['pages'][$page] ?? $seo['meta']['default'];
    $default = $seo['meta']['default'];
    
    // Merge with defaults for missing fields
    return array_merge($default, $pageSeo);
}

/**
 * Generate and output all SEO meta tags
 * @param string $page Current page name
 */
function seoRenderTags(string $page): void
{
    $seo = seoGetForPage($page);
    $siteSeo = seoLoadData();
    
    // ===== Title =====
    $title = htmlspecialchars($seo['title'] ?? $siteSeo['meta']['default']['title']);
    echo "    <title>{$title}</title>\n";
    
    // ===== Meta Tags =====
    $description = htmlspecialchars($seo['description'] ?? $siteSeo['meta']['default']['description']);
    echo "    <meta name=\"description\" content=\"{$description}\">\n";
    
    $keywords = htmlspecialchars($seo['keywords'] ?? $siteSeo['meta']['default']['keywords']);
    echo "    <meta name=\"keywords\" content=\"{$keywords}\">\n";
    
    $robots = htmlspecialchars($seo['robots'] ?? $siteSeo['meta']['default']['robots']);
    echo "    <meta name=\"robots\" content=\"{$robots}\">\n";
    
    $author = htmlspecialchars($seo['author'] ?? $siteSeo['meta']['default']['author']);
    echo "    <meta name=\"author\" content=\"{$author}\">\n";
    
    $themeColor = htmlspecialchars($seo['themeColor'] ?? $siteSeo['meta']['default']['themeColor']);
    echo "    <meta name=\"theme-color\" content=\"{$themeColor}\">\n";
    
    // ===== Viewport (always) =====
    echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
    
    // ===== Charset (always) =====
    echo "    <meta charset=\"UTF-8\">\n";
    
    // ===== Open Graph Tags =====
    $og = $siteSeo['meta']['openGraph'] ?? [];
    $ogTitle = htmlspecialchars($seo['title'] ?? $siteSeo['meta']['default']['title']);
    $ogDescription = htmlspecialchars($seo['description'] ?? $siteSeo['meta']['default']['description']);
    $ogUrl = $seo['canonical'] ?? ($siteSeo['site']['url'] ?? 'https://pan-kit.infinityfreeapp.com');
    
    echo "    <meta property=\"og:type\" content=\"" . htmlspecialchars($og['type'] ?? 'website') . "\">\n";
    echo "    <meta property=\"og:locale\" content=\"" . htmlspecialchars($og['locale'] ?? 'uk_UA') . "\">\n";
    echo "    <meta property=\"og:site_name\" content=\"" . htmlspecialchars($og['siteName'] ?? 'Portfolio') . "\">\n";
    echo "    <meta property=\"og:title\" content=\"{$ogTitle}\">\n";
    echo "    <meta property=\"og:description\" content=\"{$ogDescription}\">\n";
    echo "    <meta property=\"og:url\" content=\"{$ogUrl}\">\n";
    
    if (!empty($og['image'])) {
        echo "    <meta property=\"og:image\" content=\"" . htmlspecialchars($og['image']) . "\">\n";
        echo "    <meta property=\"og:image:alt\" content=\"" . htmlspecialchars($og['imageAlt'] ?? '') . "\">\n";
    }
    
    // ===== Twitter Card Tags =====
    $twitter = $siteSeo['meta']['twitter'] ?? [];
    echo "    <meta name=\"twitter:card\" content=\"" . htmlspecialchars($twitter['card'] ?? 'summary_large_image') . "\">\n";
    
    if (!empty($twitter['site'])) {
        echo "    <meta name=\"twitter:site\" content=\"" . htmlspecialchars($twitter['site']) . "\">\n";
    }
    if (!empty($twitter['creator'])) {
        echo "    <meta name=\"twitter:creator\" content=\"" . htmlspecialchars($twitter['creator']) . "\">\n";
    }
    
    // ===== Canonical URL =====
    if (!empty($seo['canonical'])) {
        echo "    <link rel=\"canonical\" href=\"" . htmlspecialchars($seo['canonical']) . "\">\n";
    }
    
    // ===== Favicon =====
    echo "    <link rel=\"icon\" type=\"image/x-icon\" href=\"/public/favicon.ico\">\n";
    
    // ===== Schema.org JSON-LD =====
    $schema = $siteSeo['meta']['schema'] ?? null;
    if ($schema) {
        $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "    <script type=\"application/ld+json\">\n{$schemaJson}\n    </script>\n";
    }
}
