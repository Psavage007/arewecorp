<?php
require_once 'includes/edgar.php';
require_once 'includes/ownership.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';

$cik = trim($_GET['cik'] ?? '');

if (!$cik || !ctype_digit($cik)) {
    header('Location: /');
    exit;
}

$company   = null;
$ownership = null;
$error     = null;

try {
    $company   = edgar_get_company($cik);
    $ownership = analyze_ownership($cik, $company);
} catch (Exception $e) {
    $error = 'Could not load company data from SEC EDGAR. Please try again.';
}

$initials = $company ? company_initials($company['name']) : '??';
$ticker   = $company ? implode(', ', $company['tickers']  ?? []) : '';
$exchange = $company ? implode(', ', $company['exchanges'] ?? []) : '';
$industry = $company ? ($company['sic_description'] ?: sic_to_industry($company['sic'] ?? '0')) : '';
$type     = $ownership['type'] ?? 'unknown';
$co_name  = $company['name'] ?? 'This Company';

// --- SEO ---
$type_label = $type === 'family' ? 'Family Owned' : ($type === 'corporate' ? 'Corporately Owned' : 'Ownership Unknown');
$seo_title  = 'Is ' . $co_name . ' Family Owned? — ' . $type_label . ' | AreWeCorp';
$seo_desc   = $company
    ? $co_name . ' is ' . strtolower($type_label) . '. ' . ($ownership['summary'] ?? '') . ' Verified from SEC EDGAR filings.'
    : 'Look up company ownership on AreWeCorp — powered by SEC EDGAR data.';

// Trim description to 160 chars
if (strlen($seo_desc) > 160) {
    $seo_desc = substr($seo_desc, 0, 157) . '...';
}

// JSON-LD
$jsonld = [];
if ($company) {
    $jsonld = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',   'item' => 'https://arewecorp.com/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Search', 'item' => 'https://arewecorp.com/search.php'],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $co_name],
                ],
            ],
            [
                '@type'       => 'FAQPage',
                'mainEntity'  => [
                    [
                        '@type'          => 'Question',
                        'name'           => 'Is ' . $co_name . ' family owned?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => $ownership['summary'] ?? ($co_name . ' ownership data is sourced from SEC EDGAR filings.'),
                        ],
                    ],
                    [
                        '@type'          => 'Question',
                        'name'           => 'Who owns ' . $co_name . '?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => !empty($ownership['owners'])
                                ? $co_name . ' major shareholders include: ' . implode(', ', array_map(fn($o) => $o['name'], array_slice($ownership['owners'], 0, 3))) . '. Data from SEC 13D/13G filings.'
                                : 'Ownership data for ' . $co_name . ' is sourced from SEC EDGAR filings.',
                        ],
                    ],
                ],
            ],
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'     => $seo_title,
    'description' => $seo_desc,
    'canonical' => 'https://arewecorp.com/company.php?cik=' . urlencode($cik),
    'type'      => 'article',
    'jsonld'    => $jsonld,
]); ?>
</head>
<body>

<nav>
    <div class="container nav-inner">
        <a href="/" class="nav-logo">Are<span>We</span>Corp</a>
        <ul class="nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/search.php">Search Companies</a></li>
        </ul>
    </div>
</nav>

<?php if ($error): ?>
<div class="page-header"><div class="container"><h1>Error Loading Company</h1></div></div>
<div class="container"><div class="error-msg" style="margin-top:2rem" role="alert"><?= htmlspecialchars($error) ?></div></div>

<?php elseif ($company): ?>

<div class="profile-hero">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" style="margin-bottom:1.5rem">
            <ol style="list-style:none;display:flex;gap:0.5rem;font-size:0.85rem;color:var(--gray-400);padding:0">
                <li><a href="/" style="color:var(--gray-400)">Home</a></li>
                <li style="color:var(--gray-600)">&rsaquo;</li>
                <li><a href="/search.php" style="color:var(--gray-400)">Search</a></li>
                <li style="color:var(--gray-600)">&rsaquo;</li>
                <li style="color:var(--gray-400)"><?= htmlspecialchars($co_name) ?></li>
            </ol>
        </nav>

        <div class="profile-top">
            <div class="profile-logo" aria-hidden="true"><?= $initials ?></div>
            <div class="profile-info">
                <h1>Is <?= htmlspecialchars($co_name) ?> Family Owned?</h1>
                <div class="profile-meta">
                    <?php if ($ticker): ?>
                        <span>&#128200; <?= htmlspecialchars($ticker) ?><?= $exchange ? " ({$exchange})" : '' ?></span>
                    <?php endif; ?>
                    <?php if ($industry): ?>
                        <span>&#127970; <?= htmlspecialchars($industry) ?></span>
                    <?php endif; ?>
                    <?php if ($company['state']): ?>
                        <span>&#127482;&#127480; Inc. in <?= htmlspecialchars($company['state']) ?></span>
                    <?php endif; ?>
                    <span style="color:var(--gray-400);font-size:0.8rem">CIK <?= htmlspecialchars(format_cik($cik)) ?></span>
                </div>
            </div>
            <?php if ($ownership): ?>
                <span class="badge <?= $type ?>" style="font-size:0.82rem;padding:0.4rem 0.9rem">
                    <?= htmlspecialchars($ownership['label']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">

    <?php if ($ownership): ?>

    <!-- Ownership Verdict -->
    <div class="verdict-card <?= $type ?>" role="region" aria-label="Ownership verdict">
        <div class="verdict-icon" aria-hidden="true">
            <?= $type === 'family' ? '&#127968;' : ($type === 'corporate' ? '&#127970;' : '&#10067;') ?>
        </div>
        <div>
            <div class="verdict-label"><?= htmlspecialchars($co_name) ?> is <?= htmlspecialchars($ownership['label']) ?></div>
            <div class="verdict-summary"><?= htmlspecialchars($ownership['summary']) ?></div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="info-grid">
        <div class="info-box">
            <h2 style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--gray-400);margin-bottom:1rem">Company Details</h2>
            <table style="width:100%;font-size:0.88rem;border-collapse:collapse" aria-label="Company details">
                <?php $rows = [
                    'Industry'  => $industry,
                    'Ticker'    => $ticker   ?: '—',
                    'Exchange'  => $exchange  ?: '—',
                    'State'     => $company['state'] ?: '—',
                    'SIC Code'  => $company['sic']   ?: '—',
                    'EDGAR CIK' => format_cik($cik),
                ]; ?>
                <?php foreach ($rows as $label => $value): ?>
                <tr>
                    <td style="color:var(--gray-400);padding:0.4rem 0;width:40%"><?= $label ?></td>
                    <td style="font-weight:500;padding:0.4rem 0"><?= htmlspecialchars($value) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="info-box">
            <h2 style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--gray-400);margin-bottom:1rem">Ownership Classification</h2>
            <div style="font-size:0.88rem">
                <div style="margin-bottom:0.75rem">
                    <span class="badge <?= $type ?>"><?= htmlspecialchars($ownership['label']) ?></span>
                </div>
                <p style="color:var(--gray-600);line-height:1.6">
                    <?= $type === 'family'
                        ? 'This company shows signals of family or founder control, including individuals or family-linked entities filing as 5%+ beneficial owners with the SEC.'
                        : ($type === 'corporate'
                            ? 'This company shows signals of institutional ownership, with large asset managers or corporate entities filing as major shareholders.'
                            : 'Ownership signals were insufficient to classify this company. It may be private or a smaller reporting entity with limited disclosures.')
                    ?>
                </p>
                <p style="color:var(--gray-400);font-size:0.78rem;margin-top:0.75rem">
                    Based on SEC 13D/13G filings &amp; EDGAR data.
                    <?php if ($ownership['confidence'] === 'high'): ?>
                        <strong style="color:var(--green)">High confidence.</strong>
                    <?php else: ?>
                        Moderate confidence — limited filing data available.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Beneficial Owners Table -->
    <?php if (!empty($ownership['owners'])): ?>
    <h2 style="font-size:1.1rem;font-weight:700;color:var(--navy);margin-bottom:0.5rem">
        Who Owns <?= htmlspecialchars($co_name) ?>? — Major Shareholders
    </h2>
    <p style="font-size:0.82rem;color:var(--gray-400);margin-bottom:1rem">
        Entities that have disclosed owning 5%+ of shares in SEC 13D/13G beneficial ownership filings.
    </p>
    <table class="owners-table" aria-label="Major shareholders">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Classification</th>
                <th scope="col">Filing Type</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ownership['owners'] as $owner): ?>
            <tr>
                <td><strong><?= htmlspecialchars($owner['name']) ?></strong></td>
                <td>
                    <span class="badge <?= $owner['type'] ?>">
                        <?= $owner['type'] === 'family' ? 'Individual / Family' : 'Institution' ?>
                    </span>
                </td>
                <td style="color:var(--gray-400)"><?= htmlspecialchars($owner['role']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="info-box" style="margin:1.5rem 0;text-align:center;padding:2.5rem">
        <p style="color:var(--gray-400);font-size:0.9rem">
            No 13D/13G beneficial ownership filings found for <?= htmlspecialchars($co_name) ?> in EDGAR.<br>
            This may indicate it is privately held or a smaller reporting company.
        </p>
        <a href="https://www.sec.gov/cgi-bin/browse-edgar?action=getcompany&CIK=<?= urlencode(format_cik($cik)) ?>&type=SC+13&dateb=&owner=include&count=40"
           target="_blank" rel="noopener" style="font-size:0.85rem;margin-top:0.75rem;display:inline-block">
            View filings directly on SEC.gov &rarr;
        </a>
    </div>
    <?php endif; ?>

    <p style="font-size:0.78rem;color:var(--gray-400);margin:1.5rem 0 3rem">
        Ownership data sourced from <a href="https://www.sec.gov/cgi-bin/browse-edgar?action=getcompany&CIK=<?= urlencode(format_cik($cik)) ?>&type=&dateb=&owner=include&count=40" target="_blank" rel="noopener">SEC EDGAR</a>.
        For informational purposes only — not financial or legal advice.
    </p>

    <?php endif; ?>

</div>

<?php endif; ?>

<footer>
    <div class="container footer-inner">
        <div class="footer-logo">Are<span>We</span>Corp</div>
        <p>Data from <a href="https://www.sec.gov/edgar" target="_blank" rel="noopener">SEC EDGAR</a>. For informational purposes only.</p>
    </div>
</footer>

</body>
</html>
