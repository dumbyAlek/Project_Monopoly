// init.js
let draggedInput = null;

document.querySelectorAll(".name-field").forEach(input => {
    input.addEventListener("dragstart", (e) => {
        draggedInput = e.target;
        e.target.classList.add("dragging");
        // allow dragging text fields
        e.dataTransfer.setData("text/plain", "drag"); 
    });
    input.addEventListener("dragend", (e) => {
        e.target.classList.remove("dragging");
        draggedInput = null;
    });
});

// make each dropzone accept drops
document.querySelectorAll(".dropzone").forEach(zone => {
    zone.addEventListener("dragover", e => {
        e.preventDefault();
        zone.classList.add("highlight");
    });
    zone.addEventListener("dragleave", () => {
        zone.classList.remove("highlight");
    });
    zone.addEventListener("drop", e => {
        e.preventDefault();
        zone.classList.remove("highlight");

        if (!draggedInput) return;

        // current input in the zone (if any)
        const currentInput = zone.querySelector(".name-field");

        // parent of dragged input (its original zone)
        const fromZone = draggedInput.parentElement;

        // if the drop target already has input, swap them
        if (currentInput) {
            // move currentInput back to fromZone
            fromZone.appendChild(currentInput);
        } else {
            // remove dragged input from its parent to avoid duplicates
            // (it will be appended to zone below)
        }

        zone.appendChild(draggedInput);
    });
});
