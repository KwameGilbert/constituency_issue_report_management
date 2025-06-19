<!-- Footer JS scripts -->
<script>
// Entity management functions
let currentEntityId = null;

// Open entity modal for add or edit operation
function openEntityModal(mode, entityId = null) {
    const modal = document.getElementById('entityModal');
    const titleEl = document.getElementById('entity-modal-title');
    const submitText = document.getElementById('entity-submit-text');
    const form = document.getElementById('entityForm');

    // Reset form
    form.reset();
    currentEntityId = entityId;

    if (mode === 'edit' && entityId) {
        titleEl.textContent = 'Edit Entity';
        submitText.textContent = 'Update Entity';
        document.getElementById('entity-id').value = entityId;

        // Fetch entity data
        fetchEntity(entityId);
    } else {
        titleEl.textContent = 'Add New Entity';
        submitText.textContent = 'Add Entity';
        document.getElementById('entity-id').value = '';
    }

    modal.classList.remove('hidden');
}

// Fetch entity data for editing
function fetchEntity(entityId) {
    // Show loading state
    const submitBtn = document.getElementById('entity-submit-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Loading...</span>';

    fetch(`get-entity.php?id=${entityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate form fields
                document.getElementById('entity-name').value = data.entity.name;
                document.getElementById('entity-type').value = data.entity.type;
                document.getElementById('entity-contact-person').value = data.entity.contact_person || '';
                document.getElementById('entity-phone').value = data.entity.phone || '';
                document.getElementById('entity-email').value = data.entity.email || '';
                document.getElementById('entity-address').value = data.entity.address || '';
                document.getElementById('entity-notes').value = data.entity.notes || '';
            } else {
                showToast('error', 'Error', data.message || 'Failed to load entity data');
                closeEntityModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error', 'An unexpected error occurred while loading entity data');
            closeEntityModal();
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>Update Entity</span>';
        });
}

// Close entity modal
function closeEntityModal() {
    document.getElementById('entityModal').classList.add('hidden');
    document.getElementById('entityForm').reset();
}

// Close delete confirmation modal
function closeDeleteModal() {
    document.getElementById('deleteEntityModal').classList.add('hidden');
}

// Show delete entity confirmation
function deleteEntity(entityId, entityName) {
    const modal = document.getElementById('deleteEntityModal');
    const messageEl = document.getElementById('delete-entity-message');
    const confirmBtn = document.getElementById('confirm-delete-entity');

    messageEl.textContent = `Are you sure you want to delete "${entityName}"? This action cannot be undone.`;

    // Set up delete action
    confirmBtn.onclick = function() {
        performDeleteEntity(entityId);
    };

    modal.classList.remove('hidden');
}

// Perform entity deletion
function performDeleteEntity(entityId) {
    const confirmBtn = document.getElementById('confirm-delete-entity');
    const originalText = confirmBtn.innerHTML;

    // Show loading state
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Deleting...</span>';

    // Send delete request
    const formData = new FormData();
    formData.append('entity_id', entityId);
    formData.append('action', 'delete');

    fetch('manage-entity.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove entity from DOM
                const entityItem = document.querySelector(`.entity-item[data-entity-id="${entityId}"]`);
                if (entityItem) {
                    entityItem.remove();
                }

                // Check if no entities left
                const container = document.querySelector('#entities-container .space-y-3');
                if (container && container.children.length === 0) {
                    showNoEntitiesMessage();
                }

                showToast('success', 'Success', 'Entity has been deleted successfully');
            } else {
                showToast('error', 'Error', data.message || 'Failed to delete entity');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error', 'An unexpected error occurred while deleting entity');
        })
        .finally(() => {
            closeDeleteModal();
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        });
}

// Show "no entities" message
function showNoEntitiesMessage() {
    const container = document.getElementById('entities-container');
    container.innerHTML = `
        <div id="no-entities-message" class="text-center py-8">
            <span class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-3">
                <i class="fas fa-building text-gray-400"></i>
            </span>
            <h3 class="text-sm font-medium text-gray-900 mb-1">No entities assigned</h3>
            <p class="text-xs text-gray-500 mb-4">Assign entities that are involved in this project</p>
            <button onclick="openEntityModal('add')" 
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                <i class="fas fa-plus-circle mr-2"></i> Add Entity
            </button>
        </div>
    `;
}

// Entity form submission
document.addEventListener('DOMContentLoaded', function() {
    const entityForm = document.getElementById('entityForm');

    entityForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Get form data
        const formData = new FormData(entityForm);
        const entityId = document.getElementById('entity-id').value;
        const projectId = document.getElementById('entity-project-id').value;

        // Add project ID and entity ID if exists
        formData.append('project_id', projectId);
        if (entityId) {
            formData.append('entity_id', entityId);
            formData.append('action', 'update');
        } else {
            formData.append('action', 'create');
        }

        // Disable submit button and show loading
        const submitBtn = document.getElementById('entity-submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Submitting...</span>';

        // Send AJAX request
        fetch('manage-entity.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    closeEntityModal();

                    // If it's a create operation, add entity to DOM
                    if (data.operation === 'create') {
                        addEntityToDom(data.entity);
                    } else if (data.operation === 'update') {
                        // Update entity in DOM
                        updateEntityInDom(data.entity);
                    }

                    showToast('success', 'Success', data.operation === 'create' ?
                        'Entity has been added successfully' :
                        'Entity has been updated successfully');
                } else {
                    showToast('error', 'Error', data.message || 'Failed to save entity');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error', 'An unexpected error occurred');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
    });

    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        const entityModal = document.getElementById('entityModal');
        const deleteEntityModal = document.getElementById('deleteEntityModal');

        if (event.target === entityModal) {
            closeEntityModal();
        }

        if (event.target === deleteEntityModal) {
            closeDeleteModal();
        }
    });
});

// Add entity to DOM
function addEntityToDom(entity) {
    // Check if no entities message exists and remove it
    const noEntitiesMessage = document.getElementById('no-entities-message');
    if (noEntitiesMessage) {
        noEntitiesMessage.remove();
    }

    // Get or create container
    let container = document.querySelector('#entities-container .space-y-3');
    if (!container) {
        container = document.createElement('div');
        container.className = 'space-y-3';
        document.getElementById('entities-container').appendChild(container);
    }

    // Create entity element
    const entityEl = document.createElement('div');
    entityEl.className = 'border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition entity-item';
    entityEl.setAttribute('data-entity-id', entity.id);

    let content = `
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-1">${escapeHtml(entity.name)}</h3>
                <p class="text-xs text-gray-500">${ucFirst(entity.type)}</p>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="openEntityModal('edit', ${entity.id})" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" onclick="deleteEntity(${entity.id}, '${escapeHtml(entity.name)}')" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;

    if (entity.contact_person) {
        content += `
            <p class="text-xs text-gray-600 mt-2">
                <i class="fas fa-user mr-1"></i> ${escapeHtml(entity.contact_person)}
            </p>
        `;
    }

    if (entity.phone) {
        content += `
            <p class="text-xs text-gray-600 mt-1">
                <i class="fas fa-phone-alt mr-1"></i> ${escapeHtml(entity.phone)}
            </p>
        `;
    }

    if (entity.email) {
        content += `
            <p class="text-xs text-gray-600 mt-1">
                <i class="fas fa-envelope mr-1"></i> ${escapeHtml(entity.email)}
            </p>
        `;
    }

    if (entity.address) {
        content += `
            <div class="text-xs text-gray-600 mt-1">
                <i class="fas fa-map-marker-alt mr-1"></i> 
                <span class="truncate block">${escapeHtml(entity.address)}</span>
            </div>
        `;
    }

    if (entity.notes) {
        const notes = entity.notes.length > 100 ? entity.notes.substring(0, 100) + '...' : entity.notes;
        content += `
            <div class="mt-2 pt-2 border-t border-gray-100">
                <p class="text-xs text-gray-500 italic">${escapeHtml(notes)}</p>
            </div>
        `;
    }

    entityEl.innerHTML = content;

    // Add to container with animation
    container.prepend(entityEl);
    entityEl.style.opacity = '0';
    entityEl.style.transform = 'translateY(-10px)';

    // Trigger animation
    setTimeout(() => {
        entityEl.style.transition = 'opacity 0.3s ease-in, transform 0.3s ease-out';
        entityEl.style.opacity = '1';
        entityEl.style.transform = 'translateY(0)';
    }, 10);
}

// Update entity in DOM
function updateEntityInDom(entity) {
    const entityEl = document.querySelector(`.entity-item[data-entity-id="${entity.id}"]`);
    if (!entityEl) return;

    let content = `
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-1">${escapeHtml(entity.name)}</h3>
                <p class="text-xs text-gray-500">${ucFirst(entity.type)}</p>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="openEntityModal('edit', ${entity.id})" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" onclick="deleteEntity(${entity.id}, '${escapeHtml(entity.name)}')" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;

    if (entity.contact_person) {
        content += `
            <p class="text-xs text-gray-600 mt-2">
                <i class="fas fa-user mr-1"></i> ${escapeHtml(entity.contact_person)}
            </p>
        `;
    }

    if (entity.phone) {
        content += `
            <p class="text-xs text-gray-600 mt-1">
                <i class="fas fa-phone-alt mr-1"></i> ${escapeHtml(entity.phone)}
            </p>
        `;
    }

    if (entity.email) {
        content += `
            <p class="text-xs text-gray-600 mt-1">
                <i class="fas fa-envelope mr-1"></i> ${escapeHtml(entity.email)}
            </p>
        `;
    }

    if (entity.address) {
        content += `
            <div class="text-xs text-gray-600 mt-1">
                <i class="fas fa-map-marker-alt mr-1"></i> 
                <span class="truncate block">${escapeHtml(entity.address)}</span>
            </div>
        `;
    }

    if (entity.notes) {
        const notes = entity.notes.length > 100 ? entity.notes.substring(0, 100) + '...' : entity.notes;
        content += `
            <div class="mt-2 pt-2 border-t border-gray-100">
                <p class="text-xs text-gray-500 italic">${escapeHtml(notes)}</p>
            </div>
        `;
    }

    // Update content with highlight animation
    entityEl.innerHTML = content;
    entityEl.style.transition = 'background-color 0.5s ease';
    entityEl.style.backgroundColor = '#f0f9ff';

    setTimeout(() => {
        entityEl.style.backgroundColor = '';
    }, 1500);
}

// Helper: Capitalize first letter
function ucFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Helper: Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) {
        return map[m];
    });
}

// Toast functions
function showToast(type, title, message) {
    const toast = document.getElementById('toast');
    const toastIcon = document.getElementById('toastIcon');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');

    // Set content based on type
    if (type === 'success') {
        toastIcon.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xl"></i>';
        toastTitle.textContent = title || 'Success';
        toast.querySelector('div').className = 'bg-white rounded-lg border-l-4 border-green-500 shadow-md p-4 max-w-md';
    } else if (type === 'error') {
        toastIcon.innerHTML = '<i class="fas fa-exclamation-circle text-red-500 text-xl"></i>';
        toastTitle.textContent = title || 'Error';
        toast.querySelector('div').className = 'bg-white rounded-lg border-l-4 border-red-500 shadow-md p-4 max-w-md';
    } else {
        toastIcon.innerHTML = '<i class="fas fa-info-circle text-blue-500 text-xl"></i>';
        toastTitle.textContent = title || 'Information';
        toast.querySelector('div').className = 'bg-white rounded-lg border-l-4 border-blue-500 shadow-md p-4 max-w-md';
    }

    // Set message
    toastMessage.textContent = message;

    // Show toast with animation
    toast.classList.remove('hidden');
    toast.classList.remove('-translate-y-10', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');

    // Auto hide after 5 seconds
    setTimeout(hideToast, 5000);
}

function hideToast() {
    const toast = document.getElementById('toast');
    toast.classList.add('-translate-y-10', 'opacity-0');
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 300);
}

// Add any global scripts here
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip-target]');
    tooltips.forEach(tooltip => {
        const target = document.getElementById(tooltip.dataset.tooltipTarget);
        if (target) {
            tooltip.addEventListener('mouseenter', () => {
                target.classList.remove('hidden');
            });
            tooltip.addEventListener('mouseleave', () => {
                target.classList.add('hidden');
            });
        }
    });
});
</script>
</body>

</html>