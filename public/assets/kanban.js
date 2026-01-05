document.addEventListener('DOMContentLoaded', () => {
    const draggables = document.querySelectorAll('.task-card');
    const droppables = document.querySelectorAll('.column-body');

    draggables.forEach(draggable => {
        draggable.addEventListener('dragstart', () => {
            draggable.classList.add('dragging');
        });

        draggable.addEventListener('dragend', () => {
            draggable.classList.remove('dragging');
            
            // Get data
            const taskId = draggable.dataset.taskId;
            const columnId = draggable.closest('.kanban-column').dataset.columnId;

            // Send Update to Server
            updateTaskPosition(taskId, columnId);
        });
    });

    droppables.forEach(container => {
        container.addEventListener('dragover', e => {
            e.preventDefault();
            const afterElement = getDragAfterElement(container, e.clientY);
            const draggable = document.querySelector('.dragging');
            if (afterElement == null) {
                container.appendChild(draggable);
            } else {
                container.insertBefore(draggable, afterElement);
            }
        });
    });
});

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.task-card:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function updateTaskPosition(taskId, columnId) {
    fetch('/proyecta/public/move-task', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: taskId,
            column_id: columnId
        }),
    })
    .then(response => response.json())
    .then(data => {
        console.log('Moved:', data);
    })
    .catch((error) => {
        console.error('Error:', error);
    });
}
