<?php
$base = "../";
require_once "../includes/auth_check.php";
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
     LEFT JOIN farm f ON fm.farmer_id = f.farmer_id
     GROUP BY fm.farmer_id, fm.first_name, fm.last_name, fm.contact_number, fm.address
     ORDER BY fm.farmer_id"
)->fetchAll();

$page_title = "Farmers & Farm Ownership Report";
$active     = "reports";
require "../includes/header.php";
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Farmers &amp; Farm Ownership</h1>
        <p class="text-sm text-gray-500">Farm count and total land area per registered farmer.</p>
    </div>
    <button onclick="window.print()"
            class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg inline-flex items-center gap-2 print:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
        </svg>
        Print
    </button>
</div>

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
    </table>
</div>

<?php require "../includes/footer.php"; ?>
