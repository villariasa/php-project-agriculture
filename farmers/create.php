<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Farmer.php";

$pdo         = (new Database())->connect();
$farmerModel = new Farmer($pdo);
$error       = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name     = (string) trim($_POST["first_name"]);
    $last_name      = (string) trim($_POST["last_name"]);
    $contact_number = (string) trim($_POST["contact_number"]);
    $address        = (string) trim($_POST["address"]);

    if ($first_name === "" || $last_name === "" || $contact_number === "" || $address === "") {
        $error = "All fields are required.";
    } else {
        $farmerModel->create($first_name, $last_name, $contact_number, $address);
        header("Location: index.php?msg=" . urlencode("Farmer added successfully."));
        exit;
    }
}

$page_title = "Add Farmer";
$active     = "farmers";
require "../includes/header.php";
?>

<div class="max-w-3xl mx-auto">
    <a href="index.php" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-700 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Back to list
    </a>
    <h1 class="text-2xl font-bold mb-6">Add Farmer</h1>

    <?php if ($error !== ""): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">First Name</label>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Last Name</label>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Contact Number</label>
                <input type="text" name="contact_number" required value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Address</label>
                <input type="text" name="address" required value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <a href="index.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Save Farmer</button>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
