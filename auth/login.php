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

// Time-based greeting (server time)
$hour = (int) date("G");
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
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
        /* ---------- Scene ---------- */
        @keyframes drift-a { from { transform: translate(0, 0) scale(1); } to { transform: translate(40px, -30px) scale(1.08); } }
        @keyframes drift-b { from { transform: translate(0, 0) scale(1.05); } to { transform: translate(-50px, 25px) scale(0.95); } }
        @keyframes drift-c { from { transform: translate(0, 0) rotate(0deg); } to { transform: translate(25px, 35px) rotate(8deg); } }

        .blob { position: absolute; border-radius: 9999px; filter: blur(70px); opacity: .5; will-change: transform; }
        .blob-a { animation: drift-a 16s ease-in-out infinite alternate; }
        .blob-b { animation: drift-b 20s ease-in-out infinite alternate; }
        .blob-c { animation: drift-c 24s ease-in-out infinite alternate; }

        /* Parallax layers follow the pointer (JS sets --px / --py) */
        .parallax { transform: translate3d(calc(var(--px, 0) * 1px), calc(var(--py, 0) * 1px), 0); transition: transform .3s ease-out; }

        /* ---------- Card & elements ---------- */
        @keyframes card-pop  { from { opacity: 0; transform: translateY(18px) scale(.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes logo-drop { 0% { opacity: 0; transform: translateY(-26px) scale(.8); } 60% { opacity: 1; transform: translateY(4px) scale(1.04); } 100% { transform: translateY(0) scale(1); } }
        @keyframes fade-up   { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes shake {
            10%, 90%      { transform: translateX(-1px); }
            20%, 80%      { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60%      { transform: translateX(4px); }
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .card-pop  { animation: card-pop .5s cubic-bezier(.2, .8, .3, 1) both; }
        .logo-drop { animation: logo-drop .6s cubic-bezier(.2, .8, .3, 1) .15s both; }
        .fade-up   { opacity: 0; animation: fade-up .45s ease-out forwards; }
        .delay-1 { animation-delay: .25s; }
        .delay-2 { animation-delay: .35s; }
        .delay-3 { animation-delay: .45s; }
        .shake   { animation: shake .45s ease-in-out; }
        .spinner { animation: spin .7s linear infinite; }

        /* ---------- Floating labels ---------- */
        .float-field input {
            width: 100%;
            padding: 1.35rem 2.75rem 0.45rem 2.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.9rem;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
            background: #fff;
        }
        .float-field input::placeholder { color: transparent; }
        .float-field input:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgb(34 197 94 / .25); }
        .float-field label {
            position: absolute; left: 2.75rem; top: 50%;
            transform: translateY(-50%);
            color: #9ca3af; font-size: .9rem; pointer-events: none;
            transition: all .18s ease;
        }
        .float-field input:focus + label,
        .float-field input:not(:placeholder-shown) + label {
            top: .62rem; transform: none; font-size: .68rem;
            letter-spacing: .03em; text-transform: uppercase; color: #16a34a; font-weight: 600;
        }

        @media (prefers-reduced-motion: reduce) {
            .blob-a, .blob-b, .blob-c, .card-pop, .logo-drop, .fade-up, .shake, .spinner { animation: none; opacity: 1; }
            .parallax { transform: none; transition: none; }
        }
    </style>
</head>
<body class="bg-green-950 min-h-screen flex items-center justify-center px-4 py-10 relative overflow-hidden">

<!-- Drifting background scene (decorative) -->
<div class="absolute inset-0 pointer-events-none" aria-hidden="true">
    <div class="parallax absolute inset-0" data-depth="18">
        <div class="blob blob-a bg-green-600 w-[32rem] h-[32rem] -top-32 -left-24"></div>
    </div>
    <div class="parallax absolute inset-0" data-depth="30">
        <div class="blob blob-b bg-emerald-500 w-[26rem] h-[26rem] bottom-[-8rem] right-[-6rem]"></div>
    </div>
    <div class="parallax absolute inset-0" data-depth="10">
        <div class="blob blob-c bg-green-800 w-[22rem] h-[22rem] top-1/3 left-1/2"></div>
    </div>
    <!-- Faint field lines -->
    <svg class="absolute bottom-0 left-0 w-full text-green-900 opacity-60" viewBox="0 0 1440 220" fill="none" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 160 C 300 90, 600 220, 900 150 S 1300 120, 1440 170 L 1440 220 L 0 220 Z" fill="currentColor"/>
        <path d="M0 195 C 350 140, 700 240, 1050 180 S 1350 160, 1440 200 L 1440 220 L 0 220 Z" fill="currentColor" opacity=".7"/>
    </svg>
</div>

<!-- Card -->
<div class="relative w-full max-w-md">
    <div id="login-card" class="bg-white rounded-3xl shadow-2xl p-8 sm:p-10 card-pop <?= $error !== "" ? "shake" : "" ?>">

        <!-- Logo badge -->
        <div class="flex justify-center -mt-20 mb-5">
            <div class="logo-drop bg-white rounded-2xl shadow-lg border border-gray-100 p-3">
                <img src="../assets/image-cutout.png" alt="AIS logo" class="w-14 h-14 object-contain">
            </div>
        </div>

        <div class="text-center mb-7 fade-up delay-1">
            <h1 class="text-2xl font-bold text-gray-800"><?= $greeting ?></h1>
            <p class="text-sm text-gray-500 mt-1">Sign in to the Agriculture Information System</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="flex items-start gap-2 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-5">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="login-form" class="space-y-4">

            <div class="float-field relative fade-up delay-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" id="user-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <input type="text" name="username" id="username" required autofocus autocomplete="username"
                       placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <label for="username">Username</label>
            </div>

            <div class="fade-up delay-2">
                <div class="float-field relative">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" id="lock-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Password">
                    <label for="password">Password</label>
                    <button type="button" id="toggle-password" aria-label="Show password" tabindex="-1"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 z-10">
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
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-3 rounded-xl
                           inline-flex items-center justify-center gap-2 fade-up delay-3
                           transition transform hover:-translate-y-0.5 hover:shadow-lg hover:shadow-green-600/30
                           active:translate-y-0 active:shadow-none
                           disabled:opacity-70 disabled:cursor-not-allowed disabled:transform-none">
                <svg id="login-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 spinner hidden">
                    <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                </svg>
                <span id="login-label">Sign In</span>
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-green-200/80 mt-6 fade-up delay-3">
        Agriculture Information System &middot; AP 3 Web Application Development &copy; <?= date("Y") ?>
    </p>
</div>

<script>
    // Pointer parallax: background layers drift gently toward the cursor
    var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (!reducedMotion) {
        var layers = document.querySelectorAll(".parallax");
        document.addEventListener("mousemove", function (event) {
            var x = (event.clientX / window.innerWidth) - 0.5;
            var y = (event.clientY / window.innerHeight) - 0.5;
            layers.forEach(function (layer) {
                var depth = parseFloat(layer.dataset.depth || 10);
                layer.style.setProperty("--px", (x * depth).toFixed(1));
                layer.style.setProperty("--py", (y * depth).toFixed(1));
            });
        });
    }

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

    // Focus tints the field icons green
    ["username", "password"].forEach(function (id, index) {
        var icon = document.getElementById(index === 0 ? "user-icon" : "lock-icon");
        var field = document.getElementById(id);
        field.addEventListener("focus", function () { icon.classList.replace("text-gray-400", "text-green-600"); });
        field.addEventListener("blur", function () { icon.classList.replace("text-green-600", "text-gray-400"); });
    });

    // Loading state on submit
    document.getElementById("login-form").addEventListener("submit", function () {
        var button = document.getElementById("login-button");
        button.disabled = true;
        document.getElementById("login-spinner").classList.remove("hidden");
        document.getElementById("login-label").textContent = "Signing in...";
    });
</script>

</body>
</html>
