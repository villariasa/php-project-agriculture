<?php
// Shared right side card: record-details panel (filled from the clicked
// row's data-detail JSON) + open/close logic reused by each module's
// Add side card (a per-module <aside id="add-card"> in index.php).
// Delete inside the details card is admin-only and reuses confirm_modal.php.
?>
<div id="sidecard-overlay" class="fixed inset-0 bg-black/30 z-40 hidden print:hidden" onclick="closeSideCards()"></div>

<!-- Bottom slider on mobile, right slide-over on md+ -->
<aside id="detail-card" class="fixed inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl border-t border-gray-200 md:inset-x-auto md:left-auto md:top-0 md:right-0 md:bottom-0 md:h-full md:max-h-none md:max-w-md md:rounded-none md:border-t-0 md:border-l bg-white shadow-lg z-50 hidden flex-col print:hidden">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
        <h2 id="detail-card-title" class="text-lg font-semibold">Details</h2>
        <button type="button" onclick="closeSideCards()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <div class="flex-1 overflow-y-auto px-5 py-2">
        <dl id="detail-card-body" class="divide-y divide-gray-100"></dl>
    </div>
    <div class="border-t border-gray-200 px-5 py-4 flex justify-end gap-2">
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <button type="button" id="detail-card-delete"
                    class="bg-white border border-red-300 text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg">
                Delete
            </button>
        <?php endif; ?>
        <a href="#" id="detail-card-edit"
           class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg">
            Edit
        </a>
    </div>
</aside>

<script>
function showSideCard(card) {
    document.getElementById("sidecard-overlay").classList.remove("hidden");
    card.classList.remove("hidden");
    card.classList.add("flex");
}

function closeSideCards() {
    document.getElementById("sidecard-overlay").classList.add("hidden");
    ["detail-card", "add-card", "edit-card"].forEach(function (id) {
        var card = document.getElementById(id);
        if (card) {
            card.classList.add("hidden");
            card.classList.remove("flex");
        }
    });
}

function openEditCard() {
    closeSideCards();
    var card = document.getElementById("edit-card");
    if (card) {
        showSideCard(card);
        var firstField = card.querySelector("input:not([type=hidden]):not([disabled]), select");
        if (firstField) {
            firstField.focus();
        }
    }
}

function openDetailCard(row) {
    var data = JSON.parse(row.dataset.detail);
    var body = document.getElementById("detail-card-body");
    body.innerHTML = "";
    Object.keys(data).forEach(function (label) {
        var item = document.createElement("div");
        item.className = "py-2.5";
        var dt = document.createElement("dt");
        dt.className = "text-xs uppercase tracking-wide text-gray-400";
        dt.textContent = label;
        var dd = document.createElement("dd");
        dd.className = "text-sm text-gray-800 mt-0.5";
        dd.textContent = data[label] === null || data[label] === "" ? "—" : String(data[label]);
        item.appendChild(dt);
        item.appendChild(dd);
        body.appendChild(item);
    });

    document.getElementById("detail-card-title").textContent = row.dataset.title || "Details";
    document.getElementById("detail-card-edit").href = row.dataset.editUrl;

    var deleteButton = document.getElementById("detail-card-delete");
    if (deleteButton) {
        deleteButton.onclick = function () {
            closeSideCards();
            openDeleteModal(row.dataset.deleteUrl, row.dataset.deleteMsg);
        };
    }
    showSideCard(document.getElementById("detail-card"));
}

function openAddCard() {
    closeSideCards();
    var card = document.getElementById("add-card");
    if (card) {
        showSideCard(card);
        var firstField = card.querySelector("input, select");
        if (firstField) {
            firstField.focus();
        }
    }
}

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeSideCards();
    }
});

// Re-open the Add card after a create.php validation redirect (?add=1),
// or the prefilled Edit card when ?edit=<id> is present.
document.addEventListener("DOMContentLoaded", function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get("add") === "1") {
        openAddCard();
    } else if (params.get("edit") && document.getElementById("edit-card")) {
        openEditCard();
    }
});
</script>
