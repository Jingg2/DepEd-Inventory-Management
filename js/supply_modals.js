/**
 * Supply Page Modal Interactions
 * Handles Add, Edit, View, Delete, and Stock Card modal logic.
 */

// Smart initialization - works for both regular and AJAX page loads
(function initSupplyModals() {
    console.log('supply_modals.js: Initializing...');

    // CRITICAL FIX: Force close all modals on page load
    // Some other script or PHP condition is setting them as 'active' initially
    const allModals = document.querySelectorAll('.modal');
    allModals.forEach(modal => {
        modal.classList.remove('active');
    });
    console.log('Forced all modals closed on init');

    // Get basePath from global variable or default to empty
    const basePath = typeof window.basePath !== 'undefined' ? window.basePath : '';

    // ==========================================
    // 1. HELPER FUNCTIONS
    // ==========================================

    /**
     * Toggles the "Other" category input field
     */
    window.toggleCustomCategory = function (selectElement, customGroupId) {
        const customGroup = document.getElementById(customGroupId);
        if (customGroup) {
            if (selectElement.value === 'Other') {
                customGroup.style.display = 'block';
                const input = customGroup.querySelector('input');
                if (input) input.required = true;
            } else {
                customGroup.style.display = 'none';
                const input = customGroup.querySelector('input');
                if (input) {
                    input.required = false;
                    input.value = ''; // Clear value when hidden
                }
            }
        }
    };

    /**
     * Updates the cost calculation based on Unit Cost and Quantity
     */
    function calculateTotalCost(prefix = '') {
        const unitCostInput = document.getElementById(prefix + 'unit-cost');
        const quantityInput = document.getElementById(prefix + 'quantity');
        const totalCostInput = document.getElementById(prefix + 'total-cost'); // Hidden or visible

        // For Edit modal, quantity is readonly, but might want to calculate based on it + add stock?
        // Currently logic just multiplies unit cost * quantity.

        if (unitCostInput && quantityInput && totalCostInput) {
            const unitCost = parseFloat(unitCostInput.value) || 0;
            const quantity = parseFloat(quantityInput.value) || 0;
            const total = unitCost * quantity;
            totalCostInput.value = total.toFixed(2);

            // Also update visible text if exists (e.g. in a span)
            const displaySpan = document.getElementById(prefix + 'total-cost-display');
            if (displaySpan) displaySpan.textContent = total.toFixed(2);
        }
    }

    // Attach calculation listeners (ADD)
    const addUnitCost = document.getElementById('unit-cost');
    const addQuantity = document.getElementById('quantity');
    if (addUnitCost) addUnitCost.addEventListener('input', () => calculateTotalCost(''));
    if (addQuantity) addQuantity.addEventListener('input', () => calculateTotalCost(''));

    // Attach calculation listeners (EDIT)
    const editUnitCost = document.getElementById('edit-unit-cost');
    // Quantity is readonly in edit, so no input event needed usually, but if we change logic:
    const editQuantity = document.getElementById('edit-quantity');

    if (editUnitCost) editUnitCost.addEventListener('input', () => calculateTotalCost('edit-'));



    // ==========================================
    // 2. VIEW DETAILS MODAL
    // ==========================================
    const itemModal = document.getElementById('item-modal');
    const itemCloseBtn = document.getElementById('item-close');

    if (itemModal) {
        // Close on button click
        if (itemCloseBtn) {
            itemCloseBtn.addEventListener('click', function () {
                itemModal.classList.remove('active');
                itemModal.style.display = 'none';
                itemModal.style.opacity = '0';
                itemModal.style.visibility = 'hidden';
            });
        }

        // Close on outside click
        window.addEventListener('click', function (event) {
            if (event.target === itemModal) {
                itemModal.classList.remove('active');
                itemModal.style.display = 'none';
                itemModal.style.opacity = '0';
                itemModal.style.visibility = 'hidden';
            }
        });

        // Open View Modal (Event Delegation)
        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('view-icon') || event.target.closest('.view-icon')) {
                event.stopPropagation();
                const icon = event.target.classList.contains('view-icon') ? event.target : event.target.closest('.view-icon');
                const card = icon.closest('.supply-card');

                if (card) {
                    // Extract data
                    const details = {
                        id: card.getAttribute('data-id'),
                        name: card.getAttribute('data-name'),
                        category: card.getAttribute('data-category'),
                        quantity: card.getAttribute('data-quantity'),
                        stockNo: card.getAttribute('data-stock-no'),
                        unit: card.getAttribute('data-unit'),
                        description: card.getAttribute('data-description'),
                        unitCost: card.getAttribute('data-unit-cost'),
                        status: card.getAttribute('data-status'),
                        propClass: card.getAttribute('data-property-classification'),
                        school: card.getAttribute('data-school'),
                        img: card.getAttribute('data-image') || card.querySelector('img')?.src || basePath + 'img/Bogo_City_logo.png',
                        lowT: card.getAttribute('data-low-threshold'),
                        critT: card.getAttribute('data-critical-threshold')
                    };

                    // Populate UI
                    document.getElementById('modal-title').textContent = (details.name || 'Unknown') + ' Details';
                    document.getElementById('modal-img').src = details.img;
                    document.getElementById('modal-stock-no').textContent = details.stockNo || 'N/A';
                    document.getElementById('modal-name').textContent = details.name || 'Unknown';
                    document.getElementById('modal-category').textContent = details.category || 'N/A';
                    document.getElementById('modal-unit').textContent = details.unit || 'N/A';
                    document.getElementById('modal-quantity').textContent = details.quantity || '0';
                    document.getElementById('modal-unit-cost').textContent = details.unitCost ? parseFloat(details.unitCost).toFixed(2) : '0.00';

                    const total = (parseFloat(details.quantity) || 0) * (parseFloat(details.unitCost) || 0);
                    document.getElementById('modal-total-cost').textContent = total.toFixed(2);

                    document.getElementById('modal-status').textContent = details.status || 'Unknown';
                    document.getElementById('modal-low-threshold').textContent = details.lowT || '10';
                    document.getElementById('modal-critical-threshold').textContent = details.critT || '5';
                    document.getElementById('modal-property-classification').textContent = details.propClass || 'N/A';
                    document.getElementById('modal-school').textContent = details.school || 'N/A';
                    document.getElementById('modal-description').textContent = details.description || 'No description';

                    // Track current ID for Edit/Delete from within modal
                    itemModal.setAttribute('data-current-id', details.id);
                    itemModal.classList.add('active');

                    // FORCE VISIBILITY
                    itemModal.style.display = 'flex';
                    itemModal.style.opacity = '1';
                    itemModal.style.visibility = 'visible';
                    itemModal.style.zIndex = '9999';
                }
            }
        });
    }


    // ==========================================
    // 3. ADD SUPPLY MODAL LOIC
    // ==========================================
    // Helper: Reset Add Form (Declared before use)
    function resetAddForm() {
        // Fetch dynamically to ensure element existence
        const f = document.getElementById('add-supply-form');
        if (f) {
            f.reset();
        } else {
            console.warn('resetAddForm: #add-supply-form not found');
        }

        // Reset specific fields
        const totalCostInput = document.getElementById('total-cost');
        if (totalCostInput) totalCostInput.value = '0.00';

        const idField = document.getElementById('supply-id-field');
        if (idField) idField.value = '';

        const customGroup = document.getElementById('custom-category-group');
        if (customGroup) customGroup.style.display = 'none';

        // Reset Image Preview
        const imagePreview = document.getElementById('image-preview');
        const dragDropArea = document.getElementById('drag-drop-area');
        const fileInput = document.getElementById('image');
        const previewImg = document.getElementById('preview-img');

        if (imagePreview) imagePreview.style.display = 'none';
        if (dragDropArea) {
            const dragDropContent = dragDropArea.querySelector('.drag-drop-content');
            if (dragDropContent) dragDropContent.style.display = 'block';
            dragDropArea.classList.remove('drag-over');
        }
        if (fileInput) fileInput.value = '';
        if (previewImg) previewImg.src = '';
    }

    // "Add New Supply" Button Click - Robust Delegation (Lazy Loaded)
    // FIXED: Changed from document.body to document to match Stock Card pattern
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('#btn-add-new-supply');
        if (btn) {
            console.log('Add Button Triggered via Delegation');
            e.preventDefault();
            e.stopPropagation();

            // LAZY FETCH: Get modal only when clicked to ensuring potential race conditions are mute
            const targetModal = document.getElementById('add-supply-modal');
            console.log('Modal element found:', targetModal ? 'YES' : 'NO');

            if (targetModal) {
                try {
                    console.log('Before reset - modal classes:', targetModal.className);
                    resetAddForm();
                    console.log('After reset, adding active class...');
                    targetModal.classList.add('active');

                    // FORCE VISIBILITY - Override any CSS hiding it
                    targetModal.style.display = 'block';
                    targetModal.style.opacity = '1';
                    targetModal.style.visibility = 'visible';
                    targetModal.style.zIndex = '99999';
                    targetModal.style.overflowY = 'auto'; // Ensure wrapper bounds handle overflow if needed

                    console.log('After activation - modal classes:', targetModal.className);
                    console.log('Modal display style:', window.getComputedStyle(targetModal).display);
                } catch (err) {
                    console.error('Error opening Add Modal:', err);
                }
            } else {
                console.error('CRITICAL: #add-supply-modal not found in DOM when clicked');
            }
        }
    });

    // Controlled Assets Button - Redirection Logic
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('#btn-controlled-assets');
        if (btn) {
            console.log('Controlled Assets button clicked - Redirecting');
            e.preventDefault();
            e.stopPropagation();

            // Redirect to the new controlled assets screen
            window.location.href = basePath + 'controlled_assets';
        }
    });

    // Close Button Logic for Add Modal (Attached if modal exists)
    const addModal = document.getElementById('add-supply-modal');
    if (addModal) {
        const addCloseBtn = document.getElementById('add-close');
        const cancelAddBtn = document.getElementById('cancel-add-btn');

        const closeAddModal = () => {
            addModal.classList.remove('active');
            addModal.style.display = 'none';
            addModal.style.opacity = '0';
            addModal.style.visibility = 'hidden';
            resetAddForm();
        };

        if (addCloseBtn) addCloseBtn.addEventListener('click', closeAddModal);
        if (cancelAddBtn) cancelAddBtn.addEventListener('click', closeAddModal);
        window.addEventListener('click', (e) => {
            if (e.target === addModal) closeAddModal();
        });
    }

    // ==========================================
    // 4. EDIT SUPPLY MODAL LOGIC
    // ==========================================
    const editModal = document.getElementById('edit-supply-modal');
    const editForm = document.getElementById('edit-supply-form');
    const editCloseBtn = document.getElementById('edit-close');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');

    // Helper: Reset Edit Form
    function resetEditForm() {
        if (editForm) editForm.reset();

        // Reset specific fields
        const totalCostInput = document.getElementById('edit-total-cost');
        if (totalCostInput) totalCostInput.value = '0.00';

        const customGroup = document.getElementById('edit-custom-category-group');
        if (customGroup) customGroup.style.display = 'none';

        // Reset Image Preview
        const imagePreview = document.getElementById('edit-image-preview');
        const dragDropArea = document.getElementById('edit-drag-drop-area');
        const fileInput = document.getElementById('edit-image');
        const previewImg = document.getElementById('edit-preview-img');

        if (imagePreview) imagePreview.style.display = 'none';
        if (dragDropArea) {
            const dragDropContent = dragDropArea.querySelector('.drag-drop-content');
            if (dragDropContent) dragDropContent.style.display = 'block';
            dragDropArea.classList.remove('drag-over');
        }
        if (fileInput) fileInput.value = '';
        if (previewImg) previewImg.src = '';
    }

    if (editModal) {
        const closeEditModal = () => {
            editModal.classList.remove('active');
            editModal.style.display = 'none';
            editModal.style.opacity = '0';
            editModal.style.visibility = 'hidden';
            resetEditForm();
        };

        if (editCloseBtn) editCloseBtn.addEventListener('click', closeEditModal);
        if (cancelEditBtn) cancelEditBtn.addEventListener('click', closeEditModal);
        window.addEventListener('click', (e) => {
            if (e.target === editModal) closeEditModal();
        });

        // "Edit" Icon Click (Event Delegation)
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('edit-icon') || e.target.closest('.edit-icon')) {
                e.stopPropagation();

                const icon = e.target.classList.contains('edit-icon') ? e.target : e.target.closest('.edit-icon');
                let card = icon.closest('.supply-card');

                // If clicked from Detail View modal, find the card by ID
                if (!card && icon.closest('#item-modal')) {
                    const currentId = document.getElementById('item-modal').getAttribute('data-current-id');
                    card = document.querySelector(`.supply-card[data-id="${currentId}"]`);
                    // Close detail modal
                    document.getElementById('item-modal').classList.remove('active');
                }

                if (card) {
                    resetEditForm();

                    // Extract Data
                    const data = {
                        id: card.getAttribute('data-id'),
                        name: card.getAttribute('data-name'),
                        category: card.getAttribute('data-category'),
                        unit: card.getAttribute('data-unit'),
                        desc: card.getAttribute('data-description'),
                        qty: card.getAttribute('data-quantity'),
                        stockNo: card.getAttribute('data-stock-no'),
                        unitCost: card.getAttribute('data-unit-cost'),
                        status: card.getAttribute('data-status'),
                        propClass: card.getAttribute('data-property-classification'),
                        school: card.getAttribute('data-school'),
                        lowT: card.getAttribute('data-low-threshold'),
                        critT: card.getAttribute('data-critical-threshold'),
                        img: card.getAttribute('data-image')
                    };

                    // Populate Fields
                    document.getElementById('edit-supply-id-field').value = data.id || '';
                    document.getElementById('edit-item').value = data.name || '';
                    document.getElementById('edit-stock-no').value = data.stockNo || '';
                    document.getElementById('edit-unit').value = data.unit || '';
                    document.getElementById('edit-description').value = data.desc || '';
                    document.getElementById('edit-quantity').value = data.qty || '0';
                    document.getElementById('edit-unit-cost').value = data.unitCost || '0.00';
                    document.getElementById('edit-status').value = data.status || 'Available';
                    if (document.getElementById('edit-school')) {
                        document.getElementById('edit-school').value = data.school || '';
                    }


                    if (document.getElementById('edit-low-stock-threshold')) {
                        document.getElementById('edit-low-stock-threshold').value = data.lowT || '10';
                    }
                    if (document.getElementById('edit-critical-stock-threshold')) {
                        document.getElementById('edit-critical-stock-threshold').value = data.critT || '5';
                    }

                    // Handle Category
                    const categorySelect = document.getElementById('edit-category');
                    if (categorySelect) {
                        const options = Array.from(categorySelect.options).map(opt => opt.value);
                        if (options.includes(data.category)) {
                            categorySelect.value = data.category;
                        } else {
                            categorySelect.value = 'Other';
                            const customGroup = document.getElementById('edit-custom-category-group');
                            if (customGroup) customGroup.style.display = 'block';
                            const customInput = document.getElementById('edit-custom-category');
                            if (customInput) {
                                customInput.value = data.category;
                                customInput.required = true;
                            }
                        }
                    }

                    // Image Preview in Edit
                    if (data.img && data.img !== (basePath + 'img/Bogo_City_logo.png')) {
                        const previewImg = document.getElementById('edit-preview-img');
                        const imagePreview = document.getElementById('edit-image-preview');
                        const dragDropContent = document.getElementById('edit-drag-drop-area').querySelector('.drag-drop-content');

                        if (previewImg && imagePreview) {
                            previewImg.src = data.img;
                            imagePreview.style.display = 'block';
                            if (dragDropContent) dragDropContent.style.display = 'none';
                        }
                    }

                    editModal.classList.add('active');

                    // FORCE VISIBILITY
                    editModal.style.display = 'flex';
                    editModal.style.opacity = '1';
                    editModal.style.visibility = 'visible';
                    editModal.style.zIndex = '9999';
                }
            }
        });
    }

    // ==========================================
    // 5. FORM SUBMISSION (AJAX) - ADD & EDIT
    // ==========================================

    function handleFormSubmit(form, isEdit) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const prefix = isEdit ? 'edit-' : '';
            const item = document.getElementById(prefix + 'item').value.trim();
            const category = document.getElementById(prefix + 'category').value.trim();
            const unit = document.getElementById(prefix + 'unit').value;

            if (!item || !category || !unit) {
                showModal('Please fill in all required fields.', 'warning');
                return;
            }

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showModal(isEdit ? 'Supply updated successfully!' : 'Supply saved successfully!', 'success', () => location.reload());
                    } else {
                        showModal('Error: ' + (data.message || 'Unknown error occurred.'), 'error');
                    }
                })
                .catch(err => {
                    console.error('Submission Error:', err);
                    showModal('An error occurred while saving.', 'error');
                });
        });
    }

    // Attach form submit handlers (get forms dynamically to avoid undefined errors)
    const dynAddForm = document.getElementById('add-supply-form');
    const dynEditForm = document.getElementById('edit-supply-form');
    if (dynAddForm) handleFormSubmit(dynAddForm, false);
    if (dynEditForm) handleFormSubmit(dynEditForm, true);



    // ==========================================
    // 6. DELETE FUNCTIONALITY
    // ==========================================
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('delete-icon') || e.target.closest('.delete-icon')) {
            e.stopPropagation();
            const icon = e.target.classList.contains('delete-icon') ? e.target : e.target.closest('.delete-icon');
            let card = icon.closest('.supply-card');

            // If triggered from Detail View modal
            if (!card && icon.closest('#item-modal')) {
                const currentId = document.getElementById('item-modal').getAttribute('data-current-id');
                card = document.querySelector(`.supply-card[data-id="${currentId}"]`);
            }

            if (card) {
                const id = card.getAttribute('data-id');
                const name = card.getAttribute('data-name');

                showConfirm(`Are you sure you want to delete "${name}"?`, function (result) {
                    if (!result) return;

                    const deleteItem = (force = false) => {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);
                        formData.append('ajax', '1');
                        if (force) formData.append('force', '1');

                        fetch((basePath || '') + 'supply.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    showModal(force ? 'Item and history deleted.' : 'Item deleted.', 'success', () => location.reload());
                                } else if (!force) {
                                    // Request force delete if there's history
                                    showConfirm(data.message + '\n\nForce delete all related history?', function (forceResult) {
                                        if (forceResult) {
                                            deleteItem(true);
                                        }
                                    });
                                } else {
                                    showModal('Failed: ' + data.message, 'error');
                                }
                            })
                            .catch(e => showModal('Connection error: ' + e.message, 'error'));
                    };

                    deleteItem();
                });
            }
        }
    });


    // ==========================================
    // 7. DRAG AND DROP IMAGE (Unified Helper)
    // ==========================================
    function setupDragDrop(areaId, inputId, previewId, wrapperId, removeBtnId) {
        const dragDropArea = document.getElementById(areaId);
        const fileInput = document.getElementById(inputId);
        const previewImg = document.getElementById(previewId);
        const imagePreview = document.getElementById(wrapperId);
        const removeImageBtn = document.getElementById(removeBtnId);

        if (dragDropArea && fileInput) {
            const preventDefaults = (e) => { e.preventDefault(); e.stopPropagation(); };
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
                dragDropArea.addEventListener(evt, preventDefaults, false);
                document.body.addEventListener(evt, preventDefaults, false);
            });

            dragDropArea.addEventListener('click', (e) => {
                if (e.target !== removeImageBtn) fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                if (this.files[0]) showPreview(this.files[0], previewImg, imagePreview, dragDropArea);
            });

            dragDropArea.addEventListener('drop', (e) => {
                if (e.dataTransfer.files[0]) {
                    fileInput.files = e.dataTransfer.files;
                    showPreview(e.dataTransfer.files[0], previewImg, imagePreview, dragDropArea);
                }
            });

            if (removeImageBtn) {
                removeImageBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    fileInput.value = '';
                    previewImg.src = '';
                    imagePreview.style.display = 'none';
                    dragDropArea.querySelector('.drag-drop-content').style.display = 'block';
                });
            }
        }
    }

    function showPreview(file, imgElem, wrapperElem, areaElem) {
        if (file.type.match('image.*')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imgElem.src = e.target.result;
                areaElem.querySelector('.drag-drop-content').style.display = 'none';
                wrapperElem.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    // Setup for Add Modal
    setupDragDrop('drag-drop-area', 'image', 'preview-img', 'image-preview', 'remove-image-btn');

    // Setup for Edit Modal
    setupDragDrop('edit-drag-drop-area', 'edit-image', 'edit-preview-img', 'edit-image-preview', 'edit-remove-image-btn');


    // ==========================================
    // 8. STOCK CARD MODAL
    // ==========================================
    document.addEventListener('click', function (event) {
        const icon = event.target.closest('.stock-card-icon');
        if (icon) {
            console.log('Stock Card Icon Clicked');
            event.preventDefault();
            event.stopPropagation();

            const card = icon.closest('.supply-card');
            if (card) {
                const id = card.getAttribute('data-id');
                if (id) {
                    const scModal = document.getElementById('stock-card-modal');
                    const scBody = document.getElementById('stock-card-body');
                    const scDownloadBtn = document.getElementById('download-sc-btn');

                    if (!scModal) return;

                    // Load function
                    function loadStockCardHistory(supplyId, fromDate = null, toDate = null) {
                        if (scBody) scBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading transactions...</td></tr>';

                        let url = (basePath || '') + 'api/get_stock_card_history.php?id=' + supplyId;
                        if (fromDate) url += '&from=' + fromDate;
                        if (toDate) url += '&to=' + toDate;

                        fetch(url)
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    const data = result.data;
                                    const isSemiExpendable = (card.getAttribute('data-property-classification') || "").toLowerCase().includes('semi-expendable');
                                    const cardTypeName = isSemiExpendable ? 'Property Card' : 'Stock Card';
                                    const cardTypeIcon = isSemiExpendable ? 'fa-address-card' : 'fa-file-invoice';

                                    document.getElementById('sc-item-name').textContent = data.supply.item || 'N/A';
                                    document.getElementById('sc-stock-no').textContent = data.supply.stock_no || '-';
                                    document.getElementById('sc-unit').textContent = data.supply.unit || '-';
                                    document.getElementById('sc-balance').textContent = data.supply.quantity || '0';

                                    // Update modal title
                                    const h2 = scModal.querySelector('h2');
                                    const subtitle = scModal.querySelector('.modal-subtitle');
                                    if (h2) h2.innerHTML = `<i class="fas ${cardTypeIcon}"></i> ${cardTypeName} Preview`;
                                    if (subtitle) subtitle.textContent = isSemiExpendable ? 'Official Appendix 60 format overview' : 'Official Appendix 58 format overview';

                                    // Update Table Headers
                                    const headerRow = document.getElementById('sc-header-row');
                                    if (headerRow) {
                                        if (isSemiExpendable) {
                                            // Property Card Headers (Appendix 69/60 style)
                                            headerRow.innerHTML = `
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Date</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Reference</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Receipt Qty</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Transfer/Disposal</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Issue To (Office/Person)</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Balance</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Encoder</th>
                                            `;
                                        } else {
                                            // Stock Card Headers (Appendix 58 style)
                                            headerRow.innerHTML = `
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Date</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Reference</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Receipt</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Issuance</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Office/Dept</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Balance</th>
                                                <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Encoder</th>
                                            `;
                                        }
                                    }

                                    // Update download button
                                    if (scDownloadBtn) {
                                        scDownloadBtn.innerHTML = `<i class="fas fa-download"></i> Download Official ${cardTypeName} (Excel)`;
                                        const exportApi = isSemiExpendable ? 'export_property_card_excel.php' : 'export_stock_card_excel.php';
                                        scDownloadBtn.onclick = () => {
                                            let exportUrl = (basePath || '') + 'api/' + exportApi + '?id=' + supplyId;
                                            if (fromDate) exportUrl += '&from=' + fromDate;
                                            if (toDate) exportUrl += '&to=' + toDate;
                                            window.location.href = exportUrl;
                                        };
                                    }

                                    let h = '';
                                    const transactions = data.transactions || [];
                                    const beginningBalance = data.beginning_balance || 0;

                                    if (transactions.length === 0 && !fromDate && !toDate) {
                                        h = '';
                                    } else {
                                        transactions.forEach(t => {
                                            const received = parseInt(t.received) || 0;
                                            const issued = parseInt(t.issued) || 0;
                                            // Format date based on card type to match Excel exports
                                            let dateFmt = 'N/A';
                                            if (t.date) {
                                                const d = new Date(t.date);
                                                if (isSemiExpendable) {
                                                    // MM.DD.YY for Property Card
                                                    const m = String(d.getMonth() + 1).padStart(2, '0');
                                                    const day = String(d.getDate()).padStart(2, '0');
                                                    const y = String(d.getFullYear()).slice(-2);
                                                    dateFmt = `${m}.${day}.${y}`;
                                                } else {
                                                    // YYYY-MM-DD for Stock Card
                                                    dateFmt = t.date.split(' ')[0];
                                                }
                                            }
                                            const reference = t.reference || (received > 0 ? 'Stock Receipt' : 'Adjustment');

                                            let deptOffice = '-';
                                            if (isSemiExpendable) {
                                                deptOffice = (t.first_name || t.last_name)
                                                    ? `${t.first_name || ''} ${t.last_name || ''}`.trim()
                                                    : (t.department_name || '-');
                                            } else {
                                                deptOffice = t.department_name || '-';
                                            }

                                            h += `<tr>
                                                <td class="text-center">${dateFmt}</td>
                                                <td class="text-center">${reference}</td>
                                                <td class="text-center">${received > 0 ? received : ''}</td>
                                                <td class="text-center">${issued > 0 ? issued : ''}</td>
                                                <td class="text-center">${deptOffice}</td>
                                                <td class="text-center">${t.balance}</td>
                                                <td class="text-center">${t.encoder_name || (t.type === 'Issuance' ? 'Requisition' : 'System')}</td>
                                            </tr>`;
                                        });
                                    }
                                    if (scBody) scBody.innerHTML = h;
                                } else {
                                    if (scBody) scBody.innerHTML = `<tr><td colspan="7" class="text-center" style="color:red;">${result.message}</td></tr>`;
                                }
                            })
                            .catch(error => {
                                console.error('Stock Card Fetch Error:', error);
                                if (scBody) scBody.innerHTML = `<tr><td colspan="7" class="text-center" style="color:red;">Error loading history</td></tr>`;
                            });
                    }

                    // Open and load
                    scModal.classList.add('active');
                    scModal.style.display = 'flex';
                    scModal.style.opacity = '1';
                    scModal.style.visibility = 'visible';
                    loadStockCardHistory(id);

                    // Filter button
                    const filterBtn = document.getElementById('sc-filter-btn');
                    if (filterBtn) {
                        filterBtn.onclick = () => {
                            const from = document.getElementById('sc-from-date').value;
                            const to = document.getElementById('sc-to-date').value;
                            loadStockCardHistory(id, from, to);
                        };
                    }

                    // Reset button
                    const resetBtn = document.getElementById('sc-reset-btn');
                    if (resetBtn) {
                        resetBtn.onclick = () => {
                            const fromInput = document.getElementById('sc-from-date');
                            const toInput = document.getElementById('sc-to-date');
                            if (fromInput) fromInput.value = '';
                            if (toInput) toInput.value = '';
                            loadStockCardHistory(id);
                        };
                    }

                    // Re-bind outside click for this session
                    const outsideClickSC = (e) => {
                        if (e.target === scModal) {
                            scModal.classList.remove('active');
                            scModal.style.display = 'none';
                            scModal.style.opacity = '0';
                            scModal.style.visibility = 'hidden';
                            window.removeEventListener('click', outsideClickSC);
                        }
                    };
                    window.addEventListener('click', outsideClickSC);
                }
            }
        }
    });

    console.log('Supply modals initialized successfully');



    // ==========================================
    // 6. VIEW ITEM DETAILS LOGIC
    // ==========================================
    document.addEventListener('click', function (e) {
        const viewIcon = e.target.closest('.view-icon');
        if (viewIcon) {
            console.log('View Icon Clicked');
            e.preventDefault();
            e.stopPropagation();

            const card = viewIcon.closest('.supply-card');
            if (card) {
                const modal = document.getElementById('item-modal');
                if (modal) {
                    // Populate fields
                    const img = card.getAttribute('data-image');
                    const stockNo = card.getAttribute('data-stock-no') || '-';
                    const name = card.getAttribute('data-name');
                    const category = card.getAttribute('data-category');
                    const unit = card.getAttribute('data-unit');
                    const quantity = card.getAttribute('data-quantity');
                    const unitCost = card.getAttribute('data-unit-cost');
                    const totalCost = card.getAttribute('data-total-cost');
                    const status = card.getAttribute('data-status');
                    const lowThreshold = card.getAttribute('data-low-threshold') || '10'; // Default
                    const critThreshold = card.getAttribute('data-critical-threshold') || '5'; // Default
                    const propertyClass = card.getAttribute('data-property-classification');
                    const description = card.getAttribute('data-description');

                    document.getElementById('modal-stock-no').textContent = stockNo;
                    document.getElementById('modal-name').textContent = name;
                    document.getElementById('modal-category').textContent = category;
                    document.getElementById('modal-unit').textContent = unit;
                    document.getElementById('modal-quantity').textContent = quantity;
                    document.getElementById('modal-unit-cost').textContent = unitCost;
                    document.getElementById('modal-total-cost').textContent = totalCost;
                    document.getElementById('modal-status').textContent = status;
                    document.getElementById('modal-low-threshold').textContent = lowThreshold;
                    document.getElementById('modal-critical-threshold').textContent = critThreshold;
                    document.getElementById('modal-property-classification').textContent = propertyClass;
                    document.getElementById('modal-description').textContent = description;

                    // Image Handling
                    const imgContainer = document.getElementById('modal-img-container');
                    const imgElem = document.getElementById('modal-img');
                    if (img && img !== 'assets/default-item.png') {
                        imgElem.src = img;
                        imgContainer.style.display = 'block';
                    } else {
                        imgContainer.style.display = 'none';
                    }

                    // Show modal/Add Active Class
                    modal.classList.add('active');
                    modal.style.display = 'block';
                    modal.style.opacity = '1';
                    modal.style.visibility = 'visible';

                    // Close logic
                    const closeBtn = document.getElementById('item-close');
                    const closeFooterBtn = document.getElementById('close-view-btn');

                    const closeModal = () => {
                        modal.classList.remove('active');
                        modal.style.display = 'none';
                        modal.style.opacity = '0';
                        modal.style.visibility = 'hidden';
                    };

                    if (closeBtn) closeBtn.onclick = closeModal;
                    if (closeFooterBtn) closeFooterBtn.onclick = closeModal;

                    window.addEventListener('click', (ev) => {
                        if (ev.target === modal) closeModal();
                    });
                }
            }
        }
    });

    console.log('supply_modals.js: Initialization complete');
})(); // End IIFE - Execute immediately
