/**
 * Main JavaScript file for PROYECTA
 */

// Show notification toast
function showNotification(message, type = 'info') {
    // Create container if it doesn't exist
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none';
        document.body.appendChild(container);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `transform transition-all duration-300 ease-in-out translate-y-2 opacity-0 pointer-events-auto min-w-[300px] p-4 rounded-lg shadow-lg flex items-center gap-3 ${getNotificationColor(type)}`;
    
    // Icon
    const icon = document.createElement('span');
    icon.className = 'material-symbols-outlined';
    icon.textContent = getNotificationIcon(type);
    
    // Text
    const text = document.createElement('p');
    text.className = 'text-sm font-medium';
    text.textContent = message;
    
    // Close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'ml-auto opacity-70 hover:opacity-100';
    closeBtn.innerHTML = '<span class="material-symbols-outlined text-sm">close</span>';
    closeBtn.onclick = () => removeNotification(notification);
    
    notification.appendChild(icon);
    notification.appendChild(text);
    notification.appendChild(closeBtn);
    
    container.appendChild(notification);
    
    // Animate in
    requestAnimationFrame(() => {
        notification.classList.remove('translate-y-2', 'opacity-0');
    });
    
    // Auto remove
    setTimeout(() => {
        removeNotification(notification);
    }, 5000);
}

function removeNotification(element) {
    element.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => {
        if (element.parentElement) {
            element.parentElement.removeChild(element);
        }
    }, 300);
}

function getNotificationColor(type) {
    switch(type) {
        case 'success': return 'bg-green-500 text-white';
        case 'error': return 'bg-red-500 text-white';
        case 'warning': return 'bg-yellow-500 text-black';
        default: return 'bg-blue-500 text-white';
    }
}

function getNotificationIcon(type) {
    switch(type) {
        case 'success': return 'check_circle';
        case 'error': return 'error';
        case 'warning': return 'warning';
        default: return 'info';
    }
}

// Format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
}
