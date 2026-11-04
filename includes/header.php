<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base        = $base ?? "";
$active      = $active ?? "";

// Absolute app root for all SHELL links/fetches. The shell persists across
// PJAX navigations, so relative ($base) URLs would break once the current
// directory changes; absolute paths stay valid everywhere.
$app_root = ($base === ""
    ? rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/")
    : rtrim(str_replace("\\", "/", dirname(dirname($_SERVER["SCRIPT_NAME"]))), "/")) . "/";
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
    <title><?= htmlspecialchars($page_title ?? "AIS") ?> | Agriculture Information System</title>
    <!-- Tab Favicon -->
    <link rel="icon" type="image/png" href="<?= $app_root ?>assets/tab-icon.png">
    <link rel="shortcut icon" href="<?= $app_root ?>favicon.ico">
    <link rel="apple-touch-icon" href="<?= $app_root ?>assets/tab-icon.png">

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
        // Entrance animations only on the session's first load
        if (sessionStorage.getItem("ais-visited")) {
            document.documentElement.classList.add("revisit");
        } else {
            sessionStorage.setItem("ais-visited", "1");
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

        /* Overlays fade in (sheet overlay uses opacity transitions instead) */
        #sidecard-overlay:not(.hidden) { animation: overlay-in .2s ease; }

        /* Mobile bottom sheets: springy slide + staggered item entrance */
        #records-sheet, #menu-sheet { transition: transform .3s cubic-bezier(.2, .9, .3, 1); }
        @keyframes sheet-item-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .sheet-open nav > a { animation: sheet-item-in .3s ease both; }
        .sheet-open nav > a:nth-child(1) { animation-delay: .03s; }
        .sheet-open nav > a:nth-child(2) { animation-delay: .06s; }
        .sheet-open nav > a:nth-child(3) { animation-delay: .09s; }
        .sheet-open nav > a:nth-child(4) { animation-delay: .12s; }
        .sheet-open nav > a:nth-child(5) { animation-delay: .15s; }
        .sheet-open nav > a:nth-child(6) { animation-delay: .18s; }

        /* Bottom nav: active indicator slides in, chip pops, tabs press down */
        @keyframes tab-indicator-in { from { transform: translateX(-50%) scaleX(0); } to { transform: translateX(-50%) scaleX(1); } }
        .tab-indicator { animation: tab-indicator-in .25s cubic-bezier(.2, .9, .3, 1); }
        @keyframes chip-pop { 0% { transform: scale(.7); } 60% { transform: scale(1.12); } 100% { transform: scale(1); } }
        .chip-pop { animation: chip-pop .35s cubic-bezier(.2, .9, .3, 1); }

        /* Gooey desktop sidebar: ONLY active when collapsed (hidden words mode) */
        #side-goo { display: none !important; }

        #app-sidebar.collapsed #side-goo {
            display: block !important;
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            filter: url(#side-goo-filter);
        }
        #app-sidebar.collapsed #side-goo .sblob {
            position: absolute;
            left: 50%;
            width: 2.5rem;
            height: 2.5rem;
            transform: translateX(-50%);
            border-radius: 9999px;
            background: #16a34a; /* vibrant green circular chip like mobile */
            opacity: 0;
            box-shadow: 0 4px 12px rgb(22 163 74 / 0.35);
        }
        #side-goo .sblob-a {
            transition: top .26s cubic-bezier(.3, 1.2, .4, 1), height .26s ease, opacity .2s ease;
        }
        #side-goo .sblob-b {
            transition: top .44s cubic-bezier(.3, 1.2, .4, 1), height .44s ease, opacity .2s ease;
        }
        #side-goo.no-anim .sblob { transition: none !important; }

        /* When collapsed, active icon turns white to stand out on the green bubble */
        #app-sidebar.collapsed a.side-active {
            color: #ffffff !important;
            background: transparent !important;
            position: relative;
            z-index: 2;
        }
        #app-sidebar.collapsed a.side-active svg {
            color: #ffffff !important;
        }

        /* When NOT collapsed (showing words), standard clean active pill background, no gooey */
        #app-sidebar:not(.collapsed) a.side-active {
            background-color: #f0fdf4 !important;
            color: #15803d !important;
            font-weight: 600 !important;
        }
        #app-sidebar:not(.collapsed) a.side-active svg {
            color: #16a34a !important;
        }

        /* Gooey bottom nav: bar + blob rendered on a goo-filtered layer so the
           bump under the active tab melts out of the bar; icons sit above. */
        .goo-layer { filter: url(#goo-filter) drop-shadow(0 -4px 10px rgb(0 0 0 / .08)); }
        @keyframes blob-rise { from { transform: translateY(2.5rem); } to { transform: translateY(0); } }
        #goo-blob {
            transition: left .35s cubic-bezier(.3, 1.3, .4, 1);
            animation: blob-rise .45s cubic-bezier(.2, .9, .3, 1);
        }

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

        /* ---------- Collapsible desktop sidebar ---------- */
        #app-sidebar {
            transition: width .32s cubic-bezier(.4, 0, .2, 1);
            overflow: hidden;
        }
        #app-sidebar.collapsed { width: 3.75rem; }
        #app-sidebar.collapsed #sidebar-nav {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
        #app-sidebar.collapsed nav a {
            width: 2.5rem;
            height: 2.5rem;
            padding: 0;
            gap: 0;
            justify-content: center;
            border-radius: 9999px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            z-index: 2;
        }
        #app-sidebar.collapsed .sidebar-label {
            display: none !important;
            max-width: 0 !important;
            opacity: 0 !important;
            margin: 0 !important;
        }
        #app-sidebar.collapsed .sidebar-block-label {
            display: none !important;
            max-height: 0 !important;
            opacity: 0 !important;
            margin: 0 !important;
        }
        #app-sidebar.collapsed .space-y-5 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0.75rem !important;
        }

        /* Logo fully hides when collapsed — icon-only rail, toggle centers alone */
        #app-sidebar.collapsed .sidebar-logo-link {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
            overflow: hidden !important;
        }
        #app-sidebar.collapsed .sidebar-header-row {
            justify-content: center !important;
            padding-left: 0.25rem !important;
            padding-right: 0.25rem !important;
        }
        #app-sidebar.collapsed #sidebar-toggle {
            margin: 0 auto !important;
        }

        #app-sidebar.collapsed .side-user-panel-wrap {
            padding-left: 0.25rem !important;
            padding-right: 0.25rem !important;
        }
        #app-sidebar.collapsed .side-user-panel {
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
            justify-content: center;
        }
        #app-sidebar.collapsed .side-user-panel > div:first-child {
            margin-left: auto;
            margin-right: auto;
        }
        @media (prefers-reduced-motion: reduce) {
            #app-sidebar, .sidebar-label { transition: none; }
        }

        /* Clickable white tiles (KPIs, report cards, browse shortcuts) lift on hover */
        a.bg-white:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgb(0 0 0 / 0.06); }

        /* Slim custom scrollbars (WebKit) */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        /* Brand-colored text selection */
        ::selection { background: #bbf7d0; }

        /* Save/Update button spinner (same treatment as the login button) */
        @keyframes btn-spin { to { transform: rotate(360deg); } }
        .btn-spinner { animation: btn-spin .7s linear infinite; }
        @media (prefers-reduced-motion: reduce) {
            .btn-spinner { animation-duration: 1.4s; }
        }

        /* Cross-document View Transitions: full page loads cross-fade instead
           of white-flashing (supported browsers only; others just load). */
        @view-transition { navigation: auto; }
        ::view-transition-old(root), ::view-transition-new(root) { animation-duration: .22s; }

        /* Entrance animations only play on the first load of a session */
        .revisit .chip-pop, .revisit #goo-blob, .revisit .tab-indicator { animation: none; }

        /* Soft pastel "rainbow" glow over a near-white canvas (same as login).
           Fixed attachment keeps the glow steady while the page scrolls. */
        body {
            background:
                radial-gradient(42rem 42rem at 18% 22%, rgb(167 139 250 / .18), transparent 60%),
                radial-gradient(46rem 46rem at 82% 28%, rgb(134 239 172 / .22), transparent 60%),
                radial-gradient(40rem 40rem at 28% 85%, rgb(249 168 212 / .16), transparent 60%),
                radial-gradient(36rem 36rem at 86% 80%, rgb(252 211 77 / .14), transparent 60%),
                #fafafa;
            background-attachment: fixed;
        }
        @media print {
            body { background: #fff; }
        }

        @media (prefers-reduced-motion: reduce) {
            input, select, textarea, button, a { transition: none; }
            aside[id$="-card"].flex,
            #sidecard-overlay:not(.hidden),
            #sheet-overlay:not(.hidden),
            #deleteModal:not(.hidden) > .relative,
            #toast-stack > div,
            #records-panel.swapped,
            .sheet-open nav > a,
            .tab-indicator,
            .chip-pop,
            #goo-blob { animation: none; }
            #records-sheet, #menu-sheet, #sheet-overlay, #goo-blob, #side-goo .sblob { transition: none; }
        }
    </style>
</head>
<body class="text-gray-800 min-h-screen">

<div class="flex min-h-screen">

    <!-- Sidebar (desktop only — mobile uses the bottom navigation bar).
         Sticky + h-screen keeps the user panel (logout) always visible; nav scrolls inside. -->
    <aside id="app-sidebar"
           class="hidden lg:flex w-[16.5rem] shrink-0 flex-col bg-white border-r border-gray-200 lg:sticky lg:top-0 lg:h-screen print:hidden">
        <!-- Logo + collapse toggle -->
        <div class="sidebar-header-row flex items-center justify-between gap-2 h-14 px-3.5 border-b border-gray-200 shrink-0">
            <a href="<?= $app_root ?>index.php" class="sidebar-logo-link flex items-center gap-2.5 min-w-0 flex-1 overflow-hidden">
                <img src="<?= $app_root ?>assets/image-cutout.png" alt="AIS logo" class="w-8 h-8 object-contain shrink-0">
                <span class="sidebar-block-label min-w-0 flex-1">
                    <span class="block font-bold text-gray-800 text-sm leading-tight">AIS</span>
                    <span class="block text-[10.5px] font-normal text-gray-500 whitespace-nowrap leading-tight">Agriculture Information System</span>
                </span>
            </a>
            <!-- Standard "toggle sidebar" panel glyph (VS Code / Notion style) -->
            <button type="button" id="sidebar-toggle" title="Collapse sidebar" aria-label="Collapse sidebar"
                    class="shrink-0 text-gray-400 hover:text-gray-700 hover:bg-gray-50 rounded-lg p-1.5">
                <svg id="sidebar-icon-expanded" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                    <rect x="3" y="4" width="18" height="16" rx="2.5" />
                    <path d="M9.5 4v16" />
                    <path d="m14.5 10-2 2 2 2" />
                </svg>
                <svg id="sidebar-icon-collapsed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 hidden">
                    <rect x="3" y="4" width="18" height="16" rx="2.5" />
                    <path d="M9.5 4v16" />
                    <path d="m12.5 10 2 2-2 2" />
                </svg>
            </button>
        </div>

        <!-- Nav -->
        <nav id="sidebar-nav" class="relative flex-1 overflow-y-auto px-3 py-4 space-y-5">
            <?php foreach ($nav_sections as $section => $links): ?>
                <div>
                    <p class="sidebar-block-label px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400 whitespace-nowrap"><?= $section ?></p>
                    <?php foreach ($links as $key => $link): ?>
                        <a href="<?= $app_root . $link['url'] ?>" title="<?= htmlspecialchars($link['label']) ?>"
                           class="relative flex items-center gap-3 px-3 py-2 rounded-lg text-sm mb-0.5 <?= $active === $key
                               ? 'side-active text-green-700 font-semibold'
                               : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                 class="w-5 h-5 shrink-0 <?= $active === $key ? 'text-green-600' : 'text-gray-400' ?>">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $link['icon'] ?>" />
                            </svg>
                            <span class="sidebar-label whitespace-nowrap overflow-hidden"><?= $link['label'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <!-- User panel -->
        <div class="side-user-panel-wrap border-t border-gray-200 px-4 py-3 shrink-0">
            <div class="side-user-panel flex items-center gap-3">
                <div title="<?= htmlspecialchars(($_SESSION['full_name'] ?? '') . ' (' . ucfirst($_SESSION['role'] ?? '') . ')') ?>"
                     class="w-9 h-9 rounded-full bg-green-600 text-white flex items-center justify-center text-sm font-semibold shrink-0">
                    <?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1))) ?>
                </div>
                <div class="sidebar-block-label min-w-0 flex-1">
                    <p class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? '')) ?></p>
                </div>
                <a href="<?= $app_root ?>auth/profile.php" title="My profile" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </a>
                <a href="<?= $app_root ?>auth/logout.php" title="Logout" class="text-gray-400 hover:text-red-600">
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
                <a href="<?= $app_root ?>index.php" class="lg:hidden shrink-0">
                    <img src="<?= $app_root ?>assets/image-cutout.png" alt="AIS logo" class="w-8 h-8 object-contain">
                </a>

                <!-- Breadcrumbs -->
                <nav id="breadcrumbs" class="flex items-center gap-1.5 text-sm min-w-0" aria-label="Breadcrumb">
                    <!-- Mobile only: the desktop sidebar already has a Dashboard/home icon -->
                    <a href="<?= $app_root ?>index.php" class="lg:hidden text-gray-400 hover:text-gray-600 shrink-0" title="Dashboard">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                        </svg>
                    </a>
                    <?php if (count($breadcrumbs) === 0 && ($page_title ?? "") !== "" && $active !== "dashboard"): ?>
                        <?php $breadcrumbs = [[$page_title, null]]; ?>
                    <?php endif; ?>
                    <?php foreach ($breadcrumbs as $i => $crumb): ?>
                        <!-- First separator only shows on mobile, pairing with the home icon above -->
                        <span class="text-gray-300 shrink-0 <?= $i === 0 ? 'lg:hidden' : '' ?>">/</span>
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
                    <form method="GET" action="<?= $app_root ?>search.php" autocomplete="off">
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
                <a href="<?= $app_root ?>search.php" class="sm:hidden text-gray-500 hover:text-gray-800" title="Search">
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
                                fetch("<?= $app_root ?>search.php?ajax=1&q=" + encodeURIComponent(q))
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
$menu_active    = in_array($active, ["settings", "users", "activity", "profile"], true);

// Tab slot (0-4) for the gooey blob; -1 hides it
if ($active === "dashboard")    { $nav_index = 0; }
elseif ($records_active)        { $nav_index = 1; }
elseif ($active === "search")   { $nav_index = 2; }
elseif ($active === "reports")  { $nav_index = 3; }
elseif ($menu_active)           { $nav_index = 4; }
else                            { $nav_index = -1; }

$bottom_item_on  = "text-green-700";
$bottom_item_off = "text-gray-500";
?>
        <!-- Mobile sheet overlay: fades in AND out via opacity -->
        <div id="sheet-overlay"
             class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden print:hidden"
             onclick="closeSheets()"></div>

        <!-- Records bottom sheet (the burger for the 5 record modules) -->
        <div id="records-sheet"
             class="fixed inset-x-0 bottom-0 z-50 bg-white border-t border-gray-200 rounded-t-2xl shadow-lg
                    translate-y-full lg:hidden print:hidden">
            <div class="pt-2.5 flex justify-center"><span class="w-10 h-1 rounded-full bg-gray-300"></span></div>
            <div class="flex items-center justify-between px-5 pt-1.5 pb-2">
                <h2 class="font-semibold">Records</h2>
                <button type="button" onclick="closeSheets()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <!-- App-launcher grid: icon tiles, active module highlighted -->
            <nav class="grid grid-cols-3 gap-2 px-4 pb-7 pt-1">
                <?php foreach ($nav_sections["Records"] as $key => $link): ?>
                    <a href="<?= $app_root . $link['url'] ?>"
                       class="flex flex-col items-center gap-1.5 p-3 rounded-xl transition-transform active:scale-95 <?= $active === $key ? 'bg-green-50' : 'hover:bg-gray-50' ?>">
                        <span class="w-12 h-12 rounded-2xl flex items-center justify-center <?= $active === $key
                            ? 'bg-green-600 text-white shadow-md shadow-green-600/30'
                            : 'bg-green-50 text-green-600' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $link['icon'] ?>" />
                            </svg>
                        </span>
                        <span class="text-xs <?= $active === $key ? 'text-green-700 font-semibold' : 'text-gray-600' ?>"><?= $link['label'] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Menu bottom sheet (profile, admin tools, logout) -->
        <div id="menu-sheet"
             class="fixed inset-x-0 bottom-0 z-50 bg-white border-t border-gray-200 rounded-t-2xl shadow-lg
                    translate-y-full lg:hidden print:hidden">
            <div class="pt-2.5 flex justify-center"><span class="w-10 h-1 rounded-full bg-gray-300"></span></div>
            <div class="flex items-center justify-between px-5 pt-1.5 pb-2">
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
                <a href="<?= $app_root ?>auth/profile.php" class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    My Profile
                </a>
                <?php if ($is_admin): ?>
                    <?php foreach ($nav_sections["Administration"] as $key => $link): ?>
                        <a href="<?= $app_root . $link['url'] ?>"
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
                <a href="<?= $app_root ?>auth/logout.php" class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm text-red-600 hover:bg-red-50 border-t border-gray-100 mt-2 pt-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    Logout
                </a>
            </nav>
        </div>

        <!-- Goo filters (blurs + sharpens so shapes merge) -->
        <svg width="0" height="0" class="absolute pointer-events-none" aria-hidden="true">
            <defs>
                <filter id="goo-filter">
                    <feGaussianBlur in="SourceGraphic" stdDeviation="8" result="blur" />
                    <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -8" result="goo" />
                    <feComposite in="SourceGraphic" in2="goo" operator="atop" />
                </filter>
                <filter id="side-goo-filter">
                    <feGaussianBlur in="SourceGraphic" stdDeviation="6" result="blur" />
                    <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -8" result="goo" />
                    <feBlend in="SourceGraphic" in2="goo" />
                </filter>
            </defs>
        </svg>

        <!-- Mobile bottom navigation bar (gooey) -->
        <nav id="bottom-nav" class="fixed inset-x-0 bottom-0 z-30 lg:hidden print:hidden"
             style="padding-bottom: env(safe-area-inset-bottom)">
            <!-- Goo layer: the white bar + a blob under the active tab melt together -->
            <div class="goo-layer absolute inset-0 pointer-events-none" aria-hidden="true">
                <div class="absolute inset-0 bg-white"></div>
                <?php if ($nav_index >= 0): ?>
                    <div id="goo-blob" class="absolute w-[4.5rem] h-[4.5rem] rounded-full bg-white"
                         style="top: -1.3rem; left: calc(<?= $nav_index ?> * 20% + 10% - 2.25rem);"></div>
                <?php endif; ?>
            </div>
            <div class="relative grid grid-cols-5 h-16">
                <a href="<?= $app_root ?>index.php" data-goo="0" class="relative flex flex-col items-center justify-center gap-0.5 transition-transform active:scale-90 <?= $active === 'dashboard' ? $bottom_item_on : $bottom_item_off ?>">
                    <span class="<?= $active === 'dashboard'
                        ? 'w-12 h-12 -mt-7 rounded-full bg-green-600 text-white shadow-lg shadow-green-600/40 flex items-center justify-center chip-pop'
                        : 'px-4 py-0.5 flex items-center justify-center' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                        </svg>
                    </span>
                    <span class="text-[11px] <?= $active === 'dashboard' ? 'font-semibold' : '' ?>">Dashboard</span>
                </a>
                <button type="button" onclick="openSheet('records-sheet')" data-goo="1" class="relative flex flex-col items-center justify-center gap-0.5 transition-transform active:scale-90 <?= $records_active ? $bottom_item_on : $bottom_item_off ?>">
                    <span class="<?= $records_active
                        ? 'w-12 h-12 -mt-7 rounded-full bg-green-600 text-white shadow-lg shadow-green-600/40 flex items-center justify-center chip-pop'
                        : 'px-4 py-0.5 flex items-center justify-center' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </span>
                    <span class="text-[11px] <?= $records_active ? 'font-semibold' : '' ?>">Records</span>
                </button>
                <a href="<?= $app_root ?>search.php" data-goo="2" class="relative flex flex-col items-center justify-center gap-0.5 transition-transform active:scale-90 <?= $active === 'search' ? $bottom_item_on : $bottom_item_off ?>">
                    <span class="<?= $active === 'search'
                        ? 'w-12 h-12 -mt-7 rounded-full bg-green-600 text-white shadow-lg shadow-green-600/40 flex items-center justify-center chip-pop'
                        : 'px-4 py-0.5 flex items-center justify-center' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>
                    <span class="text-[11px] <?= $active === 'search' ? 'font-semibold' : '' ?>">Search</span>
                </a>
                <a href="<?= $app_root ?>reports/index.php" data-goo="3" class="relative flex flex-col items-center justify-center gap-0.5 transition-transform active:scale-90 <?= $active === 'reports' ? $bottom_item_on : $bottom_item_off ?>">
                    <span class="<?= $active === 'reports'
                        ? 'w-12 h-12 -mt-7 rounded-full bg-green-600 text-white shadow-lg shadow-green-600/40 flex items-center justify-center chip-pop'
                        : 'px-4 py-0.5 flex items-center justify-center' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </span>
                    <span class="text-[11px] <?= $active === 'reports' ? 'font-semibold' : '' ?>">Reports</span>
                </a>
                <button type="button" onclick="openSheet('menu-sheet')" data-goo="4" class="relative flex flex-col items-center justify-center gap-0.5 transition-transform active:scale-90 <?= $menu_active ? $bottom_item_on : $bottom_item_off ?>">
                    <span class="<?= $menu_active
                        ? 'w-12 h-12 -mt-7 rounded-full bg-green-600 text-white shadow-lg shadow-green-600/40 flex items-center justify-center chip-pop'
                        : 'px-4 py-0.5 flex items-center justify-center' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                    </span>
                    <span class="text-[11px] <?= $menu_active ? 'font-semibold' : '' ?>">Menu</span>
                </button>
            </div>
        </nav>

        <script>
            function openSheet(id) {
                closeSheets();
                var overlay = document.getElementById("sheet-overlay");
                overlay.classList.remove("opacity-0", "pointer-events-none");
                var sheet = document.getElementById(id);
                sheet.classList.remove("translate-y-full");
                sheet.classList.add("sheet-open"); // staggers the items in
                if (navigator.vibrate) { navigator.vibrate(8); } // subtle haptic tick
            }

            // Swipe-down to dismiss: the sheet follows the finger, snaps back
            // on a short drag, closes past the threshold.
            ["records-sheet", "menu-sheet"].forEach(function (id) {
                var sheet    = document.getElementById(id);
                var startY   = 0;
                var dragY    = 0;
                var dragging = false;

                sheet.addEventListener("touchstart", function (event) {
                    if (sheet.classList.contains("translate-y-full")) { return; }
                    startY   = event.touches[0].clientY;
                    dragY    = 0;
                    dragging = true;
                    sheet.style.transition = "none";
                }, { passive: true });

                sheet.addEventListener("touchmove", function (event) {
                    if (!dragging) { return; }
                    dragY = Math.max(0, event.touches[0].clientY - startY);
                    sheet.style.transform = "translateY(" + dragY + "px)";
                }, { passive: true });

                sheet.addEventListener("touchend", function () {
                    if (!dragging) { return; }
                    dragging = false;
                    sheet.style.transition = "";
                    sheet.style.transform  = "";
                    if (dragY > 90) {
                        closeSheets();
                        if (navigator.vibrate) { navigator.vibrate(6); }
                    }
                    dragY = 0;
                });
            });
            function closeSheets() {
                var overlay = document.getElementById("sheet-overlay");
                overlay.classList.add("opacity-0", "pointer-events-none");
                ["records-sheet", "menu-sheet"].forEach(function (id) {
                    var sheet = document.getElementById(id);
                    sheet.classList.add("translate-y-full");
                    sheet.classList.remove("sheet-open");
                });
            }
            document.addEventListener("keydown", function (event) {
                if (event.key === "Escape") { closeSheets(); }
            });

            // ---------- Collapsible sidebar (desktop) ----------
            // Restored before the user can perceive the default state (this
            // script runs once per real page load, right after the sidebar
            // markup is parsed); the class then persists across PJAX swaps
            // since the <aside> itself is never replaced, only #sidebar-nav.
            var sidebarEl = document.getElementById("app-sidebar");
            if (sidebarEl && localStorage.getItem("ais-sidebar-collapsed") === "1") {
                sidebarEl.classList.add("collapsed");
            }
            var toggleBtn = document.getElementById("sidebar-toggle");
            function syncSidebarToggleIcon(collapsed) {
                if (!toggleBtn) { return; }
                var expandedIcon  = document.getElementById("sidebar-icon-expanded");
                var collapsedIcon = document.getElementById("sidebar-icon-collapsed");
                if (expandedIcon)  { expandedIcon.classList.toggle("hidden", collapsed); }
                if (collapsedIcon) { collapsedIcon.classList.toggle("hidden", !collapsed); }
                toggleBtn.title = collapsed ? "Expand sidebar" : "Collapse sidebar";
                toggleBtn.setAttribute("aria-label", toggleBtn.title);
            }
            if (sidebarEl && toggleBtn) {
                syncSidebarToggleIcon(sidebarEl.classList.contains("collapsed"));
            }
            // ---------- Gooey sidebar indicator (desktop) ----------
            function hideSideGoo() {
                var layer = document.getElementById("side-goo");
                if (!layer) { return; }
                var blobs = layer.querySelectorAll(".sblob");
                blobs.forEach(function (blob) { blob.style.opacity = "0"; });
            }

            function moveSideGoo(link, instant) {
                var sidebar = document.getElementById("app-sidebar");
                // ONLY takes effect in hidden words mode (collapsed)
                if (!sidebar || !sidebar.classList.contains("collapsed")) {
                    hideSideGoo();
                    return;
                }
                var nav = document.getElementById("sidebar-nav");
                var layer = document.getElementById("side-goo");
                if (!nav || !layer) { return; }
                if (!link) {
                    hideSideGoo();
                    return;
                }
                var blobs = layer.querySelectorAll(".sblob");
                var navRect  = nav.getBoundingClientRect();
                var linkRect = link.getBoundingClientRect();
                var top = (linkRect.top - navRect.top) + nav.scrollTop;
                var height = linkRect.height || 40;

                if (instant) { layer.classList.add("no-anim"); }
                blobs.forEach(function (blob) {
                    blob.style.opacity = "1";
                    blob.style.top     = top + "px";
                    blob.style.height  = height + "px";
                });
                if (instant) {
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () { layer.classList.remove("no-anim"); });
                    });
                }
            }

            function initSideGoo() {
                var nav = document.getElementById("sidebar-nav");
                if (!nav) { return; }
                var layer = document.getElementById("side-goo");
                if (!layer || layer.parentNode !== nav) {
                    layer = document.createElement("div");
                    layer.id = "side-goo";
                    layer.innerHTML = '<div class="sblob sblob-b"></div><div class="sblob sblob-a"></div>';
                    nav.insertBefore(layer, nav.firstChild);
                }
                var sidebar = document.getElementById("app-sidebar");
                if (sidebar && sidebar.classList.contains("collapsed")) {
                    moveSideGoo(nav.querySelector(".side-active"), true);
                } else {
                    hideSideGoo();
                }
            }
            initSideGoo();

            if (sidebarEl && toggleBtn && !toggleBtn.dataset.bound) {
                toggleBtn.dataset.bound = "1";
                toggleBtn.addEventListener("click", function () {
                    var collapsed = sidebarEl.classList.toggle("collapsed");
                    localStorage.setItem("ais-sidebar-collapsed", collapsed ? "1" : "0");
                    syncSidebarToggleIcon(collapsed);
                    var activeLink = sidebarEl.querySelector(".side-active");
                    if (collapsed) {
                        // Collapsed (hidden words mode) -> activate gooey indicator
                        moveSideGoo(activeLink, true);
                        setTimeout(function () { moveSideGoo(activeLink, true); }, 60);
                        setTimeout(function () { moveSideGoo(activeLink, true); }, 330);
                    } else {
                        // Expanded (showing words mode) -> hide gooey effect completely
                        hideSideGoo();
                    }
                });
            }

            // Gooey stretch on click ONLY when in collapsed mode
            document.addEventListener("click", function (event) {
                var link = event.target.closest("#sidebar-nav a");
                if (link) {
                    var sidebar = document.getElementById("app-sidebar");
                    if (sidebar && sidebar.classList.contains("collapsed")) {
                        moveSideGoo(link, false);
                    }
                }
            });

            // Keep in sync during scroll & PJAX swaps
            var sideNav = document.getElementById("sidebar-nav");
            if (sideNav) {
                sideNav.addEventListener("scroll", function () {
                    var sidebar = document.getElementById("app-sidebar");
                    if (sidebar && sidebar.classList.contains("collapsed")) {
                        var active = sideNav.querySelector(".side-active");
                        if (active) { moveSideGoo(active, true); }
                    }
                }, { passive: true });

                new MutationObserver(function () {
                    if (!document.getElementById("side-goo")) { initSideGoo(); }
                    else {
                        var sidebar = document.getElementById("app-sidebar");
                        if (sidebar && sidebar.classList.contains("collapsed")) {
                            moveSideGoo(sideNav.querySelector(".side-active"), true);
                        } else {
                            hideSideGoo();
                        }
                    }
                }).observe(sideNav, { childList: true });
            }

            // Gooey blob stretches toward the tapped tab (delegated so it
            // survives PJAX swaps of the bottom nav)
            document.addEventListener("click", function (event) {
                var tab = event.target.closest("[data-goo]");
                if (!tab) { return; }
                var blob = document.getElementById("goo-blob");
                if (blob) {
                    blob.style.left = "calc(" + tab.dataset.goo + " * 20% + 10% - 2.25rem)";
                }
            });
        </script>

<main class="flex-1 px-4 sm:px-6 py-6 pb-24 lg:pb-6 w-full max-w-screen-2xl mx-auto">
