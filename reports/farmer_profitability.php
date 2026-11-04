<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/report_helpers.php";
require_once "../config/Database.php";

$pdo = (new Database())->connect();

$date_from = (string) trim($_GET["date_from"] ?? "");
$date_to   = (string) trim($_GET["date_to"] ?? "");

// Harvest date conditions applied inside the LEFT JOIN so farmers with no
// harvests in range still appear with zeros.
$harvest_join = "h.planting_id = p.planting_id AND h.is_deleted = FALSE";
$params = [];
if ($date_from !== "") {
    $harvest_join .= " AND h.harvest_date >= :date_from";
    $params["date_from"] = $date_from;
}
if ($date_to !== "") {
    $harvest_join .= " AND h.harvest_date <= :date_to";
    $params["date_to"] = $date_to;
}

$sql = "SELECT
            fm.farmer_id,
            fm.first_name || ' ' || fm.last_name AS farmer_name,
            COUNT(DISTINCT f.farm_id) AS farm_count,
            (SELECT COALESCE(SUM(area_hectares), 0) FROM farm
             WHERE farmer_id = fm.farmer_id AND is_deleted = FALSE) AS total_area,
            COUNT(h.harvest_id) AS harvest_count,
            COALESCE(SUM(h.quantity_kg), 0) AS total_kg,
            COALESCE(SUM(h.quantity_kg * h.selling_price_per_kg), 0) AS total_revenue
        FROM farmer fm
        LEFT JOIN farm f ON f.farmer_id = fm.farmer_id AND f.is_deleted = FALSE
        LEFT JOIN planting p ON p.farm_id = f.farm_id AND p.is_deleted = FALSE
        LEFT JOIN harvest h ON {$harvest_join}
        WHERE fm.is_deleted = FALSE
        GROUP BY fm.farmer_id, farmer_name
        ORDER BY total_revenue DESC, fm.farmer_id";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Headline totals
$sum_kg      = 0;
$sum_revenue = 0;
foreach ($rows as $row) {
    $sum_kg      += $row['total_kg'];
    $sum_revenue += $row['total_revenue'];
}

// CSV export uses the same rows the page shows
if (($_GET["export"] ?? "") === "csv") {
    csvDownload("farmer_profitability",
        ["Farmer", "Farms", "Total Area (ha)", "Harvests", "Harvested (kg)", "Revenue"],
        array_map(fn($r) => [
            $r["farmer_name"], $r["farm_count"],
            number_format($r["total_area"], 2, ".", ""), $r["harvest_count"],
            number_format($r["total_kg"], 2, ".", ""),
            number_format($r["total_revenue"], 2, ".", "")
        ], $rows)
    );
}

$page_title  = "Farmer Profitability Report";
$active      = "reports";
$breadcrumbs = [["Reports", "index.php"], ["Farmer Profitability", null]];
require "../includes/header.php";
?>

<?php reportPrintHeader("Farmer Profitability", ["From" => $date_from, "To" => $date_to]); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Farmer Profitability</h1>
        <p class="text-sm text-gray-500">Harvest volume and revenue earned per farmer, ranked by revenue.</p>
    </div>
    <?php reportActions(); ?>
</div>

<?php summaryStrip([
    "Farmers"        => number_format(count($rows)),
    "Harvested"      => number_format($sum_kg, 2) . " <span class='text-sm font-normal text-gray-500'>kg</span>",
    "Total Revenue"  => "&#8369;" . number_format($sum_revenue, 2),
]); ?>

<div class="flex flex-col xl:flex-row gap-6">

<!-- Right rail: date range filter -->
<aside class="xl:w-80 shrink-0 xl:order-2 print:hidden">
    <form method="GET" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 space-y-3 xl:sticky xl:top-20">
        <div>
            <label class="block text-sm text-gray-500 mb-1">From</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                   class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        </div>
        <div>
            <label class="block text-sm text-gray-500 mb-1">To</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                   class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Apply Filter</button>
            <a href="farmer_profitability.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Reset</a>
        </div>
        <p class="text-xs text-gray-400"><?= count($rows) ?> row(s) shown</p>
    </form>
</aside>

<!-- Primary feed: report table -->
<div class="flex-1 min-w-0 xl:order-1">
<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
            <tr>
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left">Farmer</th>
                <th class="px-4 py-3 text-right">Farms</th>
                <th class="px-4 py-3 text-right">Total Area (ha)</th>
                <th class="px-4 py-3 text-right">Harvests</th>
                <th class="px-4 py-3 text-right">Harvested (kg)</th>
                <th class="px-4 py-3 text-right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No farmer records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $row): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-400"><?= $i + 1 ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['farmer_name']) ?></td>
                    <td class="px-4 py-3 text-right"><?= $row['farm_count'] ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['total_area'], 2) ?></td>
                    <td class="px-4 py-3 text-right"><?= $row['harvest_count'] ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['total_kg'], 2) ?></td>
                    <td class="px-4 py-3 text-right font-medium text-green-700">&#8369;<?= number_format($row['total_revenue'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (count($rows) > 0): ?>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-right">TOTAL:</td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_kg, 2) ?> kg</td>
                    <td class="px-4 py-3 text-right text-green-700">&#8369;<?= number_format($sum_revenue, 2) ?></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>
</div>

</div>

<?php require "../includes/footer.php"; ?>
