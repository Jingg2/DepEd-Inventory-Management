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

    closeBtns.forEach(btn => {
        if (btn) btn.addEventListener('click', () => {
            deliveryModal.style.display = 'none';
        });
    });

    // Handle Form Submission
    deliveryForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

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
                description: row.querySelector('.col-item').value, // For now description = item name or could add col
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

        // Submit via AJAX
        fetch(basePath + 'controller/supplyController.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Delivery recorded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the delivery.');
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
                <i class="fas fa-times remove-row"></i>
                <input type="hidden" class="col-property" value="Non-Expendable">
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
            document.getElementById('delivery_school').value = schoolName;
        }
        modal.style.display = 'block';
    }
};
