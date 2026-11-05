<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../includes/table_helpers.php";
require_once "../config/Database.php";
require_once "../models/Lookup.php";

// Settings is an Admin-only module
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=" . urlencode("Only administrators can access Settings."));
    exit;
}

$pdo    = (new Database())->connect();
$lookup = new Lookup($pdo);
$types  = $lookup->getTypes();

$type = (string) ($_GET["type"] ?? "soil_types");
if (!$lookup->isValidType($type)) {
    $type = "soil_types";
}

$error = "";

// Inline Add form handler
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = (string) trim($_POST["name"]);
    if ($name === "") {
        $error = "Please enter a value.";
    } else {
        $new_id = $lookup->create($type, $name);
        logActivity($pdo, "create", "lookup", (int) $new_id, "Added {$types[$type]} value: {$name}");
        header("Location: index.php?type={$type}&msg=" . urlencode("Value added successfully."));
        exit;
    }
}

// Uniform table pagination (same PER_PAGE as the record modules)
$all_rows   = $lookup->getAll($type);
$total_rows = count($all_rows);
$page       = currentPage();
$rows       = array_slice($all_rows, pageOffset($page), PER_PAGE);

$page_title  = "Settings";
$active      = "settings";
$breadcrumbs = [["Settings", null]];
require "../includes/header.php";
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Settings</h1>
        <p class="text-sm text-gray-500">Manage the dropdown values (lookup tables) used across the system.</p>
    </div>
    <button type="button" onclick="openAddCard()"
            class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg inline-flex items-center gap-2 self-start sm:self-auto print:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Add <?= $types[$type] ?> Value
    </button>
</div>

<?php require "../includes/alert.php"; ?>

<!-- Mobile: horizontal pill scroller for the 7 lookup types -->
<div class="lg:hidden flex gap-2 overflow-x-auto pb-1 mb-4 -mx-4 px-4 print:hidden">
    <?php foreach ($types as $key => $label): ?>
        <a href="index.php?type=<?= $key ?>"
           class="shrink-0 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors <?= $type === $key
               ? 'bg-green-600 text-white'
               : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="flex flex-col lg:flex-row gap-6">

    <!-- Desktop: vertical settings-style sidebar nav -->
    <aside class="hidden lg:block w-56 shrink-0 print:hidden">
        <nav class="space-y-1">
            <?php foreach ($types as $key => $label): ?>
                <a href="index.php?type=<?= $key ?>"
                   class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $type === $key
                       ? 'bg-green-50 text-green-700'
                       : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Values list for the selected type -->
    <div class="flex-1 min-w-0 max-w-2xl">
        <div id="records-panel" class="bg-white rounded-lg border border-gray-200 shadow-sm divide-y divide-gray-100">
            <?php if (count($rows) === 0): ?>
                <div class="px-4 py-8 text-center text-sm text-gray-500">No values defined yet.</div>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <div class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-gray-50">
                    <span class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($row['name']) ?></span>
                    <div class="inline-flex items-center gap-2 shrink-0">
                        <a href="#" title="Edit"
                           onclick="openEditCard(<?= (int) $row['id'] ?>, <?= htmlspecialchars(json_encode($row['name']), ENT_QUOTES) ?>); return false;"
                           class="text-blue-600 hover:text-blue-800">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </a>
                        <a href="#" title="Delete"
                           onclick="openDeleteModal('delete.php?type=<?= $type ?>&id=<?= $row['id'] ?>', 'Are you sure you want to delete <?= htmlspecialchars(addslashes($row['name']), ENT_QUOTES) ?>?'); return false;"
                           class="text-red-600 hover:text-red-800">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php paginationBar($page, $total_rows); ?>
        </div>

        <p class="text-xs text-gray-400 mt-3">Values in use by active records cannot be deleted. Deleted values are hidden from dropdowns but kept for historical records.</p>
    </div>

</div>

<!-- Side card overlay -->
<div id="sidecard-overlay" class="fixed inset-0 bg-black/30 z-40 hidden print:hidden" onclick="closeSideCards()"></div>

<!-- Add Value side card -->
<aside id="add-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-lg z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
        <h2 class="text-lg font-semibold">Add <?= $types[$type] ?> Value</h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="POST" action="index.php?type=<?= $type ?>" class="flex-1 flex flex-col overflow-y-auto">
        <div class="flex-1 px-5 py-4 space-y-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1"><?= $types[$type] ?> Value</label>
                <input type="text" name="name" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <p class="text-xs text-gray-400">This value becomes available immediately in the related dropdowns.</p>
        </div>
        <div class="border-t border-gray-200 px-5 py-4 flex justify-end gap-2">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</button>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Add Value</button>
        </div>
    </form>
</aside>

<!-- Edit Value side card -->
<aside id="edit-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-lg z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
        <h2 id="edit-card-title" class="text-lg font-semibold">Edit Value</h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="POST" action="edit.php" class="flex-1 flex flex-col overflow-y-auto">
        <input type="hidden" name="type" value="<?= $type ?>">
        <input type="hidden" name="id" id="edit-card-id" value="">
        <div class="flex-1 px-5 py-4 space-y-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1"><?= $types[$type] ?> Value</label>
                <input type="text" name="name" id="edit-card-name" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <p class="text-xs text-gray-400">Renaming updates every record that already uses this value.</p>
        </div>
        <div class="border-t border-gray-200 px-5 py-4 flex justify-end gap-2">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</button>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg">Update Value</button>
        </div>
    </form>
</aside>

<script>
function showSideCard(card) {
    var overlay = document.getElementById("sidecard-overlay");
    if (overlay) { overlay.classList.remove("hidden"); }
    card.classList.remove("hidden");
    card.classList.add("flex");
}
// Null-safe: outlives its page under PJAX (other pages lack these elements)
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
function openAddCard() {
    closeSideCards();
    var card = document.getElementById("add-card");
    showSideCard(card);
    card.querySelector("input[name='name']").focus();
}
function openEditCard(id, name) {
    closeSideCards();
    document.getElementById("edit-card-id").value = id;
    var nameField = document.getElementById("edit-card-name");
    nameField.value = name;
    document.getElementById("edit-card-title").textContent = "Edit Value #" + id;
    showSideCard(document.getElementById("edit-card"));
    nameField.focus();
}
if (!window.__aisEscBound) {
    window.__aisEscBound = true;
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") { closeSideCards(); }
    });
}
function initSettingsPage() {
    if (new URLSearchParams(window.location.search).get("add") === "1") {
        openAddCard();
    }

    // Edit slider saves via fetch: toast + background list refresh, no reload
    var editForm = document.querySelector("#edit-card form");
    if (editForm && !editForm.dataset.bound) {
        editForm.dataset.bound = "1";
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
                    fetch(window.location.href, { headers: { "X-Requested-With": "fetch" } })
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
            .catch(function () { editForm.submit(); }); // classic redirect fallback (button stays disabled during nav)
        });
    }
}
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSettingsPage);
} else {
    initSettingsPage(); // PJAX swap: document already loaded
}
</script>

<?php require "../includes/confirm_modal.php"; ?>
<?php require "../includes/footer.php"; ?>
