// Drag & Drop básico para inventário
document.addEventListener("DOMContentLoaded", function() {
    let items = document.querySelectorAll(".item");
    let slots = document.querySelectorAll(".slot");

    items.forEach(item => {
        item.draggable = true;
        item.addEventListener("dragstart", e => {
            e.dataTransfer.setData("text/plain", item.id);
        });
    });

    slots.forEach(slot => {
        slot.addEventListener("dragover", e => {
            e.preventDefault();
            slot.classList.add("hover");
        });
        slot.addEventListener("dragleave", e => {
            slot.classList.remove("hover");
        });
        slot.addEventListener("drop", e => {
            e.preventDefault();
            slot.classList.remove("hover");
            const id = e.dataTransfer.getData("text/plain");
            const draggedItem = document.getElementById(id);

            if(slot.children.length === 0){
                slot.appendChild(draggedItem);
                draggedItem.classList.add("fade-out");
                setTimeout(() => draggedItem.classList.remove("fade-out"), 300);
            }
        });
    });
});
