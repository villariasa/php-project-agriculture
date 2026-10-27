<!-- Delete Confirmation Modal (hidden by default) -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/40" onclick="closeDeleteModal()"></div>

    <!-- Modal Card -->
    <div class="relative bg-white rounded-lg border border-gray-200 shadow-lg w-full max-w-sm p-6">
        <div class="flex items-start gap-3">
            <div class="bg-red-50 text-red-600 rounded-lg p-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div>
                <h2 class="font-semibold text-gray-800">Confirm Deletion</h2>
                <p id="deleteModalMessage" class="text-sm text-gray-500 mt-1">Are you sure you want to delete this record?</p>
                <p class="text-xs text-gray-400 mt-1">This action cannot be undone.</p>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <button type="button" onclick="closeDeleteModal()"
                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm">
                Cancel
            </button>
            <a id="deleteModalConfirm" href="#"
               class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg text-sm">
                Delete
            </a>
        </div>
    </div>
</div>

<script>
    function openDeleteModal(url, message) {
        document.getElementById("deleteModalConfirm").setAttribute("href", url);
        document.getElementById("deleteModalMessage").textContent = message;
        document.getElementById("deleteModal").classList.remove("hidden");
    }

    function closeDeleteModal() {
        document.getElementById("deleteModal").classList.add("hidden");
    }

    // Close with Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeDeleteModal();
    });
</script>
