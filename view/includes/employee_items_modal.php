<!-- Employee Items Modal -->
<div id="employee-items-modal" class="modal add-supply-modal-wrapper">
    <div class="modal-content add-supply-modal-content" style="max-width: 900px;">
        <span class="close item-close-btn" onclick="closeEmployeeItemsModal()">&times;</span>
        
        <div class="modal-header-section" style="background: var(--gradient-primary); display: flex; justify-content: space-between; align-items: center; padding: 15px 20px;">
            <div>
                <h2 style="margin: 0;"><i class="fas fa-boxes"></i> Employee Assets</h2>
                <p class="modal-subtitle" id="employee-items-subtitle" style="margin: 5px 0 0 0; opacity: 0.9;">Items currently issued to employee</p>
            </div>
            <button id="btn-open-assign-asset" class="btn-primary" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
                <i class="fas fa-plus-circle"></i> Assign Asset
            </button>
        </div>

        <div class="modal-body" style="padding: 20px;">
            <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 12px; text-align: left; color: #475569;">Item Name</th>
                            <th style="padding: 12px; text-align: left; color: #475569;">Property No.</th>
                            <th style="padding: 12px; text-align: center; color: #475569;">Date Issued</th>
                            <th style="padding: 12px; text-align: center; color: #475569;">Qty</th>
                            <th style="padding: 12px; text-align: center; color: #475569;">Value</th>
                            <th style="padding: 12px; text-align: center; color: #475569;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="employee-items-body">
                        <!-- Rows -->
                    </tbody>
                </table>
            </div>
            <div id="no-items-msg" style="text-align: center; padding: 20px; color: #64748b; display: none;">
                No items currently issued.
            </div>
        </div>
    </div>
</div>
