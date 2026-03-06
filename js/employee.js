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

                if (idValue !== '' && !/^\d+$/.test(idValue)) {
                    e.preventDefault();
                    const sm = typeof window.showModal === 'function' ? window.showModal : (typeof showModal === 'function' ? showModal : null);
                    if (sm) sm('Employee ID must be numeric.', 'error');
                    else alert('Employee ID must be numeric.');
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

        // Search and Filter Logic
        const searchInput = document.getElementById('employeeSearch');
        const deptFilter = document.getElementById('deptFilter');
        const statusFilter = document.getElementById('statusFilter');
        const tableBody = document.getElementById('employeeTableBody');

        if (searchInput && deptFilter && statusFilter && tableBody) {
            const filterRows = () => {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const selectedDept = deptFilter.value;
                const selectedStatus = statusFilter.value;
                const rows = tableBody.querySelectorAll('tr');
                let foundAny = false;

                rows.forEach(row => {
                    // Skip the "No employees found" row if it exists
                    if (row.cells.length === 1 && row.cells[0].colSpan === 9) return;

                    const id = row.cells[0].textContent.toLowerCase();
                    const firstName = row.cells[1].textContent.toLowerCase();
                    const lastName = row.cells[2].textContent.toLowerCase();
                    const department = row.cells[4].textContent.trim();
                    const status = row.cells[6].textContent.trim();

                    const matchesSearch = id.includes(searchTerm) ||
                        firstName.includes(searchTerm) ||
                        lastName.includes(searchTerm);
                    const matchesDept = !selectedDept || department === selectedDept;
                    const matchesStatus = !selectedStatus || status === selectedStatus;

                    if (matchesSearch && matchesDept && matchesStatus) {
                        row.style.display = '';
                        foundAny = true;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Handle "No matching results" display
                let noResultRow = document.getElementById('noResultsRow');
                if (!foundAny) {
                    if (!noResultRow) {
                        noResultRow = document.createElement('tr');
                        noResultRow.id = 'noResultsRow';
                        noResultRow.innerHTML = `<td colspan="9" style="text-align: center; padding: 20px; color: var(--slate-500);">
                            <i class="fas fa-search" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                            No matching employees found.
                        </td>`;
                        tableBody.appendChild(noResultRow);
                    }
                } else if (noResultRow) {
                    noResultRow.remove();
                }
            };

            searchInput.addEventListener('input', filterRows);
            deptFilter.addEventListener('change', filterRows);
            statusFilter.addEventListener('change', filterRows);
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
