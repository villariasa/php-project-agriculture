<?php
// Shared report helpers: CSV export, print header block, and the summary strip.
// Every report supports ?export=csv using the same filtered rows it displays.

// Stream the report as a CSV download and stop the request.
// $headers = column labels, $rows = array of arrays in the same column order.
function csvDownload(string $filename_base, array $headers, array $rows) {
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"{$filename_base}_" . date("Y-m-d") . ".csv\"");
    $out = fopen("php://output", "w");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// Report heading shown only on the printed page: system name, title,
// applied filters, and generation date.
function reportPrintHeader(string $title, array $filters = []) {
    echo '<div class="hidden print:block mb-6 border-b border-gray-300 pb-4">';
    echo '<p class="text-sm text-gray-500">Agriculture Information System</p>';
    echo '<h1 class="text-xl font-bold">' . htmlspecialchars($title) . '</h1>';
    $parts = [];
    foreach ($filters as $label => $value) {
        if ($value !== "" && $value !== null) {
            $parts[] = htmlspecialchars($label) . ": " . htmlspecialchars($value);
        }
    }
    if (count($parts) > 0) {
        echo '<p class="text-sm text-gray-600 mt-1">Filters &mdash; ' . implode(" &middot; ", $parts) . '</p>';
    }
    echo '<p class="text-xs text-gray-400 mt-1">Generated ' . date("F j, Y g:i A") . '</p>';
    echo '</div>';
}

// Headline numbers strip above the results table. $items = label => value.
function summaryStrip(array $items) {
    echo '<div class="grid grid-cols-2 sm:grid-cols-' . min(4, count($items)) . ' gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden shadow-sm mb-6">';
    foreach ($items as $label => $value) {
        echo '<div class="bg-white p-4">';
        echo '<p class="text-xs uppercase tracking-wide text-gray-500 mb-1">' . htmlspecialchars($label) . '</p>';
        echo '<p class="text-xl font-bold">' . $value . '</p>';
        echo '</div>';
    }
    echo '</div>';
}

// Standard report action buttons (Export CSV keeps the current filters)
function reportActions() {
    $query = $_GET;
    $query["export"] = "csv";
    $csv_url = "?" . htmlspecialchars(http_build_query($query));
    echo '<div class="flex gap-2 print:hidden">';
    echo '<a href="' . $csv_url . '" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg inline-flex items-center gap-2">'
       . '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">'
       . '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>'
       . 'Export CSV</a>';
    echo '<button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg inline-flex items-center gap-2">'
       . '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">'
       . '<path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg>'
       . 'Print</button>';
    echo '</div>';
}
