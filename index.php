<?php
$base = "";
require_once "includes/auth_check.php";
require_once "config/Database.php";

$pdo = (new Database())->connect();

// ---------- Date range (defaults to the last 12 months) ----------
$default_from = date("Y-m-01", strtotime("-11 months"));
$default_to   = date("Y-m-d");
$date_from    = (string) trim($_GET["from"] ?? "");
$date_to      = (string) trim($_GET["to"] ?? "");
if ($date_from === "") { $date_from = $default_from; }
if ($date_to === "")   { $date_to   = $default_to; }
$range = ["date_from" => $date_from, "date_to" => $date_to];

// ---------- Month-over-month KPIs (fixed to the calendar month, not the range) ----------
$stmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN harvest_date >= DATE_TRUNC('month', CURRENT_DATE)
                          THEN quantity_kg * selling_price_per_kg END), 0) AS revenue_this,
        COALESCE(SUM(CASE WHEN harvest_date >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '1 month'
                           AND harvest_date <  DATE_TRUNC('month', CURRENT_DATE)
                          THEN quantity_kg * selling_price_per_kg END), 0) AS revenue_last,
        COALESCE(SUM(CASE WHEN harvest_date >= DATE_TRUNC('month', CURRENT_DATE)
                          THEN quantity_kg END), 0) AS kg_this,
        COALESCE(SUM(CASE WHEN harvest_date >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '1 month'
                           AND harvest_date <  DATE_TRUNC('month', CURRENT_DATE)
                          THEN quantity_kg END), 0) AS kg_last
     FROM harvest
     WHERE is_deleted = FALSE"
);
$stmt->execute();
$month_kpis = $stmt->fetch();

// Percent change vs last month; null when last month had no data
function pctChange(float $current, float $previous) {
    if ($previous <= 0) {
        return null;
    }
    return (($current - $previous) / $previous) * 100;
}
$revenue_pct = pctChange((float) $month_kpis['revenue_this'], (float) $month_kpis['revenue_last']);
$kg_pct      = pctChange((float) $month_kpis['kg_this'], (float) $month_kpis['kg_last']);

// ---------- Range-scoped KPIs ----------
// Average yield per hectare: each planting's harvested kg in range over its planted area
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(t.kg) / NULLIF(SUM(t.area), 0), 0)
     FROM (
        SELECT p.area_planted AS area, SUM(h.quantity_kg) AS kg
        FROM harvest h
        JOIN planting p ON h.planting_id = p.planting_id
        WHERE h.is_deleted = FALSE AND p.is_deleted = FALSE
          AND h.harvest_date BETWEEN :date_from AND :date_to
        GROUP BY p.planting_id, p.area_planted
     ) t"
);
$stmt->execute($range);
$avg_yield_per_ha = (float) $stmt->fetchColumn();

$active_plantings = $pdo->query(
    "SELECT COUNT(*) FROM planting p
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE AND ps.status_name IN ('Planted', 'Growing')"
)->fetchColumn();

$due_soon = $pdo->query(
    "SELECT COUNT(*) FROM planting p
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE
       AND ps.status_name <> 'Harvested'
       AND p.expected_harvest_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '14 days'"
)->fetchColumn();

$total_farmers = $pdo->query("SELECT COUNT(*) FROM farmer WHERE is_deleted = FALSE")->fetchColumn();

// ---------- Monthly series for the charts (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT TO_CHAR(DATE_TRUNC('month', harvest_date), 'Mon YYYY') AS month_label,
            SUM(quantity_kg) AS total_kg,
            SUM(quantity_kg * selling_price_per_kg) AS total_revenue
     FROM harvest
     WHERE is_deleted = FALSE
       AND harvest_date BETWEEN :date_from AND :date_to
     GROUP BY DATE_TRUNC('month', harvest_date)
     ORDER BY DATE_TRUNC('month', harvest_date)"
);
$stmt->execute($range);
$monthly = $stmt->fetchAll();

// ---------- Top 5 crops by harvested kg (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT c.crop_name, SUM(h.quantity_kg) AS total_kg
     FROM harvest h
     JOIN planting p ON h.planting_id = p.planting_id
     JOIN crop c ON p.crop_id = c.crop_id
     WHERE h.is_deleted = FALSE
       AND h.harvest_date BETWEEN :date_from AND :date_to
     GROUP BY c.crop_name
     ORDER BY SUM(h.quantity_kg) DESC
     LIMIT 5"
);
$stmt->execute($range);
$top_crops = $stmt->fetchAll();

// ---------- Top 5 farms by revenue (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT f.farm_name, fm.first_name || ' ' || fm.last_name AS owner_name,
            SUM(h.quantity_kg) AS total_kg,
            SUM(h.quantity_kg * h.selling_price_per_kg) AS total_revenue
     FROM harvest h
     JOIN planting p ON h.planting_id = p.planting_id
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN farmer fm ON f.farmer_id = fm.farmer_id
     WHERE h.is_deleted = FALSE
       AND h.harvest_date BETWEEN :date_from AND :date_to
     GROUP BY f.farm_name, owner_name
     ORDER BY SUM(h.quantity_kg * h.selling_price_per_kg) DESC
     LIMIT 5"
);
$stmt->execute($range);
$top_farms = $stmt->fetchAll();

// ---------- Right rail panels ----------
$upcoming_harvests = $pdo->query(
    "SELECT p.expected_harvest_date, f.farm_name, c.crop_name,
            (p.expected_harvest_date - CURRENT_DATE) AS days_left
     FROM planting p
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN crop c ON p.crop_id = c.crop_id
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE
       AND ps.status_name <> 'Harvested'
       AND p.expected_harvest_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '14 days'
     ORDER BY p.expected_harvest_date
     LIMIT 8"
)->fetchAll();

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

$badge_colors = [
    "Planted"   => "bg-blue-50 text-blue-700",
    "Growing"   => "bg-yellow-50 text-yellow-700",
    "Harvested" => "bg-green-50 text-green-700",
];

// Renders the small "vs last month" comparison line under a KPI number
function trendLine($pct) {
    if ($pct === null) {
        return '<p class="text-xs text-gray-400 mt-1">no data last month</p>';
    }
    $up    = $pct >= 0;
    $color = $up ? "text-green-700" : "text-red-600";
    $sign  = $up ? "+" : "";
    return '<p class="text-xs ' . $color . ' mt-1">' . $sign . number_format($pct, 1) . '% vs last month</p>';
}

$page_title = "Dashboard";
$active     = "dashboard";
require "includes/header.php";
?>

<!-- Page header + date range -->
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <p class="text-sm text-gray-500">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>!</p>
    </div>
    <form method="GET" class="flex items-end gap-2 print:hidden">
        <div>
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>"
                   class="p-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>"
                   class="p-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        </div>
        <button type="submit" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg text-sm">Apply</button>
        <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700 pb-2">Reset</a>
    </form>
</div>

<!-- KPI Strip -->
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden shadow-sm mb-6">

    <a href="reports/harvest_revenue.php" class="bg-white p-4 hover:bg-gray-50">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Revenue (this month)</p>
        <p class="text-2xl font-bold">&#8369;<?= number_format((float) $month_kpis['revenue_this'], 2) ?></p>
        <?= trendLine($revenue_pct) ?>
    </a>

    <a href="harvests/index.php" class="bg-white p-4 hover:bg-gray-50">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Harvested (this month)</p>
        <p class="text-2xl font-bold"><?= number_format((float) $month_kpis['kg_this'], 1) ?> <span class="text-sm font-normal text-gray-500">kg</span></p>
        <?= trendLine($kg_pct) ?>
    </a>

    <a href="reports/yield_efficiency.php" class="bg-white p-4 hover:bg-gray-50">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Avg Yield / Hectare</p>
        <p class="text-2xl font-bold"><?= number_format($avg_yield_per_ha, 1) ?> <span class="text-sm font-normal text-gray-500">kg/ha</span></p>
        <p class="text-xs text-gray-400 mt-1">within selected range</p>
    </a>

    <a href="plantings/index.php" class="bg-white p-4 hover:bg-gray-50">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Active Plantings</p>
        <p class="text-2xl font-bold"><?= number_format($active_plantings) ?></p>
        <p class="text-xs text-gray-400 mt-1">planted or growing</p>
    </a>

    <a href="plantings/index.php" class="bg-white p-4 hover:bg-gray-50">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Harvest Due Soon</p>
        <p class="text-2xl font-bold"><?= number_format($due_soon) ?></p>
        <p class="text-xs text-gray-400 mt-1">expected within 14 days</p>
    </a>

    <a href="farmers/index.php" class="bg-white p-4 hover:bg-gray-50">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Total Farmers</p>
        <p class="text-2xl font-bold"><?= number_format($total_farmers) ?></p>
        <p class="text-xs text-gray-400 mt-1">active records</p>
    </a>

</div>

<div class="flex flex-col xl:flex-row gap-6">

    <!-- Primary feed: charts + top performers -->
    <div class="flex-1 min-w-0 space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Harvest Volume Chart -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="font-semibold">Harvest Volume</h2>
                        <p class="text-xs text-gray-500">kg per month, selected range</p>
                    </div>
                    <a href="harvests/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
                </div>
                <?php if (count($monthly) === 0): ?>
                    <div class="border border-dashed border-gray-300 rounded-lg px-4 py-10 text-center text-sm text-gray-500">No harvest data in this range.</div>
                <?php else: ?>
                    <div class="relative h-64"><canvas id="volumeChart"></canvas></div>
                <?php endif; ?>
            </div>

            <!-- Revenue Chart -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="font-semibold">Revenue</h2>
                        <p class="text-xs text-gray-500">&#8369; per month, selected range</p>
                    </div>
                    <a href="reports/harvest_revenue.php" class="text-sm text-green-700 hover:underline">Full report &rarr;</a>
                </div>
                <?php if (count($monthly) === 0): ?>
                    <div class="border border-dashed border-gray-300 rounded-lg px-4 py-10 text-center text-sm text-gray-500">No revenue data in this range.</div>
                <?php else: ?>
                    <div class="relative h-64"><canvas id="revenueChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Crops Chart -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="font-semibold">Top Crops by Yield</h2>
                        <p class="text-xs text-gray-500">harvested kg, selected range</p>
                    </div>
                    <a href="crops/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
                </div>
                <?php if (count($top_crops) === 0): ?>
                    <div class="border border-dashed border-gray-300 rounded-lg px-4 py-10 text-center text-sm text-gray-500">No harvest data in this range.</div>
                <?php else: ?>
                    <div class="relative h-56"><canvas id="cropsChart"></canvas></div>
                <?php endif; ?>
            </div>

            <!-- Top Farms Table -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between px-6 pt-6 mb-4">
                    <div>
                        <h2 class="font-semibold">Top Farms by Revenue</h2>
                        <p class="text-xs text-gray-500">selected range</p>
                    </div>
                    <a href="farms/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
                </div>
                <?php if (count($top_farms) === 0): ?>
                    <div class="border border-dashed border-gray-300 rounded-lg px-4 py-10 mx-6 mb-6 text-center text-sm text-gray-500">No harvest data in this range.</div>
                <?php else: ?>
                    <table class="w-full text-sm mb-2">
                        <thead class="text-gray-500 uppercase text-xs tracking-wide">
                            <tr>
                                <th class="px-6 py-2 text-left">Farm</th>
                                <th class="px-4 py-2 text-right">kg</th>
                                <th class="px-6 py-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_farms as $row): ?>
                                <tr class="border-t border-gray-100">
                                    <td class="px-6 py-2.5">
                                        <p class="font-medium"><?= htmlspecialchars($row['farm_name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($row['owner_name']) ?></p>
                                    </td>
                                    <td class="px-4 py-2.5 text-right"><?= number_format($row['total_kg'], 1) ?></td>
                                    <td class="px-6 py-2.5 text-right font-medium text-green-700">&#8369;<?= number_format($row['total_revenue'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right rail -->
    <aside class="xl:w-96 shrink-0 space-y-6">

        <!-- Upcoming Harvests -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold">Upcoming Harvests</h2>
                <span class="text-xs text-gray-400">next 14 days</span>
            </div>
            <?php if (count($upcoming_harvests) === 0): ?>
                <div class="border border-dashed border-gray-300 rounded-lg px-4 py-6 text-center text-sm text-gray-500">Nothing due in the next two weeks.</div>
            <?php endif; ?>
            <?php foreach ($upcoming_harvests as $row): ?>
                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                    <div>
                        <p class="text-sm font-medium"><?= htmlspecialchars($row['crop_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($row['farm_name']) ?> &middot; <?= htmlspecialchars($row['expected_harvest_date']) ?></p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= (int) $row['days_left'] <= 3 ? 'bg-red-50 text-red-700' : 'bg-yellow-50 text-yellow-700' ?>">
                        <?= (int) $row['days_left'] === 0 ? 'today' : (int) $row['days_left'] . 'd left' ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Plantings -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold">Recent Plantings</h2>
                <a href="plantings/index.php" class="text-sm text-green-700 hover:underline">View all &rarr;</a>
            </div>
            <?php if (count($recent_plantings) === 0): ?>
                <div class="border border-dashed border-gray-300 rounded-lg px-4 py-6 text-center text-sm text-gray-500">No planting records yet.</div>
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
                <div class="border border-dashed border-gray-300 rounded-lg px-4 py-6 text-center text-sm text-gray-500">No harvest records yet.</div>
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

<?php if (count($monthly) > 0 || count($top_crops) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
        integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4"
        crossorigin="anonymous"></script>
<script>
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = "#6b7280";

    <?php if (count($monthly) > 0): ?>
    new Chart(document.getElementById("volumeChart"), {
        type: "bar",
        data: {
            labels: <?= json_encode(array_column($monthly, "month_label")) ?>,
            datasets: [{
                label: "Harvested (kg)",
                data: <?= json_encode(array_map("floatval", array_column($monthly, "total_kg"))) ?>,
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
                tooltip: { callbacks: { label: function (ctx) { return ctx.parsed.y.toLocaleString() + " kg"; } } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: "#f3f4f6" }, border: { display: false } }
            }
        }
    });

    new Chart(document.getElementById("revenueChart"), {
        type: "line",
        data: {
            labels: <?= json_encode(array_column($monthly, "month_label")) ?>,
            datasets: [{
                label: "Revenue",
                data: <?= json_encode(array_map("floatval", array_column($monthly, "total_revenue"))) ?>,
                borderColor: "#16a34a",
                backgroundColor: "rgba(22, 163, 74, 0.08)",
                fill: true,
                tension: 0.3,
                pointBackgroundColor: "#16a34a",
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (ctx) { return "PHP " + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2}); } } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: "#f3f4f6" }, border: { display: false } }
            }
        }
    });
    <?php endif; ?>

    <?php if (count($top_crops) > 0): ?>
    new Chart(document.getElementById("cropsChart"), {
        type: "bar",
        data: {
            labels: <?= json_encode(array_column($top_crops, "crop_name")) ?>,
            datasets: [{
                label: "Harvested (kg)",
                data: <?= json_encode(array_map("floatval", array_column($top_crops, "total_kg"))) ?>,
                backgroundColor: "#16a34a",
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
                legend: { display: false },
                tooltip: { callbacks: { label: function (ctx) { return ctx.parsed.x.toLocaleString() + " kg"; } } }
            },
            scales: {
                x: { beginAtZero: true, grid: { color: "#f3f4f6" }, border: { display: false } },
                y: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
</script>
<?php endif; ?>

<?php require "includes/footer.php"; ?>
