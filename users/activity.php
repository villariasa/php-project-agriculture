<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/table_helpers.php";
require_once "../config/Database.php";
require_once "../models/User.php";

// The activity log is an Admin-only page
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=" . urlencode("Only administrators can view the activity log."));
    exit;
}

$pdo   = (new Database())->connect();
$users = (new User($pdo))->getAll();

// Entity filter options come from a fixed whitelist (matches logActivity calls)
$entities = ["farmer", "farm", "crop", "planting", "harvest", "lookup", "user"];

$filter_user_id = (int) ($_GET["user_id"] ?? 0);
$filter_entity  = (string) ($_GET["entity"] ?? "");
if (!in_array($filter_entity, $entities, true)) {
    $filter_entity = "";
}

$where  = [];
$params = [];
if ($filter_user_id > 0) {
    $where[] = "l.user_id = :user_id";
    $params["user_id"] = $filter_user_id;
}
if ($filter_entity !== "") {
    $where[] = "l.entity = :entity";
    $params["entity"] = $filter_entity;
}
$where_sql = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// Count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log l" . $where_sql);
$stmt->execute($params);
$total_rows = (int) $stmt->fetchColumn();

$page = currentPage();
$stmt = $pdo->prepare(
    "SELECT l.*, u.username, u.full_name
     FROM activity_log l
     JOIN users u ON l.user_id = u.user_id"
    . $where_sql .
    " ORDER BY l.log_id DESC
      LIMIT " . (int) PER_PAGE . " OFFSET " . (int) pageOffset($page)
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$action_colors = [
    "create" => "bg-green-50 text-green-700",
    "update" => "bg-blue-50 text-blue-700",
    "delete" => "bg-red-50 text-red-700",
];

$page_title  = "Activity Log";
$active      = "activity";
$breadcrumbs = [["Users", "index.php"], ["Activity Log", null]];
require "../includes/header.php";
?>

<!-- Page header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold">Activity Log</h1>
    <p class="text-sm text-gray-500"><?= number_format($total_rows) ?> recorded action(s) &mdash; every create, update, and delete in the system</p>
</div>

<?php require "../includes/alert.php"; ?>

<!-- Filter toolbar above the log -->
<form method="GET" class="flex flex-wrap gap-2 mb-4 print:hidden">
    <select name="user_id" onchange="this.form.submit()" aria-label="Filter by user"
            class="flex-1 min-w-[160px] sm:flex-none py-2.5 pl-3 pr-8 text-sm bg-white border border-gray-300 rounded-full shadow-sm text-gray-700
                   focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        <option value="0">All users</option>
        <?php foreach ($users as $u): ?>
            <option value="<?= $u['user_id'] ?>" <?= $filter_user_id === (int) $u['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="entity" onchange="this.form.submit()" aria-label="Filter by record type"
            class="flex-1 min-w-[160px] sm:flex-none py-2.5 pl-3 pr-8 text-sm bg-white border border-gray-300 rounded-full shadow-sm text-gray-700
                   focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
        <option value="">All types</option>
        <?php foreach ($entities as $e): ?>
            <option value="<?= $e ?>" <?= $filter_entity === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($filter_user_id > 0 || $filter_entity !== ""): ?>
        <a href="activity.php" class="self-center text-sm text-gray-500 hover:text-gray-700 px-2">Clear filters</a>
    <?php endif; ?>
</form>

<div class="bg-white rounded-lg border border-gray-200 shadow-sm">
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
            <tr>
                <th class="px-4 py-3 text-left">When</th>
                <th class="px-4 py-3 text-left">User</th>
                <th class="px-4 py-3 text-left">Action</th>
                <th class="px-4 py-3 text-left">Record</th>
                <th class="px-4 py-3 text-left">Details</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr>
                    <td colspan="5" class="px-4 py-10">
                        <div class="border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center text-sm text-gray-500">
                            No activity recorded yet. Actions appear here as records are created, updated, or deleted.
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?= htmlspecialchars(substr($row['created_at'], 0, 16)) ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['username']) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $action_colors[$row['action']] ?? 'bg-gray-100 text-gray-700' ?>">
                            <?= htmlspecialchars($row['action']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3"><?= htmlspecialchars(ucfirst($row['entity'])) ?> #<?= $row['entity_id'] ?></td>
                    <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($row['details']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Mobile card list -->
<div class="md:hidden divide-y divide-gray-100">
    <?php if (count($rows) === 0): ?>
        <div class="p-4">
            <div class="border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center text-sm text-gray-500">
                No activity recorded yet.
            </div>
        </div>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
        <div class="px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium truncate"><?= htmlspecialchars($row['username']) ?>
                    <span class="font-normal text-gray-500"><?= htmlspecialchars(ucfirst($row['entity'])) ?> #<?= $row['entity_id'] ?></span>
                </p>
                <span class="px-2 py-1 rounded-full text-xs font-medium shrink-0 <?= $action_colors[$row['action']] ?? 'bg-gray-100 text-gray-700' ?>">
                    <?= htmlspecialchars($row['action']) ?>
                </span>
            </div>
            <?php if ($row['details'] !== ""): ?>
                <p class="text-xs text-gray-500 mt-0.5 truncate"><?= htmlspecialchars($row['details']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars(substr($row['created_at'], 0, 16)) ?></p>
        </div>
    <?php endforeach; ?>
</div>

    <?php paginationBar($page, $total_rows); ?>
</div>

<?php require "../includes/footer.php"; ?>
