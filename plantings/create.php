<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Planting.php";
require_once "../models/Farm.php";
require_once "../models/Crop.php";

$pdo           = (new Database())->connect();
$plantingModel = new Planting($pdo);
$farms         = (new Farm($pdo))->getAll();
$crops         = (new Crop($pdo))->getAll();
$error         = "";

$statuses = ["Planted", "Growing", "Harvested"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $farm_id               = (int) $_POST["farm_id"];
    $crop_id               = (int) $_POST["crop_id"];
    $planting_date         = (string) trim($_POST["planting_date"]);
    $area_planted          = (float) $_POST["area_planted"];
    $seed_quantity         = (float) $_POST["seed_quantity"];
    $expected_harvest_date = (string) trim($_POST["expected_harvest_date"]);
    $status                = (string) trim($_POST["status"]);

    if ($farm_id <= 0 || $crop_id <= 0 || $planting_date === "" || $expected_harvest_date === "" || $area_planted <= 0 || $seed_quantity <= 0) {
        $error = "All fields are required and amounts must be greater than zero.";
    } elseif ($expected_harvest_date <= $planting_date) {
        $error = "Expected harvest date must be after the planting date.";
    } elseif (!in_array($status, $statuses)) {
        $error = "Invalid status selected.";
    } else {
        $plantingModel->create($farm_id, $crop_id, $planting_date, $area_planted, $seed_quantity, $expected_harvest_date, $status);
        header("Location: index.php?msg=" . urlencode("Planting record added successfully."));
        exit;
    }
}

$page_title = "Add Planting";
$active     = "plantings";
require "../includes/header.php";
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Add Planting Record</h1>

    <?php if ($error !== ""): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Farm</label>
                <select name="farm_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Farm --</option>
                    <?php foreach ($farms as $f): ?>
                        <option value="<?= $f['farm_id'] ?>" <?= (int) ($_POST['farm_id'] ?? 0) === (int) $f['farm_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['farm_name'] . " — " . $f['owner_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Crop</label>
                <select name="crop_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Crop --</option>
                    <?php foreach ($crops as $c): ?>
                        <option value="<?= $c['crop_id'] ?>" <?= (int) ($_POST['crop_id'] ?? 0) === (int) $c['crop_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['crop_name'] . " (" . $c['variety'] . ")") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Planting Date</label>
                <input type="date" name="planting_date" required value="<?= htmlspecialchars($_POST['planting_date'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Expected Harvest Date</label>
                <input type="date" name="expected_harvest_date" required value="<?= htmlspecialchars($_POST['expected_harvest_date'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Area Planted (ha)</label>
                <input type="number" step="0.01" min="0.01" name="area_planted" required value="<?= htmlspecialchars($_POST['area_planted'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Seed Quantity (kg)</label>
                <input type="number" step="0.01" min="0.01" name="seed_quantity" required value="<?= htmlspecialchars($_POST['seed_quantity'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Status</label>
                <select name="status" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($_POST['status'] ?? 'Planted') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <a href="index.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Save Planting</button>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
