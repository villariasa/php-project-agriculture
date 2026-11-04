</main>

<script>
// ---------- Save/Update button spinner ----------
// Same loading treatment as the login button: disable, swap label for a
// spinner, and stop the user double-submitting. Works for both classic
// posts (Add forms — page navigates away) and fetch saves (Edit sliders —
// the caller restores the button via resetBtn once the fetch settles).
window.setBtnLoading = function (btn, label) {
    if (!btn || btn.dataset.loading === "1") { return; }
    btn.dataset.loading      = "1";
    btn.dataset.originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML =
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="btn-spinner w-4 h-4 inline-block align-[-2px] mr-1.5">'
        + '<path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" /></svg>'
        + (label || "Saving...");
};
window.resetBtn = function (btn) {
    if (!btn || btn.dataset.loading !== "1") { return; }
    btn.innerHTML = btn.dataset.originalHtml;
    btn.disabled  = false;
    delete btn.dataset.loading;
};

// Add-card forms are classic POSTs (not fetch-intercepted), so a delegated
// listener is enough — the page navigates away right after.
document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!form.matches("#add-card form")) { return; }
    var btn = form.querySelector("button[type=submit]");
    if (btn) { setBtnLoading(btn, "Saving..."); }
});

// ---------- PJAX navigation ----------
// Internal links swap <main> (and the nav shells) in place instead of
// reloading the page: the sidebar, topbar, and bottom nav never flash.
// Any failure falls back to a normal navigation.
(function () {
    var pjaxBusy = false;

    // Re-run the swapped page's scripts STRICTLY in order: an external lib
    // (e.g. Chart.js) fully loads before the next inline script executes.
    // Already-loaded libs are skipped. Never throws into the caller.
    function executeScripts(container) {
        var scripts = Array.prototype.slice.call(container.querySelectorAll("script"));
        return scripts.reduce(function (chain, old) {
            return chain.then(function () {
                return new Promise(function (resolve) {
                    try {
                        if (old.src) {
                            var already = Array.prototype.some.call(document.scripts, function (s) {
                                return s.src === old.src && s !== old;
                            });
                            if (already) { old.remove(); resolve(); return; }
                            var lib = document.createElement("script");
                            lib.src = old.src;
                            if (old.integrity)  { lib.integrity  = old.integrity; }
                            if (old.crossOrigin) { lib.crossOrigin = old.crossOrigin; }
                            lib.onload  = function () { resolve(); };
                            lib.onerror = function () { resolve(); };
                            old.replaceWith(lib);
                        } else {
                            var inline = document.createElement("script");
                            inline.textContent = old.textContent;
                            old.replaceWith(inline); // runs now; its errors go to window, not here
                            resolve();
                        }
                    } catch (e) {
                        resolve(); // never let one script kill the navigation
                    }
                });
            });
        }, Promise.resolve());
    }

    window.pjaxNavigate = function (url, push) {
        if (pjaxBusy) { return; }
        pjaxBusy = true;
        if (window.NProgress) { NProgress.start(); }
        fetch(url, { headers: { "X-Requested-With": "pjax" } })
            .then(function (response) {
                if (!response.ok) { throw new Error("bad response"); }
                return response.text();
            })
            .then(function (html) {
                var doc     = new DOMParser().parseFromString(html, "text/html");
                var newMain = doc.querySelector("main");
                var curMain = document.querySelector("main");
                if (!newMain || !curMain) { window.location.href = url; return; }

                // Commit URL + title first so nothing can desync them
                if (push !== false) { window.history.pushState({ pjax: true }, "", url); }
                document.title = doc.title;

                // Swap the page content + every shell piece whose state is per-page
                curMain.innerHTML = newMain.innerHTML;
                ["sidebar-nav", "breadcrumbs", "bottom-nav", "records-sheet", "menu-sheet"].forEach(function (id) {
                    var cur   = document.getElementById(id);
                    var fresh = doc.getElementById(id);
                    if (cur && fresh) { cur.innerHTML = fresh.innerHTML; }
                });
                window.scrollTo({ top: 0 });

                return executeScripts(curMain); // charts/list init, in order
            })
            .then(function () {
                pjaxBusy = false;
                if (window.NProgress) { NProgress.done(); }
            })
            .catch(function () {
                pjaxBusy = false;
                window.location.href = url; // full-load fallback, never strand
            });
    };

    document.addEventListener("click", function (event) {
        if (event.defaultPrevented) { return; }
        var link = event.target.closest("a[href]");
        if (!link) { return; }
        if (link.target === "_blank" || link.hasAttribute("download") || link.hasAttribute("data-no-pjax")) { return; }

        var href = link.getAttribute("href");
        if (!href || href.charAt(0) === "#" || href.indexOf("mailto:") === 0) { return; }

        var url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) { return; }
        if (url.pathname.indexOf("/auth/") !== -1) { return; }          // login/logout: real navigation
        if (url.search.indexOf("export=csv") !== -1) { return; }        // downloads
        if (link.closest("#records-panel")) { return; }                 // panel loader handles these

        event.preventDefault();
        closeSheetsSafely();
        pjaxNavigate(url.href);
    });

    // Crash-proof: stale page functions must never be able to kill navigation
    function closeSheetsSafely() {
        try { if (typeof closeSheets === "function") { closeSheets(); } } catch (e) {}
        try { if (typeof closeSideCards === "function") { closeSideCards(); } } catch (e) {}
    }
})();

// ---------- Custom dropdowns ----------
// Replaces every native <select> with a styled trigger + options panel.
// The real <select> stays hidden in the form, so values submit normally,
// inline onchange handlers still fire, and no-JS falls back to native.
(function () {
    var CHEVRON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="cs-chev w-4 h-4 text-gray-500 shrink-0 transition-transform duration-150"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>';
    var CHECK   = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-green-600 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';

    function closeAll() {
        document.querySelectorAll(".custom-select-panel").forEach(function (panel) { panel.classList.add("hidden"); });
        document.querySelectorAll(".custom-select-wrap .cs-chev").forEach(function (chev) { chev.classList.remove("rotate-180"); });
    }

    function enhanceSelect(select) {
        if (select.dataset.enhanced === "1" || select.multiple || select.disabled) { return; }
        select.dataset.enhanced = "1";

        var wrap = document.createElement("div");
        wrap.className = "custom-select-wrap relative";
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        // Trigger inherits the select's classes so pills stay pills, fields stay fields
        var trigger = document.createElement("button");
        trigger.type = "button";
        trigger.className = select.className + " text-left inline-flex items-center justify-between gap-2";
        trigger.setAttribute("aria-haspopup", "listbox");
        trigger.innerHTML = '<span class="truncate"></span>' + CHEVRON;
        wrap.appendChild(trigger);

        // More than 10 options -> search combobox; both variants show max
        // ~8 rows (max-h-72) and the rest scroll inside the panel.
        var isCombo = select.options.length > 10;

        var panel = document.createElement("div");
        panel.className = "custom-select-panel hidden absolute left-0 min-w-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-lg z-50 overflow-hidden";
        wrap.appendChild(panel);

        var searchInput = null;
        if (isCombo) {
            var searchBox = document.createElement("div");
            searchBox.className = "p-2 border-b border-gray-100 bg-white";
            searchBox.innerHTML = '<input type="text" placeholder="Type to search..." '
                + 'class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">';
            panel.appendChild(searchBox);
            searchInput = searchBox.firstChild;
        }

        var list = document.createElement("div");
        list.className = "max-h-72 overflow-y-auto py-1"; /* ~8 rows visible */
        panel.appendChild(list);

        select.classList.add("hidden");

        var chevron = trigger.querySelector(".cs-chev");

        function syncLabel() {
            var option = select.options[select.selectedIndex];
            trigger.firstChild.textContent = option ? option.text : "";
        }
        function pick(index) {
            select.selectedIndex = index;
            syncLabel();
            closeAll();
            select.dispatchEvent(new Event("change", { bubbles: true }));
        }
        function buildOptions(filter) {
            list.innerHTML = "";
            var term    = (filter || "").toLowerCase();
            var matches = 0;
            Array.prototype.forEach.call(select.options, function (option, index) {
                if (term !== "" && option.text.toLowerCase().indexOf(term) === -1) {
                    return;
                }
                matches++;
                var row = document.createElement("button");
                row.type = "button";
                row.className = "w-full text-left px-3.5 py-2 text-sm whitespace-nowrap inline-flex items-center justify-between gap-3 "
                    + (index === select.selectedIndex ? "text-green-700 font-medium bg-green-50" : "text-gray-700 hover:bg-gray-50");
                row.innerHTML = '<span class="truncate"></span>' + (index === select.selectedIndex ? CHECK : "");
                row.firstChild.textContent = option.text;
                row.dataset.index = index;
                row.addEventListener("click", function () { pick(index); });
                list.appendChild(row);
            });
            if (matches === 0) {
                list.innerHTML = '<p class="px-3.5 py-3 text-sm text-gray-400">No matches found.</p>';
            }
        }
        function openPanel() {
            closeAll();
            buildOptions("");
            panel.classList.remove("hidden");
            chevron.classList.add("rotate-180");
            if (searchInput) {
                searchInput.value = "";
                searchInput.focus();
            }
        }

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                buildOptions(searchInput.value.trim());
            });
            // Enter picks the first visible match
            searchInput.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    var first = list.querySelector("button[data-index]");
                    if (first) {
                        pick(parseInt(first.dataset.index, 10));
                    }
                }
            });
        }

        trigger.addEventListener("click", function () {
            if (panel.classList.contains("hidden")) { openPanel(); } else { closeAll(); }
        });
        trigger.addEventListener("keydown", function (event) {
            if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                event.preventDefault();
                var next = select.selectedIndex + (event.key === "ArrowDown" ? 1 : -1);
                if (next >= 0 && next < select.options.length) {
                    select.selectedIndex = next;
                    syncLabel();
                    select.dispatchEvent(new Event("change", { bubbles: true }));
                }
            } else if (event.key === "Enter") {
                event.preventDefault();
                panel.classList.contains("hidden") ? openPanel() : closeAll();
            }
        });

        select._syncCustom = syncLabel;
        syncLabel();
    }

    // Re-sync trigger labels after JS sets select values (edit sliders)
    window.syncCustomSelects = function (root) {
        (root || document).querySelectorAll("select[data-enhanced='1']").forEach(function (select) {
            if (select._syncCustom) { select._syncCustom(); }
        });
    };

    document.addEventListener("click", function (event) {
        if (!event.target.closest(".custom-select-wrap")) { closeAll(); }
    });
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") { closeAll(); }
    });

    function enhanceAll(root) {
        (root || document).querySelectorAll("select").forEach(enhanceSelect);
    }
    window.enhanceSelects = enhanceAll;

    // Enhance everything on the page now (toolbars, sliders, sheets, forms)...
    enhanceAll(document);
    // ...and anything added to the DOM later (dynamic panel swaps, etc.).
    // Already-enhanced selects are skipped via data-enhanced, so this is cheap.
    new MutationObserver(function () { enhanceAll(document); })
        .observe(document.body, { childList: true, subtree: true });
})();
</script>

        <!-- mb clears the fixed mobile bottom navigation bar -->
        <footer class="border-t border-gray-200 bg-white mb-16 lg:mb-0 print:hidden">
            <div class="px-4 sm:px-6 py-4 text-center text-sm text-gray-500">
                Agriculture Information System &copy; <?= date("Y") ?> | AP 3 Web Application Development
            </div>
        </footer>

    </div><!-- /content column -->

</div><!-- /app shell -->

</body>
</html>
