<?php
$base = "";
require_once "includes/auth_check.php";
require_once "config/Database.php";

$pdo = (new Database())->connect();

// App root path so result links work when the fragment is injected into
// pages inside subfolders (farmers/, reports/, ...) via the live search.
$app_root = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/") . "/";

$is_ajax = ($_GET["ajax"] ?? "") === "1";
$q       = (string) trim($_GET["q"] ?? "");
$limit   = $is_ajax ? 5 : 20;

$farmers = [];
$farms   = [];
$crops   = [];

if ($q !== "") {
    $term = "%{$q}%";

    $stmt = $pdo->prepare(
        "SELECT farmer_id, first_name, last_name, contact_number, address
         FROM farmer
         WHERE is_deleted = FALSE
           AND (first_name ILIKE :term OR last_name ILIKE :term OR address ILIKE :term)
         ORDER BY last_name, first_name
         LIMIT " . (int) $limit
    );
    $stmt->execute(["term" => $term]);
    $farmers = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT f.farm_id, f.farm_name, f.location, fm.first_name || ' ' || fm.last_name AS owner_name
         FROM farm f
         JOIN farmer fm ON f.farmer_id = fm.farmer_id
         WHERE f.is_deleted = FALSE
           AND (f.farm_name ILIKE :term OR f.location ILIKE :term)
         ORDER BY f.farm_name
         LIMIT " . (int) $limit
    );
    $stmt->execute(["term" => $term]);
    $farms = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT c.crop_id, c.crop_name, c.variety, cc.category_name
         FROM crop c
         JOIN crop_categories cc ON c.category_id = cc.category_id
         WHERE c.is_deleted = FALSE
           AND (c.crop_name ILIKE :term OR c.variety ILIKE :term)
         ORDER BY c.crop_name
         LIMIT " . (int) $limit
    );
    $stmt->execute(["term" => $term]);
    $crops = $stmt->fetchAll();
}

$total_results = count($farmers) + count($farms) + count($crops);

// Shared results fragment — rendered on this page AND returned raw for the
// live search (?ajax=1) used by the topbar dropdown and this page's input.
function renderSearchResults(string $q, array $farmers, array $farms, array $crops, string $root) {
    $total = count($farmers) + count($farms) + count($crops);

    if ($q === "") {
        return; // caller decides what to show for an empty query
    }
    if ($total === 0) {
        echo '<div class="px-4 py-8 text-center text-sm text-gray-500">No results for &ldquo;' . htmlspecialchars($q) . '&rdquo;.</div>';
        return;
    }

    // Links open the module with the record's Edit slider already open
    $groups = [
        ["label" => "Farmers", "rows" => $farmers, "url" => "farmers/index.php?edit=", "pk" => "farmer_id"],
        ["label" => "Farms",   "rows" => $farms,   "url" => "farms/index.php?edit=",   "pk" => "farm_id"],
        ["label" => "Crops",   "rows" => $crops,   "url" => "crops/index.php?edit=",   "pk" => "crop_id"],
    ];

    foreach ($groups as $group) {
        if (count($group["rows"]) === 0) {
            continue;
        }
        echo '<p class="px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">' . $group["label"] . '</p>';
        foreach ($group["rows"] as $row) {
            $href = $root . $group["url"] . $row[$group["pk"]];
            if ($group["label"] === "Farmers") {
                $title = $row["first_name"] . " " . $row["last_name"];
                $sub   = $row["address"];
                $side  = $row["contact_number"];
            } elseif ($group["label"] === "Farms") {
                $title = $row["farm_name"];
                $sub   = $row["location"];
                $side  = $row["owner_name"];
            } else {
                $title = $row["crop_name"];
                $sub   = $row["variety"];
                $side  = $row["category_name"];
            }
            echo '<a href="' . htmlspecialchars($href) . '" class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-gray-50">'
               . '<span class="min-w-0"><span class="block text-sm font-medium text-gray-800 truncate">' . htmlspecialchars($title) . '</span>'
               . '<span class="block text-xs text-gray-500 truncate">' . htmlspecialchars($sub) . '</span></span>'
               . '<span class="text-xs text-gray-400 shrink-0 truncate max-w-[40%]">' . htmlspecialchars($side) . '</span>'
               . '</a>';
        }
    }
    echo '<a href="' . htmlspecialchars($root) . 'search.php?q=' . urlencode($q) . '" class="block px-4 py-2.5 text-center text-sm text-green-700 font-medium border-t border-gray-100 hover:bg-gray-50">'
       . 'View all results (' . $total . ')</a>';
}

// Live-search fragment: return only the result rows and stop
if ($is_ajax) {
    renderSearchResults($q, $farmers, $farms, $crops, $app_root);
    exit;
}

// Quick links shown while the search box is empty
$browse_links = [
    ["label" => "Farmers",   "url" => "farmers/index.php",
     "icon" => "M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"],
    ["label" => "Farms",     "url" => "farms/index.php",
     "icon" => "M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819"],
    ["label" => "Crops",     "url" => "crops/index.php",
     "icon" => "M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z"],
    ["label" => "Plantings", "url" => "plantings/index.php",
     "icon" => "M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"],
    ["label" => "Harvests",  "url" => "harvests/index.php",
     "icon" => "m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"],
    ["label" => "Reports",   "url" => "reports/index.php",
     "icon" => "M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"],
];

$page_title  = "Search";
$active      = "";
$breadcrumbs = [["Search", null]];
require "includes/header.php";
?>

<div class="max-w-2xl mx-auto">

<!-- Big live search box -->
<form method="GET" class="mb-4">
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
             class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input type="text" name="q" id="page-search" value="<?= htmlspecialchars($q) ?>" autofocus autocomplete="off"
               placeholder="Search farmers, farms, crops..."
               class="w-full pl-12 pr-4 py-3 text-base bg-white border border-gray-300 rounded-full shadow-sm
                      focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
    </div>
    <p class="text-xs text-gray-400 mt-2 px-1">Results appear as you type.</p>
</form>

<!-- Live results / browse shortcuts -->
<div id="page-search-results" class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden <?= $q === "" ? "hidden" : "" ?> divide-y divide-gray-50 pb-1">
    <?php renderSearchResults($q, $farmers, $farms, $crops, $app_root); ?>
</div>

<div id="browse-links" class="<?= $q === "" ? "" : "hidden" ?>">
    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2 px-1">Browse</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <?php foreach ($browse_links as $link): ?>
            <a href="<?= $link['url'] ?>"
               class="flex items-center gap-3 px-4 py-3 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:border-green-600 hover:text-green-700">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $link['icon'] ?>" />
                </svg>
                <?= $link['label'] ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

</div>

<script>
// Live search on this page: fetch the shared fragment as the user types
(function () {
    var input   = document.getElementById("page-search");
    var results = document.getElementById("page-search-results");
    var browse  = document.getElementById("browse-links");
    var timer   = null;

    input.addEventListener("input", function () {
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) {
            results.classList.add("hidden");
            browse.classList.remove("hidden");
            return;
        }
        timer = setTimeout(function () {
            fetch("search.php?ajax=1&q=" + encodeURIComponent(q))
                .then(function (response) { return response.text(); })
                .then(function (html) {
                    results.innerHTML = html;
                    results.classList.remove("hidden");
                    browse.classList.add("hidden");
                });
        }, 250);
    });
})();
</script>

<?php require "includes/footer.php"; ?>
