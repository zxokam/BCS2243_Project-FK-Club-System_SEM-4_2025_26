function confirmAction(message) {
    return confirm(message || "Are you sure?");
}

function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    const keyword = input.value.toLowerCase();
    const rows = table.querySelectorAll("tbody tr");

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
