<?php
/**
 * EDGAR API — SEC data integration
 * Docs: https://www.sec.gov/developer
 */

define('EDGAR_DATA',  'https://data.sec.gov');
define('EDGAR_EFTS',  'https://efts.sec.gov/LATEST/search-index');
define('EDGAR_BROWSE','https://www.sec.gov/cgi-bin/browse-edgar');
define('EDGAR_UA',    'AreWeCorp/1.0 savagep88@gmail.com');

function edgar_get(string $url, bool $xml = false) {
    $ctx = stream_context_create(['http' => [
        'header'        => "User-Agent: " . EDGAR_UA . "\r\nAccept: application/json\r\n",
        'timeout'       => 15,
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) throw new Exception("EDGAR fetch failed: $url");
    if ($xml) return $body;
    $data = json_decode($body, true);
    if ($data === null) throw new Exception("EDGAR invalid JSON from: $url");
    return $data;
}

/**
 * Search companies by name using EDGAR company browse (Atom feed).
 * This searches actual company names, not filing text.
 */
function edgar_search_companies(string $query, int $limit = 40): array {
    $url = EDGAR_BROWSE
         . '?company=' . urlencode($query)
         . '&action=getcompany&type=10-K&dateb=&owner=include&count=' . $limit
         . '&output=atom';

    $xml_str = edgar_get($url, true);

    // Parse CIKs from the Atom feed
    $ciks = [];
    if (preg_match_all('/urn:tag:www\.sec\.gov:cik=(\d+)/', $xml_str, $m)) {
        $ciks = array_unique($m[1]);
    }

    if (empty($ciks)) return [];

    // Fetch company names from submissions API (batch up to 15)
    $results = [];
    foreach (array_slice($ciks, 0, 15) as $raw_cik) {
        $cik    = ltrim($raw_cik, '0');
        $padded = str_pad($cik, 10, '0', STR_PAD_LEFT);
        try {
            $sub = edgar_get(EDGAR_DATA . "/submissions/CIK{$padded}.json");
            if (!empty($sub['name'])) {
                $results[] = [
                    'name'     => $sub['name'],
                    'cik'      => $cik,
                    'sic_desc' => $sub['sicDescription'] ?? '',
                    'state'    => $sub['stateOfIncorporation'] ?? '',
                    'tickers'  => $sub['tickers'] ?? [],
                ];
            }
        } catch (Exception $e) {
            // skip this CIK
        }
    }

    return $results;
}

/**
 * Get full company info from EDGAR submissions endpoint.
 */
function edgar_get_company(string $cik): array {
    $padded = str_pad($cik, 10, '0', STR_PAD_LEFT);
    $data   = edgar_get(EDGAR_DATA . "/submissions/CIK{$padded}.json");

    return [
        'name'            => $data['name']                    ?? 'Unknown',
        'cik'             => $cik,
        'sic'             => $data['sic']                     ?? null,
        'sic_description' => $data['sicDescription']          ?? null,
        'state'           => $data['stateOfIncorporation']    ?? null,
        'tickers'         => $data['tickers']                 ?? [],
        'exchanges'       => $data['exchanges']               ?? [],
        'website'         => $data['website']                 ?? null,
        'category'        => $data['category']                ?? null,
        'filings'         => $data['filings']['recent']       ?? [],
    ];
}

/**
 * Get recent filings of a specific form type.
 */
function edgar_get_filings(string $cik, string $form_type, int $limit = 5): array {
    $company = edgar_get_company($cik);
    $filings = $company['filings'];
    $results = [];

    foreach ($filings['form'] ?? [] as $i => $form) {
        if (strcasecmp(trim($form), $form_type) === 0) {
            $results[] = [
                'date'        => $filings['filingDate'][$i]    ?? null,
                'accession'   => str_replace('-', '', $filings['accessionNumber'][$i] ?? ''),
                'primary_doc' => $filings['primaryDocument'][$i] ?? null,
            ];
            if (count($results) >= $limit) break;
        }
    }
    return $results;
}

/**
 * Find entities that have filed 13D/13G reports for this company.
 * These are parties that own 5%+ of shares and must disclose to the SEC.
 */
function edgar_get_beneficial_owners(string $cik, string $company_name): array {
    // Search EFTS for 13D/13G filings mentioning this company name
    $url = EDGAR_EFTS
         . '?q=' . urlencode('"' . $company_name . '"')
         . '&forms=SC+13G,SC+13D&dateRange=custom&startdt=2021-01-01';

    $data   = edgar_get($url);
    $owners = [];
    $seen   = [];

    foreach ($data['hits']['hits'] ?? [] as $hit) {
        $src          = $hit['_source'] ?? [];
        $display_names = $src['display_names'] ?? [];

        foreach ($display_names as $dn) {
            // Format: "Company Name  (TICKER)  (CIK XXXXXXXXXX)"
            if (preg_match('/^(.+?)\s*(?:\([A-Z]+\))?\s*\(CIK\s*\d+\)/', $dn, $m)) {
                $name = trim($m[1]);
            } else {
                $name = trim(preg_replace('/\s*\(CIK.*/', '', $dn));
            }

            if (!$name || isset($seen[$name])) continue;
            $seen[$name] = true;

            $owners[] = [
                'name' => $name,
                'date' => $src['file_date'] ?? null,
            ];
        }

        if (count($owners) >= 15) break;
    }

    return $owners;
}
