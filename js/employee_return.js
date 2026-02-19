document.addEventListener('DOMContentLoaded', function () {
    // Attach event listeners to View Assets buttons
    document.body.addEventListener('click', function (e) {
        if (e.target.classList.contains('view-assets-btn')) {
            const empId = e.target.getAttribute('data-id');
            const empName = e.target.getAttribute('data-name');
            openEmployeeItemsModal(empId, empName);
        }
    });
});

function openEmployeeItemsModal(empId, empName) {
    const modal = document.getElementById('employee-items-modal');
    const subtitle = document.getElementById('employee-items-subtitle');
    const tbody = document.getElementById('employee-items-body');
    const noItemsMsg = document.getElementById('no-items-msg');

    subtitle.textContent = `Items currently issued to ${empName}`;
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Loading...</td></tr>';
    noItemsMsg.style.display = 'none';

    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('active'), 10);

    // Fetch items
    fetch(`${basePath}api/get_employee_items.php?id=${encodeURIComponent(empId)}`)
        .then(response => response.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.success && data.data.length > 0) {
                data.data.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b;">
                            <strong>${item.item_name}</strong><br>
                            <small>${item.description}</small>
                        </td>
                        <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; color: #475569;">${item.stock_no || '-'}</td>
                        <td style="padding: 12px; text-align: center; border-bottom: 1px solid #f1f5f9; color: #475569;">
                            ${new Date(item.approved_date).toLocaleDateString()}
                        </td>
                        <td style="padding: 12px; text-align: center; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 600;">
                            ${item.issued_quantity} ${item.unit}
                        </td>
                         <td style="padding: 12px; text-align: center; border-bottom: 1px solid #f1f5f9; color: #475569;">
                            ₱${parseFloat(item.unit_cost).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                        </td>
                        <td style="padding: 12px; text-align: center; border-bottom: 1px solid #f1f5f9;">
                            <button onclick="openReturnModal('${item.request_item_id}', '${item.supply_id}', '${empId}', '${item.item_name.replace(/'/g, "\\'")}', ${item.issued_quantity})" 
                                style="background: #d97706; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                <i class="fas fa-undo"></i> Return
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '';
                noItemsMsg.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red; padding: 20px;">Error loading items.</td></tr>';
        });
}

function closeEmployeeItemsModal() {
    const modal = document.getElementById('employee-items-modal');
    modal.classList.remove('active');
    setTimeout(() => modal.style.display = 'none', 300);
}

// Return Modal Logic
function openReturnModal(reqItemId, supplyId, empId, itemName, maxQty) {
    const modal = document.getElementById('return-item-modal');

    document.getElementById('return-request-item-id').value = reqItemId;
    document.getElementById('return-supply-id').value = supplyId;
    document.getElementById('return-employee-id').value = empId;
    document.getElementById('return-item-name').value = itemName;

    const qtyInput = document.getElementById('return-quantity');
    qtyInput.value = 1;
    qtyInput.max = maxQty;
    document.getElementById('return-max-qty').textContent = maxQty;

    // Reset form
    document.getElementById('return-condition').value = 'Functional';
    document.getElementById('return-reason').value = '';
    toggleReasonField();

    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('active'), 10);
}

function closeReturnItemModal() {
    const modal = document.getElementById('return-item-modal');
    modal.classList.remove('active');
    setTimeout(() => modal.style.display = 'none', 300);
}

function submitReturnItem(event) {
    event.preventDefault();

    const formData = {
        request_item_id: document.getElementById('return-request-item-id').value,
        supply_id: document.getElementById('return-supply-id').value,
        employee_id: document.getElementById('return-employee-id').value,
        quantity: document.getElementById('return-quantity').value,
        condition: document.getElementById('return-condition').value,
        reason: document.getElementById('return-reason').value
    };

    if (confirm(`Are you sure you want to return ${formData.quantity} of this item as ${formData.condition}?`)) {
        fetch(`${basePath}api/return_item.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item returned successfully.');
                    closeReturnItemModal();
                    closeEmployeeItemsModal(); // Close parent modal to force refresh on next open, or specific refresh logic
                    // Ideally refresh the parent modal, but closing is safer for stateSync
                    location.reload(); // Reload page to update the main list count as well
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            });
    }
}
