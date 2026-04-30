<?php
/**
 * EDGAR API — SEC data integration
 * Docs: https://www.sec.gov/developer
 */

define('EDGAR_DATA',  'https://data.sec.gov');
define('EDGAR_EFTS',  'https://efts.sec.gov/LATEST/search-index');
define('EDGAR_UA',    'AreWeCorp/1.0 savagep88@gmail.com');

function edgar_get(string $url): array {
    $ctx = stream_context_create(['http' => [
        'header'  => "User-Agent: " . EDGAR_UA . "\r\nAccept: application/json\r\n",
        'timeout' => 15,
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) throw new Exception("EDGAR fetch failed: $url");
    $data = json_decode($body, true);
    if ($data === null) throw new Exception("EDGAR invalid JSON from: $url");
    return $data;
}

/**
 * Search companies by name.
 * Returns array of ['name' => ..., 'cik' => ..., 'state' => ..., 'sic' => ...]
 */
function edgar_search_companies(string $query, int $limit = 40): array {
    // Use EDGAR full-text search to find companies by name
    $url = EDGAR_EFTS . '?q=' . urlencode('"' . $query . '"')
         . '&forms=10-K&dateRange=custom&startdt=2022-01-01'
         . '&hits.hits._source=entity_name,entity_id,period_of_report,file_num';

    $data    = edgar_get($url);
    $results = [];
    $seen    = [];

    foreach ($data['hits']['hits'] ?? [] as $hit) {
        $src  = $hit['_source'] ?? [];
        $name = $src['entity_name'] ?? null;
        $cik  = isset($src['entity_id']) ? ltrim($src['entity_id'], '0') : null;

        if (!$name || !$cik || isset($seen[$cik])) continue;
        $seen[$cik] = true;

        $results[] = ['name' => $name, 'cik' => $cik];
        if (count($results) >= $limit) break;
    }

    // If no 10-K results, fall back to broader search
    if (empty($results)) {
        $url2    = EDGAR_EFTS . '?q=' . urlencode($query) . '&forms=10-K,10-K405&dateRange=custom&startdt=2020-01-01';
        $data2   = edgar_get($url2);
        foreach ($data2['hits']['hits'] ?? [] as $hit) {
            $src  = $hit['_source'] ?? [];
            $name = $src['entity_name'] ?? null;
            $cik  = isset($src['entity_id']) ? ltrim($src['entity_id'], '0') : null;
            if (!$name || !$cik || isset($seen[$cik])) continue;
            $seen[$cik] = true;
            $results[]  = ['name' => $name, 'cik' => $cik];
            if (count($results) >= $limit) break;
        }
    }

    return $results;
}

/**
 * Get company info from EDGAR submissions endpoint.
 */
function edgar_get_company(string $cik): array {
    $padded = str_pad($cik, 10, '0', STR_PAD_LEFT);
    $data   = edgar_get(EDGAR_DATA . "/submissions/CIK{$padded}.json");

    return [
        'name'            => $data['name']              ?? 'Unknown',
        'cik'             => $cik,
        'sic'             => $data['sic']               ?? null,
        'sic_description' => $data['sicDescription']    ?? null,
        'state'           => $data['stateOfIncorporation'] ?? null,
        'tickers'         => $data['tickers']           ?? [],
        'exchanges'       => $data['exchanges']         ?? [],
        'website'         => $data['website']           ?? null,
        'category'        => $data['category']          ?? null,
        'filings'         => $data['filings']['recent'] ?? [],
    ];
}

/**
 * Get recent filings of a specific form type for a company.
 */
function edgar_get_filings(string $cik, string $form_type, int $limit = 5): array {
    $company = edgar_get_company($cik);
    $filings = $company['filings'];
    $results = [];

    foreach ($filings['form'] ?? [] as $i => $form) {
        if (strcasecmp(trim($form), $form_type) === 0) {
            $accession = str_replace('-', '', $filings['accessionNumber'][$i] ?? '');
            $results[] = [
                'date'        => $filings['filingDate'][$i]    ?? null,
                'accession'   => $accession,
                'primary_doc' => $filings['primaryDocument'][$i] ?? null,
            ];
            if (count($results) >= $limit) break;
        }
    }
    return $results;
}

/**
 * Fetch 13D/13G filers (beneficial owners) who have reported owning 5%+ of this company.
 * Searches EDGAR for filings that name this company as the issuer.
 */
function edgar_get_beneficial_owners(string $cik, string $company_name): array {
    // Search for SC 13D and SC 13G filings referencing this company
    $url = EDGAR_EFTS . '?q=' . urlencode('"' . $company_name . '"')
         . '&forms=SC+13G,SC+13D&dateRange=custom&startdt=2022-01-01'
         . '&hits.hits._source=entity_name,file_date,period_of_report';

    $data    = edgar_get($url);
    $owners  = [];
    $seen    = [];

    foreach ($data['hits']['hits'] ?? [] as $hit) {
        $src  = $hit['_source'] ?? [];
        $name = $src['entity_name'] ?? null;
        if (!$name || isset($seen[$name])) continue;
        $seen[$name] = true;
        $owners[] = [
            'name' => $name,
            'date' => $src['file_date'] ?? ($src['period_of_report'] ?? null),
            'form' => $hit['_source']['period_of_report'] ?? null,
        ];
        if (count($owners) >= 15) break;
    }

    return $owners;
}
