<?php
session_start();

// Already logged in? Go to dashboard.
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once "../config/Database.php";
require_once "../models/User.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = (string) trim($_POST["username"] ?? "");
    $password = (string) ($_POST["password"] ?? "");

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {
        $pdo  = (new Database())->connect();
        $user = (new User($pdo))->findByUsername($username);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header("Location: ../index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Agriculture Information System</title>
    <!-- Tailwind Play CDN serves dynamic JS, so SRI hashes are not supported; required per class standard -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-md">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
        <!-- Logo -->
        <div class="flex flex-col items-center mb-6">
            <div class="bg-green-50 text-green-600 rounded-lg p-3 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800">Agriculture Information System</h1>
            <p class="text-sm text-gray-500">Sign in to your account</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Username</label>
                <input type="text" name="username" required
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">
                Sign In
            </button>
        </form>
    </div>
    <p class="text-center text-xs text-gray-400 mt-4">AP 3 Web Application Development &copy; <?= date("Y") ?></p>
</div>

</body>
</html>
