<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/table_helpers.php";
require_once "../config/Database.php";
require_once "../models/Farmer.php";

$pdo         = (new Database())->connect();
$farmerModel = new Farmer($pdo);

// Sort whitelist: URL key => SQL column (identifiers never come from the URL)
$sort_whitelist = [
    "id"         => "farmer_id",
    "name"       => "last_name",
    "contact"    => "contact_number",
    "address"    => "address",
    "registered" => "date_registered",
];
[$order, $dir, $sort_key] = resolveSort($sort_whitelist, "id");

// Record being edited via the Edit slider (?edit=<id> prefills and auto-opens it)
$edit_id     = (int) ($_GET["edit"] ?? 0);
$edit_record = $edit_id > 0 ? $farmerModel->getById($edit_id) : null;

$search     = (string) trim($_GET["search"] ?? "");
$page       = currentPage();
$total_rows = $farmerModel->countAll($search);
$farmers    = $farmerModel->getPage($search, $order, $dir, PER_PAGE, pageOffset($page));

// TRUE when the empty result comes from an active search, not an empty table
$is_filtered = $search !== "";

$page_title  = "Farmers";
$active      = "farmers";
$breadcrumbs = [["Farmers", null]];
require "../includes/header.php";
?>

<!-- LAYER 1: Top Hero Header with Title, Count, Add Farmer Button & Foliage Illustration -->
<div class="relative mb-5 min-h-[9.25rem] sm:min-h-[11.25rem] print:hidden">
    <!-- Top-right foliage & hills illustration matching reference design -->
    <div class="absolute -top-3 right-0 w-48 sm:w-64 h-36 sm:h-44 pointer-events-none select-none overflow-hidden z-0">
        <svg viewBox="0 0 220 170" class="w-full h-full object-contain object-right-bottom" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Soft ambient mounds / hills -->
            <path d="M100 170 C100 115 145 85 195 85 C215 85 230 92 240 100 L240 170 Z" fill="#edf6ef" />
            <path d="M120 170 C120 120 160 95 205 95 C220 95 235 100 240 105 L240 170 Z" fill="#e0eee2" />
            <!-- Small side leaf tip at right -->
            <path d="M210 115 C215 105 220 100 225 98 C225 108 220 118 215 125 Z" fill="#5a9661" opacity="0.8"/>
            <!-- Back leaf (olive green) -->
            <path d="M140 145 C125 110 140 75 168 65 C180 95 165 130 140 145 Z" fill="#8db98e" />
            <path d="M140 145 C152 118 160 92 168 65" stroke="#72a373" stroke-width="2.5" stroke-linecap="round"/>
            <!-- Front leaf (rich forest green) -->
            <path d="M162 150 C162 112 188 88 212 82 C218 118 198 145 162 150 Z" fill="#4d8b54" />
            <path d="M162 150 C178 128 195 105 212 82" stroke="#366b3c" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
    </div>

    <div class="relative z-10 pt-1 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#111827] tracking-tight">Farmers</h1>
            <p id="records-count" class="text-xs sm:text-sm text-gray-500 font-normal mt-0.5 sm:mt-1"><?= number_format($total_rows) ?> registered farmer record(s)</p>
        </div>
        <div class="shrink-0">
            <button type="button" onclick="openAddCard()"
                    class="bg-[#2e7d32] hover:bg-[#256829] active:scale-95 text-white font-medium px-4 sm:px-5 py-2 sm:py-2.5 rounded-full inline-flex items-center gap-1.5 sm:gap-2 shadow-sm text-xs sm:text-sm transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Add Farmer</span>
            </button>
        </div>
    </div>
</div>

<?php require "../includes/alert.php"; ?>

<!-- LAYER 2: Middle warm-toned rounded container holding search, sort, and inner white card -->
<div class="bg-[#f6f4ec] rounded-[2.25rem] sm:rounded-3xl p-3.5 sm:p-5 border border-[#ede9de] shadow-[0_4px_20px_rgba(0,0,0,0.03)] mb-6">

    <!-- Filter toolbar: search + sort inline above the table -->
    <form method="GET" id="filter-toolbar" class="mb-3.5 print:hidden">
        <input type="hidden" name="sort" value="<?= htmlspecialchars(strtolower($sort_key)) ?>">
        <input type="hidden" name="dir" value="<?= strtolower($dir) ?>">

        <div class="space-y-2.5 md:space-y-0 md:flex md:flex-wrap md:items-center md:gap-3">
            <!-- Search input pill (pure white floating on Layer 2) -->
            <div class="relative w-full md:w-72">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"
                     class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or address..."
                       class="w-full bg-white border border-gray-200/70 rounded-full shadow-sm pl-11 pr-10 py-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent transition-all">
                <?php if ($search !== ""): ?>
                    <button type="button" onclick="this.form.search.value = ''; submitToolbar(this.form);" aria-label="Clear search"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Sort dropdown pill (pure white floating on Layer 2) -->
            <div class="grid grid-cols-1 gap-2.5 md:flex md:gap-3 flex-1">
                <select aria-label="Sort by"
                        onchange="var p = this.value.split(':'); this.form.sort.value = p[0]; this.form.dir.value = p[1]; submitToolbar(this.form);"
                        class="w-full md:w-auto bg-white border border-gray-200/70 rounded-full shadow-sm py-2.5 pl-4 pr-9 text-xs sm:text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent transition-all truncate">
                    <option value="id:asc" <?= $sort_key === 'id' && $dir === 'ASC' ? 'selected' : '' ?>>Oldest</option>
                    <option value="id:desc" <?= $sort_key === 'id' && $dir === 'DESC' ? 'selected' : '' ?>>Newest</option>
                    <option value="name:asc" <?= $sort_key === 'name' && $dir === 'ASC' ? 'selected' : '' ?>>Name A&ndash;Z</option>
                    <option value="name:desc" <?= $sort_key === 'name' && $dir === 'DESC' ? 'selected' : '' ?>>Name Z&ndash;A</option>
                    <option value="registered:desc" <?= $sort_key === 'registered' && $dir === 'DESC' ? 'selected' : '' ?>>Recently registered</option>
                </select>
            </div>
        </div>
    </form>

    <!-- LAYER 3: Inner pure white card holding the farmer records -->
    <div id="records-panel" class="bg-white rounded-[1.75rem] border border-gray-100 shadow-sm overflow-hidden">
    <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
            <tr>
                <?php sortableTh("id", "ID", $sort_key, $dir); ?>
                <?php sortableTh("name", "Full Name", $sort_key, $dir); ?>
                <?php sortableTh("contact", "Contact", $sort_key, $dir); ?>
                <?php sortableTh("address", "Address", $sort_key, $dir); ?>
                <?php sortableTh("registered", "Registered", $sort_key, $dir); ?>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($farmers) === 0): ?>
                <tr>
                    <td colspan="6" class="px-4 py-10">
                        <div class="border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center text-sm text-gray-500">
                            <?php if ($is_filtered): ?>
                                No farmers match your search.
                                <a href="index.php" class="text-green-700 font-medium hover:underline">Clear search</a>
                            <?php else: ?>
                                No farmers yet.
                                <button type="button" onclick="openAddCard()" class="text-green-700 font-medium hover:underline">Add the first farmer</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($farmers as $row): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                    onclick="openDetailCard(this)"
                    data-title="Farmer #<?= $row['farmer_id'] ?>"
                    data-edit-url="index.php?edit=<?= $row['farmer_id'] ?>"
                    data-delete-url="delete.php?id=<?= $row['farmer_id'] ?>"
                    data-delete-msg="Are you sure you want to delete farmer <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) ?>?"
                    data-edit="<?= htmlspecialchars(json_encode([
                        "id"             => $row["farmer_id"],
                        "first_name"     => $row["first_name"],
                        "last_name"      => $row["last_name"],
                        "contact_number" => $row["contact_number"],
                        "address"        => $row["address"],
                    ]), ENT_QUOTES) ?>"
                    data-detail="<?= htmlspecialchars(json_encode([
                        "ID"              => $row["farmer_id"],
                        "First Name"      => $row["first_name"],
                        "Last Name"       => $row["last_name"],
                        "Contact Number"  => $row["contact_number"],
                        "Address"         => $row["address"],
                        "Date Registered" => $row["date_registered"],
                    ]), ENT_QUOTES) ?>">
                    <td class="px-4 py-3"><?= $row['farmer_id'] ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['contact_number']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['address']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['date_registered']) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-2" onclick="event.stopPropagation()">
                            <a href="index.php?edit=<?= $row['farmer_id'] ?>" title="Edit"
                               onclick="event.stopPropagation(); if (fillAndOpenEditCard(this.closest('[data-edit]'))) return false;"
                               class="text-blue-600 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="#" title="Delete"
                                   onclick="openDeleteModal('delete.php?id=<?= $row['farmer_id'] ?>', 'Are you sure you want to delete farmer <?= htmlspecialchars(addslashes($row['first_name'] . ' ' . $row['last_name']), ENT_QUOTES) ?>?'); return false;"
                                   class="text-red-600 hover:text-red-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Mobile card list (matching reference design: 48px circle icon, bold title, dual-line metadata, chevron) -->
<div class="md:hidden divide-y divide-gray-100/80">
    <?php if (count($farmers) === 0): ?>
        <div class="p-5">
            <div class="border border-dashed border-gray-300 rounded-2xl px-4 py-8 text-center text-sm text-gray-500">
                <?php if ($is_filtered): ?>
                    No farmers match your search.
                    <a href="index.php" class="text-green-700 font-medium hover:underline block mt-1">Clear search</a>
                <?php else: ?>
                    No farmers yet.
                    <button type="button" onclick="openAddCard()" class="text-green-700 font-medium hover:underline block mt-1">Add the first farmer</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php foreach ($farmers as $row): ?>
        <div class="flex items-center gap-3.5 px-4 py-3.5 hover:bg-gray-50/60 active:bg-gray-50/90 transition-colors cursor-pointer"
             onclick="openDetailCard(this)"
             data-title="Farmer #<?= $row['farmer_id'] ?>"
             data-edit-url="index.php?edit=<?= $row['farmer_id'] ?>"
             data-delete-url="delete.php?id=<?= $row['farmer_id'] ?>"
             data-delete-msg="Are you sure you want to delete farmer <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) ?>?"
                    data-edit="<?= htmlspecialchars(json_encode([
                        "id"             => $row["farmer_id"],
                        "first_name"     => $row["first_name"],
                        "last_name"      => $row["last_name"],
                        "contact_number" => $row["contact_number"],
                        "address"        => $row["address"],
                    ]), ENT_QUOTES) ?>"
             data-detail="<?= htmlspecialchars(json_encode([
                 "ID"              => $row["farmer_id"],
                 "First Name"      => $row["first_name"],
                 "Last Name"       => $row["last_name"],
                 "Contact Number"  => $row["contact_number"],
                 "Address"         => $row["address"],
                 "Date Registered" => $row["date_registered"],
             ]), ENT_QUOTES) ?>">
            <div class="w-12 h-12 rounded-full bg-[#eef7ee] text-[#2d7a46] flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[15px] font-bold text-gray-900 leading-snug truncate"><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?></p>
                <p class="text-xs text-gray-500 truncate mt-0.5"><?= htmlspecialchars($row['address']) ?></p>
                <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($row['contact_number']) ?> &middot; since <?= htmlspecialchars($row['date_registered']) ?></p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-[#f3f4f6] text-[#374151] shrink-0">Farmer</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-gray-300 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </div>
    <?php endforeach; ?>
</div>

    <?php paginationBar($page, $total_rows); ?>
    </div><!-- /LAYER 3 -->
</div><!-- /LAYER 2 -->

<!-- Add Farmer side card -->
<aside id="add-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-lg z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
        <h2 class="text-lg font-semibold">Add Farmer</h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="POST" action="create.php" class="flex-1 flex flex-col overflow-y-auto">
        <div class="flex-1 px-5 py-4 space-y-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">First Name</label>
                <input type="text" name="first_name" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Last Name</label>
                <input type="text" name="last_name" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Contact Number</label>
                <input type="text" name="contact_number" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Address</label>
                <input type="text" name="address" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
        </div>
        <div class="border-t border-gray-200 px-5 py-4 flex justify-end gap-2">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</button>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Save Farmer</button>
        </div>
    </form>
</aside>

<!-- Edit Farmer slider: filled instantly by JS from the clicked row (no reload);
     server-prefilled only for ?edit=<id> deep links -->
<aside id="edit-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-lg z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
        <h2 class="text-lg font-semibold">Edit Farmer<?= $edit_record ? " #" . $edit_id : "" ?></h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="POST" action="edit.php" class="flex-1 flex flex-col overflow-y-auto">
        <input type="hidden" name="id" value="<?= $edit_id ?>">
        <div class="flex-1 px-5 py-4 space-y-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">First Name</label>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($edit_record['first_name'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Last Name</label>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($edit_record['last_name'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Contact Number</label>
                <input type="text" name="contact_number" required value="<?= htmlspecialchars($edit_record['contact_number'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Address</label>
                <input type="text" name="address" required value="<?= htmlspecialchars($edit_record['address'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
        </div>
        <div class="border-t border-gray-200 px-5 py-4 flex justify-end gap-2">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</button>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg">Update Farmer</button>
        </div>
    </form>
</aside>

<?php require "../includes/sidecard.php"; ?>
<?php require "../includes/confirm_modal.php"; ?>
<?php require "../includes/footer.php"; ?>
