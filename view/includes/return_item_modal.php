<!-- Return Item Modal -->
<div id="return-item-modal" class="modal add-supply-modal-wrapper" style="z-index: 10002;">
    <div class="modal-content add-supply-modal-content" style="max-width: 500px;">
        <span class="close item-close-btn" onclick="closeReturnItemModal()">&times;</span>
        
        <div class="modal-header-section" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);">
            <h2><i class="fas fa-undo"></i> Return Item</h2>
            <p class="modal-subtitle">Process item return</p>
        </div>

        <div class="modal-body" style="padding: 30px;">
            <form id="return-item-form" onsubmit="submitReturnItem(event)">
                <input type="hidden" id="return-supply-id">
                <input type="hidden" id="return-employee-id">
                <input type="hidden" id="return-request-item-id">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Item</label>
                    <input type="text" id="return-item-name" readonly style="width: 100%; padding: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Quantity to Return</label>
                    <input type="number" id="return-quantity" name="quantity" min="1" required class="form-control" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <small style="color: #64748b;">Max: <span id="return-max-qty">0</span></small>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Condition</label>
                    <select id="return-condition" name="condition" class="form-control" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;" onchange="toggleReasonField()">
                        <option value="Functional">Functional (Return to Stock)</option>
                        <option value="Unserviceable">Unserviceable (Waste Report)</option>
                    </select>
                </div>

                <div class="form-group" id="return-reason-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Reason for Return <span style="color:red">*</span></label>
                    <textarea id="return-reason" name="reason" rows="3" class="form-control" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px;">
                    <button type="button" class="cancel-btn" onclick="closeReturnItemModal()" style="padding: 10px 24px;">Cancel</button>
                    <button type="submit" class="submit-btn" style="background: #d97706; color: white; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function toggleReasonField() {
    const val = document.getElementById('return-condition').value;
    const input = document.getElementById('return-reason');
    if (val === 'Unserviceable') {
        input.placeholder = "Describe the damage or reason for unserviceability...";
    } else {
        input.placeholder = "E.g. Resigned, Transferred, Project Completed, etc.";
    }
}
</script>
