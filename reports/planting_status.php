<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/report_helpers.php";
require_once "../config/Database.php";
require_once "../models/Planting.php";
require_once "../models/Lookup.php";

$pdo = (new Database())->connect();

$statuses  = (new Lookup($pdo))->getAll("planting_status");
$status_id = (int) ($_GET["status_id"] ?? 0);
$rows      = (new Planting($pdo))->getAll($status_id);

// Headline totals
$sum_area = 0;
foreach ($rows as $row) {
    $sum_area += $row['area_planted'];
}

// Selected status name (for the print header)
$status_filter_label = "";
foreach ($statuses as $s) {
    if ((int) $s['id'] === $status_id) {
        $status_filter_label = $s['name'];
    }
}

// CSV export uses the same rows the page shows
if (($_GET["export"] ?? "") === "csv") {
    csvDownload("planting_status",
        ["ID", "Farm", "Crop", "Farmer", "Planting Date", "Expected Harvest", "Area (ha)", "Status"],
        array_map(fn($r) => [
            $r["planting_id"], $r["farm_name"], $r["crop_name"] . " (" . $r["variety"] . ")",
            $r["farmer_name"], $r["planting_date"], $r["expected_harvest_date"],
            number_format($r["area_planted"], 2, ".", ""), $r["status"]
        ], $rows)
    );
}

$badge_colors = [
    "Planted"   => "bg-blue-50 text-blue-700",
    "Growing"   => "bg-yellow-50 text-yellow-700",
    "Harvested" => "bg-green-50 text-green-700",
];

$page_title  = "Planting Status Report";
$active      = "reports";
$breadcrumbs = [["Reports", "index.php"], ["Planting Status", null]];
require "../includes/header.php";
?>

<?php reportPrintHeader("Planting Cycle Status", ["Status" => $status_filter_label]); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Planting Cycle Status</h1>
        <p class="text-sm text-gray-500">All planting records filtered by cycle status.</p>
    </div>
    <?php reportActions(); ?>
</div>

<!-- Filter toolbar -->
<form method="GET" class="flex flex-wrap items-center gap-2 mb-4 print:hidden">
    <select name="status_id" onchange="this.form.submit()" aria-label="Filter by status"
            class="py-2.5 pl-3 pr-8 text-sm bg-white border border-gray-300 rounded-full shadow-sm text-gray-700
                   focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        <option value="0">All statuses</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $status_id === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <span class="text-xs text-gray-400"><?= count($rows) ?> row(s) shown</span>
</form>

<?php summaryStrip([
    "Plantings"          => number_format(count($rows)),
    "Total Area Planted" => number_format($sum_area, 2) . " <span class='text-sm font-normal text-gray-500'>ha</span>",
]); ?>

<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
            <tr>
                <th class="px-4 py-3 text-left">ID</th>
                <th class="px-4 py-3 text-left">Farm</th>
                <th class="px-4 py-3 text-left">Crop</th>
                <th class="px-4 py-3 text-left">Farmer</th>
                <th class="px-4 py-3 text-left">Planting Date</th>
                <th class="px-4 py-3 text-left">Expected Harvest</th>
                <th class="px-4 py-3 text-right">Area (ha)</th>
                <th class="px-4 py-3 text-left">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No planting records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3"><?= $row['planting_id'] ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['farm_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['crop_name'] . " (" . $row['variety'] . ")") ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['farmer_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['planting_date']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['expected_harvest_date']) ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['area_planted'], 2) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $badge_colors[$row['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (count($rows) > 0): ?>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="6" class="px-4 py-3 text-right">TOTAL:</td>
                    <td class="px-4 py-3 text-right"><?= number_format($sum_area, 2) ?> ha</td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php require "../includes/footer.php"; ?>
