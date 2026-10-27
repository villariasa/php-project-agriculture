<?php
$base = "";
require_once "includes/auth_check.php";
require_once "config/Database.php";

$pdo = (new Database())->connect();

// Summary counts
$total_farmers   = $pdo->query("SELECT COUNT(*) FROM farmer")->fetchColumn();
$total_farms     = $pdo->query("SELECT COUNT(*) FROM farm")->fetchColumn();
$total_crops     = $pdo->query("SELECT COUNT(*) FROM crop")->fetchColumn();
$active_plantings = $pdo->query("SELECT COUNT(*) FROM planting WHERE status IN ('Planted', 'Growing')")->fetchColumn();
$total_harvests  = $pdo->query("SELECT COUNT(*) FROM harvest")->fetchColumn();

// Recent plantings (last 5)
$recent_plantings = $pdo->query(
    "SELECT p.planting_date, p.status, f.farm_name, c.crop_name
     FROM planting p
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN crop c ON p.crop_id = c.crop_id
     ORDER BY p.planting_id DESC
     LIMIT 5"
)->fetchAll();

// Recent harvests (last 5)
$recent_harvests = $pdo->query(
    "SELECT h.harvest_date, h.quantity_kg, h.selling_price_per_kg, c.crop_name, f.farm_name
     FROM harvest h
     JOIN planting p ON h.planting_id = p.planting_id
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN crop c ON p.crop_id = c.crop_id
     ORDER BY h.harvest_id DESC
     LIMIT 5"
)->fetchAll();

$badge_colors = [
    "Planted"   => "bg-blue-50 text-blue-700",
    "Growing"   => "bg-yellow-50 text-yellow-700",
    "Harvested" => "bg-green-50 text-green-700",
];

$cards = [
    ["label" => "Total Farmers",    "count" => $total_farmers,    "url" => "farmers/index.php",
     "icon" => "M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"],
    ["label" => "Active Farms",     "count" => $total_farms,      "url" => "farms/index.php",
     "icon" => "M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819"],
    ["label" => "Cataloged Crops",  "count" => $total_crops,      "url" => "crops/index.php",
     "icon" => "M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z"],
    ["label" => "Active Plantings", "count" => $active_plantings, "url" => "plantings/index.php",
     "icon" => "M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"],
    ["label" => "Total Harvests",   "count" => $total_harvests,   "url" => "harvests/index.php",
     "icon" => "m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"],
];

$page_title = "Dashboard";
$active     = "dashboard";
require "includes/header.php";
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="text-sm text-gray-500">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>!</p>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php foreach ($cards as $card): ?>
        <a href="<?= $card['url'] ?>" class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 hover:border-green-300">
            <div class="bg-green-50 text-green-600 rounded-lg p-2 w-fit mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $card['icon'] ?>" />
                </svg>
            </div>
            <p class="text-3xl font-bold"><?= $card['count'] ?></p>
            <p class="text-sm text-gray-500"><?= $card['label'] ?></p>
        </a>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Recent Plantings -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold">Recent Plantings</h2>
            <a href="plantings/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
        </div>
        <?php if (count($recent_plantings) === 0): ?>
            <p class="text-sm text-gray-500 py-4 text-center">No planting records yet.</p>
        <?php endif; ?>
        <?php foreach ($recent_plantings as $row): ?>
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                    <p class="text-sm font-medium"><?= htmlspecialchars($row['crop_name']) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($row['farm_name']) ?> &middot; <?= htmlspecialchars($row['planting_date']) ?></p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $badge_colors[$row['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                    <?= htmlspecialchars($row['status']) ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Harvests -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold">Recent Harvests</h2>
            <a href="harvests/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
        </div>
        <?php if (count($recent_harvests) === 0): ?>
            <p class="text-sm text-gray-500 py-4 text-center">No harvest records yet.</p>
        <?php endif; ?>
        <?php foreach ($recent_harvests as $row): ?>
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                    <p class="text-sm font-medium"><?= htmlspecialchars($row['crop_name']) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($row['farm_name']) ?> &middot; <?= htmlspecialchars($row['harvest_date']) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium"><?= number_format($row['quantity_kg'], 2) ?> kg</p>
                    <p class="text-xs text-green-700">&#8369;<?= number_format($row['quantity_kg'] * $row['selling_price_per_kg'], 2) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require "includes/footer.php"; ?>
