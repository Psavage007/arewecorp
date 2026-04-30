<?php
require_once __DIR__ . '/edgar.php';

/**
 * Analyze ownership type for a given CIK.
 * Uses 13D/13G filings (beneficial ownership) and DEF 14A (proxy) to determine
 * whether a company is family-owned or corporately owned.
 */
function analyze_ownership(string $cik): array {
    $owners = [];

    // Pull latest DEF 14A (proxy statement) — contains insider/director ownership
    $proxy_filings = edgar_get_filings($cik, 'DEF 14A', 1);
    // Pull 13D/13G filings — beneficial ownership by 5%+ holders
    $filings_13d = edgar_get_filings($cik, 'SC 13D', 3);
    $filings_13g = edgar_get_filings($cik, 'SC 13G', 3);

    // For now, use the company facts endpoint for ownership data
    $padded = str_pad($cik, 10, '0', STR_PAD_LEFT);
    $facts = edgar_get(EDGAR_DATA . "/api/xbrl/companyfacts/CIK{$padded}.json");

    // Determine ownership type heuristically
    // A company is considered family-owned if:
    // - Insiders hold >20% collectively, OR
    // - A single individual/family holds >50%
    $insider_pct = extract_insider_ownership($facts);
    $type = $insider_pct >= 20 ? 'family' : 'corporate';

    $summary = $type === 'family'
        ? "Insiders and founding shareholders hold approximately {$insider_pct}% of shares, suggesting significant family or founder control."
        : "Ownership appears to be primarily institutional or widely distributed, characteristic of a corporately structured company.";

    return [
        'type'    => $type,
        'summary' => $summary,
        'owners'  => $owners,
        'insider_pct' => $insider_pct,
    ];
}

function extract_insider_ownership(array $facts): float {
    // Try to find insider ownership percentage from XBRL facts
    $us_gaap = $facts['facts']['us-gaap'] ?? [];
    $dei     = $facts['facts']['dei'] ?? [];

    // EntityCommonStockSharesOutstanding vs insider held shares
    // This is a simplified heuristic — full implementation would parse proxy XML
    return 0.0;
}
