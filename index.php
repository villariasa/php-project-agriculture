<?php
$base = "";
require_once "includes/auth_check.php";
require_once "config/Database.php";

$pdo = (new Database())->connect();

// Summary counts (active records only - soft-deleted rows are excluded)
$total_farmers   = $pdo->query("SELECT COUNT(*) FROM farmer WHERE is_deleted = FALSE")->fetchColumn();
$total_farms     = $pdo->query("SELECT COUNT(*) FROM farm WHERE is_deleted = FALSE")->fetchColumn();
$total_crops     = $pdo->query("SELECT COUNT(*) FROM crop WHERE is_deleted = FALSE")->fetchColumn();
$active_plantings = $pdo->query(
    "SELECT COUNT(*) FROM planting p
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE AND ps.status_name IN ('Planted', 'Growing')"
)->fetchColumn();
$total_harvests  = $pdo->query("SELECT COUNT(*) FROM harvest WHERE is_deleted = FALSE")->fetchColumn();
$total_harvested_kg = $pdo->query(
    "SELECT COALESCE(SUM(quantity_kg), 0) FROM harvest WHERE is_deleted = FALSE"
)->fetchColumn();

// Harvest volume per month (last 12 months) for the chart
$harvest_by_month = $pdo->query(
    "SELECT TO_CHAR(DATE_TRUNC('month', harvest_date), 'Mon YYYY') AS month_label,
            SUM(quantity_kg) AS total_kg
     FROM harvest
     WHERE is_deleted = FALSE
       AND harvest_date >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '11 months'
     GROUP BY DATE_TRUNC('month', harvest_date)
     ORDER BY DATE_TRUNC('month', harvest_date)"
)->fetchAll();

// Plantings per status for the chart
$plantings_by_status = $pdo->query(
    "SELECT ps.status_name, COUNT(*) AS total
     FROM planting p
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE
     GROUP BY ps.status_name
     ORDER BY COUNT(*) DESC"
)->fetchAll();

// Recent plantings (last 5)
$recent_plantings = $pdo->query(
    "SELECT p.planting_date, ps.status_name AS status, f.farm_name, c.crop_name
     FROM planting p
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN crop c ON p.crop_id = c.crop_id
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE
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
     WHERE h.is_deleted = FALSE
     ORDER BY h.harvest_id DESC
     LIMIT 5"
)->fetchAll();

$badge_colors = [
    "Planted"   => "bg-blue-50 text-blue-700",
    "Growing"   => "bg-yellow-50 text-yellow-700",
    "Harvested" => "bg-green-50 text-green-700",
];

// Chart colors keyed to the same status identity as the badges above
$status_chart_colors = [
    "Planted"   => "#3b82f6",
    "Growing"   => "#ca8a04",
    "Harvested" => "#16a34a",
];

$cards = [
    ["label" => "Total Farmers",    "count" => number_format($total_farmers),    "url" => "farmers/index.php",
     "icon" => "M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"],
    ["label" => "Active Farms",     "count" => number_format($total_farms),      "url" => "farms/index.php",
     "icon" => "M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819"],
    ["label" => "Cataloged Crops",  "count" => number_format($total_crops),      "url" => "crops/index.php",
     "icon" => "M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z"],
    ["label" => "Active Plantings", "count" => number_format($active_plantings), "url" => "plantings/index.php",
     "icon" => "M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"],
    ["label" => "Total Harvests",   "count" => number_format($total_harvests),   "url" => "harvests/index.php",
     "icon" => "m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"],
    ["label" => "Harvested (kg)",   "count" => number_format((float) $total_harvested_kg, 1), "url" => "reports/harvest_revenue.php",
     "icon" => "M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"],
];

$page_title = "Dashboard";
$active     = "dashboard";
require "includes/header.php";
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="text-sm text-gray-500">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>!</p>
</div>

<!-- KPI Strip -->
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden shadow-sm mb-6">
    <?php foreach ($cards as $card): ?>
        <a href="<?= $card['url'] ?>" class="bg-white p-4 hover:bg-gray-50">
            <div class="flex items-center gap-2 text-gray-500 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $card['icon'] ?>" />
                </svg>
                <span class="text-xs uppercase tracking-wide"><?= $card['label'] ?></span>
            </div>
            <p class="text-2xl font-bold"><?= $card['count'] ?></p>
        </a>
    <?php endforeach; ?>
</div>

<div class="flex flex-col xl:flex-row gap-6">

    <!-- Primary feed: charts -->
    <div class="flex-1 min-w-0 space-y-6">

        <!-- Harvest Volume Chart -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-semibold">Harvest Volume</h2>
                    <p class="text-xs text-gray-500">Total quantity harvested (kg) per month, last 12 months</p>
                </div>
                <a href="harvests/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
            </div>
            <?php if (count($harvest_by_month) === 0): ?>
                <p class="text-sm text-gray-500 py-10 text-center">No harvest data recorded in the last 12 months.</p>
            <?php else: ?>
                <div class="relative h-72">
                    <canvas id="harvestChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <!-- Plantings by Status Chart -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-semibold">Plantings by Status</h2>
                    <p class="text-xs text-gray-500">Count of active planting records per status</p>
                </div>
                <a href="plantings/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
            </div>
            <?php if (count($plantings_by_status) === 0): ?>
                <p class="text-sm text-gray-500 py-10 text-center">No planting records yet.</p>
            <?php else: ?>
                <div class="relative h-56">
                    <canvas id="statusChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Right rail: recent activity -->
    <aside class="xl:w-96 shrink-0 space-y-6">

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

    </aside>

</div>

<?php if (count($harvest_by_month) > 0 || count($plantings_by_status) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
        integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4"
        crossorigin="anonymous"></script>
<script>
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = "#6b7280";

    <?php if (count($harvest_by_month) > 0): ?>
    new Chart(document.getElementById("harvestChart"), {
        type: "bar",
        data: {
            labels: <?= json_encode(array_column($harvest_by_month, "month_label")) ?>,
            datasets: [{
                label: "Harvested (kg)",
                data: <?= json_encode(array_map("floatval", array_column($harvest_by_month, "total_kg"))) ?>,
                backgroundColor: "#16a34a",
                borderRadius: { topLeft: 4, topRight: 4 },
                categoryPercentage: 0.6,
                barPercentage: 0.9
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ctx.parsed.y.toLocaleString() + " kg";
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grid: { color: "#f3f4f6" },
                    border: { display: false },
                    title: { display: true, text: "kg", color: "#9ca3af" }
                }
            }
        }
    });
    <?php endif; ?>

    <?php if (count($plantings_by_status) > 0): ?>
    new Chart(document.getElementById("statusChart"), {
        type: "bar",
        data: {
            labels: <?= json_encode(array_column($plantings_by_status, "status_name")) ?>,
            datasets: [{
                label: "Plantings",
                data: <?= json_encode(array_map("intval", array_column($plantings_by_status, "total"))) ?>,
                backgroundColor: <?= json_encode(array_map(
                    fn($row) => $status_chart_colors[$row['status_name']] ?? "#6b7280",
                    $plantings_by_status
                )) ?>,
                borderRadius: { topRight: 4, bottomRight: 4 },
                categoryPercentage: 0.6,
                barPercentage: 0.9
            }]
        },
        options: {
            indexAxis: "y",
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: "#f3f4f6" },
                    border: { display: false },
                    ticks: { precision: 0 }
                },
                y: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
</script>
<?php endif; ?>

<?php require "includes/footer.php"; ?>
