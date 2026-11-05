<?php
$base = "";
require_once "includes/auth_check.php";
require_once "config/Database.php";

$pdo = (new Database())->connect();

// ---------- Date range (defaults to the last 12 months) ----------
$default_from = date("Y-m-01", strtotime("-11 months"));
$default_to   = date("Y-m-d");
$date_from    = (string) trim($_GET["from"] ?? "");
$date_to      = (string) trim($_GET["to"] ?? "");
if ($date_from === "") { $date_from = $default_from; }
if ($date_to === "")   { $date_to   = $default_to; }
$range = ["date_from" => $date_from, "date_to" => $date_to];

// ---------- Month-over-month KPIs (fixed to the calendar month, not the range) ----------
$stmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN harvest_date >= DATE_TRUNC('month', CURRENT_DATE)
                          THEN quantity_kg * selling_price_per_kg END), 0) AS revenue_this,
        COALESCE(SUM(CASE WHEN harvest_date >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '1 month'
                           AND harvest_date <  DATE_TRUNC('month', CURRENT_DATE)
                          THEN quantity_kg * selling_price_per_kg END), 0) AS revenue_last,
        COALESCE(SUM(CASE WHEN harvest_date >= DATE_TRUNC('month', CURRENT_DATE)
                          THEN quantity_kg END), 0) AS kg_this,
        COALESCE(SUM(CASE WHEN harvest_date >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '1 month'
                           AND harvest_date <  DATE_TRUNC('month', CURRENT_DATE)
                          THEN quantity_kg END), 0) AS kg_last
     FROM harvest
     WHERE is_deleted = FALSE"
);
$stmt->execute();
$month_kpis = $stmt->fetch();

// Percent change vs last month; null when last month had no data
function pctChange(float $current, float $previous) {
    if ($previous <= 0) {
        return null;
    }
    return (($current - $previous) / $previous) * 100;
}
$revenue_pct = pctChange((float) $month_kpis['revenue_this'], (float) $month_kpis['revenue_last']);
$kg_pct      = pctChange((float) $month_kpis['kg_this'], (float) $month_kpis['kg_last']);

// ---------- Range-scoped KPIs ----------
// Average yield per hectare: each planting's harvested kg in range over its planted area
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(t.kg) / NULLIF(SUM(t.area), 0), 0)
     FROM (
        SELECT p.area_planted AS area, SUM(h.quantity_kg) AS kg
        FROM harvest h
        JOIN planting p ON h.planting_id = p.planting_id
        WHERE h.is_deleted = FALSE AND p.is_deleted = FALSE
          AND h.harvest_date BETWEEN :date_from AND :date_to
        GROUP BY p.planting_id, p.area_planted
     ) t"
);
$stmt->execute($range);
$avg_yield_per_ha = (float) $stmt->fetchColumn();

$active_plantings = $pdo->query(
    "SELECT COUNT(*) FROM planting p
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE AND ps.status_name IN ('Planted', 'Growing')"
)->fetchColumn();

$due_soon = $pdo->query(
    "SELECT COUNT(*) FROM planting p
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE
       AND ps.status_name <> 'Harvested'
       AND p.expected_harvest_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '14 days'"
)->fetchColumn();

$total_farmers = $pdo->query("SELECT COUNT(*) FROM farmer WHERE is_deleted = FALSE")->fetchColumn();

// ---------- Monthly series for the charts (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT TO_CHAR(DATE_TRUNC('month', harvest_date), 'Mon YYYY') AS month_label,
            SUM(quantity_kg) AS total_kg,
            SUM(quantity_kg * selling_price_per_kg) AS total_revenue
     FROM harvest
     WHERE is_deleted = FALSE
       AND harvest_date BETWEEN :date_from AND :date_to
     GROUP BY DATE_TRUNC('month', harvest_date)
     ORDER BY DATE_TRUNC('month', harvest_date)"
);
$stmt->execute($range);
$monthly = $stmt->fetchAll();

// ---------- Top 5 crops by harvested kg (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT c.crop_name, SUM(h.quantity_kg) AS total_kg
     FROM harvest h
     JOIN planting p ON h.planting_id = p.planting_id
     JOIN crop c ON p.crop_id = c.crop_id
     WHERE h.is_deleted = FALSE
       AND h.harvest_date BETWEEN :date_from AND :date_to
     GROUP BY c.crop_name
     ORDER BY SUM(h.quantity_kg) DESC
     LIMIT 5"
);
$stmt->execute($range);
$top_crops = $stmt->fetchAll();

// ---------- Top 5 farms by revenue (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT f.farm_name, fm.first_name || ' ' || fm.last_name AS owner_name,
            SUM(h.quantity_kg) AS total_kg,
            SUM(h.quantity_kg * h.selling_price_per_kg) AS total_revenue
     FROM harvest h
     JOIN planting p ON h.planting_id = p.planting_id
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN farmer fm ON f.farmer_id = fm.farmer_id
     WHERE h.is_deleted = FALSE
       AND h.harvest_date BETWEEN :date_from AND :date_to
     GROUP BY f.farm_name, owner_name
     ORDER BY SUM(h.quantity_kg * h.selling_price_per_kg) DESC
     LIMIT 5"
);
$stmt->execute($range);
$top_farms = $stmt->fetchAll();

// ---------- Right rail panels ----------
$upcoming_harvests = $pdo->query(
    "SELECT p.expected_harvest_date, f.farm_name, c.crop_name,
            (p.expected_harvest_date - CURRENT_DATE) AS days_left
     FROM planting p
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN crop c ON p.crop_id = c.crop_id
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE
       AND ps.status_name <> 'Harvested'
       AND p.expected_harvest_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '14 days'
     ORDER BY p.expected_harvest_date
     LIMIT 8"
)->fetchAll();

$recent_harvests = $pdo->query(
    "SELECT h.harvest_date, h.quantity_kg, h.selling_price_per_kg, c.crop_name, f.farm_name
     FROM harvest h
     JOIN planting p ON h.planting_id = p.planting_id
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN crop c ON p.crop_id = c.crop_id
     WHERE h.is_deleted = FALSE
     ORDER BY h.harvest_id DESC
     LIMIT 5"
)->fetchAll();

$recent_plantings = $pdo->query(
    "SELECT p.planting_date, ps.status_name AS status, f.farm_name, c.crop_name
     FROM planting p
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN crop c ON p.crop_id = c.crop_id
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE
     ORDER BY p.planting_id DESC
     LIMIT 5"
)->fetchAll();

$badge_colors = [
    "Planted"   => "bg-blue-50 text-blue-700",
    "Growing"   => "bg-yellow-50 text-yellow-700",
    "Harvested" => "bg-green-50 text-green-700",
];

// Renders the small "vs last month" comparison line under a KPI number
function trendLine($pct) {
    if ($pct === null) {
        return '<p class="text-xs text-gray-400 mt-1">no data last month</p>';
    }
    $up    = $pct >= 0;
    $color = $up ? "text-green-700" : "text-red-600";
    $sign  = $up ? "+" : "";
    return '<p class="text-xs ' . $color . ' mt-1">' . $sign . number_format($pct, 1) . '% vs last month</p>';
}

$page_title = "Dashboard";
$active     = "dashboard";
require "includes/header.php";
?>

<!-- Page header + date range -->
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <p class="text-sm text-gray-500">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>!</p>
    </div>
    <!-- Mobile: one-line summary pill opens a bottom sheet with the real filter -->
    <button type="button" onclick="openDateSheet()" class="sm:hidden self-start inline-flex items-center gap-2 bg-white border border-gray-300 rounded-full shadow-sm px-4 py-2 text-sm text-gray-700 print:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
        </svg>
        <?= htmlspecialchars(date("M j", strtotime($date_from))) ?> &ndash; <?= htmlspecialchars(date("M j", strtotime($date_to))) ?>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-gray-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <!-- Desktop: full inline filter, unchanged -->
    <form method="GET" class="hidden sm:flex flex-wrap items-end gap-2 print:hidden">
        <div class="flex-1 min-w-[8.5rem] sm:flex-none">
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>"
                   class="w-full sm:w-auto px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-full shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        </div>
        <div class="flex-1 min-w-[8.5rem] sm:flex-none">
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>"
                   class="w-full sm:w-auto px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-full shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        </div>
        <button type="submit" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-1.5 rounded-full text-sm shadow-sm">Apply</button>
        <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700 pb-2">Reset</a>
    </form>
</div>

<!-- Mobile date-filter sheet (same visual language as the app's Records/Menu sheets) -->
<div id="date-sheet-overlay" class="sm:hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-40 opacity-0 pointer-events-none transition-opacity duration-300 print:hidden" onclick="closeDateSheet()"></div>
<div id="date-sheet" class="sm:hidden fixed inset-x-0 bottom-0 z-50 bg-white border-t border-gray-200 rounded-t-2xl shadow-lg translate-y-full print:hidden">
    <div class="pt-2.5 flex justify-center"><span class="w-10 h-1 rounded-full bg-gray-300"></span></div>
    <div class="flex items-center justify-between px-5 pt-1.5 pb-3 border-b border-gray-100">
        <h2 class="font-semibold">Filter by Date</h2>
        <button type="button" onclick="closeDateSheet()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="GET" class="p-5 pb-7 space-y-4">
        <div>
            <label class="block text-sm text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>"
                   class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        </div>
        <div>
            <label class="block text-sm text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>"
                   class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        </div>
        <div class="flex gap-2 pt-1">
            <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 rounded-lg">Apply</button>
            <a href="index.php" class="flex-1 text-center bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 py-2.5 rounded-lg">Reset</a>
        </div>
    </form>
</div>

<script>
(function () {
    window.openDateSheet = function () {
        var overlay = document.getElementById("date-sheet-overlay");
        var sheet   = document.getElementById("date-sheet");
        if (overlay) { overlay.classList.remove("opacity-0", "pointer-events-none"); }
        if (sheet)   { sheet.classList.remove("translate-y-full"); }
    };
    window.closeDateSheet = function () {
        var overlay = document.getElementById("date-sheet-overlay");
        var sheet   = document.getElementById("date-sheet");
        if (overlay) { overlay.classList.add("opacity-0", "pointer-events-none"); }
        if (sheet)   { sheet.classList.add("translate-y-full"); }
    };
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") { closeDateSheet(); }
    });

    // Swipe-down to dismiss (same technique as the app's other bottom sheets)
    var sheet = document.getElementById("date-sheet");
    if (sheet) {
        var startY = 0, dragY = 0, dragging = false;
        sheet.addEventListener("touchstart", function (event) {
            if (sheet.classList.contains("translate-y-full")) { return; }
            startY = event.touches[0].clientY;
            dragY = 0;
            dragging = true;
            sheet.style.transition = "none";
        }, { passive: true });
        sheet.addEventListener("touchmove", function (event) {
            if (!dragging) { return; }
            dragY = Math.max(0, event.touches[0].clientY - startY);
            sheet.style.transform = "translateY(" + dragY + "px)";
        }, { passive: true });
        sheet.addEventListener("touchend", function () {
            if (!dragging) { return; }
            dragging = false;
            sheet.style.transition = "";
            sheet.style.transform  = "";
            if (dragY > 90) { closeDateSheet(); }
            dragY = 0;
        });
    }
})();
</script>

<?php
// Shared KPI data so the mobile scroll-row and desktop grid render from one list
$kpi_tiles = [
    ["url" => "reports/harvest_revenue.php", "label" => "Revenue (this month)",
     "value" => "&#8369;" . number_format((float) $month_kpis['revenue_this'], 2), "sub" => trendLine($revenue_pct)],
    ["url" => "harvests/index.php", "label" => "Harvested (this month)",
     "value" => number_format((float) $month_kpis['kg_this'], 1) . " <span class='text-sm font-normal text-gray-500'>kg</span>", "sub" => trendLine($kg_pct)],
    ["url" => "reports/yield_efficiency.php", "label" => "Avg Yield / Hectare",
     "value" => number_format($avg_yield_per_ha, 1) . " <span class='text-sm font-normal text-gray-500'>kg/ha</span>",
     "sub" => "<p class='text-xs text-gray-400 mt-1'>within selected range</p>"],
    ["url" => "plantings/index.php", "label" => "Active Plantings",
     "value" => number_format($active_plantings), "sub" => "<p class='text-xs text-gray-400 mt-1'>planted or growing</p>"],
    ["url" => "plantings/index.php", "label" => "Harvest Due Soon",
     "value" => number_format($due_soon), "sub" => "<p class='text-xs text-gray-400 mt-1'>expected within 14 days</p>"],
    ["url" => "farmers/index.php", "label" => "Total Farmers",
     "value" => number_format($total_farmers), "sub" => "<p class='text-xs text-gray-400 mt-1'>active records</p>"],
];
?>

<!-- KPI Strip: mobile = horizontal scroll-snap row (saves vertical space,
     next-card peek signals swipeability); desktop = full grid, all visible. -->
<div id="kpi-scroll" class="sm:hidden flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2 mb-6 -mx-4 px-4 sm:mx-0 sm:px-0">
    <?php foreach ($kpi_tiles as $tile): ?>
        <a href="<?= $tile['url'] ?>" class="snap-start shrink-0 w-[42%] min-w-[9.5rem] bg-white rounded-lg border border-gray-200 shadow-sm p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 mb-1"><?= $tile['label'] ?></p>
            <p class="text-xl font-bold"><?= $tile['value'] ?></p>
            <?= $tile['sub'] ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="hidden sm:grid sm:grid-cols-3 xl:grid-cols-6 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden shadow-sm mb-6">
    <?php foreach ($kpi_tiles as $tile): ?>
        <a href="<?= $tile['url'] ?>" class="bg-white p-4 hover:bg-gray-50">
            <p class="text-xs uppercase tracking-wide text-gray-500 mb-1"><?= $tile['label'] ?></p>
            <p class="text-xl sm:text-2xl font-bold"><?= $tile['value'] ?></p>
            <?= $tile['sub'] ?>
        </a>
    <?php endforeach; ?>
</div>

<style>
    /* Hide the scrollbar on the KPI row — snap + peek is the only affordance needed */
    #kpi-scroll { scrollbar-width: none; -ms-overflow-style: none; }
    #kpi-scroll::-webkit-scrollbar { display: none; }
</style>

<div class="flex flex-col xl:flex-row gap-6">

    <!-- Primary feed: charts + top performers -->
    <div class="flex-1 min-w-0 space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Harvest Volume Chart -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="font-semibold">Harvest Volume</h2>
                        <p class="text-xs text-gray-500">kg per month, selected range</p>
                    </div>
                    <a href="harvests/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
                </div>
                <?php if (count($monthly) === 0): ?>
                    <div class="border border-dashed border-gray-300 rounded-lg px-4 py-10 text-center text-sm text-gray-500">No harvest data in this range.</div>
                <?php else: ?>
                    <div class="relative h-56 sm:h-64"><canvas id="volumeChart"></canvas></div>
                <?php endif; ?>
            </div>

            <!-- Revenue Chart -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="font-semibold">Revenue</h2>
                        <p class="text-xs text-gray-500">&#8369; per month, selected range</p>
                    </div>
                    <a href="reports/harvest_revenue.php" class="text-sm text-green-700 hover:underline">Full report &rarr;</a>
                </div>
                <?php if (count($monthly) === 0): ?>
                    <div class="border border-dashed border-gray-300 rounded-lg px-4 py-10 text-center text-sm text-gray-500">No revenue data in this range.</div>
                <?php else: ?>
                    <div class="relative h-56 sm:h-64"><canvas id="revenueChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Crops Chart -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="font-semibold">Top Crops by Yield</h2>
                        <p class="text-xs text-gray-500">harvested kg, selected range</p>
                    </div>
                    <a href="crops/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
                </div>
                <?php if (count($top_crops) === 0): ?>
                    <div class="border border-dashed border-gray-300 rounded-lg px-4 py-10 text-center text-sm text-gray-500">No harvest data in this range.</div>
                <?php else: ?>
                    <div class="relative h-52 sm:h-56"><canvas id="cropsChart"></canvas></div>
                <?php endif; ?>
            </div>

            <!-- Top Farms Table -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between px-6 pt-6 mb-4">
                    <div>
                        <h2 class="font-semibold">Top Farms by Revenue</h2>
                        <p class="text-xs text-gray-500">selected range</p>
                    </div>
                    <a href="farms/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
                </div>
                <?php if (count($top_farms) === 0): ?>
                    <div class="border border-dashed border-gray-300 rounded-lg px-4 py-10 mx-6 mb-6 text-center text-sm text-gray-500">No harvest data in this range.</div>
                <?php else: ?>
                    <table class="w-full text-sm mb-2">
                        <thead class="text-gray-500 uppercase text-xs tracking-wide">
                            <tr>
                                <th class="px-6 py-2 text-left">Farm</th>
                                <th class="px-4 py-2 text-right">kg</th>
                                <th class="px-6 py-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_farms as $row): ?>
                                <tr class="border-t border-gray-100">
                                    <td class="px-6 py-2.5">
                                        <p class="font-medium"><?= htmlspecialchars($row['farm_name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($row['owner_name']) ?></p>
                                    </td>
                                    <td class="px-4 py-2.5 text-right"><?= number_format($row['total_kg'], 1) ?></td>
                                    <td class="px-6 py-2.5 text-right font-medium text-green-700">&#8369;<?= number_format($row['total_revenue'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right rail -->
    <aside class="xl:w-96 shrink-0 space-y-6">

        <!-- Upcoming Harvests -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold">Upcoming Harvests</h2>
                <span class="text-xs text-gray-400">next 14 days</span>
            </div>
            <?php if (count($upcoming_harvests) === 0): ?>
                <div class="border border-dashed border-gray-300 rounded-lg px-4 py-6 text-center text-sm text-gray-500">Nothing due in the next two weeks.</div>
            <?php endif; ?>
            <?php foreach ($upcoming_harvests as $row): ?>
                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                    <div>
                        <p class="text-sm font-medium"><?= htmlspecialchars($row['crop_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($row['farm_name']) ?> &middot; <?= htmlspecialchars($row['expected_harvest_date']) ?></p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= (int) $row['days_left'] <= 3 ? 'bg-red-50 text-red-700' : 'bg-yellow-50 text-yellow-700' ?>">
                        <?= (int) $row['days_left'] === 0 ? 'today' : (int) $row['days_left'] . 'd left' ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Plantings -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold">Recent Plantings</h2>
                <a href="plantings/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
            </div>
            <?php if (count($recent_plantings) === 0): ?>
                <div class="border border-dashed border-gray-300 rounded-lg px-4 py-6 text-center text-sm text-gray-500">No planting records yet.</div>
            <?php endif; ?>
            <?php foreach ($recent_plantings as $row): ?>
                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                    <div>
                        <p class="text-sm font-medium"><?= htmlspecialchars($row['crop_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($row['farm_name']) ?> &middot; <?= htmlspecialchars($row['planting_date']) ?></p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= $badge_colors[$row['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Harvests -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold">Recent Harvests</h2>
                <a href="harvests/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
            </div>
            <?php if (count($recent_harvests) === 0): ?>
                <div class="border border-dashed border-gray-300 rounded-lg px-4 py-6 text-center text-sm text-gray-500">No harvest records yet.</div>
            <?php endif; ?>
            <?php foreach ($recent_harvests as $row): ?>
                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                    <div>
                        <p class="text-sm font-medium"><?= htmlspecialchars($row['crop_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($row['farm_name']) ?> &middot; <?= htmlspecialchars($row['harvest_date']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium"><?= number_format($row['quantity_kg'], 2) ?> kg</p>
                        <p class="text-xs text-green-700">&#8369;<?= number_format($row['quantity_kg'] * $row['selling_price_per_kg'], 2) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </aside>

</div>

<?php if (count($monthly) > 0 || count($top_crops) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
        integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4"
        crossorigin="anonymous"></script>
<script>
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = "#6b7280";

    <?php if (count($monthly) > 0): ?>
    new Chart(document.getElementById("volumeChart"), {
        type: "bar",
        data: {
            labels: <?= json_encode(array_column($monthly, "month_label")) ?>,
            datasets: [{
                label: "Harvested (kg)",
                data: <?= json_encode(array_map("floatval", array_column($monthly, "total_kg"))) ?>,
                backgroundColor: "#16a34a",
                borderRadius: { topLeft: 4, topRight: 4 },
                categoryPercentage: 0.6,
                barPercentage: 0.9
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (ctx) { return ctx.parsed.y.toLocaleString() + " kg"; } } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: "#f3f4f6" }, border: { display: false } }
            }
        }
    });

    new Chart(document.getElementById("revenueChart"), {
        type: "line",
        data: {
            labels: <?= json_encode(array_column($monthly, "month_label")) ?>,
            datasets: [{
                label: "Revenue",
                data: <?= json_encode(array_map("floatval", array_column($monthly, "total_revenue"))) ?>,
                borderColor: "#16a34a",
                backgroundColor: "rgba(22, 163, 74, 0.08)",
                fill: true,
                tension: 0.3,
                pointBackgroundColor: "#16a34a",
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (ctx) { return "PHP " + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2}); } } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: "#f3f4f6" }, border: { display: false } }
            }
        }
    });
    <?php endif; ?>

    <?php if (count($top_crops) > 0): ?>
    new Chart(document.getElementById("cropsChart"), {
        type: "bar",
        data: {
            labels: <?= json_encode(array_column($top_crops, "crop_name")) ?>,
            datasets: [{
                label: "Harvested (kg)",
                data: <?= json_encode(array_map("floatval", array_column($top_crops, "total_kg"))) ?>,
                backgroundColor: "#16a34a",
                borderRadius: { topRight: 4, bottomRight: 4 },
                categoryPercentage: 0.6,
                barPercentage: 0.9
            }]
        },
        options: {
            indexAxis: "y",
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (ctx) { return ctx.parsed.x.toLocaleString() + " kg"; } } }
            },
            scales: {
                x: { beginAtZero: true, grid: { color: "#f3f4f6" }, border: { display: false } },
                y: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
</script>
<?php endif; ?>

<?php require "includes/footer.php"; ?>
