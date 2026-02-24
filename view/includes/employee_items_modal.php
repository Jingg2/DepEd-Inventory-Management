<!-- Employee Items Modal -->
<div id="employee-items-modal" class="modal add-supply-modal-wrapper">
    <div class="modal-content add-supply-modal-content" style="max-width: 900px;">
        <span class="close item-close-btn" onclick="closeEmployeeItemsModal()">&times;</span>
        
        <div class="modal-header-section" style="background: var(--gradient-primary);">
            <h2><i class="fas fa-boxes"></i> Employee Assets</h2>
            <p class="modal-subtitle" id="employee-items-subtitle">Items currently issued to employee</p>
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
