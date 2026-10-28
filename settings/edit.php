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

$type = (string) ($_GET["type"] ?? "");
$id   = (int) ($_GET["id"] ?? 0);

if (!$lookup->isValidType($type)) {
    header("Location: index.php?error=" . urlencode("Invalid lookup type."));
    exit;
}

$row = $lookup->getById($type, $id);
if (!$row) {
    header("Location: index.php?type={$type}&error=" . urlencode("Value not found."));
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = (string) trim($_POST["name"]);
    if ($name === "") {
        $error = "Please enter a value.";
    } else {
        $lookup->update($type, $id, $name);
        header("Location: index.php?type={$type}&msg=" . urlencode("Value updated successfully."));
        exit;
    }
    $row['name'] = $name;
}

$page_title = "Edit " . $lookup->getLabel($type);
$active     = "settings";
require "../includes/header.php";
?>

<div class="max-w-md mx-auto">
    <a href="index.php?type=<?= $type ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-700 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Back to settings
    </a>
    <h1 class="text-2xl font-bold mb-6">Edit <?= $lookup->getLabel($type) ?> #<?= $id ?></h1>

    <?php if ($error !== ""): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <label class="block text-sm text-gray-500 mb-1">Value</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($row['name']) ?>"
               class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        <div class="flex justify-end gap-2 mt-6">
            <a href="index.php?type=<?= $type ?>" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Update Value</button>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
