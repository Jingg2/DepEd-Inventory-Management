<!-- filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\view\includes\delivery_modal.php -->
<div id="delivery-modal" class="modal add-supply-modal-wrapper">
    <div class="modal-content" style="max-width: 1200px; width: 95%;">
        <span class="close" id="delivery-close">&times;</span>
        
        <div class="modal-header-section" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 25px 40px; border-radius: 16px 16px 0 0; color: white; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; opacity: 0.1; font-size: 150px; transform: rotate(-15deg);">
                <i class="fas fa-truck"></i>
            </div>
            <h2 style="margin: 0; font-size: 1.8rem; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-truck-loading"></i> New Delivery Receipt
            </h2>
            <p class="modal-subtitle" style="margin: 8px 0 0 0; opacity: 0.9; font-size: 0.95rem;">Record multiple items from a single delivery receipt</p>
        </div>

        <form id="delivery-form" style="display: flex; flex-direction: column; height: 80vh;">
            <div class="modal-body" style="padding: 30px 40px; overflow-y: auto; flex: 1;">
                
                <!-- Receipt Header Info -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div class="form-group">
                        <label for="receipt_no"><i class="fas fa-hashtag"></i> Receipt Number</label>
                        <input type="text" id="receipt_no" name="receipt_no" placeholder="e.g. 000644" required>
                    </div>
                    <div class="form-group">
                        <label for="delivery_school"><i class="fas fa-school"></i> School / Destination</label>
                        <input type="text" id="delivery_school" name="school" placeholder="Select or enter school" required>
                    </div>
                    <div class="form-group">
                        <label for="delivery_date"><i class="fas fa-calendar-alt"></i> Delivery Date</label>
                        <input type="date" id="delivery_date" name="delivery_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="supplier"><i class="fas fa-building"></i> Supplier</label>
                        <input type="text" id="supplier" name="supplier" value="Inspiration Publishing Co." required>
                    </div>
                </div>

                <!-- Items Table -->
                <div style="overflow-x: auto;">
                    <table id="delivery-items-table" style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                        <thead>
                            <tr style="text-align: left; color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                <th style="padding: 10px 15px; width: 40%;">Item Description</th>
                                <th style="padding: 10px 15px;">Category</th>
                                <th style="padding: 10px 15px;">Unit</th>
                                <th style="padding: 10px 15px;">Unit Price</th>
                                <th style="padding: 10px 15px; width: 100px;">Qty</th>
                                <th style="padding: 10px 15px;">Amount</th>
                                <th style="padding: 10px 15px;"></th>
                            </tr>
                        </thead>
                        <tbody id="delivery-items-body">
                            <!-- Rows will be injected by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <button type="button" id="add-item-row" style="background: white; color: #10b981; border: 2px dashed #10b981; padding: 12px; border-radius: 8px; width: 100%; cursor: pointer; font-weight: 600; transition: all 0.2s; margin-top: 10px;">
                    <i class="fas fa-plus-circle"></i> Add Another Item
                </button>

                <!-- Remarks / Footer Info -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 30px;">
                    <div class="form-group">
                        <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea id="address" name="address" rows="2" placeholder="Guadalupe, Bogo City, Cebu"></textarea>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div class="form-group">
                            <label for="delivered_by">Delivered by:</label>
                            <input type="text" id="delivered_by" name="delivered_by" placeholder="Driver / Representative name">
                        </div>
                        <div class="form-group">
                            <label for="received_by_officer">Received by (Supply Officer):</label>
                            <input type="text" id="received_by_officer" name="received_by_officer" placeholder="Name">
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer" style="padding: 20px 40px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; border-radius: 0 0 16px 16px;">
                <div style="font-size: 1.2rem; font-weight: 700; color: #1e293b;">
                    Total Amount: <span style="color: #10b981;">₱<span id="grand-total">0.00</span></span>
                    <input type="hidden" name="total_amount" id="total_amount_input" value="0">
                </div>
                <div style="display: flex; gap: 15px;">
                    <button type="button" class="cancel-btn" id="delivery-cancel" style="padding: 12px 24px; background: #f1f5f9; color: #64748b; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="submit" style="padding: 12px 30px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                        <i class="fas fa-save"></i> Save Delivery
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
#delivery-items-table input, #delivery-items-table select {
    width: 100%;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.9rem;
}
#delivery-items-table .row-amount {
    font-weight: 600;
    color: #334155;
}
.remove-row {
    color: #ef4444;
    cursor: pointer;
    font-size: 1.1rem;
    transition: opacity 0.2s;
}
.remove-row:hover { opacity: 0.7; }
</style>
