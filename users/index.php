<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/table_helpers.php";
require_once "../config/Database.php";
require_once "../models/User.php";

// User management is an Admin-only module
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=" . urlencode("Only administrators can manage users."));
    exit;
}

$pdo       = (new Database())->connect();
$userModel = new User($pdo);

// Sort whitelist ensures user input cannot inject arbitrary SQL into ORDER BY
$sort_whitelist = [
    "id"       => "user_id",
    "username" => "username",
    "name"     => "full_name",
    "role"     => "role",
    "created"  => "created_at",
];
[$order, $dir, $sort_key] = resolveSort($sort_whitelist, "id");

// Record being edited via the Edit slider (?edit=<id> prefills and auto-opens it)
$edit_id     = (int) ($_GET["edit"] ?? 0);
$edit_record = $edit_id > 0 ? $userModel->getById($edit_id) : null;

$role        = (string) trim($_GET["role"] ?? "");
if (!in_array($role, ["admin", "staff"], true)) {
    $role = "";
}
$search      = (string) trim($_GET["search"] ?? "");
$page        = currentPage();
$total_rows  = $userModel->countAll($role, $search);
$users       = $userModel->getPage($role, $search, $order, $dir, PER_PAGE, pageOffset($page));
$is_filtered = $search !== "" || $role !== "";

$page_title  = "Users";
$active      = "users";
$breadcrumbs = [["Users", null]];
require "../includes/header.php";
?>

<!-- LAYER 1: Top Hero Header with Title, Count, Add User Button & Foliage Illustration -->
<div class="relative mb-5 min-h-[8.25rem] sm:min-h-[10.25rem] print:hidden">
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
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#111827] tracking-tight">Users</h1>
            <p id="records-count" class="text-xs sm:text-sm text-gray-500 font-normal mt-0.5 sm:mt-1"><?= number_format($total_rows) ?> active user account(s)</p>
        </div>
        <div class="shrink-0">
            <button type="button" onclick="openAddCard()"
                    class="bg-[#2e7d32] hover:bg-[#256829] active:scale-95 text-white font-medium px-4 sm:px-5 py-2 sm:py-2.5 rounded-full inline-flex items-center gap-1.5 sm:gap-2 shadow-sm text-xs sm:text-sm transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Add User</span>
            </button>
        </div>
    </div>
</div>

<?php require "../includes/alert.php"; ?>

<!-- LAYER 2: Middle warm-toned rounded container holding search, filters, sort, and inner white card -->
<div class="bg-[#f6f4ec] rounded-[2.25rem] sm:rounded-3xl p-3.5 sm:p-5 border border-[#ede9de] shadow-[0_4px_20px_rgba(0,0,0,0.03)] mb-6">

    <!-- Filter toolbar: search + role filter + sort inline above the table -->
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
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search username or name..."
                       class="w-full bg-white border border-gray-200/70 rounded-full shadow-sm pl-11 pr-10 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent transition-all">
                <?php if ($search !== ""): ?>
                    <button type="button" onclick="this.form.search.value = ''; submitToolbar(this.form);" aria-label="Clear search"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Role filter pill -->
            <div class="w-full md:w-auto">
                <select name="role" aria-label="Filter by role" onchange="submitToolbar(this.form);"
                        class="w-full md:w-auto bg-white border border-gray-200/70 rounded-full shadow-sm py-2.5 pl-4 pr-9 text-xs sm:text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent transition-all truncate">
                    <option value="" <?= $role === "" ? "selected" : "" ?>>All Roles</option>
                    <option value="admin" <?= $role === "admin" ? "selected" : "" ?>>Admin</option>
                    <option value="staff" <?= $role === "staff" ? "selected" : "" ?>>Staff</option>
                </select>
            </div>

            <!-- Sort dropdown pill -->
            <div class="grid grid-cols-1 gap-2.5 md:flex md:gap-3 flex-1 justify-end">
                <select aria-label="Sort by"
                        onchange="var p = this.value.split(':'); this.form.sort.value = p[0]; this.form.dir.value = p[1]; submitToolbar(this.form);"
                        class="w-full md:w-auto bg-white border border-gray-200/70 rounded-full shadow-sm py-2.5 pl-4 pr-9 text-xs sm:text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent transition-all truncate">
                    <option value="id:asc" <?= $sort_key === 'id' && $dir === 'ASC' ? 'selected' : '' ?>>Oldest</option>
                    <option value="id:desc" <?= $sort_key === 'id' && $dir === 'DESC' ? 'selected' : '' ?>>Newest</option>
                    <option value="username:asc" <?= $sort_key === 'username' && $dir === 'ASC' ? 'selected' : '' ?>>Username A&ndash;Z</option>
                    <option value="username:desc" <?= $sort_key === 'username' && $dir === 'DESC' ? 'selected' : '' ?>>Username Z&ndash;A</option>
                    <option value="name:asc" <?= $sort_key === 'name' && $dir === 'ASC' ? 'selected' : '' ?>>Name A&ndash;Z</option>
                    <option value="name:desc" <?= $sort_key === 'name' && $dir === 'DESC' ? 'selected' : '' ?>>Name Z&ndash;A</option>
                </select>
            </div>
        </div>
    </form>

    <!-- LAYER 3: Inner pure white card holding the user records -->
    <div id="records-panel" class="bg-white rounded-[1.75rem] border border-gray-100 shadow-sm overflow-hidden">
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/80 text-gray-500 uppercase text-[11px] tracking-wider border-b border-gray-100">
                    <tr>
                        <?php sortableTh("id", "ID", $sort_key, $dir); ?>
                        <?php sortableTh("username", "User Profile", $sort_key, $dir); ?>
                        <?php sortableTh("name", "Full Name", $sort_key, $dir); ?>
                        <?php sortableTh("role", "Role", $sort_key, $dir); ?>
                        <?php sortableTh("created", "Created", $sort_key, $dir); ?>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (count($users) === 0): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-10">
                                <div class="border border-dashed border-gray-300 rounded-2xl px-4 py-8 text-center text-sm text-gray-500 bg-gray-50/50">
                                    <?php if ($is_filtered): ?>
                                        No user accounts match your search.
                                        <a href="index.php" class="text-[#2e7d32] font-semibold hover:underline block mt-1">Clear filters</a>
                                    <?php else: ?>
                                        No users registered yet.
                                        <button type="button" onclick="openAddCard()" class="text-[#2e7d32] font-semibold hover:underline block mt-1">Add the first user</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($users as $row): ?>
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-4 py-3.5 text-xs text-gray-500 font-mono">#<?= $row['user_id'] ?></td>
                            <td class="px-4 py-3.5 font-medium text-gray-900">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100/70 text-[#2e7d32] flex items-center justify-center text-xs font-bold shrink-0">
                                        <?= htmlspecialchars(strtoupper(substr($row['username'], 0, 1))) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($row['username']) ?></span>
                                        <?php if ((int) $row['user_id'] === (int) $_SESSION['user_id']): ?>
                                            <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">You</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-gray-700 text-sm"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $row['role'] === 'admin'
                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60'
                                    : 'bg-sky-50 text-sky-700 border border-sky-200/60' ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $row['role'] === 'admin' ? 'bg-emerald-500' : 'bg-sky-500' ?>"></span>
                                    <?= htmlspecialchars(ucfirst($row['role'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-500 font-medium"><?= htmlspecialchars(substr($row['created_at'], 0, 10)) ?></td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="index.php?edit=<?= $row['user_id'] ?>" title="Edit User"
                                       onclick="return openUserEdit(<?= (int) $row['user_id'] ?>, <?= htmlspecialchars(json_encode($row['full_name']), ENT_QUOTES) ?>, '<?= $row['role'] ?>', <?= htmlspecialchars(json_encode($row['username']), ENT_QUOTES) ?>);"
                                       class="p-1.5 text-gray-500 hover:text-[#2e7d32] hover:bg-emerald-50 rounded-lg transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </a>
                                    <?php if ((int) $row['user_id'] !== (int) $_SESSION['user_id']): ?>
                                        <a href="#" title="Deactivate User"
                                           onclick="openDeleteModal('delete.php?id=<?= $row['user_id'] ?>', 'Deactivate user <?= htmlspecialchars(addslashes($row['username']), ENT_QUOTES) ?>? They will no longer be able to log in.'); return false;"
                                           class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
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

        <!-- Mobile card list -->
        <div class="md:hidden divide-y divide-gray-100">
            <?php if (count($users) === 0): ?>
                <div class="p-6 text-center text-sm text-gray-500">
                    No user accounts match your search.
                </div>
            <?php endif; ?>
            <?php foreach ($users as $row): ?>
                <div class="flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors">
                    <div class="w-10 h-10 rounded-full bg-emerald-100/70 text-[#2e7d32] flex items-center justify-center text-sm font-bold shrink-0">
                        <?= htmlspecialchars(strtoupper(substr($row['username'], 0, 1))) ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($row['username']) ?></span>
                            <?php if ((int) $row['user_id'] === (int) $_SESSION['user_id']): ?>
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">You</span>
                            <?php endif; ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $row['role'] === 'admin'
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60'
                                : 'bg-sky-50 text-sky-700 border border-sky-200/60' ?>">
                                <?= htmlspecialchars(ucfirst($row['role'])) ?>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 truncate mt-0.5"><?= htmlspecialchars($row['full_name']) ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Created <?= htmlspecialchars(substr($row['created_at'], 0, 10)) ?></p>
                    </div>
                    <div class="inline-flex items-center gap-1 shrink-0">
                        <a href="index.php?edit=<?= $row['user_id'] ?>" title="Edit"
                           onclick="return openUserEdit(<?= (int) $row['user_id'] ?>, <?= htmlspecialchars(json_encode($row['full_name']), ENT_QUOTES) ?>, '<?= $row['role'] ?>', <?= htmlspecialchars(json_encode($row['username']), ENT_QUOTES) ?>);"
                           class="p-2 text-gray-500 hover:text-[#2e7d32] hover:bg-emerald-50 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </a>
                        <?php if ((int) $row['user_id'] !== (int) $_SESSION['user_id']): ?>
                            <a href="#" title="Deactivate"
                               onclick="openDeleteModal('delete.php?id=<?= $row['user_id'] ?>', 'Deactivate user <?= htmlspecialchars(addslashes($row['username']), ENT_QUOTES) ?>? They will no longer be able to log in.'); return false;"
                               class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php paginationBar($page, $total_rows); ?>
    </div>
</div>

<!-- Add User side card -->
<div id="sidecard-overlay" class="fixed inset-0 bg-black/30 z-40 hidden print:hidden" onclick="closeSideCards()"></div>
<aside id="add-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-2xl z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <h2 class="text-lg font-bold text-gray-900">Add User</h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="POST" action="create.php" class="flex-1 flex flex-col overflow-y-auto">
        <div class="flex-1 px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Username</label>
                <input type="text" name="username" required placeholder="e.g. jdelacruz"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent outline-none text-sm text-gray-800 transition-all">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Full Name</label>
                <input type="text" name="full_name" required placeholder="e.g. Juan Dela Cruz"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent outline-none text-sm text-gray-800 transition-all">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Password</label>
                <input type="password" name="password" required minlength="6" placeholder="••••••••"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent outline-none text-sm text-gray-800 transition-all">
                <p class="text-xs text-gray-400 mt-1">At least 6 characters.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Role</label>
                <select name="role" required
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent outline-none text-sm text-gray-800 transition-all bg-white">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <div class="border-t border-gray-100 px-6 py-4 flex justify-end gap-2.5 bg-gray-50/50">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-full text-sm font-medium transition-colors shadow-sm">Cancel</button>
            <button type="submit" class="bg-[#2e7d32] hover:bg-[#256829] text-white font-medium px-5 py-2 rounded-full text-sm transition-all shadow-sm">Save User</button>
        </div>
    </form>
</aside>

<!-- Edit User slider -->
<aside id="edit-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-2xl z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <h2 id="edit-card-title" class="text-lg font-bold text-gray-900">Edit User<?= $edit_record ? " — " . htmlspecialchars($edit_record['username']) : "" ?></h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="POST" action="edit.php" class="flex-1 flex flex-col overflow-y-auto">
        <input type="hidden" name="id" value="<?= $edit_id ?>">
        <div class="flex-1 px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Username</label>
                <input type="text" id="edit-card-username" value="<?= htmlspecialchars($edit_record['username'] ?? '') ?>" disabled
                       class="w-full px-3.5 py-2.5 border border-gray-200 bg-gray-50 text-gray-500 rounded-xl text-sm font-medium">
                <p class="text-[11px] text-gray-400 mt-1">Usernames cannot be changed.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Full Name</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($edit_record['full_name'] ?? '') ?>"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent outline-none text-sm text-gray-800 transition-all">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Role</label>
                <select name="role" required
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent outline-none text-sm text-gray-800 transition-all bg-white">
                    <option value="staff" <?= ($edit_record['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= ($edit_record['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">New Password</label>
                <input type="password" name="password" minlength="6" placeholder="Leave blank to keep unchanged"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2e7d32] focus:border-transparent outline-none text-sm text-gray-800 transition-all">
                <p class="text-[11px] text-gray-400 mt-1">Leave blank to keep the current password.</p>
            </div>
        </div>
        <div class="border-t border-gray-100 px-6 py-4 flex justify-end gap-2.5 bg-gray-50/50">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-full text-sm font-medium transition-colors shadow-sm">Cancel</button>
            <button type="submit" class="bg-[#2e7d32] hover:bg-[#256829] text-white font-medium px-5 py-2 rounded-full text-sm transition-all shadow-sm">Update User</button>
        </div>
    </form>
</aside>

<script>
// Instant edit (no reload): fill the edit slider from the clicked row's values
function openUserEdit(id, fullName, role, username) {
    var card = document.getElementById("edit-card");
    if (!card) {
        return true; // fall back to the href reload
    }
    card.querySelector('[name="id"]').value = id;
    card.querySelector('[name="full_name"]').value = fullName;
    card.querySelector('[name="role"]').value = role;
    card.querySelector('[name="password"]').value = "";
    document.getElementById("edit-card-username").value = username;
    document.getElementById("edit-card-title").textContent = "Edit User — " + username;
    if (window.syncCustomSelects) {
        syncCustomSelects(card);
    }
    openEditCard();
    return false;
}

function showSideCard(card) {
    var overlay = document.getElementById("sidecard-overlay");
    if (overlay) { overlay.classList.remove("hidden"); }
    card.classList.remove("hidden");
    card.classList.add("flex");
    var firstField = card.querySelector("input:not([type=hidden]):not([disabled]), select");
    if (firstField) { firstField.focus(); }
}
function openAddCard() {
    closeSideCards();
    showSideCard(document.getElementById("add-card"));
}
function openEditCard() {
    closeSideCards();
    var card = document.getElementById("edit-card");
    if (card) { showSideCard(card); }
}
function closeSideCards() {
    var overlay = document.getElementById("sidecard-overlay");
    if (overlay) { overlay.classList.add("hidden"); }
    ["add-card", "edit-card"].forEach(function (id) {
        var card = document.getElementById(id);
        if (card) {
            card.classList.add("hidden");
            card.classList.remove("flex");
        }
    });
}
if (!window.__aisEscBound) {
    window.__aisEscBound = true;
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") { closeSideCards(); }
    });
}
function initUsersPage() {
    var params = new URLSearchParams(window.location.search);
    if (params.get("add") === "1") {
        openAddCard();
    } else if (params.get("edit") && document.getElementById("edit-card")) {
        openEditCard();
    }

    // Edit slider saves via fetch: toast + background panel refresh, no reload
    var editCard = document.getElementById("edit-card");
    if (editCard && !editCard.dataset.bound) {
        editCard.dataset.bound = "1";
        var editForm = editCard.querySelector("form");
        editForm.addEventListener("submit", function (event) {
            event.preventDefault();
            var saveBtn = editForm.querySelector("button[type=submit]");
            setBtnLoading(saveBtn, "Updating...");
            fetch(editForm.action, {
                method: "POST",
                body: new FormData(editForm),
                headers: { "X-Requested-With": "fetch" }
            })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (result.ok) {
                    closeSideCards();
                    resetBtn(saveBtn);
                    var url = new URL(window.location.href);
                    ["edit", "add", "msg", "error"].forEach(function (p) { url.searchParams.delete(p); });
                    window.history.replaceState(null, "", url);
                    fetch(url, { headers: { "X-Requested-With": "fetch" } })
                        .then(function (r) { return r.text(); })
                        .then(function (html) {
                            var fresh = new DOMParser().parseFromString(html, "text/html").getElementById("records-panel");
                            var panel = document.getElementById("records-panel");
                            if (fresh && panel) { panel.innerHTML = fresh.innerHTML; }
                        });
                } else {
                    resetBtn(saveBtn);
                }
                showToast(result.message, result.ok ? "success" : "error");
            })
            .catch(function () { editForm.submit(); }); // classic redirect fallback
        });
    }
}
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initUsersPage);
} else {
    initUsersPage(); // PJAX swap
}
</script>

<?php require "../includes/confirm_modal.php"; ?>
<?php require "../includes/footer.php"; ?>
