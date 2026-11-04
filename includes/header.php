<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base        = $base ?? "";
$active      = $active ?? "";
$breadcrumbs = $breadcrumbs ?? [];
$is_admin    = ($_SESSION['role'] ?? '') === 'admin';

// Sidebar navigation, grouped into sections. Icons are Heroicons outline paths.
$nav_sections = [
    "Overview" => [
        "dashboard" => ["label" => "Dashboard", "url" => "index.php",
            "icon" => "m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"],
    ],
    "Records" => [
        "farmers" => ["label" => "Farmers", "url" => "farmers/index.php",
            "icon" => "M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"],
        "farms" => ["label" => "Farms", "url" => "farms/index.php",
            "icon" => "M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819"],
        "crops" => ["label" => "Crops", "url" => "crops/index.php",
            "icon" => "M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z"],
        "plantings" => ["label" => "Plantings", "url" => "plantings/index.php",
            "icon" => "M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"],
        "harvests" => ["label" => "Harvests", "url" => "harvests/index.php",
            "icon" => "m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"],
    ],
    "Insights" => [
        "reports" => ["label" => "Reports", "url" => "reports/index.php",
            "icon" => "M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"],
    ],
];

// Administration section is visible to admins only (also guarded server-side per page)
if ($is_admin) {
    $nav_sections["Administration"] = [
        "users" => ["label" => "Users", "url" => "users/index.php",
            "icon" => "M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"],
        "activity" => ["label" => "Activity Log", "url" => "users/activity.php",
            "icon" => "M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"],
        "settings" => ["label" => "Settings", "url" => "settings/index.php",
            "icon" => "M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"],
    ];
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

    <!-- NProgress: slim top loading bar for navigations and background fetches -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css"
          integrity="sha384-QUGa0w7S89w/V+840rnBtrQYyUEd1VHb88E0/8vc9FvgRy7b7wHlk8S+o0bNJcv0" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"
            integrity="sha384-WVrcwN/kiINFnwTi170GvMPVLHVBao1WfcXL/BZAK3VaUaaWX0OOsxMgCiFmrIb1" crossorigin="anonymous"></script>
    <style>
        #nprogress .bar {
            background: #22a447 !important;
            height: 3px !important;
        }
        #nprogress .peg { box-shadow: 0 0 10px #22a447, 0 0 5px #22a447 !important; }
    </style>
    <script>
        if (window.NProgress) {
            NProgress.configure({ showSpinner: false, trickleSpeed: 120 });
            // Full page navigations (nav links, form posts, logout, ...)
            window.addEventListener("beforeunload", function () { NProgress.start(); });
        }
    </script>
    <script>
        tailwind.config = {
            theme: {
                fontFamily: {
                    sans: ["system-ui", "-apple-system", "Segoe UI", "Roboto", "Helvetica Neue", "Arial", "sans-serif"],
                },
            },
        };
    </script>
    <style>
        /* ---------- Upgraded form controls (replace the default browser look) ---------- */

        /* Selects: no native arrow — custom chevron, consistent everywhere */
        select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='m19.5 8.25-7.5 7.5-7.5-7.5'/></svg>");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            background-size: 0.95rem 0.95rem;
            padding-right: 2.3rem !important;
            cursor: pointer;
        }
        select:focus {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2316a34a' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='m19.5 8.25-7.5 7.5-7.5-7.5'/></svg>");
        }

        /* Inputs & selects: smooth focus/hover transitions, gentle hover border */
        input, select, textarea {
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        input:hover:not(:disabled):not(:focus),
        select:hover:not(:disabled):not(:focus) {
            border-color: #9ca3af;
        }

        /* Number fields: no default spinner buttons */
        input[type="number"] { -moz-appearance: textfield; }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        /* Date fields: softer picker icon */
        input[type="date"]::-webkit-calendar-picker-indicator { opacity: .55; cursor: pointer; }
        input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; }

        /* Buttons: subtle press feedback everywhere */
        button, a { transition: background-color .15s ease, color .15s ease, border-color .15s ease, transform .12s ease, box-shadow .15s ease; }
        button:active:not(:disabled) { transform: scale(.98); }

        /* ---------- Interaction feedback ---------- */

        @keyframes card-in-up    { from { transform: translateY(100%); } to { transform: translateY(0); } }
        @keyframes card-in-right { from { transform: translateX(100%); opacity: .6; } to { transform: translateX(0); opacity: 1; } }
        @keyframes overlay-in    { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pop-in        { from { opacity: 0; transform: scale(.95); } to { opacity: 1; transform: scale(1); } }
        @keyframes toast-in      { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes rows-in       { from { opacity: .35; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

        /* Side cards slide in: from the bottom on mobile, from the right on md+ */
        aside[id$="-card"].flex { animation: card-in-up .28s cubic-bezier(.2, .8, .3, 1); }
        @media (min-width: 768px) {
            aside[id$="-card"].flex { animation: card-in-right .25s cubic-bezier(.2, .8, .3, 1); }
        }

        /* Overlays fade in */
        #sidecard-overlay:not(.hidden),
        #sheet-overlay:not(.hidden) { animation: overlay-in .2s ease; }

        /* Delete confirmation pops in */
        #deleteModal:not(.hidden) > .relative { animation: pop-in .2s ease; }

        /* Toasts drop in from the top */
        #toast-stack > div { animation: toast-in .25s ease; }

        /* Records panel: dim while fetching, settle in after a sort/filter/save */
        #records-panel.loading { opacity: .45; pointer-events: none; transition: opacity .15s ease; }
        #records-panel.swapped { animation: rows-in .25s ease; }

        /* Table rows ease into their hover tint */
        tbody tr { transition: background-color .15s ease; }

        /* Sidebar nav nudges on hover */
        #app-sidebar nav a:hover { transform: translateX(2px); }

        /* Clickable white tiles (KPIs, report cards, browse shortcuts) lift on hover */
        a.bg-white:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgb(0 0 0 / 0.06); }

        /* Slim custom scrollbars (WebKit) */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        /* Brand-colored text selection */
        ::selection { background: #bbf7d0; }

        @media (prefers-reduced-motion: reduce) {
            input, select, textarea, button, a { transition: none; }
            aside[id$="-card"].flex,
            #sidecard-overlay:not(.hidden),
            #sheet-overlay:not(.hidden),
            #deleteModal:not(.hidden) > .relative,
            #toast-stack > div,
            #records-panel.swapped { animation: none; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

<div class="flex min-h-screen">

    <!-- Sidebar (desktop only — mobile uses the bottom navigation bar).
         Sticky + h-screen keeps the user panel (logout) always visible; nav scrolls inside. -->
    <aside id="app-sidebar"
           class="hidden lg:flex w-60 shrink-0 flex-col bg-white border-r border-gray-200 lg:sticky lg:top-0 lg:h-screen print:hidden">
        <!-- Logo -->
        <a href="<?= $base ?>index.php" class="flex items-center gap-2.5 h-14 px-4 border-b border-gray-200 shrink-0">
            <img src="<?= $base ?>assets/image-cutout.png" alt="AIS logo" class="w-8 h-8 object-contain">
            <span class="font-bold text-gray-800 leading-tight">AIS<span class="block text-[11px] font-normal text-gray-500">Agriculture Information System</span></span>
        </a>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5">
            <?php foreach ($nav_sections as $section => $links): ?>
                <div>
                    <p class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400"><?= $section ?></p>
                    <?php foreach ($links as $key => $link): ?>
                        <a href="<?= $base . $link['url'] ?>"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm mb-0.5 <?= $active === $key
                               ? 'bg-green-50 text-green-700 font-semibold'
                               : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                 class="w-5 h-5 shrink-0 <?= $active === $key ? 'text-green-600' : 'text-gray-400' ?>">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $link['icon'] ?>" />
                            </svg>
                            <?= $link['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <!-- User panel -->
        <div class="border-t border-gray-200 px-4 py-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-green-600 text-white flex items-center justify-center text-sm font-semibold shrink-0">
                    <?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1))) ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? '')) ?></p>
                </div>
                <a href="<?= $base ?>auth/profile.php" title="My profile" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </a>
                <a href="<?= $base ?>auth/logout.php" title="Logout" class="text-gray-400 hover:text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Content column -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top bar -->
        <header class="sticky top-0 z-30 bg-white border-b border-gray-200 print:hidden">
            <div class="flex items-center gap-3 h-14 px-4 sm:px-6">
                <!-- Logo (mobile only — the sidebar carries it on desktop) -->
                <a href="<?= $base ?>index.php" class="lg:hidden shrink-0">
                    <img src="<?= $base ?>assets/image-cutout.png" alt="AIS logo" class="w-8 h-8 object-contain">
                </a>

                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-1.5 text-sm min-w-0" aria-label="Breadcrumb">
                    <a href="<?= $base ?>index.php" class="text-gray-400 hover:text-gray-600 shrink-0" title="Dashboard">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                        </svg>
                    </a>
                    <?php if (count($breadcrumbs) === 0 && ($page_title ?? "") !== "" && $active !== "dashboard"): ?>
                        <?php $breadcrumbs = [[$page_title, null]]; ?>
                    <?php endif; ?>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <span class="text-gray-300 shrink-0">/</span>
                        <?php if (!empty($crumb[1])): ?>
                            <a href="<?= $crumb[1] ?>" class="text-gray-500 hover:text-gray-800 truncate"><?= htmlspecialchars($crumb[0]) ?></a>
                        <?php else: ?>
                            <span class="text-gray-800 font-medium truncate"><?= htmlspecialchars($crumb[0]) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>

                <div class="flex-1"></div>

                <!-- Global live search (results drop down as you type) -->
                <div id="global-search-wrap" class="hidden sm:block w-56 md:w-72 relative">
                    <form method="GET" action="<?= $base ?>search.php" autocomplete="off">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                             class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="text" name="q" id="global-search" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Search farmers, farms, crops..."
                               class="w-full pl-9 pr-3 py-1.5 text-sm bg-gray-50 border border-gray-200 rounded-full
                                      focus:bg-white focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    </form>
                    <div id="global-search-results"
                         class="hidden absolute right-0 top-full mt-1.5 w-80 max-w-[calc(100vw-2rem)] max-h-96 overflow-y-auto
                                bg-white border border-gray-200 rounded-lg shadow-lg z-50 divide-y divide-gray-50 pb-1"></div>
                </div>
                <a href="<?= $base ?>search.php" class="sm:hidden text-gray-500 hover:text-gray-800" title="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </a>

                <script>
                    // Topbar live search: debounced fetch of the shared results fragment
                    (function () {
                        var input = document.getElementById("global-search");
                        var panel = document.getElementById("global-search-results");
                        var timer = null;
                        input.addEventListener("input", function () {
                            clearTimeout(timer);
                            var q = input.value.trim();
                            if (q.length < 2) {
                                panel.classList.add("hidden");
                                panel.innerHTML = "";
                                return;
                            }
                            timer = setTimeout(function () {
                                fetch("<?= $base ?>search.php?ajax=1&q=" + encodeURIComponent(q))
                                    .then(function (response) { return response.text(); })
                                    .then(function (html) {
                                        panel.innerHTML = html;
                                        panel.classList.remove("hidden");
                                    });
                            }, 250);
                        });
                        input.addEventListener("keydown", function (event) {
                            if (event.key === "Escape") { panel.classList.add("hidden"); }
                        });
                        document.addEventListener("click", function (event) {
                            if (!event.target.closest("#global-search-wrap")) { panel.classList.add("hidden"); }
                        });
                    })();
                </script>
            </div>
        </header>

<?php
// ---------- Mobile bottom navigation (hidden on lg+, where the sidebar takes over) ----------
$record_keys    = ["farmers", "farms", "crops", "plantings", "harvests"];
$records_active = in_array($active, $record_keys, true);
$menu_active    = in_array($active, ["settings", "users", "activity"], true);

$bottom_item_on  = "text-green-700";
$bottom_item_off = "text-gray-500";
?>
        <!-- Mobile sheet overlay -->
        <div id="sheet-overlay" class="fixed inset-0 bg-black/30 z-40 hidden lg:hidden print:hidden" onclick="closeSheets()"></div>

        <!-- Records bottom sheet (the burger for the 5 record modules) -->
        <div id="records-sheet"
             class="fixed inset-x-0 bottom-0 z-50 bg-white border-t border-gray-200 rounded-t-2xl shadow-lg
                    translate-y-full transition-transform duration-200 lg:hidden print:hidden">
            <div class="flex items-center justify-between px-5 pt-4 pb-2">
                <h2 class="font-semibold">Records</h2>
                <button type="button" onclick="closeSheets()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="px-3 pb-6">
                <?php foreach ($nav_sections["Records"] as $key => $link): ?>
                    <a href="<?= $base . $link['url'] ?>"
                       class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm <?= $active === $key
                           ? 'bg-green-50 text-green-700 font-semibold'
                           : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                             class="w-5 h-5 shrink-0 <?= $active === $key ? 'text-green-600' : 'text-gray-400' ?>">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $link['icon'] ?>" />
                        </svg>
                        <?= $link['label'] ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Menu bottom sheet (profile, admin tools, logout) -->
        <div id="menu-sheet"
             class="fixed inset-x-0 bottom-0 z-50 bg-white border-t border-gray-200 rounded-t-2xl shadow-lg
                    translate-y-full transition-transform duration-200 lg:hidden print:hidden">
            <div class="flex items-center justify-between px-5 pt-4 pb-2">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-green-600 text-white flex items-center justify-center text-sm font-semibold shrink-0">
                        <?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1))) ?>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? '')) ?></p>
                    </div>
                </div>
                <button type="button" onclick="closeSheets()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="px-3 pb-6">
                <a href="<?= $base ?>auth/profile.php" class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    My Profile
                </a>
                <?php if ($is_admin): ?>
                    <?php foreach ($nav_sections["Administration"] as $key => $link): ?>
                        <a href="<?= $base . $link['url'] ?>"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm <?= $active === $key
                               ? 'bg-green-50 text-green-700 font-semibold'
                               : 'text-gray-700 hover:bg-gray-50' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                 class="w-5 h-5 shrink-0 <?= $active === $key ? 'text-green-600' : 'text-gray-400' ?>">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $link['icon'] ?>" />
                            </svg>
                            <?= $link['label'] ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?= $base ?>auth/logout.php" class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm text-red-600 hover:bg-red-50 border-t border-gray-100 mt-2 pt-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    Logout
                </a>
            </nav>
        </div>

        <!-- Mobile bottom navigation bar -->
        <nav class="fixed inset-x-0 bottom-0 z-30 bg-white border-t border-gray-200 lg:hidden print:hidden"
             style="padding-bottom: env(safe-area-inset-bottom)">
            <div class="grid grid-cols-5 h-16">
                <a href="<?= $base ?>index.php" class="flex flex-col items-center justify-center gap-0.5 <?= $active === 'dashboard' ? $bottom_item_on : $bottom_item_off ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                    </svg>
                    <span class="text-[11px] <?= $active === 'dashboard' ? 'font-semibold' : '' ?>">Dashboard</span>
                </a>
                <button type="button" onclick="openSheet('records-sheet')" class="flex flex-col items-center justify-center gap-0.5 <?= $records_active ? $bottom_item_on : $bottom_item_off ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <span class="text-[11px] <?= $records_active ? 'font-semibold' : '' ?>">Records</span>
                </button>
                <a href="<?= $base ?>search.php" class="flex flex-col items-center justify-center gap-0.5 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <span class="text-[11px]">Search</span>
                </a>
                <a href="<?= $base ?>reports/index.php" class="flex flex-col items-center justify-center gap-0.5 <?= $active === 'reports' ? $bottom_item_on : $bottom_item_off ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    <span class="text-[11px] <?= $active === 'reports' ? 'font-semibold' : '' ?>">Reports</span>
                </a>
                <button type="button" onclick="openSheet('menu-sheet')" class="flex flex-col items-center justify-center gap-0.5 <?= $menu_active ? $bottom_item_on : $bottom_item_off ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    <span class="text-[11px] <?= $menu_active ? 'font-semibold' : '' ?>">Menu</span>
                </button>
            </div>
        </nav>

        <script>
            function openSheet(id) {
                closeSheets();
                document.getElementById("sheet-overlay").classList.remove("hidden");
                document.getElementById(id).classList.remove("translate-y-full");
            }
            function closeSheets() {
                document.getElementById("sheet-overlay").classList.add("hidden");
                ["records-sheet", "menu-sheet"].forEach(function (id) {
                    document.getElementById(id).classList.add("translate-y-full");
                });
            }
            document.addEventListener("keydown", function (event) {
                if (event.key === "Escape") { closeSheets(); }
            });
        </script>

<main class="flex-1 px-4 sm:px-6 py-6 pb-24 lg:pb-6 w-full max-w-screen-2xl mx-auto">
