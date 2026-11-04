<?php
// Toast notifications (bottom-right, auto-dismiss).
// Sources: ?msg= / ?error= query params from redirects, or page-local
// $msg / $error variables set before this include (edit/settings forms).
$toast_success = (string) ($_GET["msg"] ?? "");
$toast_error   = (string) ($_GET["error"] ?? "");
if ($toast_success === "" && isset($msg) && $msg !== "") {
    $toast_success = (string) $msg;
}
if ($toast_error === "" && isset($error) && $error !== "") {
    $toast_error = (string) $error;
}
?>
<?php if (!defined("TOAST_INCLUDED")): define("TOAST_INCLUDED", true); ?>
<!-- Toasts: top-center on all screens -->
<div id="toast-stack" class="fixed top-4 left-1/2 -translate-x-1/2 z-[70] w-80 max-w-[calc(100vw-2rem)] space-y-2 print:hidden"></div>

<script>
function showToast(message, type) {
    var stack = document.getElementById("toast-stack");
    var toast = document.createElement("div");
    var accent = type === "error" ? "border-red-500" : "border-green-600";
    var iconColor = type === "error" ? "text-red-500" : "text-green-600";
    var iconPath = type === "error"
        ? "M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        : "M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z";

    toast.className = "flex items-start gap-2 bg-white border border-gray-200 border-l-4 " + accent +
        " rounded-lg shadow-sm px-4 py-3 text-sm text-gray-700 transition-opacity duration-300";
    toast.innerHTML =
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0 ' + iconColor + '">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="' + iconPath + '" />' +
        '</svg>' +
        '<span class="flex-1"></span>' +
        '<button type="button" class="text-gray-400 hover:text-gray-600" aria-label="Dismiss">' +
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">' +
                '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />' +
            '</svg>' +
        '</button>';
    toast.querySelector("span").textContent = message;

    var removeToast = function () {
        toast.classList.add("opacity-0");
        setTimeout(function () { toast.remove(); }, 300);
    };
    toast.querySelector("button").addEventListener("click", removeToast);
    stack.appendChild(toast);
    setTimeout(removeToast, 4000);
}
</script>
<?php endif; ?>

<?php if ($toast_success !== "" || $toast_error !== ""): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    <?php if ($toast_success !== ""): ?>
    showToast(<?= json_encode($toast_success) ?>, "success");
    <?php endif; ?>
    <?php if ($toast_error !== ""): ?>
    showToast(<?= json_encode($toast_error) ?>, "error");
    <?php endif; ?>
});
</script>
<?php endif; ?>
