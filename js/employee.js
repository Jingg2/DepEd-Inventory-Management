(function () {
    console.log('employee.js: IIFE executing...');

    function initEmployeeModule() {
        console.log('employee.js: Initializing module...');

        const registrationModal = document.getElementById("registrationModal");
        const editModal = document.getElementById("editEmployeeModal");
        const openRegBtn = document.getElementById("openModalBtn");

        if (openRegBtn) {
            openRegBtn.onclick = (e) => {
                e.preventDefault();
                console.log('Opening registration modal');
                if (registrationModal) registrationModal.style.display = "block";
            };
        }

        const employeeForm = document.getElementById("employeeForm");
        if (employeeForm) {
            employeeForm.addEventListener('submit', function (e) {
                const idInput = this.querySelector('input[name="employee_id"]');
                const idValue = idInput ? idInput.value.trim() : '';

                if (!/^\d{7}$/.test(idValue)) {
                    e.preventDefault();
                    const sm = typeof window.showModal === 'function' ? window.showModal : (typeof showModal === 'function' ? showModal : null);
                    if (sm) sm('Employee ID must be exactly 7 digits.', 'error');
                    else alert('Employee ID must be exactly 7 digits.');
                    return false;
                }
            });
        }

        // Action Button Handling (Event Delegation)
        document.addEventListener('click', function (e) {
            // Modal Closing
            if (e.target.classList.contains('close-modal') || e.target.classList.contains('btn-cancel')) {
                const modal = e.target.closest('.modal');
                if (modal) modal.style.display = 'none';
                return;
            }

            if (e.target.classList.contains('modal')) {
                e.target.style.display = "none";
                return;
            }

            // Edit Button
            const editBtn = e.target.closest('.update-employee-btn');
            if (editBtn) {
                console.log('Edit clicked for ID:', editBtn.dataset.id);
                const data = editBtn.dataset;

                if (document.getElementById('edit_employee_id')) {
                    document.getElementById('edit_employee_id').value = data.id || '';
                    document.getElementById('edit_first_name').value = data.firstName || '';
                    document.getElementById('edit_last_name').value = data.lastName || '';
                    document.getElementById('edit_position').value = data.position || '';
                    document.getElementById('edit_department_id').value = data.departmentId || '';
                    document.getElementById('edit_role').value = data.role || 'Staff';
                    document.getElementById('edit_status').value = data.status || 'Active';

                    const deptSelect = document.getElementById('edit_department_id');
                    if (typeof window.toggleDeptField === 'function') {
                        window.toggleDeptField(deptSelect, 'editCustomDeptGroup');
                    }
                }

                if (editModal) editModal.style.display = 'block';
                return;
            }

            // Delete Button
            const deleteBtn = e.target.closest('.delete-employee-btn');
            if (deleteBtn) {
                e.preventDefault();
                const id = deleteBtn.dataset.id;
                const name = deleteBtn.dataset.name;
                console.log('Delete button clicked for:', name, id);

                const showMsg = typeof window.showModal === 'function' ? window.showModal :
                    (typeof showModal === 'function' ? showModal : null);

                if (showMsg) {
                    console.log('Using custom showModal for delete confirmation');
                    showMsg(`Are you sure you want to delete employee <strong>${name}</strong> (ID: ${id})?<br><br><small style="color: #e74c3c;"><strong>Warning:</strong> This will permanently delete all related requisitions and records associated with this employee.</small>`, 'confirm', (result) => {
                        if (result) {
                            console.log('User confirmed deletion of:', id);
                            submitDelete(id);
                        } else {
                            console.log('Deletion cancelled by user');
                        }
                    });
                } else {
                    console.warn('showModal not found, falling back to window.confirm');
                    if (confirm(`Are you sure you want to delete employee ${name} (ID: ${id})?`)) {
                        submitDelete(id);
                    }
                }
                return;
            }
        });

        function submitDelete(id) {
            console.log('Submitting deletion fetch for ID:', id);
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('employee_id', id);
            formData.append('ajax', '1');

            fetch('employees', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON response:', text);
                        throw new Error('Server returned an invalid response. Please check the console for details.');
                    }
                })
                .then(data => {
                    console.log('Delete API response:', data);
                    if (data.success) {
                        const sm = typeof window.showModal === 'function' ? window.showModal : (typeof showModal === 'function' ? showModal : null);
                        if (sm) sm('Employee deleted successfully!', 'success', () => location.reload());
                        else { alert('Employee deleted successfully!'); location.reload(); }
                    } else {
                        const sm = typeof window.showModal === 'function' ? window.showModal : (typeof showModal === 'function' ? showModal : null);
                        if (sm) sm(data.message || 'Failed to delete employee.', 'error');
                        else alert(data.message || 'Failed to delete employee.');
                    }
                })
                .catch(error => {
                    console.error('Delete fetch error:', error);
                    alert('An unexpected error occurred during deletion: ' + error.message);
                });
        }

        // Auto-open modal if there's an error message
        const errorMsg = document.querySelector('.error-message');
        if (errorMsg) {
            const parentModal = errorMsg.closest('.modal');
            if (parentModal) parentModal.style.display = "block";
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeeModule);
    } else {
        initEmployeeModule();
    }
})();
