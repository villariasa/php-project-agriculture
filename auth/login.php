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
$assets_url = "https://pub-61c51da16a4c4a07a98e2b704e994353.r2.dev/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Agriculture Information System</title>
    <!-- Tab Favicon -->
    <link rel="icon" type="image/png" href="../assets/tab-icon.png">
    <link rel="shortcut icon" href="../favicon.ico">
    <link rel="apple-touch-icon" href="../assets/tab-icon.png">

    <!-- Tailwind Play CDN serves dynamic JS, so SRI hashes are not supported; required per class standard -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- NProgress: slim top loading bar while signing in / navigating -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css"
          integrity="sha384-QUGa0w7S89w/V+840rnBtrQYyUEd1VHb88E0/8vc9FvgRy7b7wHlk8S+o0bNJcv0" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"
            integrity="sha384-WVrcwN/kiINFnwTi170GvMPVLHVBao1WfcXL/BZAK3VaUaaWX0OOsxMgCiFmrIb1" crossorigin="anonymous"></script>
    <style>
        #nprogress .bar { background: #22a447 !important; height: 3px !important; }
        #nprogress .peg { box-shadow: 0 0 10px #22a447, 0 0 5px #22a447 !important; }
    </style>
    <script>
        if (window.NProgress) {
            NProgress.configure({ showSpinner: false, trickleSpeed: 120 });
            window.addEventListener("beforeunload", function () { NProgress.start(); });
        }
    </script>

    <script>
        tailwind.config = {
            theme: {
                fontFamily: {
                    sans: ["system-ui", "-apple-system", "Segoe UI", "Roboto", "Helvetica Neue", "Arial", "sans-serif"],
                },
            },
        };
    </script>
    <style>
        /* Life layer: same layout, just motion. Disabled for reduced-motion users. */
        @keyframes fade-up   { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes logo-drop { 0% { opacity: 0; transform: translateY(-20px) scale(.85); } 60% { opacity: 1; transform: translateY(3px) scale(1.03); } 100% { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes shake {
            10%, 90%      { transform: translateX(-1px); }
            20%, 80%      { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60%      { transform: translateX(4px); }
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .fade-up   { opacity: 0; animation: fade-up .45s ease-out forwards; }
        .delay-1   { animation-delay: .1s; }
        .delay-2   { animation-delay: .2s; }
        .delay-3   { animation-delay: .3s; }
        .logo-drop { animation: logo-drop .55s cubic-bezier(.2, .8, .3, 1) both; }
        .shake     { animation: shake .45s ease-in-out; }
        .spinner   { animation: spin .7s linear infinite; }

        input { transition: border-color .15s ease, box-shadow .15s ease; }

        /* Cross-document View Transitions: page loads cross-fade (supported browsers) */
        @view-transition { navigation: auto; }

        /* Soft pastel "rainbow" glow over a near-white canvas (per reference) */
        body {
            background:
                radial-gradient(42rem 42rem at 18% 22%, rgb(167 139 250 / .22), transparent 60%),
                radial-gradient(46rem 46rem at 82% 28%, rgb(134 239 172 / .26), transparent 60%),
                radial-gradient(40rem 40rem at 28% 85%, rgb(249 168 212 / .20), transparent 60%),
                radial-gradient(36rem 36rem at 86% 80%, rgb(252 211 77 / .16), transparent 60%),
                #fafafa;
        }

        @media (prefers-reduced-motion: reduce) {
            .fade-up, .logo-drop, .shake, .spinner { animation: none; opacity: 1; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-md">
    <div id="login-card" class="bg-white rounded-2xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.08)] overflow-hidden fade-up <?= $error !== "" ? "shake" : "" ?>">

        <!-- Card header: white, per reference -->
        <div class="px-8 pt-8 pb-6 text-center border-b border-gray-100">
            <img src="../assets/image-cutout.png" alt="AIS logo" class="w-14 h-14 object-contain mx-auto mb-4 logo-drop">
            <h1 class="text-2xl font-bold text-gray-900 fade-up delay-1">Sign In</h1>
            <p class="text-sm text-gray-500 mt-1.5 fade-up delay-1">Agriculture Information System &mdash; sign in to your account.</p>
        </div>

        <!-- Card body: light gray form section, per reference -->
        <div class="bg-gray-50 px-8 py-7">

        <?php if ($error !== ""): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-5">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="login-form" class="space-y-5">
            <div class="fade-up delay-2">
                <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 mb-1.5">Username</label>
                <input type="text" name="username" required autocomplete="username" placeholder="Your username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-lg shadow-sm placeholder-gray-400
                              focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div class="fade-up delay-2">
                <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Your password"
                           class="w-full px-3.5 py-2.5 pr-10 bg-white border border-gray-200 rounded-lg shadow-sm placeholder-gray-400
                                  focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <button type="button" id="toggle-password" aria-label="Show password" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <p id="caps-warning" class="hidden text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-1.5 mt-2">
                    Caps Lock is on.
                </p>
            </div>
            <button type="submit" id="login-button"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg fade-up delay-3
                           inline-flex items-center justify-center gap-2
                           transition transform hover:-translate-y-0.5 hover:shadow-md active:translate-y-0 active:shadow-none
                           disabled:opacity-70 disabled:cursor-not-allowed disabled:transform-none">
                <svg id="login-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 spinner hidden">
                    <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                </svg>
                <span id="login-label">Sign In</span>
            </button>
        </form>

        </div><!-- /card body -->
    </div>
    <p class="text-center text-xs text-gray-400 mt-5 fade-up delay-3">AP 3 Web Application Development &copy; <?= date("Y") ?></p>
</div>

<script>
    // Show / hide password
    var passwordField = document.getElementById("password");
    document.getElementById("toggle-password").addEventListener("click", function () {
        var showing = passwordField.type === "text";
        passwordField.type = showing ? "password" : "text";
        document.getElementById("eye-open").classList.toggle("hidden", !showing);
        document.getElementById("eye-closed").classList.toggle("hidden", showing);
        this.setAttribute("aria-label", showing ? "Show password" : "Hide password");
        passwordField.focus();
    });

    // Caps Lock warning
    passwordField.addEventListener("keyup", function (event) {
        if (typeof event.getModifierState === "function") {
            document.getElementById("caps-warning").classList.toggle("hidden", !event.getModifierState("CapsLock"));
        }
    });
    passwordField.addEventListener("blur", function () {
        document.getElementById("caps-warning").classList.add("hidden");
    });

    // Loading state on submit (button never feels dead)
    document.getElementById("login-form").addEventListener("submit", function () {
        var button = document.getElementById("login-button");
        button.disabled = true;
        document.getElementById("login-spinner").classList.remove("hidden");
        document.getElementById("login-label").textContent = "Signing in...";
    });
</script>

</body>
</html>
