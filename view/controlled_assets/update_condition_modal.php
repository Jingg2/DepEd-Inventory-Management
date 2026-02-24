<!-- Modal for Updating Item Condition -->
<div id="update-condition-modal" class="modal add-supply-modal-wrapper">
    <div class="modal-content add-supply-modal-content" style="max-width: 500px;">
        <span class="close item-close-btn" onclick="closeUpdateConditionModal()">&times;</span>
        
        <div class="modal-header-section" style="background: var(--gradient-primary);">
            <h2><i class="fas fa-clipboard-check"></i> Update Condition</h2>
            <p class="modal-subtitle">Update the physical status of this item</p>
        </div>

        <div class="modal-body" style="padding: 30px;">
            <form id="update-condition-form" onsubmit="submitUpdateCondition(event)">
                <input type="hidden" id="update-condition-id">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="item-condition-select" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Condition</label>
                    <select id="item-condition-select" name="condition" class="form-control" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
                        <option value="Functional">Functional (In Good Condition)</option>
                        <option value="For Repair">For Repair (Needs Maintenance)</option>
                        <option value="Condemned">Condemned (Beyond Repair)</option>
                        <option value="Lost">Lost / Stolen</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px;">
                    <button type="button" class="cancel-btn" onclick="closeUpdateConditionModal()" style="padding: 10px 24px;">Cancel</button>
                    <button type="submit" class="submit-btn" style="background: var(--primary-emerald); color: white; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
