<?php
$base = "../";
require_once "../includes/auth_check.php";

$reports = [
    ["title" => "Farmers & Farm Ownership", "url" => "farmers_farms.php",
     "desc"  => "List of farmers with their farm count and total land area.",
     "icon"  => "M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"],
    ["title" => "Crop Production per Farm", "url" => "crop_production.php",
     "desc"  => "Harvested quantities per crop on each farm, filterable by farm.",
     "icon"  => "M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z"],
    ["title" => "Planting Cycle Status", "url" => "planting_status.php",
     "desc"  => "All planting records grouped by current cycle status.",
     "icon"  => "M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"],
    ["title" => "Harvest Yield & Revenue", "url" => "harvest_revenue.php",
     "desc"  => "Harvest quantities and total revenue within a date range.",
     "icon"  => "m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"],
];

$page_title = "Reports";
$active     = "reports";
require "../includes/header.php";
?>

<div class="max-w-4xl mx-auto">

<div class="mb-6">
    <h1 class="text-2xl font-bold">Reports</h1>
    <p class="text-sm text-gray-500">Printable summaries generated from live records</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($reports as $report): ?>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 flex items-start gap-4">
            <div class="bg-green-50 text-green-600 rounded-lg p-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $report['icon'] ?>" />
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="font-semibold"><?= $report['title'] ?></h2>
                <p class="text-sm text-gray-500 mb-3"><?= $report['desc'] ?></p>
                <a href="<?= $report['url'] ?>"
                   class="inline-block bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-1.5 rounded-lg text-sm">
                    Open Report
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</div>

<?php require "../includes/footer.php"; ?>
