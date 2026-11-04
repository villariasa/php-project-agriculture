<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/User.php";

$pdo       = (new Database())->connect();
$userModel = new User($pdo);
$error     = "";
$msg       = "";

$user_id = (int) $_SESSION['user_id'];
$user    = $userModel->getById($user_id);

if (!$user) {
    header("Location: logout.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name        = (string) trim($_POST["full_name"]);
    $current_password = (string) ($_POST["current_password"] ?? "");
    $new_password     = (string) ($_POST["new_password"] ?? "");
    $confirm_password = (string) ($_POST["confirm_password"] ?? "");

    // Current password is always required to save profile changes
    $full_user = $userModel->findByUsername($user['username']);

    if ($full_name === "") {
        $error = "Full name is required.";
    } elseif (!password_verify($current_password, $full_user['password_hash'])) {
        $error = "Your current password is incorrect.";
    } elseif ($new_password !== "" && strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } else {
        $userModel->update($user_id, $full_name, $user['role'], $new_password);
        logActivity($pdo, "update", "user", $user_id, "Updated own profile");
        $_SESSION['full_name'] = $full_name;
        $msg  = $new_password !== "" ? "Profile and password updated." : "Profile updated.";
        $user = $userModel->getById($user_id);
    }
}

$page_title  = "My Profile";
$active      = "profile";
$breadcrumbs = [["My Profile", null]];
require "../includes/header.php";
?>

<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold mb-1">My Profile</h1>
    <p class="text-sm text-gray-500 mb-6">Update your display name or change your password.</p>

    <?php require "../includes/alert.php"; ?>

    <form method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Username</label>
                <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled
                       class="w-full p-2 border border-gray-200 bg-gray-50 text-gray-500 rounded-lg">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Role</label>
                <input type="text" value="<?= htmlspecialchars(ucfirst($user['role'])) ?>" disabled
                       class="w-full p-2 border border-gray-200 bg-gray-50 text-gray-500 rounded-lg">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-500 mb-1">Full Name</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
        </div>

        <div class="border-t border-gray-100 mt-6 pt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <h2 class="font-semibold text-sm">Change Password</h2>
                <p class="text-xs text-gray-400">Leave the new password fields blank to keep your current password.</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">New Password</label>
                <input type="password" name="new_password" minlength="6"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Confirm New Password</label>
                <input type="password" name="confirm_password" minlength="6"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
        </div>

        <div class="border-t border-gray-100 mt-6 pt-6">
            <label class="block text-sm text-gray-500 mb-1">Current Password <span class="text-red-500">*</span></label>
            <input type="password" name="current_password" required
                   class="w-full md:w-1/2 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            <p class="text-xs text-gray-400 mt-1">Required to save any change.</p>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <a href="../index.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Save Changes</button>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
