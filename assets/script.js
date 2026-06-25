function confirmAction(message) {
    return confirm(message || "Are you sure?");
}

function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    const keyword = input.value.toLowerCase();
    let rows = table.querySelectorAll("tbody tr");

    // Some pages use card grids instead of real <table> rows.
    // Fall back to direct child cards so the same search function works there too.
    if (rows.length === 0) {
        rows = table.querySelectorAll(":scope > .event-card, :scope > .card, :scope > .panel");
    }

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(keyword) ? "" : "none";
    });
}

function validateRequiredForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const fields = form.querySelectorAll("[required]");
    for (let field of fields) {
        if (field.value.trim() === "") {
            alert("Please fill in all required fields.");
            field.focus();
            return false;
        }
    }
    return true;
}

function toggleRoleMenu(event) {
    event.stopPropagation();
    const currentSwitch = event.currentTarget.closest(".role-switch");

    document.querySelectorAll(".role-switch.open").forEach(function(item) {
        if (item !== currentSwitch) {
            item.classList.remove("open");
        }
    });

    if (currentSwitch) {
        currentSwitch.classList.toggle("open");
    }
}

document.addEventListener("click", function() {
    document.querySelectorAll(".role-switch.open").forEach(function(item) {
        item.classList.remove("open");
    });
});

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        document.querySelectorAll(".role-switch.open").forEach(function(item) {
            item.classList.remove("open");
        });
    }
});

function toggleEventFilters() {
    const panel = document.getElementById("eventFilterPanel");
    if (!panel) return;

    const isHidden = panel.style.display === "none" || window.getComputedStyle(panel).display === "none";
    panel.style.display = isHidden ? "block" : "none";
}

function getEventDateModeMatch(eventDateValue, dateMode) {
    if (!dateMode || dateMode === "all") return true;
    if (!eventDateValue) return false;

    const eventDate = new Date(eventDateValue + "T00:00:00");
    if (Number.isNaN(eventDate.getTime())) return true;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (dateMode === "upcoming") return eventDate >= today;
    if (dateMode === "today") return eventDate.getTime() === today.getTime();
    if (dateMode === "past") return eventDate < today;

    return true;
}

function filterEventCards() {
    const list = document.getElementById("eventList");
    if (!list) return;

    const keyword = (document.getElementById("eventSearch")?.value || "").trim().toLowerCase();
    const clubFilter = document.getElementById("eventClubFilter")?.value || "all";
    const statusFilter = document.getElementById("eventStatusFilter")?.value || "all";
    const dateFilter = document.getElementById("eventDateFilter")?.value || "all";
    const cards = list.querySelectorAll(".event-card");
    let visibleCount = 0;

    cards.forEach(card => {
        const searchText = (card.dataset.search || card.innerText || "").toLowerCase();
        const cardClub = card.dataset.club || "";
        const cardStatus = card.dataset.status || "open";
        const cardDate = card.dataset.date || "";

        const matchesSearch = keyword === "" || searchText.includes(keyword);
        const matchesClub = clubFilter === "all" || cardClub === clubFilter;
        const matchesStatus = statusFilter === "all" || cardStatus === statusFilter;
        const matchesDate = getEventDateModeMatch(cardDate, dateFilter);
        const isVisible = matchesSearch && matchesClub && matchesStatus && matchesDate;

        card.style.display = isVisible ? "" : "none";
        if (isVisible) visibleCount++;
    });

    const emptyMessage = document.getElementById("eventNoResults");
    if (emptyMessage) {
        emptyMessage.style.display = visibleCount === 0 ? "block" : "none";
    }
}

function clearEventFilters() {
    const searchInput = document.getElementById("eventSearch");
    const clubSelect = document.getElementById("eventClubFilter");
    const statusSelect = document.getElementById("eventStatusFilter");
    const dateSelect = document.getElementById("eventDateFilter");

    if (searchInput) searchInput.value = "";
    if (clubSelect) clubSelect.value = "all";
    if (statusSelect) statusSelect.value = "all";
    if (dateSelect) dateSelect.value = "all";

    filterEventCards();
}
