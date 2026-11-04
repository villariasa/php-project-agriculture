<?php
// Shared handler response: the edit sliders save via fetch() and expect JSON
// (no page reload); a plain form post still gets the classic redirect.
function respondAndExit(bool $ok, string $message, string $redirect) {
    if (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "fetch") {
        header("Content-Type: application/json");
        echo json_encode(["ok" => $ok, "message" => $message]);
        exit;
    }
    header("Location: " . $redirect);
    exit;
}
