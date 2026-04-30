<?php
require_once 'includes/edgar.php';
require_once 'includes/db.php';
require_once 'includes/ownership.php';

$cik = trim($_GET['cik'] ?? '');
$company = null;
$ownership = null;
$error = null;

if (!$cik || !ctype_digit($cik)) {
    header('Location: index.php');
    exit;
}

try {
    $company = edgar_get_company($cik);
    $ownership = analyze_ownership($cik);
} catch (Exception $e) {
    $error = 'Could not load company data. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $company ? htmlspecialchars($company['name']) : 'Company' ?> - AreWeCorp</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">AreWeCorp</a></h1>
        </div>
    </header>

    <main class="container">
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($company): ?>
            <div class="company-header">
                <h2><?= htmlspecialchars($company['name']) ?></h2>
                <p class="meta">CIK: <?= htmlspecialchars($cik) ?> &mdash; <?= htmlspecialchars($company['sic_description'] ?? 'N/A') ?></p>
            </div>

            <?php if ($ownership): ?>
                <div class="ownership-verdict <?= $ownership['type'] === 'family' ? 'family' : 'corporate' ?>">
                    <span class="label"><?= $ownership['type'] === 'family' ? 'Family Owned' : 'Corporately Owned' ?></span>
                    <p><?= htmlspecialchars($ownership['summary']) ?></p>
                </div>

                <?php if (!empty($ownership['owners'])): ?>
                    <h3>Key Owners / Insiders</h3>
                    <table class="owners-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Ownership %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ownership['owners'] as $owner): ?>
                                <tr>
                                    <td><?= htmlspecialchars($owner['name']) ?></td>
                                    <td><?= htmlspecialchars($owner['role'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($owner['pct'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>Data sourced from <a href="https://www.sec.gov/edgar" target="_blank">SEC EDGAR</a>.</p>
        </div>
    </footer>
</body>
</html>
