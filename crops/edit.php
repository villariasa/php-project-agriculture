<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Crop.php";
require_once "../models/Lookup.php";

$pdo       = (new Database())->connect();
$cropModel = new Crop($pdo);
$lookup    = new Lookup($pdo);
$error     = "";

$categories = $lookup->getAll("crop_categories");
$seasons    = $lookup->getAll("seasons");

$crop_id = (int) ($_GET["id"] ?? 0);
$crop    = $cropModel->getById($crop_id);

if (!$crop) {
    header("Location: index.php?error=" . urlencode("Crop not found."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $crop_name           = (string) trim($_POST["crop_name"]);
    $variety             = (string) trim($_POST["variety"]);
    $category_id         = (int) $_POST["category_id"];
    $growing_period_days = (int) $_POST["growing_period_days"];
    $season_id           = (int) $_POST["season_id"];

    if ($crop_name === "" || $variety === "" || $category_id <= 0 || $growing_period_days <= 0 || $season_id <= 0) {
        $error = "All fields are required and growing period must be greater than zero.";
    } else {
        $cropModel->update($crop_id, $crop_name, $variety, $category_id, $growing_period_days, $season_id);
        header("Location: index.php?msg=" . urlencode("Crop updated successfully."));
        exit;
    }
    $crop = array_merge($crop, $_POST);
}

$page_title = "Edit Crop";
$active     = "crops";
require "../includes/header.php";
?>

<div class="max-w-3xl mx-auto">
    <a href="index.php" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-700 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Back to list
    </a>
    <h1 class="text-2xl font-bold mb-6">Edit Crop #<?= $crop_id ?></h1>

    <?php if ($error !== ""): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Crop Name</label>
                <input type="text" name="crop_name" required value="<?= htmlspecialchars($crop['crop_name']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Variety</label>
                <input type="text" name="variety" required value="<?= htmlspecialchars($crop['variety']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Category</label>
                <select name="category_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int) $crop['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Growing Period (days)</label>
                <input type="number" min="1" name="growing_period_days" required value="<?= htmlspecialchars($crop['growing_period_days']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Recommended Season</label>
                <select name="season_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Season --</option>
                    <?php foreach ($seasons as $season): ?>
                        <option value="<?= $season['id'] ?>" <?= (int) $crop['season_id'] === (int) $season['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($season['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <a href="index.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Update Crop</button>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
