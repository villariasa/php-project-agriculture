<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Farm.php";
require_once "../models/Farmer.php";
require_once "../models/Lookup.php";

$pdo       = (new Database())->connect();
$farmModel = new Farm($pdo);
$farmers   = (new Farmer($pdo))->getAll();
$lookup    = new Lookup($pdo);
$error     = "";

$soil_types       = $lookup->getAll("soil_types");
$irrigation_types = $lookup->getAll("irrigation_types");

$farm_id = (int) ($_GET["id"] ?? 0);
$farm    = $farmModel->getById($farm_id);

if (!$farm) {
    header("Location: index.php?error=" . urlencode("Farm not found."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $farmer_id          = (int) $_POST["farmer_id"];
    $farm_name          = (string) trim($_POST["farm_name"]);
    $location           = (string) trim($_POST["location"]);
    $area_hectares      = (float) $_POST["area_hectares"];
    $soil_type_id       = (int) $_POST["soil_type_id"];
    $irrigation_type_id = (int) $_POST["irrigation_type_id"];

    if ($farmer_id <= 0 || $farm_name === "" || $location === "" || $area_hectares <= 0 || $soil_type_id <= 0 || $irrigation_type_id <= 0) {
        $error = "All fields are required and area must be greater than zero.";
    } else {
        $farmModel->update($farm_id, $farmer_id, $farm_name, $location, $area_hectares, $soil_type_id, $irrigation_type_id);
        header("Location: index.php?msg=" . urlencode("Farm updated successfully."));
        exit;
    }
    $farm = array_merge($farm, $_POST);
}

$page_title = "Edit Farm";
$active     = "farms";
require "../includes/header.php";
?>

<div class="max-w-3xl mx-auto">
    <a href="index.php" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-700 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Back to list
    </a>
    <h1 class="text-2xl font-bold mb-6">Edit Farm #<?= $farm_id ?></h1>

    <?php if ($error !== ""): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-500 mb-1">Owner (Farmer)</label>
                <select name="farmer_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Farmer --</option>
                    <?php foreach ($farmers as $fm): ?>
                        <option value="<?= $fm['farmer_id'] ?>" <?= (int) $farm['farmer_id'] === (int) $fm['farmer_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fm['first_name'] . " " . $fm['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Farm Name</label>
                <input type="text" name="farm_name" required value="<?= htmlspecialchars($farm['farm_name']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Location</label>
                <input type="text" name="location" required value="<?= htmlspecialchars($farm['location']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Area (Hectares)</label>
                <input type="number" step="0.01" min="0.01" name="area_hectares" required value="<?= htmlspecialchars($farm['area_hectares']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Soil Type</label>
                <select name="soil_type_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Soil Type --</option>
                    <?php foreach ($soil_types as $soil): ?>
                        <option value="<?= $soil['id'] ?>" <?= (int) $farm['soil_type_id'] === (int) $soil['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($soil['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Irrigation Type</label>
                <select name="irrigation_type_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Irrigation --</option>
                    <?php foreach ($irrigation_types as $irr): ?>
                        <option value="<?= $irr['id'] ?>" <?= (int) $farm['irrigation_type_id'] === (int) $irr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($irr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <a href="index.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Update Farm</button>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
