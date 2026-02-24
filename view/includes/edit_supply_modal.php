<!-- Modal for Editing Supply -->
<div id="edit-supply-modal" class="modal add-supply-modal-wrapper">
    <div class="modal-content add-supply-modal-content">
        <span class="close add-close-btn" id="edit-close" onclick="document.getElementById('edit-supply-modal').classList.remove('active')">&times;</span>
        <div class="modal-header-section">
            <h2><i class="fas fa-edit"></i> Edit Supply</h2>
            <p class="modal-subtitle">Update the details of the inventory item</p>
        </div>
        <form id="edit-supply-form" class="supply-form" method="POST" action="<?php echo $actionPath; ?>" enctype="multipart/form-data">
            <input type="hidden" id="edit-supply-id-field" name="supply_id" value="">
            <!-- Basic Information Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>Basic Information</h3>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-item">
                            <i class="fas fa-tag"></i> Item Name <span class="required">*</span>
                        </label>
                        <input type="text" id="edit-item" name="item" required placeholder="Enter item name">
                    </div>
                    <div class="form-group">
                        <label for="edit-stock-no">
                            <i class="fas fa-barcode"></i> Stock Number
                        </label>
                        <input type="text" id="edit-stock-no" name="stock_no" placeholder="Auto-generated if empty">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-category">
                            <i class="fas fa-folder"></i> Category <span class="required">*</span>
                        </label>
                        <select id="edit-category" name="category" required onchange="toggleCustomCategory(this, 'edit-custom-category-group')">
                            <option value="">Select Category</option>
                            <?php foreach ($uniqueCategories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                            <option value="Other">Other...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-property-classification">
                            <i class="fas fa-list-ul"></i> Property Classification
                        </label>
                        <select id="edit-property-classification" name="property_classification">
                            <option value="">Select Classification</option>
                            <option value="Consumable / Expendable">Consumable / Expendable</option>
                            <option value="Semi-Expendable (Low Value)">Semi-Expendable (Low Value)</option>
                            <option value="Semi-Expendable (High Value)">Semi-Expendable (High Value)</option>
                            <option value="Property, Plant and Equipment (PPE)">Property, Plant and Equipment (PPE)</option>
                        </select>
                    </div>

                    <div class="form-group" id="edit-custom-category-group" style="display:none;">
                        <label for="edit-custom-category">
                            <i class="fas fa-pen"></i> Specify Category <span class="required">*</span>
                        </label>
                        <input type="text" id="edit-custom-category" name="custom_category" placeholder="Enter new category">
                    </div>
                    <div class="form-group">
                        <label for="edit-unit">
                            <i class="fas fa-ruler"></i> Unit <span class="required">*</span>
                        </label>
                        <select id="edit-unit" name="unit" required>
                            <option value="">Select Unit</option>
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="box">Box</option>
                            <option value="pack">Pack</option>
                            <option value="bottle">Bottle</option>
                            <option value="roll">Roll</option>
                            <option value="set">Set</option>
                            <option value="ream">Ream</option>
                            <option value="kg">Kilogram (kg)</option>
                            <option value="g">Gram (g)</option>
                            <option value="L">Liter (L)</option>
                            <option value="ml">Milliliter (ml)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit-description">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea id="edit-description" name="description" rows="3" placeholder="Enter detailed description of the item"></textarea>
                </div>

            </div>

            <!-- Inventory Details Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fas fa-warehouse"></i>
                    <h3>Inventory Details</h3>
                </div>
                <div class="form-row">
                    <div class="form-group" id="edit-current-qty-group">
                        <label for="edit-quantity" id="edit-lbl-quantity">
                            <i class="fas fa-cubes"></i> Total Quantity <span class="required">*</span>
                        </label>
                        <input type="number" id="edit-quantity" name="quantity" min="0" value="0" required placeholder="0" readonly>
                    </div>
                    <div class="form-group" id="edit-add-stock-group">
                        <label for="edit-add-stock" id="edit-lbl-add-stock">
                            <i class="fas fa-plus-circle"></i> Add Quantity / Stock (Optional)
                        </label>
                        <input type="number" id="edit-add-stock" name="add_stock" min="0" value="0" placeholder="Amount to add">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-previous-month">
                            <i class="fas fa-history"></i> Previous Month Balance
                        </label>
                        <input type="number" id="edit-previous-month" name="previous_month" min="0" value="0" placeholder="Value from previous month">
                    </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-issuance">
                            <i class="fas fa-file-export"></i> Issuance for the Month
                        </label>
                        <input type="number" id="edit-issuance" name="issuance" min="0" value="0" placeholder="Total items issued">
                    </div>
                    <div class="form-group">
                        <label for="edit-requisition">
                            <i class="fas fa-cart-plus"></i> Monthly Acquisition
                        </label>
                        <input type="number" id="edit-requisition" name="requisition" min="0" value="0" placeholder="Total acquisitions">
                    </div>
                </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-unit-cost">
                            <i class="fas fa-dollar-sign"></i> Unit Cost
                        </label>
                        <input type="number" id="edit-unit-cost" name="unit_cost" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="edit-status">
                            <i class="fas fa-check-circle"></i> Status
                        </label>
                        <select id="edit-status" name="status">
                            <option value="Available" selected>Available</option>
                            <option value="Low Stock">Low Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Discontinued">Discontinued</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-low-stock-threshold">
                            <i class="fas fa-exclamation-triangle"></i> Low Stock Threshold
                        </label>
                        <input type="number" id="edit-low-stock-threshold" name="low_stock_threshold" min="0" value="<?php echo $defaultLow; ?>" placeholder="Alert when stock <= this">
                    </div>
                    <div class="form-group">
                        <label for="edit-critical-stock-threshold">
                            <i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i> Critical Threshold
                        </label>
                        <input type="number" id="edit-critical-stock-threshold" name="critical_stock_threshold" min="0" value="<?php echo $defaultCritical; ?>" placeholder="Alert when stock <= this">
                    </div>
                </div>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" id="edit-total-cost" name="total_cost" value="0.00">
            <input type="hidden" name="bal_as_of_date" value="<?php echo date('Y-m-d'); ?>">

            <!-- Image Upload Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fas fa-image"></i>
                    <h3>Item Image</h3>
                </div>
                <div class="form-group full-width">
                    <div class="drag-drop-area" id="edit-drag-drop-area">
                        <input type="file" id="edit-image" name="image" accept="image/*" style="display: none;">
                        <div class="drag-drop-content">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="drag-drop-text">Drag and drop an image here, or <label for="edit-image" class="browse-link">browse</label></p>
                            <p class="drag-drop-hint">Supports: JPG, PNG, GIF (Max 5MB)</p>
                        </div>
                        <div class="image-preview" id="edit-image-preview" style="display: none;">
                            <img id="edit-preview-img" src="" alt="Preview">
                            <button type="button" class="remove-image-btn" id="edit-remove-image-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="cancel-btn" id="cancel-edit-btn">Cancel</button>
                <button type="submit"><i class="fas fa-save"></i> Update Supply</button>
            </div>
        </form>
    </div>
</div>
