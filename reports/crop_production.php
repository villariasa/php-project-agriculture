<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/report_helpers.php";
require_once "../config/Database.php";
require_once "../models/Farm.php";

$pdo   = (new Database())->connect();
$farms = (new Farm($pdo))->getAll();

$farm_id = (int) ($_GET["farm_id"] ?? 0);

$sql = "SELECT
            f.farm_name,
            c.crop_name,
            c.variety,
            COUNT(h.harvest_id) AS harvest_count,
            COALESCE(SUM(h.quantity_kg), 0) AS total_quantity_kg
        FROM planting p
        JOIN farm f ON p.farm_id = f.farm_id
        JOIN crop c ON p.crop_id = c.crop_id
        LEFT JOIN harvest h ON h.planting_id = p.planting_id AND h.is_deleted = FALSE
        WHERE p.is_deleted = FALSE";
$params = [];
if ($farm_id > 0) {
    $sql .= " AND f.farm_id = :farm_id";
    $params["farm_id"] = $farm_id;
}
$sql .= " GROUP BY f.farm_name, c.crop_name, c.variety
          ORDER BY f.farm_name, c.crop_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Headline totals
$sum_harvests = 0;
$sum_kg       = 0;
foreach ($rows as $row) {
    $sum_harvests += $row['harvest_count'];
    $sum_kg       += $row['total_quantity_kg'];
}

// Selected farm name (for the print header)
$farm_filter_label = "";
foreach ($farms as $f) {
    if ((int) $f['farm_id'] === $farm_id) {
        $farm_filter_label = $f['farm_name'];
    }
}

// CSV export uses the same rows the page shows
if (($_GET["export"] ?? "") === "csv") {
    csvDownload("crop_production",
        ["Farm", "Crop", "Variety", "Harvests", "Total Harvested (kg)"],
        array_map(fn($r) => [
            $r["farm_name"], $r["crop_name"], $r["variety"],
            $r["harvest_count"], number_format($r["total_quantity_kg"], 2, ".", "")
        ], $rows)
    );
}

$page_title  = "Crop Production Report";
$active      = "reports";
$breadcrumbs = [["Reports", "index.php"], ["Crop Production", null]];
require "../includes/header.php";
?>

<?php reportPrintHeader("Crop Production per Farm", ["Farm" => $farm_filter_label]); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Crop Production per Farm</h1>
        <p class="text-sm text-gray-500">Harvested quantity totals per crop on each farm.</p>
    </div>
    <?php reportActions(); ?>
</div>

<!-- Filter toolbar -->
<form method="GET" class="flex flex-wrap items-center gap-2 mb-4 print:hidden">
    <select name="farm_id" onchange="this.form.submit()" aria-label="Filter by farm"
            class="py-2.5 pl-3 pr-8 text-sm bg-white border border-gray-300 rounded-full shadow-sm text-gray-700
                   focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        <option value="0">All Farms</option>
        <?php foreach ($farms as $f): ?>
            <option value="<?= $f['farm_id'] ?>" <?= $farm_id === (int) $f['farm_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($f['farm_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <span class="text-xs text-gray-400"><?= count($rows) ?> row(s) shown</span>
</form>

<?php summaryStrip([
    "Farm / Crop Rows" => number_format(count($rows)),
    "Harvests"         => number_format($sum_harvests),
    "Total Harvested"  => number_format($sum_kg, 2) . " <span class='text-sm font-normal text-gray-500'>kg</span>",
]); ?>

<!-- Row-limited viewport: extra rows scroll; print shows everything -->
<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto max-h-[70vh] overflow-y-auto print:max-h-none print:overflow-visible">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide sticky top-0 z-10">
            <tr>
                <th class="px-4 py-3 text-left">Farm</th>
                <th class="px-4 py-3 text-left">Crop</th>
                <th class="px-4 py-3 text-left">Variety</th>
                <th class="px-4 py-3 text-right">Harvests</th>
                <th class="px-4 py-3 text-right">Total Harvested (kg)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No production data found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['farm_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['crop_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['variety']) ?></td>
                    <td class="px-4 py-3 text-right"><?= $row['harvest_count'] ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['total_quantity_kg'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (count($rows) > 0): ?>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-right">TOTAL:</td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_harvests) ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_kg, 2) ?> kg</td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php require "../includes/footer.php"; ?>
