<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/report_helpers.php";
require_once "../config/Database.php";

$pdo = (new Database())->connect();

$rows = $pdo->query(
    "SELECT
        fm.farmer_id,
        fm.first_name || ' ' || fm.last_name AS farmer_name,
        fm.contact_number,
        fm.address,
        COUNT(f.farm_id) AS farm_count,
        COALESCE(SUM(f.area_hectares), 0) AS total_hectares
     FROM farmer fm
     LEFT JOIN farm f ON fm.farmer_id = f.farmer_id AND f.is_deleted = FALSE
     WHERE fm.is_deleted = FALSE
     GROUP BY fm.farmer_id, fm.first_name, fm.last_name, fm.contact_number, fm.address
     ORDER BY fm.farmer_id"
)->fetchAll();

// Headline totals
$sum_farms    = 0;
$sum_hectares = 0;
foreach ($rows as $row) {
    $sum_farms    += $row['farm_count'];
    $sum_hectares += $row['total_hectares'];
}

// CSV export uses the same rows the page shows
if (($_GET["export"] ?? "") === "csv") {
    csvDownload("farmers_farms",
        ["ID", "Farmer", "Contact", "Address", "Farms Owned", "Total Area (ha)"],
        array_map(fn($r) => [
            $r["farmer_id"], $r["farmer_name"], $r["contact_number"],
            $r["address"], $r["farm_count"], number_format($r["total_hectares"], 2, ".", "")
        ], $rows)
    );
}

$page_title  = "Farmers & Farm Ownership Report";
$active      = "reports";
$breadcrumbs = [["Reports", "index.php"], ["Farmers & Farm Ownership", null]];
require "../includes/header.php";
?>

<div class="max-w-5xl mx-auto">

<?php reportPrintHeader("Farmers & Farm Ownership"); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Farmers &amp; Farm Ownership</h1>
        <p class="text-sm text-gray-500">Farm count and total land area per registered farmer.</p>
    </div>
    <?php reportActions(); ?>
</div>

<?php summaryStrip([
    "Farmers"           => number_format(count($rows)),
    "Farms Owned"       => number_format($sum_farms),
    "Total Area"        => number_format($sum_hectares, 2) . " <span class='text-sm font-normal text-gray-500'>ha</span>",
]); ?>

<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
            <tr>
                <th class="px-4 py-3 text-left">ID</th>
                <th class="px-4 py-3 text-left">Farmer</th>
                <th class="px-4 py-3 text-left">Contact</th>
                <th class="px-4 py-3 text-left">Address</th>
                <th class="px-4 py-3 text-right">Farms Owned</th>
                <th class="px-4 py-3 text-right">Total Area (ha)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No data available.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3"><?= $row['farmer_id'] ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['farmer_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['contact_number']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['address']) ?></td>
                    <td class="px-4 py-3 text-right"><?= $row['farm_count'] ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['total_hectares'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (count($rows) > 0): ?>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-right">TOTAL:</td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_farms) ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_hectares, 2) ?> ha</td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

</div>

<?php require "../includes/footer.php"; ?>
