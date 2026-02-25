document.addEventListener('DOMContentLoaded', function () {
    console.log('admin_requisition.js: Initializing...');

    // Selectors
    const adminRequestModal = document.getElementById('admin-request-modal');
    const adminRequestCloseBtn = document.getElementById('admin-request-close');
    const adminViewRequestBtn = document.getElementById('admin-view-requisition');
    const adminRequestTableBody = document.getElementById('admin-request-table-body');
    const adminClearRequestBtn = document.getElementById('admin-clear-request-btn');
    const adminSubmitRequestBtn = document.getElementById('admin-submit-request-btn');
    const adminEmptyRow = document.getElementById('admin-empty-request-row');
    const adminRequestBadge = document.getElementById('admin-view-requisition');
    const adminFabBtn = document.getElementById('admin-fab-view-request');
    const adminFabBadge = document.getElementById('admin-fab-badge');

    let requestItems = [];
    let isEmployeeValid = false;

    // Initialize
    updateRequestCount();

    // Help Function to Open Modal
    function openAdminRequisitionModal() {
        renderRequestTable();
        adminRequestModal.classList.add('active');
        fetchCategorizedEmployees(); // Ensure data is loaded and UI is populated
    }

    // Event Listeners related to Request Modal
    if (adminViewRequestBtn) {
        adminViewRequestBtn.addEventListener('click', openAdminRequisitionModal);
    }

    if (adminRequestCloseBtn) {
        adminRequestCloseBtn.addEventListener('click', function () {
            adminRequestModal.classList.remove('active');
        });
    }

    if (adminFabBtn) {
        adminFabBtn.addEventListener('click', openAdminRequisitionModal);
    }

    // Add to Request logic (Event Delegation)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-admin-request-item');
        if (btn) {
            e.preventDefault();
            const card = btn.closest('.supply-card');
            if (card) {
                const item = {
                    id: card.getAttribute('data-id'),
                    stockNo: card.getAttribute('data-stock-no'),
                    name: card.getAttribute('data-name'),
                    description: card.getAttribute('data-description'),
                    unit: card.getAttribute('data-unit'),
                    maxQty: parseInt(card.getAttribute('data-quantity')),
                    requestQty: 1
                };
                addToRequest(item);
            }
        }
    });

    if (adminClearRequestBtn) {
        adminClearRequestBtn.addEventListener('click', function () {
            showConfirm('Are you sure you want to clear all items?', function (result) {
                if (result) {
                    requestItems = [];
                    updateRequestCount();
                    renderRequestTable();
                }
            });
        });
    }

    // --- Standardized Employee Selection Logic ---
    const empSelect = document.getElementById('admin-req-emp-id-select');
    const empHiddenIdInput = document.getElementById('admin-req-emp-id');
    const empNameInput = document.getElementById('admin-req-name');
    const empPositionInput = document.getElementById('admin-req-designation');
    const empDeptInput = document.getElementById('admin-req-department');
    const empOfficeFilter = document.getElementById('admin-emp-office-filter');

    let employeeData = null; // Stores { "Dept Name": [employees...] }

    // Fetch employee data and initial UI setup
    function fetchCategorizedEmployees() {
        // If we already have data, just ensure the UI is populated
        if (employeeData) {
            populateOfficeFilter();
            populateEmployeeSelect();
            return Promise.resolve(employeeData);
        }

        const basePath = typeof window.basePath !== 'undefined' ? window.basePath : '';
        return fetch(`${basePath}api/get_employees_categorized.php`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    employeeData = data.departments;
                    populateOfficeFilter();
                    populateEmployeeSelect();
                    return employeeData;
                }
                throw new Error(data.message || 'Failed to fetch employees');
            })
            .catch(error => console.error('Error fetching categorized employees:', error));
    }

    function populateOfficeFilter() {
        if (!empOfficeFilter || !employeeData) return;

        const currentVal = empOfficeFilter.value;
        empOfficeFilter.innerHTML = '<option value="">All Offices / Departments</option>';

        Object.keys(employeeData).sort().forEach(dept => {
            const option = document.createElement('option');
            option.value = dept;
            option.textContent = dept;
            empOfficeFilter.appendChild(option);
        });

        empOfficeFilter.value = currentVal;
    }

    function populateEmployeeSelect() {
        if (!empSelect || !employeeData) return;

        const selectedOffice = empOfficeFilter ? empOfficeFilter.value : '';
        empSelect.innerHTML = '<option value="">Select Employee...</option>';

        for (const [dept, employees] of Object.entries(employeeData)) {
            if (selectedOffice !== '' && selectedOffice !== dept) continue;

            const group = document.createElement('optgroup');
            group.label = dept;

            employees.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.employee_id;
                option.textContent = `${emp.first_name} ${emp.last_name}`; // Only name as requested
                option._empData = emp;
                group.appendChild(option);
            });

            empSelect.appendChild(group);
        }
    }

    if (empSelect) {
        empSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const emp = selectedOption._empData;

            if (emp) {
                empHiddenIdInput.value = emp.employee_id;
                empNameInput.value = `${emp.first_name} ${emp.last_name}`;
                empPositionInput.value = emp.position || '';
                empDeptInput.value = emp.department_name || '';
                isEmployeeValid = true;

                // Selection Feedback
                const fieldsToHighlight = [empNameInput, empPositionInput, empDeptInput, empSelect];
                fieldsToHighlight.forEach(field => {
                    if (field) {
                        field.classList.add('selection-highlight');
                        setTimeout(() => field.classList.remove('selection-highlight'), 1000);
                    }
                });
            } else {
                empHiddenIdInput.value = '';
                empNameInput.value = '';
                empPositionInput.value = '';
                empDeptInput.value = '';
                isEmployeeValid = false;
            }
        });
    }

    if (empOfficeFilter) {
        empOfficeFilter.addEventListener('change', () => {
            if (employeeData) {
                populateEmployeeSelect();
            } else {
                fetchCategorizedEmployees();
            }
        });
    }


    // Global click listener to close dropdowns (no longer needed for standard select)
    // document.addEventListener('click', closeAllDropdowns); // Removed as per new UI

    if (adminSubmitRequestBtn) {
        adminSubmitRequestBtn.addEventListener('click', function () {
            if (requestItems.length === 0) {
                showModal('Please add items to the requisition first.', 'warning');
                return;
            }

            const empId = document.getElementById('admin-req-emp-id').value;
            const name = document.getElementById('admin-req-name').value;
            const purpose = document.getElementById('admin-req-purpose').value;

            if (!isEmployeeValid || !empId || !name || name === 'Employee Not Found') {
                showModal('Please enter a valid Employee ID.', 'warning');
                return;
            }

            // Reset name color if it was red from previous error
            empNameInput.style.color = '#2d3748';

            // Show loading state
            const originalBtnText = adminSubmitRequestBtn.innerHTML;
            adminSubmitRequestBtn.disabled = true;
            adminSubmitRequestBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const payload = {
                employee: {
                    id: empId,
                    name: name,
                    date: document.getElementById('admin-req-date').value,
                    designation: document.getElementById('admin-req-designation').value,
                    department: document.getElementById('admin-req-department').value,
                    purpose: purpose
                },
                items: requestItems
            };

            const basePath = typeof window.basePath !== 'undefined' ? window.basePath : '';

            fetch(`${basePath}api/submit_requisition.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reset cart
                        requestItems = [];
                        updateRequestCount();
                        renderRequestTable();
                        adminRequestModal.classList.remove('active');

                        // Clear form
                        document.getElementById('admin-req-emp-id').value = '';
                        document.getElementById('admin-req-name').value = '';
                        document.getElementById('admin-req-designation').value = '';
                        document.getElementById('admin-req-department').value = '';
                        document.getElementById('admin-req-purpose').value = '';
                        document.getElementById('admin-req-emp-id').style.borderColor = '#e2e8f0';
                        document.getElementById('admin-req-emp-id').style.backgroundColor = '#ffffff';
                        isEmployeeValid = false;

                        showModal(`Requisition submitted successfully! No: ${data.requisition_no}`, 'success', () => location.reload());
                    } else {
                        showModal('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Submission Error:', error);
                    showModal('An error occurred while submitting.', 'error');
                })
                .finally(() => {
                    adminSubmitRequestBtn.disabled = false;
                    adminSubmitRequestBtn.innerHTML = originalBtnText;
                });
        });
    }

    function addToRequest(newItem) {
        const existingItem = requestItems.find(item => item.id === newItem.id);

        if (existingItem) {
            if (existingItem.requestQty < existingItem.maxQty) {
                existingItem.requestQty++;
                if (typeof showModal === 'function') {
                    showModal(`Added another ${newItem.name}.`, 'success');
                }
            } else {
                showModal(`Cannot add more. Max stock available is ${existingItem.maxQty}.`, 'warning');
            }
        } else {
            if (newItem.maxQty > 0) {
                requestItems.push(newItem);
                if (typeof showModal === 'function') {
                    showModal(`${newItem.name} added to requisition.`, 'success');
                }
            } else {
                showModal('This item is out of stock.', 'error');
            }
        }
        updateRequestCount();
    }

    function updateRequestCount() {
        const count = requestItems.reduce((sum, item) => sum + item.requestQty, 0);
        if (adminRequestBadge) {
            const hasAdmin = adminRequestBadge.textContent.includes('Admin');
            const label = hasAdmin ? 'Requisition Slip (Admin)' : 'Requisition Slip';
            adminRequestBadge.innerHTML = `<i class="fas fa-clipboard-list"></i> ${label} (${count})`;
        }

        // Update FAB
        if (adminFabBadge) {
            adminFabBadge.textContent = count;
            adminFabBadge.style.display = count > 0 ? 'flex' : 'none';
        }

        // Trigger pulse animation on FAB if adding items
        if (adminFabBtn && count > 0) {
            adminFabBtn.classList.remove('fab-pulse');
            void adminFabBtn.offsetWidth; // Trigger reflow
            adminFabBtn.classList.add('fab-pulse');
        }
    }

    function renderRequestTable() {
        adminRequestTableBody.innerHTML = '';

        if (requestItems.length === 0) {
            adminRequestTableBody.appendChild(adminEmptyRow);
            return;
        }

        requestItems.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.stockNo}</td>
                <td>${item.unit}</td>
                <td>${item.name}</td>
                <td>${item.description || 'N/A'}</td>
                <td>
                    <input type="number" min="1" max="${item.maxQty}" value="${item.requestQty}" 
                           class="admin-qty-input form-control" data-id="${item.id}"
                           style="width: 80px;">
                </td>
                <td style="text-align: center;">
                    <button class="admin-remove-btn" data-id="${item.id}" style="background: #fed7d7; color: #c53030; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            adminRequestTableBody.appendChild(tr);
        });

        // Add event listeners to newly created elements
        adminRequestTableBody.querySelectorAll('.admin-remove-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                requestItems = requestItems.filter(item => item.id !== id);
                updateRequestCount();
                renderRequestTable();
            });
        });

        adminRequestTableBody.querySelectorAll('.admin-qty-input').forEach(input => {
            input.addEventListener('change', function () {
                const id = this.getAttribute('data-id');
                let newQty = parseInt(this.value);
                const item = requestItems.find(i => i.id === id);
                if (item) {
                    if (newQty < 1) newQty = 1;

                    if (newQty > item.maxQty) {
                        showModal(`Only ${item.maxQty} available in stock.`, 'warning');
                        newQty = item.maxQty;
                    }

                    item.requestQty = newQty;
                    this.value = newQty;
                    updateRequestCount();
                }
            });
        });
    }
});
