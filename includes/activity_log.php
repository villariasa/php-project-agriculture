<?php
// Audit trail helper. Call from every create / update / delete handler
// AFTER the write succeeds. Appends one row to activity_log.
function logActivity(PDO $pdo, string $action, string $entity, int $entity_id, string $details = "") {
    $sql = "INSERT INTO activity_log (user_id, action, entity, entity_id, details)
            VALUES (:user_id, :action, :entity, :entity_id, :details)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "user_id"   => (int) ($_SESSION["user_id"] ?? 0),
        "action"    => $action,
        "entity"    => $entity,
        "entity_id" => $entity_id,
        "details"   => $details
    ]);
}
