<?php
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';
$featured = require 'data/featured.php';

$jsonld = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type'           => 'WebSite',
            '@id'             => 'https://arewecorp.com/#website',
            'url'             => 'https://arewecorp.com',
            'name'            => 'AreWeCorp',
            'description'     => 'Find out if any US company is family owned or corporately owned.',
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => 'https://arewecorp.com/search.php?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
        [
            '@type'     => 'Organization',
            '@id'       => 'https://arewecorp.com/#organization',
            'name'      => 'AreWeCorp',
            'url'       => 'https://arewecorp.com',
            'sameAs'    => ['https://github.com/Psavage007/arewecorp'],
        ],
        [
            '@type'      => 'FAQPage',
            'mainEntity' => [
                [
                    '@type'          => 'Question',
                    'name'           => 'How do I find out if a company is family owned?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Search the company name on AreWeCorp. We analyze SEC EDGAR filings including 13D/13G beneficial ownership reports and proxy statements to determine if a company is family owned or corporately owned.',
                    ],
                ],
                [
                    '@type'          => 'Question',
                    'name'           => 'What is the difference between a family owned and corporately owned company?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'A family owned company is one where a founding family or individual holds majority voting control or significant ownership. A corporately owned company is primarily owned by institutional investors such as Vanguard, BlackRock, or other asset management firms with no single controlling family.',
                    ],
                ],
                [
                    '@type'          => 'Question',
                    'name'           => 'Is Walmart family owned?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Yes, Walmart is family owned. The Walton family controls approximately 47% of Walmart shares through Walton Enterprises LLC, making it one of the largest family-controlled public companies in the United States.',
                    ],
                ],
                [
                    '@type'          => 'Question',
                    'name'           => 'Where does the ownership data come from?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'All ownership data is sourced directly from the SEC\'s EDGAR database, which contains 13D and 13G beneficial ownership filings, proxy statements (DEF 14A), and other public disclosures required by US securities law.',
                    ],
                ],
            ],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'       => 'Is a Company Family Owned? Look Up Any US Company | AreWeCorp',
    'description' => 'Find out if any US company is family owned or corporately owned. Search 12,000+ companies using official SEC EDGAR ownership data — free, instant results.',
    'canonical'   => 'https://arewecorp.com/',
    'jsonld'      => $jsonld,
]); ?>
</head>
<body>

<nav>
    <div class="container nav-inner">
        <a href="/" class="nav-logo">Are<span>We</span>Corp</a>
        <ul class="nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/search.php">Search Companies</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
        </ul>
    </div>
</nav>

<section class="hero">
    <div class="container hero-content">
        <div class="hero-eyebrow">Powered by SEC EDGAR Data</div>
        <h1>Is a Company<br><span>Family Owned?</span></h1>
        <p>Search any US company to instantly find out if it's family owned or corporately owned — verified from official SEC filings.</p>

        <div class="search-wrap">
            <form class="search-box" action="/search.php" method="GET">
                <input type="text" name="q" placeholder="Search any company — Walmart, Apple, Ford..." autocomplete="off" required aria-label="Search company name">
                <button type="submit">Look It Up</button>
            </form>
            <p class="search-hint">
                Try:
                <span onclick="document.querySelector('.search-box input').value='Walmart'">Walmart</span>
                <span onclick="document.querySelector('.search-box input').value='Ford Motor'">Ford Motor</span>
                <span onclick="document.querySelector('.search-box input').value='Apple'">Apple</span>
                <span onclick="document.querySelector('.search-box input').value='News Corp'">News Corp</span>
            </p>
        </div>
    </div>
</section>

<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat">
            <div class="stat-num">12,000+</div>
            <div class="stat-label">Companies Searchable</div>
        </div>
        <div class="stat">
            <div class="stat-num">SEC</div>
            <div class="stat-label">Official Data Source</div>
        </div>
        <div class="stat">
            <div class="stat-num">13D/13G</div>
            <div class="stat-label">Filing Types Analyzed</div>
        </div>
        <div class="stat">
            <div class="stat-num">Free</div>
            <div class="stat-label">Always Free to Search</div>
        </div>
    </div>
</div>

<div class="container">

    <section class="section" aria-label="Featured family owned companies">
        <div class="section-header">
            <h2 class="section-title"><span class="green"></span>Featured: Family Owned Companies</h2>
            <a href="/search.php?filter=family" class="view-all">View more &rarr;</a>
        </div>
        <div class="cards-grid">
            <?php foreach ($featured['family'] as $co): ?>
            <a href="/company.php?cik=<?= urlencode($co['cik']) ?>" class="company-card">
                <div class="card-top">
                    <div class="card-logo"><?= company_initials($co['name']) ?></div>
                    <span class="badge family">Family Owned</span>
                </div>
                <div>
                    <div class="card-name"><?= htmlspecialchars($co['name']) ?></div>
                    <div class="card-industry"><?= htmlspecialchars($co['industry']) ?></div>
                </div>
                <div class="card-owners">
                    <strong>Key owners:</strong> <?= htmlspecialchars($co['owners']) ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section" style="padding-top:0" aria-label="Featured corporately owned companies">
        <div class="section-header">
            <h2 class="section-title"><span class="red"></span>Featured: Corporately Owned Companies</h2>
            <a href="/search.php?filter=corporate" class="view-all">View more &rarr;</a>
        </div>
        <div class="cards-grid">
            <?php foreach ($featured['corporate'] as $co): ?>
            <a href="/company.php?cik=<?= urlencode($co['cik']) ?>" class="company-card">
                <div class="card-top">
                    <div class="card-logo"><?= company_initials($co['name']) ?></div>
                    <span class="badge corporate">Corporate</span>
                </div>
                <div>
                    <div class="card-name"><?= htmlspecialchars($co['name']) ?></div>
                    <div class="card-industry"><?= htmlspecialchars($co['industry']) ?></div>
                </div>
                <div class="card-owners">
                    <strong>Key owners:</strong> <?= htmlspecialchars($co['owners']) ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- FAQ Section for SEO -->
    <section class="section" style="padding-top:0" aria-label="Frequently asked questions">
        <div class="section-header">
            <h2 class="section-title">Common Questions About Company Ownership</h2>
        </div>
        <div class="info-grid">
            <div class="info-box">
                <h3 style="font-size:0.95rem;color:var(--navy);text-transform:none;letter-spacing:0;margin-bottom:0.5rem">How do I find out if a company is family owned?</h3>
                <p style="font-size:0.88rem;color:var(--gray-600);line-height:1.6">Search any company name above. We analyze SEC EDGAR filings — specifically 13D and 13G beneficial ownership reports — to identify whether individuals, families, or institutions control a company.</p>
            </div>
            <div class="info-box">
                <h3 style="font-size:0.95rem;color:var(--navy);text-transform:none;letter-spacing:0;margin-bottom:0.5rem">What makes a company "family owned"?</h3>
                <p style="font-size:0.88rem;color:var(--gray-600);line-height:1.6">A company is considered family owned when a founding family or individual holds majority voting control or a significant ownership stake — often through a dual-class share structure or direct holdings.</p>
            </div>
            <div class="info-box">
                <h3 style="font-size:0.95rem;color:var(--navy);text-transform:none;letter-spacing:0;margin-bottom:0.5rem">Where does the ownership data come from?</h3>
                <p style="font-size:0.88rem;color:var(--gray-600);line-height:1.6">All data is sourced directly from the SEC's EDGAR database — the same public records used by investors, journalists, and regulators. Companies that own 5%+ of shares must disclose this by law.</p>
            </div>
            <div class="info-box">
                <h3 style="font-size:0.95rem;color:var(--navy);text-transform:none;letter-spacing:0;margin-bottom:0.5rem">Can I look up private companies?</h3>
                <p style="font-size:0.88rem;color:var(--gray-600);line-height:1.6">Our database covers 12,000+ companies registered with the SEC. Purely private companies with no SEC filings — like small local businesses — won't appear, as they have no public ownership disclosure requirements.</p>
            </div>
        </div>
    </section>

</div>

<section class="how-section" id="how-it-works">
    <div class="container">
        <h2 style="color:#fff;font-size:1.35rem;font-weight:700;text-align:center;margin-bottom:0">How We Determine If a Company Is Family Owned</h2>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Search a Company</h3>
                <p>Type any US company name. We search across 12,000+ companies registered with the SEC.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>We Analyze SEC Filings</h3>
                <p>We pull 13D/13G beneficial ownership reports and proxy statements filed directly with the SEC.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Get the Verdict</h3>
                <p>See who the major owners are and whether the company is family controlled or institutionally owned.</p>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container footer-inner">
        <div class="footer-logo">Are<span>We</span>Corp</div>
        <p>Data from <a href="https://www.sec.gov/edgar" target="_blank" rel="noopener">SEC EDGAR</a>. For informational purposes only.</p>
    </div>
</footer>

</body>
</html>
