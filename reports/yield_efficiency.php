<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/report_helpers.php";
require_once "../config/Database.php";
require_once "../models/Lookup.php";

$pdo    = (new Database())->connect();
$lookup = new Lookup($pdo);

$categories  = $lookup->getAll("crop_categories");
$category_id = (int) ($_GET["category_id"] ?? 0);

// Yield efficiency per crop: harvested kg over the planted area of the
// plantings that produced those harvests.
$sql = "SELECT
            c.crop_name,
            c.variety,
            cc.category_name,
            COUNT(DISTINCT p.planting_id) AS planting_count,
            COALESCE(SUM(p2.area), 0) AS total_area,
            COALESCE(SUM(p2.kg), 0) AS total_kg
        FROM crop c
        JOIN crop_categories cc ON c.category_id = cc.category_id
        LEFT JOIN planting p ON p.crop_id = c.crop_id AND p.is_deleted = FALSE
        LEFT JOIN (
            SELECT p.planting_id, p.area_planted AS area, SUM(h.quantity_kg) AS kg
            FROM planting p
            JOIN harvest h ON h.planting_id = p.planting_id AND h.is_deleted = FALSE
            WHERE p.is_deleted = FALSE
            GROUP BY p.planting_id, p.area_planted
        ) p2 ON p2.planting_id = p.planting_id
        WHERE c.is_deleted = FALSE";
$params = [];
if ($category_id > 0) {
    $sql .= " AND c.category_id = :category_id";
    $params["category_id"] = $category_id;
}
$sql .= " GROUP BY c.crop_name, c.variety, cc.category_name
          ORDER BY CASE WHEN COALESCE(SUM(p2.area), 0) > 0
                        THEN COALESCE(SUM(p2.kg), 0) / SUM(p2.area) ELSE 0 END DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Headline totals
$sum_kg   = 0;
$sum_area = 0;
foreach ($rows as $row) {
    $sum_kg   += $row['total_kg'];
    $sum_area += $row['total_area'];
}
$overall_yield = $sum_area > 0 ? $sum_kg / $sum_area : 0;

// Selected category name (for the print header)
$category_filter_label = "";
foreach ($categories as $cat) {
    if ((int) $cat['id'] === $category_id) {
        $category_filter_label = $cat['name'];
    }
}

// CSV export uses the same rows the page shows
if (($_GET["export"] ?? "") === "csv") {
    csvDownload("yield_efficiency",
        ["Crop", "Variety", "Category", "Plantings", "Harvested Area (ha)", "Harvested (kg)", "Yield (kg/ha)"],
        array_map(fn($r) => [
            $r["crop_name"], $r["variety"], $r["category_name"], $r["planting_count"],
            number_format($r["total_area"], 2, ".", ""),
            number_format($r["total_kg"], 2, ".", ""),
            $r["total_area"] > 0 ? number_format($r["total_kg"] / $r["total_area"], 2, ".", "") : "0.00"
        ], $rows)
    );
}

$page_title  = "Yield Efficiency Report";
$active      = "reports";
$breadcrumbs = [["Reports", "index.php"], ["Yield Efficiency", null]];
require "../includes/header.php";
?>

<?php reportPrintHeader("Yield Efficiency per Crop", ["Category" => $category_filter_label]); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Yield Efficiency per Crop</h1>
        <p class="text-sm text-gray-500">Average kg harvested per hectare planted, ranked by efficiency.</p>
    </div>
    <?php reportActions(); ?>
</div>

<!-- Filter toolbar -->
<form method="GET" class="flex flex-wrap items-center gap-2 mb-4 print:hidden">
    <select name="category_id" onchange="this.form.submit()" aria-label="Filter by category"
            class="py-2.5 pl-3 pr-8 text-sm bg-white border border-gray-300 rounded-full shadow-sm text-gray-700
                   focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        <option value="0">All categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $category_id === (int) $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <span class="text-xs text-gray-400"><?= count($rows) ?> row(s) shown</span>
</form>

<?php summaryStrip([
    "Crops"           => number_format(count($rows)),
    "Harvested"       => number_format($sum_kg, 2) . " <span class='text-sm font-normal text-gray-500'>kg</span>",
    "Harvested Area"  => number_format($sum_area, 2) . " <span class='text-sm font-normal text-gray-500'>ha</span>",
    "Overall Yield"   => number_format($overall_yield, 1) . " <span class='text-sm font-normal text-gray-500'>kg/ha</span>",
]); ?>

<!-- Row-limited viewport: extra rows scroll; print shows everything -->
<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto max-h-[70vh] overflow-y-auto print:max-h-none print:overflow-visible">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide sticky top-0 z-10">
            <tr>
                <th class="px-4 py-3 text-left">Crop</th>
                <th class="px-4 py-3 text-left">Category</th>
                <th class="px-4 py-3 text-right">Plantings</th>
                <th class="px-4 py-3 text-right">Harvested Area (ha)</th>
                <th class="px-4 py-3 text-right">Harvested (kg)</th>
                <th class="px-4 py-3 text-right">Yield (kg/ha)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No crop records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <?php $yield = $row['total_area'] > 0 ? $row['total_kg'] / $row['total_area'] : 0; ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['crop_name'] . " (" . $row['variety'] . ")") ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            <?= htmlspecialchars($row['category_name']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['planting_count']) ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['total_area'], 2) ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['total_kg'], 2) ?></td>
                    <td class="px-4 py-3 text-right font-medium <?= $yield > 0 ? 'text-green-700' : 'text-gray-400' ?>">
                        <?= $yield > 0 ? number_format($yield, 1) : "&mdash;" ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (count($rows) > 0): ?>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-right">TOTAL:</td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_area, 2) ?> ha</td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_kg, 2) ?> kg</td>
                    <td class="px-4 py-3 text-right text-green-700"><?= number_format($overall_yield, 1) ?></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php require "../includes/footer.php"; ?>
