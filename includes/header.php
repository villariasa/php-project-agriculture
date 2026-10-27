<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base   = $base ?? "";
$active = $active ?? "";

$nav_links = [
    "dashboard" => ["label" => "Dashboard", "url" => "index.php"],
    "farmers"   => ["label" => "Farmers",   "url" => "farmers/index.php"],
    "farms"     => ["label" => "Farms",     "url" => "farms/index.php"],
    "crops"     => ["label" => "Crops",     "url" => "crops/index.php"],
    "plantings" => ["label" => "Plantings", "url" => "plantings/index.php"],
    "harvests"  => ["label" => "Harvests",  "url" => "harvests/index.php"],
    "reports"   => ["label" => "Reports",   "url" => "reports/index.php"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? "AIS") ?> | Agriculture Information System</title>
    <!-- Tailwind Play CDN serves dynamic JS, so SRI hashes are not supported; required per class standard -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

<nav class="bg-white border-b border-gray-200 print:hidden">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="<?= $base ?>index.php" class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-green-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                </svg>
                <span class="font-bold text-gray-800 hidden lg:block">Agriculture Information System</span>
                <span class="font-bold text-gray-800 lg:hidden">AIS</span>
            </a>

            <!-- Nav Links -->
            <div class="hidden md:flex items-center gap-1">
                <?php foreach ($nav_links as $key => $link): ?>
                    <a href="<?= $base . $link['url'] ?>"
                       class="px-3 py-2 text-sm rounded-lg <?= $active === $key
                           ? 'text-green-700 font-semibold bg-green-50'
                           : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' ?>">
                        <?= $link['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- User Info -->
            <div class="flex items-center gap-3">
                <span class="hidden sm:block text-sm text-gray-600"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
                <span class="px-2 py-1 rounded-full text-xs font-medium <?= ($_SESSION['role'] ?? '') === 'admin' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' ?>">
                    <?= htmlspecialchars(ucfirst($_SESSION['role'] ?? '')) ?>
                </span>
                <a href="<?= $base ?>auth/logout.php" title="Logout" class="text-gray-400 hover:text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Mobile Nav -->
        <div class="md:hidden flex gap-1 overflow-x-auto pb-2">
            <?php foreach ($nav_links as $key => $link): ?>
                <a href="<?= $base . $link['url'] ?>"
                   class="px-3 py-1 text-sm rounded-lg whitespace-nowrap <?= $active === $key
                       ? 'text-green-700 font-semibold bg-green-50'
                       : 'text-gray-600 hover:bg-gray-50' ?>">
                    <?= $link['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-6 w-full flex-1">
