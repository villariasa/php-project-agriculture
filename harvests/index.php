<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/table_helpers.php";
require_once "../config/Database.php";
require_once "../models/Harvest.php";
require_once "../models/Planting.php";
require_once "../models/Lookup.php";

$pdo          = (new Database())->connect();
$harvestModel = new Harvest($pdo);
$lookup       = new Lookup($pdo);

// Sort whitelist: URL key => SQL column (identifiers never come from the URL)
$sort_whitelist = [
    "id"       => "h.harvest_id",
    "farm"     => "f.farm_name",
    "crop"     => "c.crop_name",
    "date"     => "h.harvest_date",
    "quantity" => "h.quantity_kg",
    "price"    => "h.selling_price_per_kg",
    "value"    => "h.quantity_kg * h.selling_price_per_kg",
    "grade"    => "qg.grade_name",
];
[$order, $dir, $sort_key] = resolveSort($sort_whitelist, "id", "desc");

// Record being edited via the Edit slider (?edit=<id> prefills and auto-opens it)
$edit_id     = (int) ($_GET["edit"] ?? 0);
$edit_record = $edit_id > 0 ? $harvestModel->getById($edit_id) : null;

$grade_id   = (int) ($_GET["grade_id"] ?? 0);
$page       = currentPage();
$total_rows = $harvestModel->countAll($grade_id);
$harvests   = $harvestModel->getPage($grade_id, $order, $dir, PER_PAGE, pageOffset($page));

// Data for the filter + Add side card form
$plantings = (new Planting($pdo))->getAll();
$grades    = $lookup->getAll("quality_grades");
$remarks   = $lookup->getAll("harvest_remarks");

$grade_colors = [
    "Grade A" => "bg-green-50 text-green-700",
    "Grade B" => "bg-yellow-50 text-yellow-700",
    "Grade C" => "bg-red-50 text-red-700",
];

$page_title  = "Harvests";
$active      = "harvests";
$breadcrumbs = [["Harvests", null]];
require "../includes/header.php";
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Harvest Records</h1>
        <p class="text-sm text-gray-500"><?= number_format($total_rows) ?> harvest yield record(s)</p>
    </div>
    <button type="button" onclick="openAddCard()"
            class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg inline-flex items-center gap-2 self-start sm:self-auto print:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Add Harvest
    </button>
</div>

<?php require "../includes/alert.php"; ?>

<div class="flex flex-col xl:flex-row gap-6">

<!-- Right rail: filter -->
<aside class="xl:w-80 shrink-0 xl:order-2 print:hidden">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 space-y-4 xl:sticky xl:top-20">
        <form method="GET" class="space-y-2">
            <label class="block text-sm text-gray-500">Filter by quality grade</label>
            <select name="grade_id" onchange="this.form.submit()"
                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                <option value="0">All</option>
                <?php foreach ($grades as $grade): ?>
                    <option value="<?= $grade['id'] ?>" <?= $grade_id === (int) $grade['id'] ? 'selected' : '' ?>><?= htmlspecialchars($grade['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <!-- Mobile sort controls (desktop sorts via the column headers) -->
            <div class="grid grid-cols-2 gap-2 md:hidden pt-1">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Sort by</label>
                    <select name="sort" onchange="this.form.submit()"
                            class="w-full p-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                        <option value="id" <?= $sort_key === 'id' ? 'selected' : '' ?>>ID</option>
                        <option value="date" <?= $sort_key === 'date' ? 'selected' : '' ?>>Harvest date</option>
                        <option value="quantity" <?= $sort_key === 'quantity' ? 'selected' : '' ?>>Quantity</option>
                        <option value="value" <?= $sort_key === 'value' ? 'selected' : '' ?>>Total value</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Order</label>
                    <select name="dir" onchange="this.form.submit()"
                            class="w-full p-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                        <option value="asc" <?= $dir === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                        <option value="desc" <?= $dir === 'DESC' ? 'selected' : '' ?>>Descending</option>
                    </select>
                </div>
            </div>
        </form>
        <p class="text-xs text-gray-400 hidden md:block">Click a column header to sort &middot; click a row for details</p>
        <p class="text-xs text-gray-400 md:hidden">Tap a record for details and actions</p>
    </div>
</aside>

<!-- Primary feed: table -->
<div class="flex-1 min-w-0 xl:order-1">
<div class="bg-white rounded-lg border border-gray-200 shadow-sm">
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
            <tr>
                <?php sortableTh("id", "ID", $sort_key, $dir); ?>
                <?php sortableTh("farm", "Farm", $sort_key, $dir); ?>
                <?php sortableTh("crop", "Crop", $sort_key, $dir); ?>
                <?php sortableTh("date", "Harvest Date", $sort_key, $dir); ?>
                <?php sortableTh("quantity", "Quantity (kg)", $sort_key, $dir, "right"); ?>
                <?php sortableTh("price", "Price/kg", $sort_key, $dir, "right"); ?>
                <?php sortableTh("value", "Total Value", $sort_key, $dir, "right"); ?>
                <?php sortableTh("grade", "Grade", $sort_key, $dir); ?>
                <th class="px-4 py-3 text-left">Remark</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($harvests) === 0): ?>
                <tr>
                    <td colspan="10" class="px-4 py-10">
                        <div class="border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center text-sm text-gray-500">
                            No harvest records found.
                            <button type="button" onclick="openAddCard()" class="text-green-700 font-medium hover:underline">Add the first harvest</button>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($harvests as $row): ?>
                <?php $total_value = $row['quantity_kg'] * $row['selling_price_per_kg']; ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                    onclick="openDetailCard(this)"
                    data-title="Harvest #<?= $row['harvest_id'] ?>"
                    data-edit-url="index.php?edit=<?= $row['harvest_id'] ?>"
                    data-delete-url="delete.php?id=<?= $row['harvest_id'] ?>"
                    data-delete-msg="Are you sure you want to delete harvest #<?= $row['harvest_id'] ?> (<?= htmlspecialchars($row['crop_name'] . ' from ' . $row['farm_name'], ENT_QUOTES) ?>)?"
                    data-detail="<?= htmlspecialchars(json_encode([
                        "ID"                 => $row["harvest_id"],
                        "Farm"               => $row["farm_name"],
                        "Crop"               => $row["crop_name"] . " (" . $row["variety"] . ")",
                        "Harvest Date"       => $row["harvest_date"],
                        "Quantity (kg)"      => number_format($row["quantity_kg"], 2),
                        "Price per kg"       => "PHP " . number_format($row["selling_price_per_kg"], 2),
                        "Total Value"        => "PHP " . number_format($total_value, 2),
                        "Quality Grade"      => $row["quality_grade"],
                        "Remark"             => $row["remark_description"],
                    ]), ENT_QUOTES) ?>">
                    <td class="px-4 py-3"><?= $row['harvest_id'] ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['farm_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['crop_name'] . " (" . $row['variety'] . ")") ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['harvest_date']) ?></td>
                    <td class="px-4 py-3 text-right"><?= number_format($row['quantity_kg'], 2) ?></td>
                    <td class="px-4 py-3 text-right">&#8369;<?= number_format($row['selling_price_per_kg'], 2) ?></td>
                    <td class="px-4 py-3 text-right font-medium text-green-700">&#8369;<?= number_format($total_value, 2) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $grade_colors[$row['quality_grade']] ?? 'bg-gray-100 text-gray-700' ?>">
                            <?= htmlspecialchars($row['quality_grade']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['remark_description']) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-2" onclick="event.stopPropagation()">
                            <a href="index.php?edit=<?= $row['harvest_id'] ?>" title="Edit" class="text-blue-600 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="#" title="Delete"
                                   onclick="openDeleteModal('delete.php?id=<?= $row['harvest_id'] ?>', 'Are you sure you want to delete harvest #<?= $row['harvest_id'] ?> (<?= htmlspecialchars(addslashes($row['crop_name'] . ' from ' . $row['farm_name']), ENT_QUOTES) ?>)?'); return false;"
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

<!-- Mobile card list (same records, tap for details) -->
<div class="md:hidden divide-y divide-gray-100">
    <?php if (count($harvests) === 0): ?>
        <div class="p-4">
            <div class="border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center text-sm text-gray-500">
                No harvest records found.
                <button type="button" onclick="openAddCard()" class="text-green-700 font-medium hover:underline">Add the first harvest</button>
            </div>
        </div>
    <?php endif; ?>
    <?php foreach ($harvests as $row): ?>
        <?php $total_value = $row['quantity_kg'] * $row['selling_price_per_kg']; ?>
        <div class="flex items-center gap-3 px-4 py-3 active:bg-gray-50 cursor-pointer"
             onclick="openDetailCard(this)"
             data-title="Harvest #<?= $row['harvest_id'] ?>"
             data-edit-url="index.php?edit=<?= $row['harvest_id'] ?>"
             data-delete-url="delete.php?id=<?= $row['harvest_id'] ?>"
             data-delete-msg="Are you sure you want to delete harvest #<?= $row['harvest_id'] ?> (<?= htmlspecialchars($row['crop_name'] . ' from ' . $row['farm_name'], ENT_QUOTES) ?>)?"
             data-detail="<?= htmlspecialchars(json_encode([
                 "ID"            => $row["harvest_id"],
                 "Farm"          => $row["farm_name"],
                 "Crop"          => $row["crop_name"] . " (" . $row["variety"] . ")",
                 "Harvest Date"  => $row["harvest_date"],
                 "Quantity (kg)" => number_format($row["quantity_kg"], 2),
                 "Price per kg"  => "PHP " . number_format($row["selling_price_per_kg"], 2),
                 "Total Value"   => "PHP " . number_format($total_value, 2),
                 "Quality Grade" => $row["quality_grade"],
                 "Remark"        => $row["remark_description"],
             ]), ENT_QUOTES) ?>">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium truncate"><?= htmlspecialchars($row['crop_name']) ?> <span class="font-normal text-gray-500">@ <?= htmlspecialchars($row['farm_name']) ?></span></p>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($row['harvest_date']) ?>
                    &middot; <span class="px-1.5 py-0.5 rounded-full text-[11px] font-medium <?= $grade_colors[$row['quality_grade']] ?? 'bg-gray-100 text-gray-700' ?>"><?= htmlspecialchars($row['quality_grade']) ?></span>
                </p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-sm font-medium"><?= number_format($row['quantity_kg'], 2) ?> kg</p>
                <p class="text-xs text-green-700 font-medium">&#8369;<?= number_format($total_value, 2) ?></p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-300 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </div>
    <?php endforeach; ?>
</div>

    <?php paginationBar($page, $total_rows); ?>
</div>
</div>

</div>

<!-- Add Harvest side card -->
<aside id="add-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-lg z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
        <h2 class="text-lg font-semibold">Add Harvest Record</h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="POST" action="create.php" class="flex-1 flex flex-col overflow-y-auto">
        <div class="flex-1 px-5 py-4 space-y-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Planting</label>
                <select name="planting_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Planting --</option>
                    <?php foreach ($plantings as $p): ?>
                        <option value="<?= $p['planting_id'] ?>">
                            <?= htmlspecialchars($p['farm_name'] . " — " . $p['crop_name'] . " (" . $p['planting_date'] . ")") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Harvest Date</label>
                <input type="date" name="harvest_date" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Quantity Harvested (kg)</label>
                <input type="number" step="0.01" min="0.01" name="quantity_kg" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Selling Price per kg (&#8369;)</label>
                <input type="number" step="0.01" min="0.01" name="selling_price_per_kg" required
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Quality Grade</label>
                <select name="grade_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Grade --</option>
                    <?php foreach ($grades as $grade): ?>
                        <option value="<?= $grade['id'] ?>"><?= htmlspecialchars($grade['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Remark</label>
                <select name="remark_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="">-- Select Remark --</option>
                    <?php foreach ($remarks as $rem): ?>
                        <option value="<?= $rem['id'] ?>"><?= htmlspecialchars($rem['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="border-t border-gray-200 px-5 py-4 flex justify-end gap-2">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</button>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg">Save Harvest</button>
        </div>
    </form>
</aside>

<?php if ($edit_record): ?>
<!-- Edit Harvest slider (opens automatically via ?edit=<id>) -->
<aside id="edit-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-lg z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
        <h2 class="text-lg font-semibold">Edit Harvest #<?= $edit_id ?></h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <form method="POST" action="edit.php" class="flex-1 flex flex-col overflow-y-auto">
        <input type="hidden" name="id" value="<?= $edit_id ?>">
        <div class="flex-1 px-5 py-4 space-y-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Planting</label>
                <select name="planting_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <?php foreach ($plantings as $p): ?>
                        <option value="<?= $p['planting_id'] ?>" <?= (int) $edit_record['planting_id'] === (int) $p['planting_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['farm_name'] . " — " . $p['crop_name'] . " (" . $p['planting_date'] . ")") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Harvest Date</label>
                <input type="date" name="harvest_date" required value="<?= htmlspecialchars($edit_record['harvest_date']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Quantity Harvested (kg)</label>
                <input type="number" step="0.01" min="0.01" name="quantity_kg" required value="<?= htmlspecialchars($edit_record['quantity_kg']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Selling Price per kg (&#8369;)</label>
                <input type="number" step="0.01" min="0.01" name="selling_price_per_kg" required value="<?= htmlspecialchars($edit_record['selling_price_per_kg']) ?>"
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Quality Grade</label>
                <select name="grade_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <?php foreach ($grades as $grade): ?>
                        <option value="<?= $grade['id'] ?>" <?= (int) $edit_record['grade_id'] === (int) $grade['id'] ? 'selected' : '' ?>><?= htmlspecialchars($grade['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Remark</label>
                <select name="remark_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <?php foreach ($remarks as $rem): ?>
                        <option value="<?= $rem['id'] ?>" <?= (int) $edit_record['remark_id'] === (int) $rem['id'] ? 'selected' : '' ?>><?= htmlspecialchars($rem['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="border-t border-gray-200 px-5 py-4 flex justify-end gap-2">
            <button type="button" onclick="closeSideCards()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg">Cancel</button>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg">Update Harvest</button>
        </div>
    </form>
</aside>
<?php endif; ?>

<?php require "../includes/sidecard.php"; ?>
<?php require "../includes/confirm_modal.php"; ?>
<?php require "../includes/footer.php"; ?>
