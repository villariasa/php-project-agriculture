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

// Settings (lookup table management) is visible to admins only
if (($_SESSION['role'] ?? '') === 'admin') {
    $nav_links["settings"] = ["label" => "Settings", "url" => "settings/index.php"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? "AIS") ?> | Agriculture Information System</title>
    <!-- Tailwind Play CDN serves dynamic JS, so SRI hashes are not supported; required per class standard -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                fontFamily: {
                    sans: ["system-ui", "-apple-system", "Segoe UI", "Roboto", "Helvetica Neue", "Arial", "sans-serif"],
                },
            },
        };
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

<nav class="sticky top-0 z-40 bg-white border-b border-gray-200 print:hidden">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <!-- Logo -->
            <a href="<?= $base ?>index.php" class="flex items-center gap-2 shrink-0">
                <img src="<?= $base ?>assets/image-cutout.png" alt="AIS logo" class="w-9 h-9 object-contain">
                <span class="font-bold text-gray-800 hidden xl:block">Agriculture Information System</span>
                <span class="font-bold text-gray-800 xl:hidden">AIS</span>
            </a>

            <!-- Nav Tabs (centered, full-height underline style) -->
            <div class="hidden md:flex items-stretch justify-center flex-1 px-4 self-stretch">
                <?php foreach ($nav_links as $key => $link): ?>
                    <a href="<?= $base . $link['url'] ?>"
                       class="inline-flex items-center px-4 text-sm border-b-2 -mb-px <?= $active === $key
                           ? 'border-green-600 text-green-700 font-semibold'
                           : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' ?>">
                        <?= $link['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- User Info -->
            <div class="flex items-center gap-3 shrink-0">
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
        <div class="md:hidden flex overflow-x-auto -mb-px">
            <?php foreach ($nav_links as $key => $link): ?>
                <a href="<?= $base . $link['url'] ?>"
                   class="px-3 py-2 text-sm whitespace-nowrap border-b-2 <?= $active === $key
                       ? 'border-green-600 text-green-700 font-semibold'
                       : 'border-transparent text-gray-600 hover:text-gray-900' ?>">
                    <?= $link['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<main class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full flex-1">
