<!-- Modal for Adding New Supply -->
<div id="add-supply-modal" class="modal add-supply-modal-wrapper">
    <div class="modal-content add-supply-modal-content">
        <span class="close add-close-btn" id="add-close" onclick="document.getElementById('add-supply-modal').classList.remove('active')">&times;</span>
        <div class="modal-header-section">
            <h2><i class="fas fa-plus-circle"></i> Add New Supply</h2>
            <p class="modal-subtitle">Fill in the details below to add a new item to inventory</p>
        </div>
        <form id="add-supply-form" class="supply-form" method="POST" action="<?php echo $actionPath; ?>" enctype="multipart/form-data">
            <input type="hidden" id="supply-id-field" name="supply_id" value="">
            <!-- Basic Information Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>Basic Information</h3>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="item">
                            <i class="fas fa-tag"></i> Item Name <span class="required">*</span>
                        </label>
                        <input type="text" id="item" name="item" required placeholder="Enter item name">
                    </div>
                    <div class="form-group">
                        <label for="stock-no">
                            <i class="fas fa-barcode"></i> Stock Number
                        </label>
                        <input type="text" id="stock-no" name="stock_no" placeholder="Auto-generated if empty">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">
                            <i class="fas fa-folder"></i> Category <span class="required">*</span>
                        </label>
                        <select id="category" name="category" required onchange="toggleCustomCategory(this, 'custom-category-group')">
                            <option value="">Select Category</option>
                            <?php foreach ($uniqueCategories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                            <option value="Other">Other...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="property-classification">
                            <i class="fas fa-list-ul"></i> Property Classification
                        </label>
                        <select id="property-classification" name="property_classification">
                            <option value="">Select Classification</option>
                            <option value="Consumable / Expendable">Consumable / Expendable</option>
                            <option value="Semi-Expendable (Low Value)">Semi-Expendable (Low Value)</option>
                            <option value="Semi-Expendable (High Value)">Semi-Expendable (High Value)</option>
                            <option value="Property, Plant and Equipment (PPE)">Property, Plant and Equipment (PPE)</option>
                        </select>
                    </div>

                    <div class="form-group" id="custom-category-group" style="display:none;">
                        <label for="custom-category">
                            <i class="fas fa-pen"></i> Specify Category <span class="required">*</span>
                        </label>
                        <input type="text" id="custom-category" name="custom_category" placeholder="Enter new category">
                    </div>
                    <div class="form-group">
                        <label for="unit">
                            <i class="fas fa-ruler"></i> Unit <span class="required">*</span>
                        </label>
                        <select id="unit" name="unit" required>
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
                    <label for="description">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea id="description" name="description" rows="3" placeholder="Enter detailed description of the item"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="school">
                        <i class="fas fa-school"></i> School / Destination
                    </label>
                    <input type="text" id="school" name="school" placeholder="Enter school name or destination">
                </div>
            </div>

            <!-- Inventory Details Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fas fa-warehouse"></i>
                    <h3>Inventory Details</h3>
                </div>
                <div class="form-row">
                    <div class="form-group" id="current-qty-group">
                        <label for="quantity" id="lbl-quantity">
                            <i class="fas fa-cubes"></i> Total Quantity <span class="required">*</span>
                        </label>
                        <input type="number" id="quantity" name="quantity" min="0" value="0" required placeholder="0">
                    </div>
                    <div class="form-group" id="add-stock-group" style="display: none;">
                        <label for="add-stock" id="lbl-add-stock">
                            <i class="fas fa-plus-circle"></i> Add Quantity / Stock (Optional)
                        </label>
                        <input type="number" id="add-stock" name="add_stock" min="0" value="0" placeholder="Amount to add">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="previous_month">
                            <i class="fas fa-history"></i> Previous Month Balance
                        </label>
                        <input type="number" id="previous_month" name="previous_month" min="0" value="0" placeholder="Value from previous month">
                    </div>
                    <div class="form-group">
                        <label for="issuance">
                            <i class="fas fa-file-export"></i> Issuance for the Month
                        </label>
                        <input type="number" id="issuance" name="issuance" min="0" value="0" placeholder="Total items issued">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit-cost">
                            <i class="fas fa-dollar-sign"></i> Unit Cost
                        </label>
                        <input type="number" id="unit-cost" name="unit_cost" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="status">
                            <i class="fas fa-check-circle"></i> Status
                        </label>
                        <select id="status" name="status">
                            <option value="Available" selected>Available</option>
                            <option value="Low Stock">Low Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Discontinued">Discontinued</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="low-stock-threshold">
                            <i class="fas fa-exclamation-triangle"></i> Low Stock Threshold
                        </label>
                        <input type="number" id="low-stock-threshold" name="low_stock_threshold" min="0" value="<?php echo $defaultLow; ?>" placeholder="Alert when stock <= this">
                    </div>
                    <div class="form-group">
                        <label for="critical-stock-threshold">
                            <i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i> Critical Threshold
                        </label>
                        <input type="number" id="critical-stock-threshold" name="critical_stock_threshold" min="0" value="<?php echo $defaultCritical; ?>" placeholder="Alert when stock <= this">
                    </div>
                </div>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" id="total-cost" name="total_cost" value="0.00">
            <input type="hidden" name="bal_as_of_date" value="<?php echo date('Y-m-d'); ?>">

            <!-- Image Upload Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fas fa-image"></i>
                    <h3>Item Image</h3>
                </div>
                <div class="form-group full-width">
                    <div class="drag-drop-area" id="drag-drop-area">
                        <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                        <div class="drag-drop-content">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="drag-drop-text">Drag and drop an image here, or <label for="image" class="browse-link">browse</label></p>
                            <p class="drag-drop-hint">Supports: JPG, PNG, GIF (Max 5MB)</p>
                        </div>
                        <div class="image-preview" id="image-preview" style="display: none;">
                            <img id="preview-img" src="" alt="Preview">
                            <button type="button" class="remove-image-btn" id="remove-image-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="cancel-btn" id="cancel-add-btn">Cancel</button>
                <button type="submit"><i class="fas fa-check"></i> Add Supply</button>
            </div>
        </form>
    </div>
</div>
