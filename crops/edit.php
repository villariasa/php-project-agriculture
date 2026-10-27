<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Crop.php";

$pdo       = (new Database())->connect();
$cropModel = new Crop($pdo);
$error     = "";

$categories = ["Cereal", "Legume", "Root Crop", "Fruit", "Vegetable"];
$seasons    = ["Dry Season", "Wet Season", "Year Round"];

$crop_id = (int) ($_GET["id"] ?? 0);
$crop    = $cropModel->getById($crop_id);

if (!$crop) {
    header("Location: index.php?error=" . urlencode("Crop not found."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $crop_name           = (string) trim($_POST["crop_name"]);
    $variety             = (string) trim($_POST["variety"]);
    $crop_category       = (string) trim($_POST["crop_category"]);
    $growing_period_days = (int) $_POST["growing_period_days"];
    $recommended_season  = (string) trim($_POST["recommended_season"]);

    if ($crop_name === "" || $variety === "" || $crop_category === "" || $growing_period_days <= 0 || $recommended_season === "") {
        $error = "All fields are required and growing period must be greater than zero.";
    } else {
        $cropModel->update($crop_id, $crop_name, $variety, $crop_category, $growing_period_days, $recommended_season);
        header("Location: index.php?msg=" . urlencode("Crop updated successfully."));
        exit;
    }
    $crop = array_merge($crop, $_POST);
}

$page_title = "Edit Crop";
$active     = "crops";
require "../includes/header.php";
?>

<div class="max-w-2xl mx-auto">
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
                <select name="crop_category" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= $crop['crop_category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
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
                <select name="recommended_season" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Season --</option>
                    <?php foreach ($seasons as $season): ?>
                        <option value="<?= $season ?>" <?= $crop['recommended_season'] === $season ? 'selected' : '' ?>><?= $season ?></option>
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
