/**
 * delivery.js
 * Handles the multi-item delivery receipt entry system
 */

document.addEventListener('DOMContentLoaded', function () {
    const deliveryModal = document.getElementById('delivery-modal');
    if (!deliveryModal) return;

    const deliveryTableBody = document.getElementById('delivery-items-body');
    const addRowBtn = document.getElementById('add-item-row');
    const grandTotalSpan = document.getElementById('grand-total');
    const totalAmountInput = document.getElementById('total_amount_input');
    const closeBtns = [document.getElementById('delivery-close'), document.getElementById('delivery-cancel')];
    const deliveryForm = document.getElementById('delivery-form');

    // Initial Row
    addItemRow();

    // Event Listeners
    addRowBtn.addEventListener('click', addItemRow);

    // Toggle new school input
    window.toggleNewSchoolInput = function (select) {
        const newSchoolGroup = document.getElementById('new-school-group');
        const newSchoolInput = document.getElementById('new_school_name');
        const schoolNameInput = document.getElementById('delivery_school_name');
        const addressInput = document.getElementById('address');

        if (select.value === 'other') {
            newSchoolGroup.style.display = 'block';
            newSchoolInput.required = true;
            schoolNameInput.value = ''; // Will be set from new_school_name on submit
            if (addressInput) addressInput.value = '';
        } else {
            newSchoolGroup.style.display = 'none';
            newSchoolInput.required = false;
            // Set the readable name for backward compatibility/reporting
            const selectedOption = select.options[select.selectedIndex];
            schoolNameInput.value = selectedOption.dataset.name || '';

            // Auto-populate address if available
            if (addressInput) {
                addressInput.value = selectedOption.dataset.address || '';
            }
        }
    };

    closeBtns.forEach(btn => {
        if (btn) btn.addEventListener('click', () => {
            deliveryModal.style.display = 'none';
        });
    });

    // Handle Form Submission
    deliveryForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Sync school name if "Other"
        if (formData.get('school_id') === 'other') {
            formData.set('school', formData.get('new_school_name'));
        }

        // Convert rows to JSON
        const items = [];
        const rows = deliveryTableBody.querySelectorAll('tr');
        rows.forEach(row => {
            const item = {
                item: row.querySelector('.col-item').value,
                category: row.querySelector('.col-category').value,
                unit: row.querySelector('.col-unit').value,
                unit_cost: row.querySelector('.col-price').value,
                quantity: row.querySelector('.col-qty').value,
                description: row.querySelector('.col-item').value,
                property_classification: row.querySelector('.col-property').value
            };
            if (item.item) items.push(item);
        });

        if (items.length === 0) {
            alert('Please add at least one item.');
            return;
        }

        formData.append('items', JSON.stringify(items));
        formData.append('action', 'save_delivery');
        formData.append('ajax', '1');

        // Submit via AJAX
        fetch(basePath + 'supply.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Server responded with ' + response.status + ': ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Delivery recorded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred.'));
                }
            })
            .catch(error => {
                console.error('Save Delivery Error:', error);
                alert('An error occurred while saving the delivery: ' + error.message);
            });
    });

    function addItemRow() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding: 5px;">
                <input type="text" class="col-item" placeholder="Item Name / Description" required>
            </td>
            <td style="padding: 5px;">
                <select class="col-category">
                    <option value="Instructional Materials">Instructional Materials</option>
                    <option value="Office Supplies">Office Supplies</option>
                    <option value="IT Equipment">IT Equipment</option>
                    <option value="Miscellaneous">Miscellaneous</option>
                </select>
            </td>
            <td style="padding: 5px;">
                <input type="text" class="col-unit" placeholder="Unit" value="pcs">
            </td>
            <td style="padding: 5px;">
                <input type="number" step="0.01" class="col-price" placeholder="0.00" value="0">
            </td>
            <td style="padding: 5px;">
                <input type="number" class="col-qty" placeholder="0" value="1">
            </td>
            <td style="padding: 5px; text-align: right;">
                <span class="row-amount">₱0.00</span>
            </td>
            <td style="padding: 5px; text-align: center;">
                <select class="col-property" style="width: 100px; padding: 5px; font-size: 0.8rem;">
                    <option value="Consumable / Expendable">Expendable</option>
                    <option value="Semi-Expendable (Low Value)">Semi-Exp (Low)</option>
                    <option value="Semi-Expendable (High Value)">Semi-Exp (High)</option>
                    <option value="Property, Plant and Equipment (PPE)" selected>PPE</option>
                </select>
            </td>
            <td style="padding: 5px; text-align: center;">
                <i class="fas fa-times remove-row"></i>
            </td>
        `;

        // Price/Qty logic
        const priceInput = tr.querySelector('.col-price');
        const qtyInput = tr.querySelector('.col-qty');
        const amountSpan = tr.querySelector('.row-amount');
        const removeBtn = tr.querySelector('.remove-row');

        const updateAmount = () => {
            const total = parseFloat(priceInput.value || 0) * parseInt(qtyInput.value || 0);
            amountSpan.textContent = '₱' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            updateGrandTotal();
        };

        priceInput.addEventListener('input', updateAmount);
        qtyInput.addEventListener('input', updateAmount);

        removeBtn.addEventListener('click', () => {
            if (deliveryTableBody.querySelectorAll('tr').length > 1) {
                tr.remove();
                updateGrandTotal();
            }
        });

        deliveryTableBody.appendChild(tr);
        updateGrandTotal();
    }

    function updateGrandTotal() {
        let grandTotal = 0;
        const rows = deliveryTableBody.querySelectorAll('tr');
        rows.forEach(row => {
            const price = parseFloat(row.querySelector('.col-price').value || 0);
            const qty = parseInt(row.querySelector('.col-qty').value || 0);
            grandTotal += (price * qty);
        });

        grandTotalSpan.textContent = grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        totalAmountInput.value = grandTotal;
    }
});

// Helper for other scripts to open the modal
window.openDeliveryModal = function (schoolName = '') {
    const modal = document.getElementById('delivery-modal');
    if (modal) {
        if (schoolName) {
            const select = document.getElementById('delivery_school_id');
            const schoolNameInput = document.getElementById('delivery_school_name');

            // Try to find the school in the dropdown
            let found = false;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].dataset.name === schoolName) {
                    select.selectedIndex = i;
                    schoolNameInput.value = schoolName;
                    found = true;
                    break;
                }
            }

            // If not found, we don't switch to "Other" automatically to avoid confusion, 
            // but the system is ready.
            if (!found) {
                select.value = '';
                schoolNameInput.value = '';
            }

            // Ensure "Other" input is hidden if we found a match
            toggleNewSchoolInput(select);
        }
        modal.style.display = 'block';
    }
};
