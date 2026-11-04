<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/User.php";

// User management is an Admin-only module
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=" . urlencode("Only administrators can manage users."));
    exit;
}

$pdo       = (new Database())->connect();
$userModel = new User($pdo);
$users     = $userModel->getAll();

// Record being edited via the Edit slider (?edit=<id> prefills and auto-opens it)
$edit_id     = (int) ($_GET["edit"] ?? 0);
$edit_record = $edit_id > 0 ? $userModel->getById($edit_id) : null;

$page_title  = "Users";
$active      = "users";
$breadcrumbs = [["Users", null]];
require "../includes/header.php";
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Users</h1>
        <p class="text-sm text-gray-500"><?= count($users) ?> active user account(s)</p>
    </div>
    <button type="button" onclick="openAddCard()"
            class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg inline-flex items-center gap-2 self-start sm:self-auto print:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Add User
    </button>
</div>

<?php require "../includes/alert.php"; ?>

<div class="max-w-4xl">
<div class="bg-white rounded-lg border border-gray-200 shadow-sm">
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
            <tr>
                <th class="px-4 py-3 text-left">ID</th>
                <th class="px-4 py-3 text-left">Username</th>
                <th class="px-4 py-3 text-left">Full Name</th>
                <th class="px-4 py-3 text-left">Role</th>
                <th class="px-4 py-3 text-left">Created</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $row): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3"><?= $row['user_id'] ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['username']) ?>
                        <?php if ((int) $row['user_id'] === (int) $_SESSION['user_id']): ?>
                            <span class="text-xs text-gray-400">(you)</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['full_name']) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $row['role'] === 'admin' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' ?>">
                            <?= htmlspecialchars(ucfirst($row['role'])) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars(substr($row['created_at'], 0, 10)) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-2">
                            <a href="index.php?edit=<?= $row['user_id'] ?>" title="Edit" class="text-blue-600 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </a>
                            <?php if ((int) $row['user_id'] !== (int) $_SESSION['user_id']): ?>
                                <a href="#" title="Deactivate"
                                   onclick="openDeleteModal('delete.php?id=<?= $row['user_id'] ?>', 'Deactivate user <?= htmlspecialchars(addslashes($row['username']), ENT_QUOTES) ?>? They will no longer be able to log in.'); return false;"
                                   class="text-red-600 hover:text-red-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
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
    <?php foreach ($users as $row): ?>
        <div class="flex items-center gap-3 px-4 py-3">
            <div class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-semibold shrink-0">
                <?= htmlspecialchars(strtoupper(substr($row['full_name'], 0, 1))) ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium truncate"><?= htmlspecialchars($row['username']) ?>
                    <?php if ((int) $row['user_id'] === (int) $_SESSION['user_id']): ?>
                        <span class="text-xs text-gray-400">(you)</span>
                    <?php endif; ?>
                </p>
                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($row['full_name']) ?> &middot; since <?= htmlspecialchars(substr($row['created_at'], 0, 10)) ?></p>
            </div>
            <span class="px-2 py-1 rounded-full text-xs font-medium shrink-0 <?= $row['role'] === 'admin' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' ?>">
                <?= htmlspecialchars(ucfirst($row['role'])) ?>
            </span>
            <div class="inline-flex items-center gap-2 shrink-0">
                <a href="index.php?edit=<?= $row['user_id'] ?>" title="Edit" class="text-blue-600 hover:text-blue-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                </a>
                <?php if ((int) $row['user_id'] !== (int) $_SESSION['user_id']): ?>
                    <a href="#" title="Deactivate"
                       onclick="openDeleteModal('delete.php?id=<?= $row['user_id'] ?>', 'Deactivate user <?= htmlspecialchars(addslashes($row['username']), ENT_QUOTES) ?>? They will no longer be able to log in.'); return false;"
                       class="text-red-600 hover:text-red-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</div>
</div>

<!-- Add User side card -->
<div id="sidecard-overlay" class="fixed inset-0 bg-black/30 z-40 hidden print:hidden" onclick="closeSideCards()"></div>
<aside id="add-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-lg z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
        <h2 class="text-lg font-semibold">Add User</h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="POST" action="create.php" class="flex-1 flex flex-col overflow-y-auto">
        <div class="flex-1 px-5 py-4 space-y-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Username</label>
                <input type="text" name="username" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Full Name</label>
                <input type="text" name="full_name" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Password</label>
                <input type="password" name="password" required minlength="6"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                <p class="text-xs text-gray-400 mt-1">At least 6 characters.</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Role</label>
                <!-- Roles come from the users.role CHECK constraint (not a lookup table) -->
                <select name="role" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <div class="border-t border-gray-200 px-5 py-4 flex justify-end gap-2">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</button>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Save User</button>
        </div>
    </form>
</aside>

<?php if ($edit_record): ?>
<!-- Edit User slider (opens automatically via ?edit=<id>) -->
<aside id="edit-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-lg z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
        <h2 class="text-lg font-semibold">Edit User &mdash; <?= htmlspecialchars($edit_record['username']) ?></h2>
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
                <label class="block text-sm text-gray-500 mb-1">Username</label>
                <input type="text" value="<?= htmlspecialchars($edit_record['username']) ?>" disabled
                       class="w-full p-2 border border-gray-200 bg-gray-50 text-gray-500 rounded-lg">
                <p class="text-xs text-gray-400 mt-1">Usernames cannot be changed.</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Full Name</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($edit_record['full_name']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Role</label>
                <!-- Roles come from the users.role CHECK constraint (not a lookup table) -->
                <select name="role" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="staff" <?= $edit_record['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= $edit_record['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">New Password</label>
                <input type="password" name="password" minlength="6"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                <p class="text-xs text-gray-400 mt-1">Leave blank to keep the current password.</p>
            </div>
        </div>
        <div class="border-t border-gray-200 px-5 py-4 flex justify-end gap-2">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</button>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg">Update User</button>
        </div>
    </form>
</aside>
<?php endif; ?>

<script>
function showSideCard(card) {
    document.getElementById("sidecard-overlay").classList.remove("hidden");
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
    document.getElementById("sidecard-overlay").classList.add("hidden");
    ["add-card", "edit-card"].forEach(function (id) {
        var card = document.getElementById(id);
        if (card) {
            card.classList.add("hidden");
            card.classList.remove("flex");
        }
    });
}
document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") { closeSideCards(); }
});
document.addEventListener("DOMContentLoaded", function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get("add") === "1") {
        openAddCard();
    } else if (params.get("edit") && document.getElementById("edit-card")) {
        openEditCard();
    }
});
</script>

<?php require "../includes/confirm_modal.php"; ?>
<?php require "../includes/footer.php"; ?>
