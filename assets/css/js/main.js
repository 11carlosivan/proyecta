// PROYECTA - JavaScript Principal

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeMobileMenu();
    initializeTooltips();
    initializeFormValidation();
    initializeKanbanDragDrop();
    initializeNotifications();
    initializeSearch();
});

// Sistema de tema oscuro/claro
function initializeTheme() {
    const themeToggle = document.getElementById('theme-toggle');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    
    // Verificar preferencia guardada
    const savedTheme = localStorage.getItem('proyecta-theme');
    const systemTheme = prefersDark.matches ? 'dark' : 'light';
    const theme = savedTheme || systemTheme;
    
    // Aplicar tema
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    
    // Configurar toggle
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('proyecta-theme', isDark ? 'dark' : 'light');
            updateThemeIcon(isDark);
        });
        
        updateThemeIcon(document.documentElement.classList.contains('dark'));
    }
    
    // Escuchar cambios del sistema
    prefersDark.addEventListener('change', function(e) {
        if (!localStorage.getItem('proyecta-theme')) {
            if (e.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    });
}

function updateThemeIcon(isDark) {
    const themeToggle = document.getElementById('theme-toggle');
    if (!themeToggle) return;
    
    const icon = themeToggle.querySelector('.material-symbols-outlined');
    if (icon) {
        icon.textContent = isDark ? 'light_mode' : 'dark_mode';
    }
}

// Menú móvil
function initializeMobileMenu() {
    const menuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('aside');
    
    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('flex');
        });
    }
    
    // Cerrar menú al hacer clic fuera en móvil
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 768 && sidebar && !sidebar.classList.contains('hidden')) {
            if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('flex');
            }
        }
    });
}

// Tooltips
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'fixed z-50 px-2 py-1 text-xs bg-gray-900 text-white rounded shadow-lg';
            tooltip.textContent = this.getAttribute('data-tooltip');
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - 30) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2) + 'px';
            tooltip.style.transform = 'translateX(-50%)';
            
            tooltip.id = 'dynamic-tooltip';
            document.body.appendChild(tooltip);
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.getElementById('dynamic-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });
}

// Validación de formularios
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showFormErrors(this);
            }
        });
        
        // Validación en tiempo real
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    if (!value) {
        isValid = false;
        message = 'Este campo es requerido';
    } else {
        switch (field.type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    message = 'Ingresa un email válido';
                }
                break;
                
            case 'password':
                if (value.length < 8) {
                    isValid = false;
                    message = 'Mínimo 8 caracteres';
                }
                break;
                
            case 'tel':
                const phoneRegex = /^[\d\s\-\+\(\)]+$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                    message = 'Teléfono inválido';
                }
                break;
        }
    }
    
    if (!isValid) {
        showFieldError(field, message);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const error = document.createElement('div');
    error.className = 'mt-1 text-sm text-red-600';
    error.textContent = message;
    
    field.classList.add('border-red-500');
    field.parentNode.appendChild(error);
}

function clearFieldError(field) {
    field.classList.remove('border-red-500');
    
    const existingError = field.parentNode.querySelector('.text-red-600');
    if (existingError) {
        existingError.remove();
    }
}

function showFormErrors(form) {
    const firstError = form.querySelector('.border-red-500');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstError.focus();
    }
}

// Kanban - Drag & Drop
function initializeKanbanDragDrop() {
    const tasks = document.querySelectorAll('.kanban-task');
    const columns = document.querySelectorAll('.kanban-column-content');
    
    let draggedTask = null;
    
    // Eventos para tareas
    tasks.forEach(task => {
        task.setAttribute('draggable', 'true');
        
        task.addEventListener('dragstart', function(e) {
            draggedTask = this;
            this.classList.add('opacity-50');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
        });
        
        task.addEventListener('dragend', function() {
            this.classList.remove('opacity-50');
            draggedTask = null;
            
            // Actualizar estado en servidor
            const taskId = this.dataset.taskId;
            const newStatus = this.closest('.kanban-column').dataset.status;
            
            if (taskId && newStatus) {
                updateTaskStatus(taskId, newStatus);
            }
        });
    });
    
    // Eventos para columnas
    columns.forEach(column => {
        column.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('bg-gray-50', 'dark:bg-gray-800/50');
        });
        
        column.addEventListener('dragleave', function() {
            this.classList.remove('bg-gray-50', 'dark:bg-gray-800/50');
        });
        
        column.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('bg-gray-50', 'dark:bg-gray-800/50');
            
            if (draggedTask && draggedTask.parentNode !== this) {
                this.appendChild(draggedTask);
                draggedTask.classList.add('fade-in');
                
                // Notificación visual
                showNotification('Tarea movida', 'success');
            }
        });
    });
}

function updateTaskStatus(taskId, status) {
    fetch('/api/tasks/update-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: taskId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showNotification('Error al actualizar tarea', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Notificaciones del sistema
function initializeNotifications() {
    const notificationBtn = document.querySelector('[data-notifications]');
    
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            loadNotifications();
            toggleNotificationsPanel();
        });
    }
    
    // Verificar nuevas notificaciones periódicamente
    setInterval(checkNewNotifications, 30000); // Cada 30 segundos
}

function loadNotifications() {
    fetch('/api/notifications/get.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderNotifications(data.notifications);
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => console.error('Error:', error));
}

function renderNotifications(notifications) {
    const container = document.getElementById('notifications-container');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-center text-gray-500">
                No hay notificaciones
            </div>
        `;
        return;
    }
    
    notifications.forEach(notification => {
        const item = document.createElement('div');
        item.className = `p-3 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 ${notification.read_at ? '' : 'bg-blue-50 dark:bg-blue-900/20'}`;
        item.innerHTML = `
            <div class="flex gap-3">
                <div class="flex-shrink-0">
                    <span class="material-symbols-outlined text-${notification.type}">${getNotificationIcon(notification.type)}</span>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-sm">${notification.title}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">${notification.message}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">${formatTimeAgo(notification.created_at)}</p>
                </div>
                ${notification.read_at ? '' : '<span class="size-2 bg-blue-500 rounded-full self-start mt-1"></span>'}
            </div>
        `;
        
        if (notification.link) {
            item.addEventListener('click', function() {
                window.location.href = notification.link;
                markAsRead(notification.id);
            });
        } else {
            item.addEventListener('click', function() {
                markAsRead(notification.id);
            });
        }
        
        container.appendChild(item);
    });
}

function getNotificationIcon(type) {
    const icons = {
        'info': 'info',
        'success': 'check_circle',
        'warning': 'warning',
        'error': 'error',
        'task': 'assignment',
        'project': 'folder',
        'team': 'group'
    };
    
    return icons[type] || 'notifications';
}

function toggleNotificationsPanel() {
    const panel = document.getElementById('notifications-panel');
    if (panel) {
        panel.classList.toggle('hidden');
    }
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('[data-notification-badge]');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

function checkNewNotifications() {
    fetch('/api/notifications/count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.count);
            }
        })
        .catch(error => console.error('Error:', error));
}

function markAsRead(notificationId) {
    fetch('/api/notifications/mark-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    });
}

// Búsqueda en tiempo real
function initializeSearch() {
    const searchInput = document.getElementById('global-search');
    
    if (searchInput) {
        let timeout = null;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            
            const query = this.value.trim();
            if (query.length < 2) {
                hideSearchResults();
                return;
            }
            
            timeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        // Ocultar resultados al hacer clic fuera
        document.addEventListener('click', function(event) {
            const searchContainer = document.getElementById('search-results');
            if (searchContainer && !searchContainer.contains(event.target) && searchInput !== event.target) {
                hideSearchResults();
            }
        });
    }
}

function performSearch(query) {
    fetch(`/api/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSearchResults(data.results);
            }
        })
        .catch(error => console.error('Error:', error));
}

function showSearchResults(results) {
    let container = document.getElementById('search-results');
    
    if (!container) {
        container = document.createElement('div');
        container.id = 'search-results';
        container.className = 'absolute top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50';
        
        const searchInput = document.getElementById('global-search');
        searchInput.parentNode.appendChild(container);
    }
    
    if (results.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-center text-gray-500">
                No se encontraron resultados
            </div>
        `;
        container.classList.remove('hidden');
        return;
    }
    
    let html = '<div class="max-h-96 overflow-y-auto">';
    
    results.forEach(result => {
        html += `
            <a href="${result.link}" class="block p-3 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-gray-400">${result.icon}</span>
                    <div class="flex-1">
                        <p class="font-medium text-sm">${result.title}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">${result.subtitle}</p>
                    </div>
                </div>
            </a>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
    container.classList.remove('hidden');
}

function hideSearchResults() {
    const container = document.getElementById('search-results');
    if (container) {
        container.classList.add('hidden');
    }
}

// Utilidades
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffSec < 60) return 'hace un momento';
    if (diffMin < 60) return `hace ${diffMin} minuto${diffMin !== 1 ? 's' : ''}`;
    if (diffHour < 24) return `hace ${diffHour} hora${diffHour !== 1 ? 's' : ''}`;
    if (diffDay < 7) return `hace ${diffDay} día${diffDay !== 1 ? 's' : ''}`;
    
    return date.toLocaleDateString();
}

function showNotification(message, type = 'info') {
    // Eliminar notificaciones anteriores
    const oldNotifications = document.querySelectorAll('.toast-notification');
    oldNotifications.forEach(notification => notification.remove());
    
    // Crear nueva notificación
    const notification = document.createElement('div');
    notification.className = `toast-notification fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-0 opacity-100`;
    
    const colors = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-white',
        info: 'bg-blue-500 text-white'
    };
    
    notification.className += ' ' + (colors[type] || colors.info);
    notification.innerHTML = `
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined">${getNotificationIcon(type)}</span>
            <span>${message}</span>
            <button class="ml-4" onclick="this.parentElement.parentElement.remove()">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Confirmación antes de acciones importantes
function confirmAction(message, callback) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="material-symbols-outlined text-yellow-500">warning</span>
                    <h3 class="text-lg font-semibold">Confirmar acción</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-6">${message}</p>
                <div class="flex justify-end gap-3">
                    <button class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" onclick="this.closest('.fixed').remove()">
                        Cancelar
                    </button>
                    <button class="px-4 py-2 bg-red-500 text-white hover:bg-red-600 rounded-lg" onclick="this.closest('.fixed').remove(); callback();">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Sobrescribir callback para ejecutar la función original
    const confirmBtn = modal.querySelector('button.bg-red-500');
    const originalOnclick = confirmBtn.getAttribute('onclick');
    confirmBtn.setAttribute('onclick', originalOnclick.replace('callback()', `(${callback.toString()})()`));
}

// Exportar funciones globales
window.showNotification = showNotification;
window.confirmAction = confirmAction;
window.formatTimeAgo = formatTimeAgo;