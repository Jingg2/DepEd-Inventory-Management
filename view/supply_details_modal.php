<!-- Modal for Item Details -->
<div id="item-modal" class="modal add-supply-modal-wrapper">
    <div class="modal-content add-supply-modal-content">
        <span class="close item-close-btn" id="item-close">&times;</span>
        
        <div class="modal-header-section">
            <h2><i class="fas fa-box-open"></i> Supply Details</h2>
            <p class="modal-subtitle">Comprehensive view of asset specification and status</p>
        </div>

        <div class="modal-body" style="padding: 30px 40px;">
            <div class="image-preview-container" style="text-align: center; margin-bottom: 25px; display: none;" id="modal-img-container">
                <img id="modal-img" src="" alt="Item Image" style="max-height: 250px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;">
            </div>

            <div class="details-grid">
                <p><strong>Stock Number:</strong> <span id="modal-stock-no"></span></p>
                <p><strong>Item Name:</strong> <span id="modal-name"></span></p>
                <p><strong>Category:</strong> <span id="modal-category"></span></p>
                <p><strong>Unit:</strong> <span id="modal-unit"></span></p>
                <p><strong>Quantity:</strong> <span id="modal-quantity"></span></p>
                <p><strong>Unit Cost:</strong> ₱<span id="modal-unit-cost"></span></p>
                <p><strong>Total Cost:</strong> ₱<span id="modal-total-cost"></span></p>
                <p><strong>Status:</strong> <span id="modal-status"></span></p>
                <p><strong>Low Stock Threshold:</strong> <span id="modal-low-threshold"></span></p>
                <p><strong>Critical Threshold:</strong> <span id="modal-critical-threshold"></span></p>
                <p><strong>Property Class:</strong> <span id="modal-property-classification"></span></p>

                <div style="grid-column: 1 / -1;">
                    <p style="flex-direction: column; align-items: flex-start; gap: 8px;">
                        <strong>Description:</strong> 
                        <span id="modal-description" style="text-align: left; width: 100%; line-height: 1.6;"></span>
                    </p>
                </div>
            </div>


        </div>
        
        <div class="modal-footer" style="padding: 20px 40px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 15px; border-radius: 0 0 16px 16px;">
            <!-- Actions will be handled by JS or direct buttons if needed, currently using icons for edit/delete in card, but valid to have them here too if requested. 
                 For now, keeping it clean as a 'View' modal. If actions are needed, they can be re-injected. -->
                 <button type="button" class="cancel-btn" id="close-view-btn" style="padding: 10px 24px;">Close</button>
        </div>
    </div>
</div>
