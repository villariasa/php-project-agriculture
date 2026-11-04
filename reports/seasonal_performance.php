<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/report_helpers.php";
require_once "../config/Database.php";

$pdo = (new Database())->connect();

// Performance per recommended season of the planted crop: plantings started,
// area planted, harvested kg, and revenue.
$rows = $pdo->query(
    "SELECT
        s.season_name,
        COUNT(DISTINCT p.planting_id) AS planting_count,
        COALESCE(SUM(h.quantity_kg), 0) AS total_kg,
        COALESCE(SUM(h.quantity_kg * h.selling_price_per_kg), 0) AS total_revenue
     FROM seasons s
     LEFT JOIN crop c ON c.season_id = s.season_id AND c.is_deleted = FALSE
     LEFT JOIN planting p ON p.crop_id = c.crop_id AND p.is_deleted = FALSE
     LEFT JOIN harvest h ON h.planting_id = p.planting_id AND h.is_deleted = FALSE
     WHERE s.is_deleted = FALSE
     GROUP BY s.season_name
     ORDER BY total_revenue DESC"
)->fetchAll();

// Headline totals
$sum_plantings = 0;
$sum_kg        = 0;
$sum_revenue   = 0;
foreach ($rows as $row) {
    $sum_plantings += $row['planting_count'];
    $sum_kg        += $row['total_kg'];
    $sum_revenue   += $row['total_revenue'];
}

// CSV export uses the same rows the page shows
if (($_GET["export"] ?? "") === "csv") {
    csvDownload("seasonal_performance",
        ["Season", "Plantings", "Harvested (kg)", "Revenue"],
        array_map(fn($r) => [
            $r["season_name"], $r["planting_count"],
            number_format($r["total_kg"], 2, ".", ""),
            number_format($r["total_revenue"], 2, ".", "")
        ], $rows)
    );
}

$page_title  = "Seasonal Performance Report";
$active      = "reports";
$breadcrumbs = [["Reports", "index.php"], ["Seasonal Performance", null]];
require "../includes/header.php";
?>

<div class="max-w-5xl mx-auto">

<?php reportPrintHeader("Seasonal Performance"); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Seasonal Performance</h1>
        <p class="text-sm text-gray-500">Plantings, harvested volume, and revenue grouped by the crop's recommended season.</p>
    </div>
    <?php reportActions(); ?>
</div>

<?php summaryStrip([
    "Plantings"     => number_format($sum_plantings),
    "Harvested"     => number_format($sum_kg, 2) . " <span class='text-sm font-normal text-gray-500'>kg</span>",
    "Total Revenue" => "&#8369;" . number_format($sum_revenue, 2),
]); ?>

<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
            <tr>
                <th class="px-4 py-3 text-left">Season</th>
                <th class="px-4 py-3 text-right">Plantings</th>
                <th class="px-4 py-3 text-right">Harvested (kg)</th>
                <th class="px-4 py-3 text-right">Revenue</th>
                <th class="px-4 py-3 text-right">Share of Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No season records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <?php $share = $sum_revenue > 0 ? ($row['total_revenue'] / $sum_revenue) * 100 : 0; ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['season_name']) ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['planting_count']) ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['total_kg'], 2) ?></td>
                    <td class="px-4 py-3 text-right font-medium text-green-700">&#8369;<?= number_format($row['total_revenue'], 2) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-2">
                            <div class="w-24 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-green-600 rounded-full" style="width: <?= number_format($share, 0) ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-500 w-10 text-right"><?= number_format($share, 1) ?>%</span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (count($rows) > 0): ?>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td class="px-4 py-3 text-right">TOTAL:</td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_plantings) ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_kg, 2) ?> kg</td>
                    <td class="px-4 py-3 text-right text-green-700">&#8369;<?= number_format($sum_revenue, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

</div>

<?php require "../includes/footer.php"; ?>
