<!-- Modal for Admin Requisition Slip -->
<div id="admin-request-modal" class="modal redesigned-modal-wrapper">
    <div class="modal-content redesigned-modal" style="max-width: 900px;">
        <div class="modal-header-custom">
            <h2 id="request-modal-title"><i class="fas fa-file-invoice"></i> Requisition and Issue Slip (Admin)</h2>
            <span class="close-custom" id="admin-request-close">&times;</span>
        </div>
        
        <div class="modal-body-custom">
            <div class="requisition-form">
                <div class="form-header">
                    <h3 class="info-title">Employee Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Select Office / Department</label>
                            <select id="admin-emp-office-filter" class="form-control">
                                <option value="">All Offices / Departments</option>
                                <!-- Populated by JS -->
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Select Employee Name</label>
                            <select id="admin-req-emp-id-select" class="form-control">
                                <option value="">Select Employee...</option>
                                <!-- Populated by JS -->
                            </select>
                            <input type="hidden" id="admin-req-emp-id">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Employee ID</label>
                            <input type="text" id="admin-req-emp-id-display" class="form-control" placeholder="ID Number" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" id="admin-req-name" class="form-control" placeholder="Full Name" readonly>
                        </div>
                         <div class="form-group">
                            <label class="form-label">Designation</label>
                            <input type="text" id="admin-req-designation" class="form-control" placeholder="Position" readonly>
                        </div>
                         <div class="form-group full-width-span">
                            <label class="form-label">Department / Office</label>
                            <input type="text" id="admin-req-department" class="form-control" placeholder="Department" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Request</label>
                            <input type="date" id="admin-req-date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group full-width-span">
                            <label class="form-label">Purpose of Request</label>
                            <textarea id="admin-req-purpose" class="form-control" placeholder="Enter the purpose of this issuance"></textarea>
                        </div>
                    </div>
                </div>

                <div class="request-items-section">
                    <h3 class="info-title">Selected Items</h3>
                    <div class="table-container">
                        <table class="request-items-table">
                            <thead>
                                <tr>
                                    <th>Stock No.</th>
                                    <th>Unit</th>
                                    <th>Item Name</th>
                                    <th>Description</th>
                                    <th class="qty-col">Quantity</th>
                                    <th class="action-col">Action</th>
                                </tr>
                            </thead>
                            <tbody id="admin-request-table-body">
                                <tr id="admin-empty-request-row">
                                    <td colspan="6" style="text-align: center; padding: 30px; color: #a0aec0;">No items added to request yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="request-form-actions">
                     <button type="button" id="admin-clear-request-btn" class="btn-clear-request">Clear All</button>
                     <button type="button" id="admin-submit-request-btn" class="btn-submit-request">
                        <i class="fas fa-paper-plane"></i> Submit Requisition
                     </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Floating Action Button -->
<div class="fab-request-container" id="admin-fab-container">
    <div class="fab-label">Requisition Slip (Admin)</div>
    <button class="fab-button admin-fab-button" id="admin-fab-view-request" title="Admin Requisition Slip">
        <i class="fas fa-clipboard-check fab-icon"></i>
        <span class="fab-badge" id="admin-fab-badge">0</span>
    </button>
</div>

<style>
    /* Premium Admin Requisition Modal Styles */
    .redesigned-modal-wrapper.modal.active {
        display: flex !important;
        justify-content: center;
        align-items: center;
        background: rgba(0, 0, 0, 0.6) !important;
    }
    
    #admin-request-modal .redesigned-modal {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .modal-header-custom {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: white;
        padding: 25px 30px;
        text-align: left;
        position: relative;
        box-shadow: 0 4px 15px rgba(5, 150, 105, 0.2);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modal-header-custom h2 {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 700;
        color: white !important;
        display: flex !important;
        align-items: center;
        gap: 15px;
        letter-spacing: -0.01em;
        visibility: visible !important;
        opacity: 1 !important;
        white-space: normal;
        line-height: 1.2;
        word-break: keep-all;
    }

    #request-modal-title {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    .modal-header-custom h2 i {
        font-size: 1.2rem;
        background: rgba(255, 255, 255, 0.15);
        padding: 10px;
        border-radius: 12px;
        backdrop-filter: blur(4px);
    }

    .close-custom {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 24px;
        cursor: pointer;
        color: white;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .close-custom:hover { opacity: 1; }

    .modal-body-custom {
        padding: 30px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        padding: 10px 0;
    }
    
    .full-width-span { grid-column: 1 / -1; }
    
    .form-label { 
        display: block; 
        font-weight: 700; 
        font-size: 0.75rem; 
        color: #718096; 
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .form-control { 
        width: 100% !important; 
        padding: 12px 15px !important; 
        border: 1px solid #e2e8f0 !important; 
        border-radius: 8px !important; 
        font-size: 0.95rem !important;
        background-color: #f8fafc !important;
        transition: all 0.2s;
    }
    
    .redesigned-modal-wrapper .form-control:focus {
        border-color: #059669 !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1) !important;
        outline: none;
    }

    .info-title { 
        color: #1a202c; 
        margin-bottom: 20px; 
        font-size: 1.1rem; 
        font-weight: 700;
        border-bottom: 2px solid #edf2f7; 
        padding-bottom: 10px; 
        margin-top: 25px; 
    }

    .request-items-table { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0;
        margin-top: 10px; 
        border: 1px solid #edf2f7;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .request-items-table th { 
        background: #f8fafc; 
        padding: 15px; 
        font-weight: 700; 
        text-align: left; 
        color: #4a5568;
        border-bottom: 2px solid #edf2f7; 
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    
    .request-items-table td { 
        padding: 15px; 
        border-bottom: 1px solid #edf2f7; 
        color: #2d3748;
        font-size: 0.95rem;
    }

    .request-form-actions { 
        margin-top: 40px; 
        display: flex; 
        justify-content: flex-end; 
        gap: 15px; 
        padding-top: 20px;
        border-top: 1px solid #edf2f7;
    }
    
    .btn-clear-request { 
        padding: 12px 25px; 
        border: 1px solid #e2e8f0; 
        background: #fff; 
        color: #718096; 
        border-radius: 8px; 
        cursor: pointer; 
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-clear-request:hover {
        background: #f7fafc;
        border-color: #cbd5e0;
        color: #4a5568;
    }
    
    .btn-submit-request { 
        padding: 12px 30px; 
        border: none; 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
        color: white; 
        border-radius: 8px; 
        cursor: pointer; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        font-weight: 700; 
        transition: transform 0.2s, background 0.2s;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
    }
    
    .btn-submit-request:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(5, 150, 105, 0.3);
    }
    
    .btn-submit-request:active {
        transform: translateY(0);
    }

    /* FAB Styles */
    .fab-request-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        z-index: 9999;
    }

    .fab-button {
        width: 65px;
        height: 65px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0d2137 0%, #1a3a5a 100%);
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 25px rgba(13, 33, 55, 0.3);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
    }

    .fab-button:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 15px 30px rgba(13, 33, 55, 0.4);
    }

    .fab-icon {
        font-size: 1.8rem;
    }

    .fab-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 24px;
        height: 24px;
        padding: 0 6px;
        border-radius: 12px;
        border: 2px solid white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
        animation: badgePop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes badgePop {
        0% { transform: scale(0); }
        100% { transform: scale(1); }
    }

    .fab-label {
        background: white;
        color: #0d2137;
        padding: 8px 15px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        opacity: 0;
        transform: translateX(20px);
        transition: all 0.3s ease;
        white-space: nowrap;
        border: 1px solid #e2e8f0;
    }

    .fab-button:hover + .fab-label, 
    .fab-request-container:hover .fab-label {
        opacity: 1;
        transform: translateX(0);
    }

    @keyframes fabPulse {
        0% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.7); }
        70% { box-shadow: 0 0 0 15px rgba(5, 150, 105, 0); }
        100% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0); }
    }


</style>
