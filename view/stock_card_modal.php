<!-- Stock Card Preview Modal -->
<div id="stock-card-modal" class="modal">
    <div class="modal-content stock-card-modal-content">
        <span class="close" onclick="document.getElementById('stock-card-modal').classList.remove('active'); document.getElementById('stock-card-modal').style.display='none';">&times;</span>
        <div class="modal-header-section" style="padding: 15px 20px;">
            <h2 style="font-size: 1.25rem; margin-bottom: 2px;"><i class="fas fa-file-invoice"></i> Stock Card Preview</h2>
            <p class="modal-subtitle" style="font-size: 0.85rem;">Official format overview</p>
        </div>
        
        <div class="stock-card-info-header" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: flex-start; background: #f8fafc; border-bottom: 1px solid #edf2f7;">
            <div>
                <h3 style="margin: 0 0 3px 0; color: #2d3748; font-size: 1rem; font-weight: 700;">Item: <span id="sc-item-name" style="color: #2A4D88;"></span></h3>
                <p style="margin: 0; color: #64748b; font-size: 0.85rem;"><strong>Stock No:</strong> <span id="sc-stock-no"></span></p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0 0 3px 0; color: #64748b; font-size: 0.85rem;"><strong>Unit:</strong> <span id="sc-unit" style="color: #2d3748; font-weight: 600;"></span></p>
                <p style="margin: 0; color: #64748b; font-size: 0.85rem;"><strong>Running Balance:</strong> <span id="sc-balance" style="color: #27ae60; font-weight: 800; font-size: 0.95rem;"></span></p>
            </div>
        </div>

        <!-- Date range filter - HORIZONTAL LAYOUT -->
        <div class="sc-filter-controls" style="display: flex; flex-direction: row; align-items: flex-end; gap: 15px; padding: 10px 20px; background: white; border-bottom: 1px solid #edf2f7; flex-wrap: nowrap;">
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px; font-weight: 600;">From Date</label>
                <input type="date" id="sc-from-date" style="padding: 6px 10px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 0.85rem; background: #f8fafc; width: 140px;">
            </div>
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px; font-weight: 600;">To Date</label>
                <input type="date" id="sc-to-date" style="padding: 6px 10px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 0.85rem; background: #f8fafc; width: 140px;">
            </div>
            <button id="sc-filter-btn" style="padding: 6px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; background: #217346; color: white; font-size: 0.8rem; height: 32px; white-space: nowrap;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button id="sc-reset-btn" style="padding: 6px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; border: 1px solid #CBD5E1; background: white; color: #64748b; font-size: 0.8rem; height: 32px; white-space: nowrap;">
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>

        <div style="padding: 15px 20px;">
            <div id="stock-card-table-container" style="max-height: 400px; overflow-y: auto;">
                <table class="stock-card-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f1f5f9;" id="sc-header-row">
                            <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Date</th>
                            <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Reference</th>
                            <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Receipt</th>
                            <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Issuance</th>
                            <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Office/Dept</th>
                            <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Balance</th>
                            <th style="padding: 10px; border: 1px solid #e2e8f0; font-size: 0.8rem;">Encoder</th>
                        </tr>
                    </thead>
                    <tbody id="stock-card-body">
                        <!-- Content will be loaded via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid #edf2f7; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="cancel-btn" onclick="document.getElementById('stock-card-modal').classList.remove('active'); document.getElementById('stock-card-modal').style.display='none';">Close</button>
            <button type="button" id="print-stock-card-btn" class="add-supply-btn" style="background: #2A4D88;"><i class="fas fa-print"></i> Print PDF</button>
            <button type="button" id="download-sc-btn" class="add-supply-btn" style="background: #217346;"><i class="fas fa-file-excel"></i> Export Official Excel</button>
        </div>
    </div>
</div>
