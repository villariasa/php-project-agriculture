<?php
$base = "../";
require_once "../includes/auth_check.php";
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

$page_title = "Harvest Revenue Report";
$active     = "reports";
require "../includes/header.php";
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Harvest Yield &amp; Revenue</h1>
        <p class="text-sm text-gray-500">Harvest quantities and revenue within a date range.</p>
    </div>
    <button onclick="window.print()"
            class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg inline-flex items-center gap-2 print:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
        </svg>
        Print
    </button>
</div>

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
            <a href="harvest_revenue.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Reset</a>
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
</div>

</div>

<?php require "../includes/footer.php"; ?>
