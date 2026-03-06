<!-- Assign Item Modal (For Existing Records) -->
<div id="assign-item-modal" class="modal add-supply-modal-wrapper">
    <div class="modal-content add-supply-modal-content" style="max-width: 500px;">
        <span class="close assign-close-btn">&times;</span>
        
        <div class="modal-header-section" style="background: var(--gradient-navy);">
            <h2><i class="fas fa-hand-holding-box"></i> Direct Asset Assignment</h2>
            <p class="modal-subtitle">Record an item already held by this employee</p>
        </div>

        <div class="modal-body" style="padding: 20px;">
            <form id="assign-item-form">
                <input type="hidden" id="assign-emp-id">
                
                <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: #1e3a8a;">Selected Employee</label>
                    <input type="text" id="assign-emp-name-display" class="form-control" readonly style="background: #f1f5f9; font-weight: 600;">
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: #1e3a8a;">Search Item to Assign</label>
                    <div style="position: relative;" class="dropdown-search-container">
                        <input type="text" id="assign-item-search" class="form-control" placeholder="Search or click to browse items..." autocomplete="off" style="padding-right: 40px;">
                        <i class="fas fa-chevron-down dropdown-arrow" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; pointer-events: none; transition: transform 0.2s;"></i>
                        <button type="button" id="clear-selected-item" style="display: none; position: absolute; right: 35px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #94a3b8; cursor: pointer; font-size: 0.8rem;"><i class="fas fa-times-circle"></i></button>
                        <div id="assign-item-results" class="search-dropdown-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 8px; z-index: 1000; max-height: 250px; overflow-y: auto; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); margin-top: 4px;"></div>
                    </div>
                    <input type="hidden" id="selected-assign-item-id">
                </div>

                <div id="selected-item-preview" style="display: none; margin-bottom: 20px; padding: 12px; background: #f0fdf4; border-radius: 8px; border: 1px solid #dcfce7;">
                    <div style="font-weight: 700; color: #166534;" id="preview-item-name"></div>
                    <div style="font-size: 0.85rem; color: #15803d;" id="preview-item-details"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; color: #1e3a8a;">Quantity</label>
                        <input type="number" id="assign-qty" class="form-control" value="1" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; color: #1e3a8a;">Date Assigned</label>
                        <input type="date" id="assign-date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: #1e3a8a;">Stock Type</label>
                    <select id="assign-stock-type" class="form-control">
                        <option value="New">New Stock</option>
                        <option value="Returned">Returned/Used Stock</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: #1e3a8a;">Purpose/Note</label>
                    <textarea id="assign-purpose" class="form-control" rows="2" placeholder="e.g., Direct assignment of existing record"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: #1e3a8a; display: flex; justify-content: space-between; align-items: center;">
                        Deduct from Inventory Stock?
                        <label class="switch" style="transform: scale(0.85); origin: right;">
                            <input type="checkbox" id="assign-deduct-stock">
                            <span class="slider round"></span>
                        </label>
                    </label>
                    <div id="stock-deduction-info" style="margin-top: 5px; padding: 10px; background: #fffbeb; border-radius: 8px; border: 1px solid #fef3c7; font-size: 0.85rem; color: #92400e;">
                        <i class="fas fa-info-circle"></i> <strong>Mode:</strong> This assignment will <strong id="deduct-status-text">NOT</strong> deduct from the current inventory quantity. It is for recording existing holdings only.
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-radius: 0 0 12px 12px;">
            <button type="button" class="btn-cancel assign-close-btn" style="background: #94a3b8; border: none; color: white; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">Cancel</button>
            <button type="button" id="btn-submit-assignment" class="btn-primary" style="background: var(--gradient-navy); border: none; color: white; padding: 10px 30px; border-radius: 8px; cursor: pointer; font-weight: 700;">Confirm Assignment</button>
        </div>
    </div>
</div>

<style>
/* Modal Visibility */
#assign-item-modal.modal.active {
    display: flex !important;
    background: rgba(0, 0, 0, 0.4);
    align-items: center;
    justify-content: center;
}

/* Reuse or redefine slider styles for this modal */
#assign-item-modal .switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 22px;
}
#assign-item-modal .switch input { opacity: 0; width: 0; height: 0; }
#assign-item-modal .slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e0; transition: .4s; border-radius: 22px;
}
#assign-item-modal .slider:before {
    position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
    background-color: white; transition: .4s; border-radius: 50%;
}
#assign-item-modal input:checked + .slider { background-color: #1e3a8a; }
#assign-item-modal input:checked + .slider:before { transform: translateX(22px); }

.search-dropdown-item {
    padding: 12px 15px;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 1px solid #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.search-dropdown-item:hover {
    background: #f1f5f9;
}
.search-dropdown-item:last-child {
    border-bottom: none;
}
.dropdown-search-container.active .dropdown-arrow {
    transform: translateY(-50%) rotate(180deg);
}
.stock-badge {
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 4px;
    background: #e2e8f0;
    color: #475569;
    font-weight: 600;
}
</style>
