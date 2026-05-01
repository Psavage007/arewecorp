<?php
/**
 * Outputs the full <head> block with SEO meta tags, OG, Twitter card,
 * canonical, and JSON-LD structured data.
 *
 * @param array $seo {
 *   title       string  Full page title (with site name)
 *   description string  Meta description
 *   canonical   string  Full canonical URL
 *   type        string  og:type — 'website' or 'article' (default: website)
 *   noindex     bool    If true, adds noindex
 *   jsonld      array   Raw JSON-LD object(s) to inject
 * }
 */
function seo_head(array $seo): void {
    $site_name  = 'AreWeCorp';
    $site_url   = 'https://arewecorp.com';
    $title      = htmlspecialchars($seo['title']       ?? $site_name);
    $desc       = htmlspecialchars($seo['description'] ?? 'Find out if any US company is family owned or corporately owned, sourced from SEC EDGAR filings.');
    $canonical  = htmlspecialchars($seo['canonical']   ?? $site_url);
    $og_type    = htmlspecialchars($seo['type']        ?? 'website');
    $noindex    = !empty($seo['noindex']);
    $jsonld     = $seo['jsonld'] ?? [];
    $ga_id      = getenv('GA_MEASUREMENT_ID') ?: '';
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary SEO -->
    <title><?= $title ?></title>
    <meta name="description" content="<?= $desc ?>">
    <?php if ($noindex): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
    <meta name="robots" content="index, follow">
    <?php endif; ?>
    <link rel="canonical" href="<?= $canonical ?>">

    <!-- Open Graph -->
    <meta property="og:type"        content="<?= $og_type ?>">
    <meta property="og:title"       content="<?= $title ?>">
    <meta property="og:description" content="<?= $desc ?>">
    <meta property="og:url"         content="<?= $canonical ?>">
    <meta property="og:site_name"   content="<?= htmlspecialchars($site_name) ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary">
    <meta name="twitter:title"       content="<?= $title ?>">
    <meta name="twitter:description" content="<?= $desc ?>">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <?php if ($ga_id): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga_id) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= htmlspecialchars($ga_id) ?>');</script>
    <?php endif; ?>

    <?php if (!empty($jsonld)): ?>
    <!-- Structured Data -->
    <script type="application/ld+json">
    <?= json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>
<?php
}
