<?php
require_once 'includes/edgar.php';
require_once 'includes/ownership.php';
require_once 'includes/helpers.php';

$cik = trim($_GET['cik'] ?? '');

if (!$cik || !ctype_digit($cik)) {
    header('Location: index.php');
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
$ticker   = $company ? implode(', ', $company['tickers'] ?? []) : '';
$exchange = $company ? implode(', ', $company['exchanges'] ?? []) : '';
$industry = $company ? ($company['sic_description'] ?: sic_to_industry($company['sic'] ?? '0')) : '';
$type     = $ownership['type'] ?? 'unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $company ? htmlspecialchars($company['name']) : 'Company' ?> — AreWeCorp</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav>
    <div class="container nav-inner">
        <a href="index.php" class="nav-logo">Are<span>We</span>Corp</a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="search.php">Search</a></li>
        </ul>
    </div>
</nav>

<?php if ($error): ?>
<div class="page-header"><div class="container"><h1>Error</h1></div></div>
<div class="container"><div class="error-msg" style="margin-top:2rem"><?= htmlspecialchars($error) ?></div></div>

<?php elseif ($company): ?>

<div class="profile-hero">
    <div class="container">
        <p style="color:var(--gray-400);font-size:0.85rem;margin-bottom:1.5rem">
            <a href="index.php" style="color:var(--gray-400)">Home</a> &rsaquo;
            <a href="search.php" style="color:var(--gray-400)">Search</a> &rsaquo;
            <?= htmlspecialchars($company['name']) ?>
        </p>
        <div class="profile-top">
            <div class="profile-logo"><?= $initials ?></div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($company['name']) ?></h1>
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

    <!-- Verdict -->
    <div class="verdict-card <?= $type ?>">
        <div class="verdict-icon">
            <?= $type === 'family' ? '&#127968;' : ($type === 'corporate' ? '&#127970;' : '&#10067;') ?>
        </div>
        <div>
            <div class="verdict-label"><?= htmlspecialchars($ownership['label']) ?></div>
            <div class="verdict-summary"><?= htmlspecialchars($ownership['summary']) ?></div>
        </div>
    </div>

    <!-- Info grid -->
    <div class="info-grid">
        <div class="info-box">
            <h3>Company Details</h3>
            <table style="width:100%;font-size:0.88rem;border-collapse:collapse">
                <?php $rows = [
                    'Industry'    => $industry,
                    'Ticker'      => $ticker ?: '—',
                    'Exchange'    => $exchange ?: '—',
                    'State'       => $company['state'] ?: '—',
                    'SIC Code'    => $company['sic'] ?: '—',
                    'EDGAR CIK'   => format_cik($cik),
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
            <h3>Ownership Classification</h3>
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

    <!-- Owners table -->
    <?php if (!empty($ownership['owners'])): ?>
    <h2 style="font-size:1.1rem;font-weight:700;color:var(--navy);margin-bottom:0.5rem">
        Beneficial Owners &amp; Major Shareholders
    </h2>
    <p style="font-size:0.82rem;color:var(--gray-400);margin-bottom:1rem">
        Entities that have disclosed owning 5%+ of shares in SEC filings.
    </p>
    <table class="owners-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Classification</th>
                <th>Filing Type</th>
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
            No 13D/13G beneficial ownership filings found for this company in EDGAR.<br>
            This may indicate it is privately held or a smaller reporting company.
        </p>
        <a href="https://www.sec.gov/cgi-bin/browse-edgar?action=getcompany&CIK=<?= urlencode(format_cik($cik)) ?>&type=SC+13&dateb=&owner=include&count=40"
           target="_blank" style="font-size:0.85rem;margin-top:0.75rem;display:inline-block">
            View filings directly on SEC.gov &rarr;
        </a>
    </div>
    <?php endif; ?>

    <p style="font-size:0.78rem;color:var(--gray-400);margin:1.5rem 0 3rem">
        Data sourced from <a href="https://www.sec.gov/cgi-bin/browse-edgar?action=getcompany&CIK=<?= urlencode(format_cik($cik)) ?>&type=&dateb=&owner=include&count=40" target="_blank">SEC EDGAR</a>.
        Last updated from live EDGAR data. For informational purposes only — not financial or legal advice.
    </p>

    <?php endif; ?>

</div>

<?php endif; ?>

<footer>
    <div class="container footer-inner">
        <div class="footer-logo">Are<span>We</span>Corp</div>
        <p>Data from <a href="https://www.sec.gov/edgar" target="_blank">SEC EDGAR</a>. For informational purposes only.</p>
    </div>
</footer>

</body>
</html>
