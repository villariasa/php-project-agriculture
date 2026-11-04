<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/report_helpers.php";
require_once "../config/Database.php";

$pdo = (new Database())->connect();

$date_from = (string) trim($_GET["date_from"] ?? "");
$date_to   = (string) trim($_GET["date_to"] ?? "");

$sql = "SELECT
            h.harvest_id,
            f.farm_name,
            c.crop_name,
            c.variety,
            h.harvest_date,
            h.quantity_kg,
            h.selling_price_per_kg,
            qg.grade_name AS quality_grade,
            r.remark_description
        FROM harvest h
        JOIN planting p ON h.planting_id = p.planting_id
        JOIN farm f ON p.farm_id = f.farm_id
        JOIN crop c ON p.crop_id = c.crop_id
        JOIN quality_grades qg ON h.grade_id = qg.grade_id
        JOIN harvest_remarks r ON h.remark_id = r.remark_id";
$params = [];
$where  = ["h.is_deleted = FALSE"];
if ($date_from !== "") {
    $where[] = "h.harvest_date >= :date_from";
    $params["date_from"] = $date_from;
}
if ($date_to !== "") {
    $where[] = "h.harvest_date <= :date_to";
    $params["date_to"] = $date_to;
}
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY h.harvest_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Totals
$total_quantity = 0;
$total_revenue  = 0;
foreach ($rows as $row) {
    $total_quantity += $row['quantity_kg'];
    $total_revenue  += $row['quantity_kg'] * $row['selling_price_per_kg'];
}

// CSV export uses the same rows the page shows
if (($_GET["export"] ?? "") === "csv") {
    csvDownload("harvest_revenue",
        ["Date", "Farm", "Crop", "Quantity (kg)", "Price/kg", "Revenue", "Grade", "Remark"],
        array_map(fn($r) => [
            $r["harvest_date"], $r["farm_name"], $r["crop_name"] . " (" . $r["variety"] . ")",
            number_format($r["quantity_kg"], 2, ".", ""),
            number_format($r["selling_price_per_kg"], 2, ".", ""),
            number_format($r["quantity_kg"] * $r["selling_price_per_kg"], 2, ".", ""),
            $r["quality_grade"], $r["remark_description"]
        ], $rows)
    );
}

$page_title  = "Harvest Revenue Report";
$active      = "reports";
$breadcrumbs = [["Reports", "index.php"], ["Harvest Yield & Revenue", null]];
require "../includes/header.php";
?>

<?php reportPrintHeader("Harvest Yield & Revenue", ["From" => $date_from, "To" => $date_to]); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Harvest Yield &amp; Revenue</h1>
        <p class="text-sm text-gray-500">Harvest quantities and revenue within a date range.</p>
    </div>
    <?php reportActions(); ?>
</div>

<!-- Filter toolbar -->
<form method="GET" class="flex flex-wrap items-end gap-2 mb-4 print:hidden">
    <div>
        <label class="block text-xs text-gray-500 mb-1">From</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
               class="py-2 px-3 text-sm bg-white border border-gray-300 rounded-full shadow-sm
                      focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">To</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
               class="py-2 px-3 text-sm bg-white border border-gray-300 rounded-full shadow-sm
                      focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
    </div>
    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-full text-sm">Apply</button>
    <a href="harvest_revenue.php" class="text-sm text-gray-500 hover:text-gray-700 self-center px-1">Reset</a>
    <span class="text-xs text-gray-400 self-center"><?= count($rows) ?> row(s) shown</span>
</form>

<?php summaryStrip([
    "Harvests"       => number_format(count($rows)),
    "Total Quantity" => number_format($total_quantity, 2) . " <span class='text-sm font-normal text-gray-500'>kg</span>",
    "Total Revenue"  => "&#8369;" . number_format($total_revenue, 2),
]); ?>

<!-- Row-limited viewport: extra rows scroll; print shows everything -->
<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto max-h-[70vh] overflow-y-auto print:max-h-none print:overflow-visible">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide sticky top-0 z-10">
            <tr>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Farm</th>
                <th class="px-4 py-3 text-left">Crop</th>
                <th class="px-4 py-3 text-right">Quantity (kg)</th>
                <th class="px-4 py-3 text-right">Price/kg</th>
                <th class="px-4 py-3 text-right">Revenue</th>
                <th class="px-4 py-3 text-left">Grade</th>
                <th class="px-4 py-3 text-left">Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No harvests found for this period.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3"><?= htmlspecialchars($row['harvest_date']) ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['farm_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['crop_name'] . " (" . $row['variety'] . ")") ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['quantity_kg'], 2) ?></td>
                    <td class="px-4 py-3 text-right">&#8369;<?= number_format($row['selling_price_per_kg'], 2) ?></td>
                    <td class="px-4 py-3 text-right font-medium text-green-700">&#8369;<?= number_format($row['quantity_kg'] * $row['selling_price_per_kg'], 2) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['quality_grade']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['remark_description']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (count($rows) > 0): ?>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-right">TOTAL:</td>
                    <td class="px-4 py-3 text-right"><?= number_format($total_quantity, 2) ?> kg</td>
                    <td></td>
                    <td class="px-4 py-3 text-right text-green-700">&#8369;<?= number_format($total_revenue, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php require "../includes/footer.php"; ?>
