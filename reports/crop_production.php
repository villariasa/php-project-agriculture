<?php
$base = "../";
require_once "../includes/auth_check.php";
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

$page_title = "Crop Production Report";
$active     = "reports";
require "../includes/header.php";
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Crop Production per Farm</h1>
        <p class="text-sm text-gray-500">Harvested quantity totals per crop on each farm.</p>
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

<!-- Right rail: filter -->
<aside class="xl:w-80 shrink-0 xl:order-2 print:hidden">
    <form method="GET" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 space-y-2 xl:sticky xl:top-20">
        <label class="block text-sm text-gray-500">Filter by farm</label>
        <select name="farm_id" onchange="this.form.submit()"
                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            <option value="0">All Farms</option>
            <?php foreach ($farms as $f): ?>
                <option value="<?= $f['farm_id'] ?>" <?= $farm_id === (int) $f['farm_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['farm_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-400 pt-2"><?= count($rows) ?> row(s) shown</p>
    </form>
</aside>

<!-- Primary feed: report table -->
<div class="flex-1 min-w-0 xl:order-1">
<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
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
    </table>
</div>
</div>

</div>

<?php require "../includes/footer.php"; ?>
