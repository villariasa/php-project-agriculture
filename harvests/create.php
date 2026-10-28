<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Harvest.php";
require_once "../models/Planting.php";
require_once "../models/Lookup.php";

$pdo           = (new Database())->connect();
$harvestModel  = new Harvest($pdo);
$plantingModel = new Planting($pdo);
$lookup        = new Lookup($pdo);
$plantings     = $plantingModel->getAll();
$remarks       = $lookup->getAll("harvest_remarks");
$grades        = $lookup->getAll("quality_grades");
$error         = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $planting_id          = (int) $_POST["planting_id"];
    $harvest_date         = (string) trim($_POST["harvest_date"]);
    $quantity_kg          = (float) $_POST["quantity_kg"];
    $selling_price_per_kg = (float) $_POST["selling_price_per_kg"];
    $grade_id             = (int) $_POST["grade_id"];
    $remark_id            = (int) $_POST["remark_id"];

    if ($planting_id <= 0 || $harvest_date === "" || $quantity_kg <= 0 || $selling_price_per_kg <= 0 || $grade_id <= 0 || $remark_id <= 0) {
        $error = "All fields are required and amounts must be greater than zero.";
    } else {
        $harvestModel->create($planting_id, $harvest_date, $quantity_kg, $selling_price_per_kg, $grade_id, $remark_id);
        // Mark the related planting as Harvested
        $plantingModel->updateStatusByName($planting_id, "Harvested");
        header("Location: index.php?msg=" . urlencode("Harvest record added successfully."));
        exit;
    }
}

$page_title = "Add Harvest";
$active     = "harvests";
require "../includes/header.php";
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Add Harvest Record</h1>

    <?php if ($error !== ""): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-500 mb-1">Planting</label>
                <select name="planting_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Planting --</option>
                    <?php foreach ($plantings as $p): ?>
                        <option value="<?= $p['planting_id'] ?>" <?= (int) ($_POST['planting_id'] ?? 0) === (int) $p['planting_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['farm_name'] . " — " . $p['crop_name'] . " (" . $p['planting_date'] . ")") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Harvest Date</label>
                <input type="date" name="harvest_date" required value="<?= htmlspecialchars($_POST['harvest_date'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Quantity Harvested (kg)</label>
                <input type="number" step="0.01" min="0.01" name="quantity_kg" required value="<?= htmlspecialchars($_POST['quantity_kg'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Selling Price per kg (&#8369;)</label>
                <input type="number" step="0.01" min="0.01" name="selling_price_per_kg" required value="<?= htmlspecialchars($_POST['selling_price_per_kg'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Quality Grade</label>
                <select name="grade_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Grade --</option>
                    <?php foreach ($grades as $grade): ?>
                        <option value="<?= $grade['id'] ?>" <?= (int) ($_POST['grade_id'] ?? 0) === (int) $grade['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($grade['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-500 mb-1">Remark</label>
                <select name="remark_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Remark --</option>
                    <?php foreach ($remarks as $rem): ?>
                        <option value="<?= $rem['id'] ?>" <?= (int) ($_POST['remark_id'] ?? 0) === (int) $rem['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rem['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <a href="index.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Save Harvest</button>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
