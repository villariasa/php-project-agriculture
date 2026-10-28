<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Crop.php";

$pdo       = (new Database())->connect();
$cropModel = new Crop($pdo);
$crops     = $cropModel->getAll();

$page_title = "Crops";
$active     = "crops";
require "../includes/header.php";
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Crop Catalog</h1>
    <a href="create.php"
       class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg inline-flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Add Crop
    </a>
</div>

<?php require "../includes/alert.php"; ?>

<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
            <tr>
                <th class="px-4 py-3 text-left">ID</th>
                <th class="px-4 py-3 text-left">Crop Name</th>
                <th class="px-4 py-3 text-left">Variety</th>
                <th class="px-4 py-3 text-left">Category</th>
                <th class="px-4 py-3 text-right">Growing Period (days)</th>
                <th class="px-4 py-3 text-left">Season</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($crops) === 0): ?>
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">No crops found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($crops as $row): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3"><?= $row['crop_id'] ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['crop_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['variety']) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            <?= htmlspecialchars($row['category_name']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right"><?= $row['growing_period_days'] ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['season_name']) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-2">
                            <a href="edit.php?id=<?= $row['crop_id'] ?>" title="Edit" class="text-blue-600 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="#" title="Delete"
                                   onclick="openDeleteModal('delete.php?id=<?= $row['crop_id'] ?>', 'Are you sure you want to delete crop <?= htmlspecialchars(addslashes($row['crop_name'] . ' (' . $row['variety'] . ')'), ENT_QUOTES) ?>?'); return false;"
                                   class="text-red-600 hover:text-red-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require "../includes/confirm_modal.php"; ?>
<?php require "../includes/footer.php"; ?>
