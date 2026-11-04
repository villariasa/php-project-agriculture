<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/report_helpers.php";
require_once "../config/Database.php";

$pdo = (new Database())->connect();

// ---------- Date range (defaults to the last 12 months) ----------
$default_from = date("Y-m-01", strtotime("-11 months"));
$default_to   = date("Y-m-d");
$date_from    = (string) trim($_GET["from"] ?? "");
$date_to      = (string) trim($_GET["to"] ?? "");
if ($date_from === "") { $date_from = $default_from; }
if ($date_to === "")   { $date_to   = $default_to; }
$range = ["date_from" => $date_from, "date_to" => $date_to];

// ---------- Headline KPIs (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS harvest_count,
            COALESCE(SUM(quantity_kg), 0) AS total_kg,
            COALESCE(SUM(quantity_kg * selling_price_per_kg), 0) AS total_revenue
     FROM harvest
     WHERE is_deleted = FALSE AND harvest_date BETWEEN :date_from AND :date_to"
);
$stmt->execute($range);
$kpi = $stmt->fetch();

$avg_price = $kpi['total_kg'] > 0 ? $kpi['total_revenue'] / $kpi['total_kg'] : 0;

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

// ---------- Monthly volume + revenue series ----------
$stmt = $pdo->prepare(
    "SELECT TO_CHAR(DATE_TRUNC('month', harvest_date), 'Mon YYYY') AS month_label,
            SUM(quantity_kg) AS total_kg,
            SUM(quantity_kg * selling_price_per_kg) AS total_revenue
     FROM harvest
     WHERE is_deleted = FALSE AND harvest_date BETWEEN :date_from AND :date_to
     GROUP BY DATE_TRUNC('month', harvest_date)
     ORDER BY DATE_TRUNC('month', harvest_date)"
);
$stmt->execute($range);
$monthly = $stmt->fetchAll();

// ---------- Plantings by status (current snapshot, not range) ----------
$plantings_by_status = $pdo->query(
    "SELECT ps.status_name, COUNT(*) AS total
     FROM planting p
     JOIN planting_status ps ON p.status_id = ps.status_id
     WHERE p.is_deleted = FALSE
     GROUP BY ps.status_name
     ORDER BY COUNT(*) DESC"
)->fetchAll();

// ---------- Harvests by quality grade (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT qg.grade_name, COUNT(*) AS total, SUM(h.quantity_kg) AS total_kg
     FROM harvest h
     JOIN quality_grades qg ON h.grade_id = qg.grade_id
     WHERE h.is_deleted = FALSE AND h.harvest_date BETWEEN :date_from AND :date_to
     GROUP BY qg.grade_name
     ORDER BY qg.grade_name"
);
$stmt->execute($range);
$by_grade = $stmt->fetchAll();

// ---------- Top 5 crops by harvested kg (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT c.crop_name, SUM(h.quantity_kg) AS total_kg
     FROM harvest h
     JOIN planting p ON h.planting_id = p.planting_id
     JOIN crop c ON p.crop_id = c.crop_id
     WHERE h.is_deleted = FALSE AND h.harvest_date BETWEEN :date_from AND :date_to
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
     WHERE h.is_deleted = FALSE AND h.harvest_date BETWEEN :date_from AND :date_to
     GROUP BY f.farm_name, owner_name
     ORDER BY SUM(h.quantity_kg * h.selling_price_per_kg) DESC
     LIMIT 5"
);
$stmt->execute($range);
$top_farms = $stmt->fetchAll();

// ---------- Top 5 farmers by revenue (within the range) ----------
$stmt = $pdo->prepare(
    "SELECT fm.first_name || ' ' || fm.last_name AS farmer_name,
            COUNT(h.harvest_id) AS harvest_count,
            SUM(h.quantity_kg * h.selling_price_per_kg) AS total_revenue
     FROM harvest h
     JOIN planting p ON h.planting_id = p.planting_id
     JOIN farm f ON p.farm_id = f.farm_id
     JOIN farmer fm ON f.farmer_id = fm.farmer_id
     WHERE h.is_deleted = FALSE AND h.harvest_date BETWEEN :date_from AND :date_to
     GROUP BY farmer_name
     ORDER BY SUM(h.quantity_kg * h.selling_price_per_kg) DESC
     LIMIT 5"
);
$stmt->execute($range);
$top_farmers = $stmt->fetchAll();

$has_data = count($monthly) > 0;

// Chart colors keyed to the badge identities used across the system
$status_chart_colors = ["Planted" => "#3b82f6", "Growing" => "#ca8a04", "Harvested" => "#16a34a"];
$grade_chart_colors  = ["Grade A" => "#16a34a", "Grade B" => "#ca8a04", "Grade C" => "#dc2626"];

// Detailed report links (printable + CSV pages)
$detail_reports = [
    ["title" => "Farmers & Farm Ownership", "url" => "farmers_farms.php"],
    ["title" => "Crop Production per Farm", "url" => "crop_production.php"],
    ["title" => "Planting Cycle Status",    "url" => "planting_status.php"],
    ["title" => "Harvest Yield & Revenue",  "url" => "harvest_revenue.php"],
    ["title" => "Farmer Profitability",     "url" => "farmer_profitability.php"],
    ["title" => "Seasonal Performance",     "url" => "seasonal_performance.php"],
    ["title" => "Yield Efficiency",         "url" => "yield_efficiency.php"],
];

$page_title  = "Reports";
$active      = "reports";
$breadcrumbs = [["Reports", null]];
require "../includes/header.php";
?>

<?php reportPrintHeader("Analytics Overview", ["From" => $date_from, "To" => $date_to]); ?>

<!-- Page header + date range -->
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Reports &amp; Analytics</h1>
        <p class="text-sm text-gray-500">Production and revenue overview &middot; <?= htmlspecialchars($date_from) ?> to <?= htmlspecialchars($date_to) ?></p>
    </div>
    <div class="flex items-end gap-2 print:hidden">
        <form method="GET" class="flex items-end gap-2">
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
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-3 py-1.5 rounded-lg text-sm">Apply</button>
            <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700 pb-2">Reset</a>
        </form>
        <button onclick="window.print()"
                class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg text-sm inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
            </svg>
            Print
        </button>
    </div>
</div>

<!-- KPI strip -->
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden shadow-sm mb-6">
    <div class="bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Revenue</p>
        <p class="text-xl font-bold">&#8369;<?= number_format((float) $kpi['total_revenue'], 2) ?></p>
    </div>
    <div class="bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Harvested</p>
        <p class="text-xl font-bold"><?= number_format((float) $kpi['total_kg'], 1) ?> <span class="text-sm font-normal text-gray-500">kg</span></p>
    </div>
    <div class="bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Harvests</p>
        <p class="text-xl font-bold"><?= number_format((int) $kpi['harvest_count']) ?></p>
    </div>
    <div class="bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Avg Price / kg</p>
        <p class="text-xl font-bold">&#8369;<?= number_format($avg_price, 2) ?></p>
    </div>
    <div class="bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Avg Yield / ha</p>
        <p class="text-xl font-bold"><?= number_format($avg_yield_per_ha, 1) ?> <span class="text-sm font-normal text-gray-500">kg</span></p>
    </div>
    <div class="bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Active Plantings</p>
        <p class="text-xl font-bold"><?= number_format($active_plantings) ?></p>
    </div>
</div>

<?php if (!$has_data): ?>
    <div class="border border-dashed border-gray-300 rounded-lg px-4 py-12 text-center text-sm text-gray-500 mb-6">
        No harvest data recorded between <?= htmlspecialchars($date_from) ?> and <?= htmlspecialchars($date_to) ?>.
        Adjust the date range above, or <a href="../harvests/index.php" class="text-green-700 font-medium hover:underline">record a harvest</a>.
    </div>
<?php else: ?>

<!-- Production & revenue trend (full width) -->
<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="font-semibold">Production &amp; Revenue by Month</h2>
            <p class="text-xs text-gray-500">Bars: harvested kg &middot; Line: revenue (&#8369;)</p>
        </div>
        <a href="harvest_revenue.php" class="text-sm text-green-700 hover:underline print:hidden">Detailed report &rarr;</a>
    </div>
    <div class="relative h-72"><canvas id="trendChart"></canvas></div>
</div>

<!-- Breakdown charts -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="mb-4">
            <h2 class="font-semibold">Plantings by Status</h2>
            <p class="text-xs text-gray-500">current snapshot of all active plantings</p>
        </div>
        <?php if (count($plantings_by_status) === 0): ?>
            <div class="border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center text-sm text-gray-500">No planting records.</div>
        <?php else: ?>
            <div class="relative h-52"><canvas id="statusChart"></canvas></div>
        <?php endif; ?>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="mb-4">
            <h2 class="font-semibold">Harvests by Grade</h2>
            <p class="text-xs text-gray-500">quality distribution, selected range</p>
        </div>
        <?php if (count($by_grade) === 0): ?>
            <div class="border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center text-sm text-gray-500">No harvests in range.</div>
        <?php else: ?>
            <div class="relative h-52"><canvas id="gradeChart"></canvas></div>
        <?php endif; ?>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="mb-4">
            <h2 class="font-semibold">Top Crops by Yield</h2>
            <p class="text-xs text-gray-500">harvested kg, selected range</p>
        </div>
        <?php if (count($top_crops) === 0): ?>
            <div class="border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center text-sm text-gray-500">No harvests in range.</div>
        <?php else: ?>
            <div class="relative h-52"><canvas id="cropsChart"></canvas></div>
        <?php endif; ?>
    </div>
</div>

<!-- Leaderboards -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between px-6 pt-6 mb-3">
            <div>
                <h2 class="font-semibold">Top Farms by Revenue</h2>
                <p class="text-xs text-gray-500">selected range</p>
            </div>
            <a href="crop_production.php" class="text-sm text-green-700 hover:underline print:hidden">Detailed report &rarr;</a>
        </div>
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
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between px-6 pt-6 mb-3">
            <div>
                <h2 class="font-semibold">Top Farmers by Revenue</h2>
                <p class="text-xs text-gray-500">selected range</p>
            </div>
            <a href="farmer_profitability.php" class="text-sm text-green-700 hover:underline print:hidden">Detailed report &rarr;</a>
        </div>
        <table class="w-full text-sm mb-2">
            <thead class="text-gray-500 uppercase text-xs tracking-wide">
                <tr>
                    <th class="px-6 py-2 text-left">Farmer</th>
                    <th class="px-4 py-2 text-right">Harvests</th>
                    <th class="px-6 py-2 text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_farmers as $row): ?>
                    <tr class="border-t border-gray-100">
                        <td class="px-6 py-2.5 font-medium"><?= htmlspecialchars($row['farmer_name']) ?></td>
                        <td class="px-4 py-2.5 text-right"><?= number_format($row['harvest_count']) ?></td>
                        <td class="px-6 py-2.5 text-right font-medium text-green-700">&#8369;<?= number_format($row['total_revenue'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<!-- Detailed printable reports -->
<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 print:hidden">
    <h2 class="font-semibold mb-1">Detailed Reports</h2>
    <p class="text-xs text-gray-500 mb-4">Full record-level reports — each is filterable, printable, and exportable to CSV.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
        <?php foreach ($detail_reports as $report): ?>
            <a href="<?= $report['url'] ?>"
               class="flex items-center justify-between gap-2 px-4 py-3 border border-gray-200 rounded-lg text-sm text-gray-700 hover:border-green-600 hover:text-green-700">
                <?= $report['title'] ?>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($has_data): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
        integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4"
        crossorigin="anonymous"></script>
<script>
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = "#6b7280";

    // Combo chart: bars = harvested kg (left axis), line = revenue (right axis)
    new Chart(document.getElementById("trendChart"), {
        data: {
            labels: <?= json_encode(array_column($monthly, "month_label")) ?>,
            datasets: [
                {
                    type: "line",
                    label: "Revenue",
                    data: <?= json_encode(array_map("floatval", array_column($monthly, "total_revenue"))) ?>,
                    borderColor: "#166534",
                    backgroundColor: "#166534",
                    yAxisID: "yRevenue",
                    tension: 0.3,
                    pointRadius: 3
                },
                {
                    type: "bar",
                    label: "Harvested (kg)",
                    data: <?= json_encode(array_map("floatval", array_column($monthly, "total_kg"))) ?>,
                    backgroundColor: "#86efac",
                    borderRadius: { topLeft: 4, topRight: 4 },
                    yAxisID: "yKg",
                    categoryPercentage: 0.6,
                    barPercentage: 0.9
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom", labels: { boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            if (ctx.dataset.yAxisID === "yRevenue") {
                                return "Revenue: PHP " + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                            return "Harvested: " + ctx.parsed.y.toLocaleString() + " kg";
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                yKg: {
                    position: "left", beginAtZero: true,
                    grid: { color: "#f3f4f6" }, border: { display: false },
                    title: { display: true, text: "kg", color: "#9ca3af" }
                },
                yRevenue: {
                    position: "right", beginAtZero: true,
                    grid: { drawOnChartArea: false }, border: { display: false },
                    title: { display: true, text: "₱", color: "#9ca3af" }
                }
            }
        }
    });

    <?php if (count($by_grade) > 0): ?>
    new Chart(document.getElementById("gradeChart"), {
        type: "doughnut",
        data: {
            labels: <?= json_encode(array_column($by_grade, "grade_name")) ?>,
            datasets: [{
                data: <?= json_encode(array_map("intval", array_column($by_grade, "total"))) ?>,
                backgroundColor: <?= json_encode(array_map(
                    fn($row) => $grade_chart_colors[$row['grade_name']] ?? "#6b7280",
                    $by_grade
                )) ?>,
                borderWidth: 2,
                borderColor: "#ffffff"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "62%",
            plugins: { legend: { position: "bottom", labels: { boxWidth: 12 } } }
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

    <?php if (count($plantings_by_status) > 0): ?>
    new Chart(document.getElementById("statusChart"), {
        type: "doughnut",
        data: {
            labels: <?= json_encode(array_column($plantings_by_status, "status_name")) ?>,
            datasets: [{
                data: <?= json_encode(array_map("intval", array_column($plantings_by_status, "total"))) ?>,
                backgroundColor: <?= json_encode(array_map(
                    fn($row) => $status_chart_colors[$row['status_name']] ?? "#6b7280",
                    $plantings_by_status
                )) ?>,
                borderWidth: 2,
                borderColor: "#ffffff"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "62%",
            plugins: { legend: { position: "bottom", labels: { boxWidth: 12 } } }
        }
    });
    <?php endif; ?>
</script>
<?php endif; ?>

<?php require "../includes/footer.php"; ?>
