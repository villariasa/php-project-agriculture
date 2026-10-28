<?php
$base = "../";
require_once "../includes/auth_check.php";
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
        $lookup->create($type, $name);
        header("Location: index.php?type={$type}&msg=" . urlencode("Value added successfully."));
        exit;
    }
}

$rows = $lookup->getAll($type);

$page_title = "Settings";
$active     = "settings";
require "../includes/header.php";
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold">Settings</h1>
    <p class="text-sm text-gray-500">Manage the dropdown values (lookup tables) used across the system.</p>
</div>

<?php require "../includes/alert.php"; ?>

<!-- Lookup Table Tabs -->
<div class="flex overflow-x-auto border-b border-gray-200 mb-6">
    <?php foreach ($types as $key => $label): ?>
        <a href="index.php?type=<?= $key ?>"
           class="px-3 py-2 text-sm whitespace-nowrap border-b-2 -mb-px <?= $type === $key
               ? 'border-green-600 text-green-700 font-semibold'
               : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="flex flex-col xl:flex-row gap-6">
    <!-- Values List -->
    <div class="flex-1 min-w-0 xl:order-1 bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto h-fit">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
                <tr>
                    <th class="px-4 py-3 text-left">ID</th>
                    <th class="px-4 py-3 text-left"><?= $types[$type] ?></th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No values defined yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-3"><?= $row['id'] ?></td>
                        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="edit.php?type=<?= $type ?>&id=<?= $row['id'] ?>" title="Edit" class="text-blue-600 hover:text-blue-800">
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
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Inline Add Form (right rail) -->
    <aside class="xl:w-80 shrink-0 xl:order-2">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 h-fit xl:sticky xl:top-20">
        <h2 class="font-semibold mb-4">Add New Value</h2>

        <?php if ($error !== ""): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label class="block text-sm text-gray-500 mb-1"><?= $types[$type] ?> Value</label>
            <input type="text" name="name" required
                   class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none mb-4">
            <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg inline-flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Value
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-3">Values in use by active records cannot be deleted. Deleted values are hidden from dropdowns but kept for historical records.</p>
    </div>
    </aside>
</div>

<?php require "../includes/confirm_modal.php"; ?>
<?php require "../includes/footer.php"; ?>
