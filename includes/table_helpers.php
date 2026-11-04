<?php
// Shared list-page helpers: whitelisted column sorting, pagination math,
// sortable header cells, and the pagination bar. Used by every module index.

const PER_PAGE = 10;

// Resolve ?sort= / ?dir= against the module's whitelist.
// SQL identifiers come ONLY from the whitelist values — never from the URL.
// Returns [order_by_sql, direction, active_sort_key].
function resolveSort(array $whitelist, string $default_key, string $default_dir = "asc") {
    $key = (string) ($_GET["sort"] ?? "");
    if (!isset($whitelist[$key])) {
        $key = $default_key;
    }
    $dir = strtolower((string) ($_GET["dir"] ?? $default_dir)) === "desc" ? "DESC" : "ASC";
    return [$whitelist[$key], $dir, $key];
}

// Current page number from ?page= (minimum 1)
function currentPage() {
    return max(1, (int) ($_GET["page"] ?? 1));
}

// Row offset for the current page
function pageOffset(int $page) {
    return ($page - 1) * PER_PAGE;
}

// Query string that keeps the current GET filters and applies overrides.
// One-shot params (toast messages, add-card reopen) are dropped so sort and
// pagination links do not replay them.
function buildQuery(array $overrides) {
    $params = $_GET;
    unset($params["msg"], $params["error"], $params["add"], $params["edit"]);
    return htmlspecialchars(http_build_query(array_merge($params, $overrides)));
}

// Sortable <th>: link that sorts by $key, toggling asc/desc when already active
function sortableTh(string $key, string $label, string $sort_key, string $dir, string $align = "left") {
    $is_active = $key === $sort_key;
    $next_dir  = $is_active && $dir === "ASC" ? "desc" : "asc";
    $query     = buildQuery(["sort" => $key, "dir" => $next_dir, "page" => 1]);
    $arrow     = "";
    if ($is_active) {
        $path  = $dir === "ASC" ? "m4.5 15.75 7.5-7.5 7.5 7.5" : "m19.5 8.25-7.5 7.5-7.5-7.5";
        $arrow = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">'
               . '<path stroke-linecap="round" stroke-linejoin="round" d="' . $path . '" /></svg>';
    }
    echo '<th class="px-4 py-3 text-' . $align . '">'
       . '<a href="?' . $query . '" class="inline-flex items-center gap-1 hover:text-gray-800 ' . ($is_active ? 'text-gray-800' : '') . '">'
       . htmlspecialchars($label) . $arrow
       . '</a></th>';
}

// Pagination bar: "Showing X–Y of Z" plus Prev / numbered / Next links.
// Preserves all current GET filters in every link.
function paginationBar(int $page, int $total_rows) {
    $total_pages = max(1, (int) ceil($total_rows / PER_PAGE));
    $from        = $total_rows === 0 ? 0 : pageOffset($page) + 1;
    $to          = min($total_rows, $page * PER_PAGE);

    echo '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-gray-200 text-sm print:hidden">';
    echo '<p class="text-gray-500">Showing <span class="font-medium text-gray-800">' . $from . '&ndash;' . $to
       . '</span> of <span class="font-medium text-gray-800">' . number_format($total_rows) . '</span> records</p>';

    if ($total_pages > 1) {
        echo '<nav class="inline-flex items-center gap-1" aria-label="Pagination">';

        $link_class   = "px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50";
        $active_class = "px-3 py-1.5 rounded-lg border border-green-600 bg-green-600 text-white font-medium";
        $gap_class    = "px-2 text-gray-400";

        if ($page > 1) {
            echo '<a href="?' . buildQuery(["page" => $page - 1]) . '" class="' . $link_class . '">Prev</a>';
        }

        // Window: first, last, and the pages around the current one
        $printed_gap = false;
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i === 1 || $i === $total_pages || abs($i - $page) <= 2) {
                $class = $i === $page ? $active_class : $link_class;
                echo '<a href="?' . buildQuery(["page" => $i]) . '" class="' . $class . '">' . $i . '</a>';
                $printed_gap = false;
            } elseif (!$printed_gap) {
                echo '<span class="' . $gap_class . '">&hellip;</span>';
                $printed_gap = true;
            }
        }

        if ($page < $total_pages) {
            echo '<a href="?' . buildQuery(["page" => $page + 1]) . '" class="' . $link_class . '">Next</a>';
        }

        echo '</nav>';
    }
    echo '</div>';
}
