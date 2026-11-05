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

$page_title = "Home";
$active     = "dashboard";
require "includes/header.php";
?>

<!-- Page header + date range -->
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5 sm:mb-6">
    <!-- App-style greeting (avatar + hello), all screen sizes -->
    <div class="flex items-center gap-3">
        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-green-600 text-white flex items-center justify-center font-semibold shrink-0">
            <?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1))) ?>
        </div>
        <div>
            <p class="text-sm text-gray-500 leading-tight">Hello,</p>
            <p class="font-bold text-gray-900 sm:text-lg leading-tight"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
        </div>
    </div>

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

<?php $range_revenue = array_sum(array_map("floatval", array_column($monthly, "total_revenue"))); ?>

<!-- Hero row: revenue card spans 2 cols, the two actionable stats beside it.
     Mobile: 2-col grid -> hero full width, tinted cards side by side under it. -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 mb-4 print:hidden">

<div class="col-span-2 bg-green-950 text-white rounded-2xl p-5 shadow-lg">
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm text-green-200">Revenue</p>
        <!-- Mobile: opens the date sheet; desktop: static (header form sets the range) -->
        <button type="button" onclick="openDateSheet()"
                class="sm:hidden inline-flex items-center gap-1.5 bg-white/10 text-green-100 rounded-full px-3 py-1.5 text-xs">
            <?= htmlspecialchars(date("M j", strtotime($date_from))) ?> &ndash; <?= htmlspecialchars(date("M j", strtotime($date_to))) ?>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <span class="hidden sm:inline-flex items-center bg-white/10 text-green-100 rounded-full px-3 py-1.5 text-xs">
            <?= htmlspecialchars(date("M j", strtotime($date_from))) ?> &ndash; <?= htmlspecialchars(date("M j", strtotime($date_to))) ?>
        </span>
    </div>
    <p id="hero-revenue" class="text-3xl font-bold mt-2" data-amount="&#8369;<?= number_format($range_revenue, 2) ?>">&#8369;<?= number_format($range_revenue, 2) ?></p>
    <button type="button" id="hide-balance" class="mt-3 inline-flex items-center gap-1.5 text-xs text-green-300">
        <svg id="hero-eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <svg id="hero-eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 hidden">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
        <span id="hide-balance-label">Hide balance</span>
    </button>
</div>

<!-- The two actionable stats as tinted cards (grid children, all sizes) -->
    <a href="plantings/index.php" class="bg-blue-50 rounded-2xl p-4 hover:bg-blue-100 transition-colors">
        <div class="flex items-center justify-between gap-2 mb-2">
            <p class="text-sm text-gray-600">Active Plantings</p>
            <span class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
            </span>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= number_format($active_plantings) ?></p>
    </a>
    <a href="plantings/index.php" class="bg-amber-50 rounded-2xl p-4 hover:bg-amber-100 transition-colors">
        <div class="flex items-center justify-between gap-2 mb-2">
            <p class="text-sm text-gray-600">Harvest Due Soon</p>
            <span class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
            </span>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= number_format($due_soon) ?></p>
    </a>
</div>

<!-- Quick actions: add-shortcuts for the 5 core entities, in workflow order -->
<div class="grid grid-cols-5 gap-2 mb-5 sm:mb-6 sm:max-w-lg print:hidden">
    <a href="farms/index.php?add=1" class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform">
        <span class="w-12 h-12 rounded-2xl bg-green-600 text-white flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
        </span>
        <span class="text-[11px] text-gray-600 text-center leading-tight">Farm</span>
    </a>
    <a href="farmers/index.php?add=1" class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform">
        <span class="w-12 h-12 rounded-2xl bg-green-600 text-white flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
        </span>
        <span class="text-[11px] text-gray-600 text-center leading-tight">Farmer</span>
    </a>
    <a href="crops/index.php?add=1" class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform">
        <span class="w-12 h-12 rounded-2xl bg-green-600 text-white flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 0 0 9-9c0-4.97-9-13.5-9-13.5S3 7.03 3 12a9 9 0 0 0 9 9Zm0 0v-7.5" />
            </svg>
        </span>
        <span class="text-[11px] text-gray-600 text-center leading-tight">Crop</span>
    </a>
    <a href="plantings/index.php?add=1" class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform">
        <span class="w-12 h-12 rounded-2xl bg-green-600 text-white flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
        </span>
        <span class="text-[11px] text-gray-600 text-center leading-tight">Planting</span>
    </a>
    <a href="harvests/index.php?add=1" class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform">
        <span class="w-12 h-12 rounded-2xl bg-green-600 text-white flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
        </span>
        <span class="text-[11px] text-gray-600 text-center leading-tight">Harvest</span>
    </a>
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

    // Hide-balance toggle on the hero card (persists for the session)
    var heroAmount = document.getElementById("hero-revenue");
    var hideBtn    = document.getElementById("hide-balance");
    function setBalanceHidden(hidden) {
        if (!heroAmount || !hideBtn) { return; }
        // data-amount arrives entity-decoded, so it's the display-ready string
        heroAmount.textContent = hidden ? "₱ ••••••" : heroAmount.dataset.amount;
        document.getElementById("hero-eye-open").classList.toggle("hidden", hidden);
        document.getElementById("hero-eye-closed").classList.toggle("hidden", !hidden);
        document.getElementById("hide-balance-label").textContent = hidden ? "Show balance" : "Hide balance";
        sessionStorage.setItem("ais-hide-balance", hidden ? "1" : "0");
    }
    if (hideBtn) {
        hideBtn.addEventListener("click", function () {
            setBalanceHidden(sessionStorage.getItem("ais-hide-balance") !== "1");
        });
        if (sessionStorage.getItem("ais-hide-balance") === "1") {
            setBalanceHidden(true);
        }
    }

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
    // Revenue + Harvested-this-month already live in the hero/tinted cards above
    ["url" => "reports/yield_efficiency.php", "label" => "Avg Yield / Hectare",
     "value" => number_format($avg_yield_per_ha, 1) . " <span class='text-sm font-normal text-gray-500'>kg/ha</span>",
     "sub" => "<p class='text-xs text-gray-400 mt-1'>within selected range</p>"],
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

<div class="hidden sm:grid sm:grid-cols-2 xl:grid-cols-4 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden shadow-sm mb-6">
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

<!-- Recents: the only "what's happening" feed Home needs — full analytics live in Reports -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

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

</div>

<?php require "includes/footer.php"; ?>
