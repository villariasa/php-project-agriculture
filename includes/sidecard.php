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
    var overlay = document.getElementById("sidecard-overlay");
    if (overlay) { overlay.classList.remove("hidden"); }
    card.classList.remove("hidden");
    card.classList.add("flex");
}

// Null-safe: this function outlives its page under PJAX, so every element
// lookup must tolerate pages that don't have side cards (reports, dashboard).
function closeSideCards() {
    var overlay = document.getElementById("sidecard-overlay");
    if (overlay) { overlay.classList.add("hidden"); }
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

// Instant edit (no page reload): fill the edit card's fields from the
// clicked row's data-edit JSON (form field name => raw value) and open it.
function fillAndOpenEditCard(sourceEl) {
    var card = document.getElementById("edit-card");
    if (!card || !sourceEl || !sourceEl.dataset.edit) {
        return false;
    }
    var data = JSON.parse(sourceEl.dataset.edit);
    Object.keys(data).forEach(function (name) {
        var field = card.querySelector('[name="' + name + '"]');
        if (field) {
            field.value = data[name];
        }
    });
    var title = card.querySelector("h2");
    if (title && sourceEl.dataset.title) {
        title.textContent = "Edit " + sourceEl.dataset.title;
    }
    if (window.syncCustomSelects) {
        syncCustomSelects(card);
    }
    openEditCard();
    return true;
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
    var editLink = document.getElementById("detail-card-edit");
    editLink.href = row.dataset.editUrl;
    // Prefer the instant in-place edit; the href reload is only a fallback
    editLink.onclick = function (event) {
        if (fillAndOpenEditCard(row)) {
            event.preventDefault();
        }
    };

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

if (!window.__aisEscBound) {
    window.__aisEscBound = true;
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeSideCards();
        }
    });
}

// ---------- Dynamic list: sort, paginate, filter, and save WITHOUT reloads ----------
// Fetches the same page in the background and swaps the server-rendered
// records panel (and header count) in place. Falls back to normal navigation
// when fetch fails.
function loadRecords(url, mode) {
    var panel = document.getElementById("records-panel");
    if (panel) {
        panel.classList.add("loading");
    }
    if (window.NProgress) { NProgress.start(); }
    fetch(url, { headers: { "X-Requested-With": "fetch" } })
        .then(function (response) { return response.text(); })
        .then(function (html) {
            var doc = new DOMParser().parseFromString(html, "text/html");
            ["records-panel", "records-count"].forEach(function (id) {
                var current = document.getElementById(id);
                var fresh   = doc.getElementById(id);
                if (current && fresh) {
                    current.innerHTML = fresh.innerHTML;
                }
            });
            if (panel) {
                panel.classList.remove("loading");
                panel.classList.remove("swapped");
                void panel.offsetWidth; // restart the settle-in animation
                panel.classList.add("swapped");
            }
            if (mode === "replace") {
                window.history.replaceState(null, "", url);
            } else if (mode !== "silent") {
                window.history.pushState(null, "", url);
            }
            if (window.NProgress) { NProgress.done(); }
        })
        .catch(function () { window.location.href = url; });
}

// Toolbar selects / clear button call this instead of form.submit() so the
// submit event fires and gets intercepted below.
function submitToolbar(form) {
    if (form.requestSubmit) {
        form.requestSubmit();
    } else {
        form.submit();
    }
}

// Per-page init: binds to the CURRENT page's elements. Re-run after every
// PJAX swap (this script lives inside <main>, so it re-executes on swap).
function initListPage() {
    // Re-open the Add card after a create.php validation redirect (?add=1),
    // or the prefilled Edit card when ?edit=<id> is present (deep link).
    var params = new URLSearchParams(window.location.search);
    if (params.get("add") === "1") {
        openAddCard();
    } else if (params.get("edit") && document.getElementById("edit-card")) {
        openEditCard();
    }

    // Filter toolbar (search / filter select / sort select)
    var toolbar = document.getElementById("filter-toolbar");
    if (toolbar && !toolbar.dataset.bound) {
        toolbar.dataset.bound = "1";
        toolbar.addEventListener("submit", function (event) {
            event.preventDefault();
            var query = new URLSearchParams(new FormData(toolbar));
            loadRecords("?" + query.toString());
        });
    }

    // Edit slider saves via fetch: toast + background panel refresh, no reload
    var editCard = document.getElementById("edit-card");
    if (editCard && !editCard.dataset.bound) {
        editCard.dataset.bound = "1";
        var editForm = editCard.querySelector("form");
        editForm.addEventListener("submit", function (event) {
            event.preventDefault();
            if (window.NProgress) { NProgress.start(); }
            fetch(editForm.action, {
                method: "POST",
                body: new FormData(editForm),
                headers: { "X-Requested-With": "fetch" }
            })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (window.NProgress) { NProgress.done(); }
                if (result.ok) {
                    closeSideCards();
                    var url = new URL(window.location.href);
                    ["edit", "add", "msg", "error"].forEach(function (p) { url.searchParams.delete(p); });
                    loadRecords(url.toString(), "replace");
                }
                showToast(result.message, result.ok ? "success" : "error");
            })
            .catch(function () { editForm.submit(); }); // classic redirect fallback
        });
    }
}

// Document-level listeners: bound exactly once per real page load
if (!window.__aisListGlobals) {
    window.__aisListGlobals = true;

    // Sort headers + pagination links inside the records panel
    document.addEventListener("click", function (event) {
        var link = event.target.closest("#records-panel a[href^='?']");
        if (link) {
            event.preventDefault();
            loadRecords(link.getAttribute("href"));
        }
    });

    // Back/forward: PJAX the whole page when available, else re-render the panel
    window.addEventListener("popstate", function () {
        if (window.pjaxNavigate) {
            pjaxNavigate(window.location.href, false);
        } else {
            loadRecords(window.location.href, "silent");
        }
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initListPage);
} else {
    initListPage(); // PJAX swap: document is already loaded
}
</script>
