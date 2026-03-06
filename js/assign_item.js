document.addEventListener('DOMContentLoaded', function () {
    const assignModal = document.getElementById('assign-item-modal');
    const assignForm = document.getElementById('assign-item-form');
    const assignItemSearch = document.getElementById('assign-item-search');
    const assignItemResults = document.getElementById('assign-item-results');
    const btnSubmitAssignment = document.getElementById('btn-submit-assignment');
    const btnOpenAssignAsset = document.getElementById('btn-open-assign-asset');
    const assignDeductToggle = document.getElementById('assign-deduct-stock');
    const deductStatusText = document.getElementById('deduct-status-text');
    const deductInfoBox = document.getElementById('stock-deduction-info');

    // Closer buttons
    const closeBtns = document.querySelectorAll('.assign-close-btn');

    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            assignModal.classList.remove('active');
            resetAssignForm();
        });
    });

    if (btnOpenAssignAsset) {
        btnOpenAssignAsset.addEventListener('click', () => {
            // Get employee info from the current assets modal context
            const empId = document.getElementById('view-assets-emp-id')?.value;
            const empName = document.getElementById('view-assets-emp-name')?.value;

            if (!empId) {
                if (typeof showModal === 'function') showModal('No employee selected.', 'warning');
                return;
            }

            document.getElementById('assign-emp-id').value = empId;
            document.getElementById('assign-emp-name-display').value = empName || 'Unknown Employee';

            assignModal.classList.add('active');
        });
    }

    // Direct Assign Icon (from employee table)
    document.addEventListener('click', function (e) {
        const icon = e.target.closest('.direct-assign-icon');
        if (icon) {
            console.log('Direct assign button clicked');
            const empId = icon.getAttribute('data-id');
            const empName = icon.getAttribute('data-name');
            console.log('Assigning for:', empName, empId);

            const empIdInput = document.getElementById('assign-emp-id');
            const empNameInput = document.getElementById('assign-emp-name-display');

            if (empIdInput && empNameInput) {
                empIdInput.value = empId;
                empNameInput.value = empName || 'Unknown Employee';

                assignModal.classList.add('active');
                console.log('Modal opened via table button');
            } else {
                console.error('Assign modal inputs not found!');
            }
        }
    });

    if (assignDeductToggle) {
        assignDeductToggle.addEventListener('change', function () {
            if (this.checked) {
                deductStatusText.textContent = 'WILL';
                deductStatusText.style.color = '#c2410c';
                deductInfoBox.style.background = '#fff7ed';
                deductInfoBox.style.borderColor = '#fdba74';
                if (typeof showModal === 'function') {
                    // Optional subtle toast or notification
                }
            } else {
                deductStatusText.textContent = 'NOT';
                deductStatusText.style.color = '#92400e';
                deductInfoBox.style.background = '#fffbeb';
                deductInfoBox.style.borderColor = '#fef3c7';
            }
        });
    }

    // Search Logic
    let searchTimeout;
    const clearBtn = document.getElementById('clear-selected-item');
    const searchContainer = assignItemSearch.closest('.dropdown-search-container');

    function fetchAndShowResults(query = '') {
        const basePath = typeof window.basePath !== 'undefined' ? window.basePath : '';
        fetch(`${basePath}api/search_supplies.php?query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.supplies.length > 0) {
                    renderSearchResults(data.supplies);
                } else {
                    assignItemResults.innerHTML = '<div style="padding:15px; color:#64748b; font-size:0.9rem; text-align:center;"><i class="fas fa-search"></i> No items found</div>';
                    assignItemResults.style.display = 'block';
                }
            })
            .catch(err => console.error('Error fetching supplies:', err));
    }

    assignItemSearch.addEventListener('focus', function () {
        searchContainer.classList.add('active');
        if (this.value.trim() === '') {
            fetchAndShowResults('');
        }
    });

    assignItemSearch.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query === '') {
            fetchAndShowResults('');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetchAndShowResults(query);
        }, 300);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            resetSelectedItem();
        });
    }

    function renderSearchResults(supplies) {
        assignItemResults.innerHTML = supplies.map(s => {
            const stock = parseInt(s.quantity) || 0;
            const retStock = parseInt(s.returned_quantity) || 0;
            const classification = s.property_classification || 'No Classification';
            return `
                <div class="search-dropdown-item" data-id="${s.supply_id}" data-name="${s.item}" data-unit="${s.unit}" data-stock-no="${s.stock_no}" data-classification="${classification}">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 0.9rem; color: #1e293b;">${s.item}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">${s.stock_no || 'No Stock No.'} | ${s.unit}</div>
                        <div style="font-size: 0.7rem; color: #1e40af; font-weight: 600;">${classification}</div>
                    </div>
                    <div style="text-align: right;">
                        <span class="stock-badge" title="New Stock">${stock} New</span>
                        <span class="stock-badge" style="background: #fef3c7; color: #92400e;" title="Returned Stock">${retStock} Ret</span>
                    </div>
                </div>
            `;
        }).join('');
        assignItemResults.style.display = 'block';

        // Add click events to results
        assignItemResults.querySelectorAll('.search-dropdown-item').forEach(item => {
            item.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const unit = this.getAttribute('data-unit');
                const stockNo = this.getAttribute('data-stock-no');
                const classification = this.getAttribute('data-classification');

                document.getElementById('selected-assign-item-id').value = id;
                assignItemSearch.value = name;
                assignItemResults.style.display = 'none';
                searchContainer.classList.remove('active');
                if (clearBtn) clearBtn.style.display = 'block';

                // Show preview
                const preview = document.getElementById('selected-item-preview');
                document.getElementById('preview-item-name').textContent = name;
                document.getElementById('preview-item-details').textContent = `${stockNo || 'No Stock No.'} | ${unit} [${classification}]`;
                preview.style.display = 'block';
            });
        });
    }

    function resetSelectedItem() {
        document.getElementById('selected-assign-item-id').value = '';
        assignItemSearch.value = '';
        document.getElementById('selected-item-preview').style.display = 'none';
        if (clearBtn) clearBtn.style.display = 'none';
    }

    // Submit Logic
    btnSubmitAssignment.addEventListener('click', function () {
        const empId = document.getElementById('assign-emp-id').value;
        const supplyId = document.getElementById('selected-assign-item-id').value;
        const qty = document.getElementById('assign-qty').value;
        const date = document.getElementById('assign-date').value;
        const stockType = document.getElementById('assign-stock-type').value;
        const purpose = document.getElementById('assign-purpose').value;
        const isDeduct = assignDeductToggle ? assignDeductToggle.checked : false;

        if (!supplyId) {
            const msg = 'Please select an item first.';
            if (typeof showModal === 'function') showModal(msg, 'warning');
            else alert(msg);
            return;
        }

        if (qty <= 0) {
            const msg = 'Quantity must be greater than 0.';
            if (typeof showModal === 'function') showModal(msg, 'warning');
            else alert(msg);
            return;
        }

        const payload = {
            employee: { id: empId, date: date, purpose: purpose },
            items: [{ id: supplyId, requestQty: qty, stockType: stockType }]
        };

        const apiEndpoint = isDeduct ? 'api/submit_direct_assignment.php' : 'api/submit_direct_assignment_no_stock.php';

        const originalText = btnSubmitAssignment.innerHTML;
        btnSubmitAssignment.disabled = true;
        btnSubmitAssignment.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        const basePath = typeof window.basePath !== 'undefined' ? window.basePath : '';
        fetch(`${basePath}${apiEndpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof showModal === 'function') {
                        const successMsg = isDeduct ? 'Asset assigned and stock deducted!' : 'Asset assigned successfully! (No stock deduction)';
                        showModal(successMsg, 'success', () => {
                            assignModal.classList.remove('active');
                            resetAssignForm();
                            // If we are in employee page, we might want to refresh the assets tab/modal
                            if (typeof window.viewEmployeeAssets === 'function') {
                                window.viewEmployeeAssets(empId, document.getElementById('assign-emp-name-display').value);
                            } else {
                                location.reload();
                            }
                        });
                    }
                } else {
                    if (typeof showModal === 'function') showModal('Error: ' + data.message, 'error');
                }
            })
            .catch(err => {
                console.error('Submission error:', err);
                const msg = 'Connection error. Please check your network and try again.';
                if (typeof showModal === 'function') showModal(msg, 'error');
                else alert(msg);
            })
            .finally(() => {
                btnSubmitAssignment.disabled = false;
                btnSubmitAssignment.innerHTML = originalText;
            });
    });

    function resetAssignForm() {
        assignForm.reset();
        document.getElementById('selected-assign-item-id').value = '';
        document.getElementById('selected-item-preview').style.display = 'none';
        assignItemResults.style.display = 'none';
        if (clearBtn) clearBtn.style.display = 'none';
        if (searchContainer) searchContainer.classList.remove('active');
    }

    // Close results on outside click
    document.addEventListener('click', function (e) {
        if (!assignItemSearch.contains(e.target) && !assignItemResults.contains(e.target)) {
            assignItemResults.style.display = 'none';
        }
    });

});
