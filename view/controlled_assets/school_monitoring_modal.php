<!-- Modal for School Monitoring -->
<div id="school-monitoring-modal" class="modal add-supply-modal-wrapper">
    <div class="modal-content add-supply-modal-content" style="max-width: 800px;">
        <span class="close item-close-btn" onclick="closeSchoolMonitoringModal()">&times;</span>
        
        <div class="modal-header-section" style="background: var(--gradient-primary);">
            <h2><i class="fas fa-chart-pie"></i> Inventory Status Monitor</h2>
            <p class="modal-subtitle">Real-time overview of stock levels for <?php echo htmlspecialchars($schoolName); ?></p>
        </div>

        <div class="modal-body" style="padding: 30px;">
            <div class="status-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <!-- Cards will be populated here -->
                <div class="status-card" style="background: #ecfdf5; border: 1px solid #10b981; padding: 20px; border-radius: 12px; text-align: center;">
                    <h3 style="margin: 0; color: #047857; font-size: 2rem;" id="monitor-functional">0</h3>
                    <p style="margin: 5px 0 0; color: #059669; font-weight: 600;">Functional</p>
                </div>
                <div class="status-card" style="background: #fffbeb; border: 1px solid #f59e0b; padding: 20px; border-radius: 12px; text-align: center;">
                    <h3 style="margin: 0; color: #b45309; font-size: 2rem;" id="monitor-repair">0</h3>
                    <p style="margin: 5px 0 0; color: #d97706; font-weight: 600;">For Repair</p>
                </div>
                <div class="status-card" style="background: #fef2f2; border: 1px solid #ef4444; padding: 20px; border-radius: 12px; text-align: center;">
                    <h3 style="margin: 0; color: #b91c1c; font-size: 2rem;" id="monitor-condemned">0</h3>
                    <p style="margin: 5px 0 0; color: #dc2626; font-weight: 600;">Condemned</p>
                </div>
                <div class="status-card" style="background: #f1f5f9; border: 1px solid #64748b; padding: 20px; border-radius: 12px; text-align: center;">
                    <h3 style="margin: 0; color: #334155; font-size: 2rem;" id="monitor-lost">0</h3>
                    <p style="margin: 5px 0 0; color: #475569; font-weight: 600;">Lost / Stolen</p>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <h4 style="margin-top: 0; color: #1e293b; margin-bottom: 15px;">Overall Condition Report</h4>
                <div style="height: 20px; width: 100%; background: #e2e8f0; border-radius: 10px; overflow: hidden; display: flex;">
                    <div id="bar-functional" style="height: 100%; background: #10b981; width: 0%;"></div>
                    <div id="bar-repair" style="height: 100%; background: #f59e0b; width: 0%;"></div>
                    <div id="bar-condemned" style="height: 100%; background: #ef4444; width: 0%;"></div>
                    <div id="bar-lost" style="height: 100%; background: #64748b; width: 0%;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 0.85rem; color: #64748b;">
                    <span>Total Items: <strong id="monitor-total">0</strong></span>
                    <span>Last Updated: <strong><?php echo date('M d, Y H:i'); ?></strong></span>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin: 0; color: #1e293b;">Item Details</h4>
                    <input type="text" id="monitor-search" placeholder="Search items..." style="padding: 8px 15px; border: 1px solid #cbd5e1; border-radius: 8px; width: 250px;">
                </div>
                <div style="overflow-x: auto; max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead style="background: #f8fafc; position: sticky; top: 0;">
                            <tr>
                                <th style="padding: 12px 15px; text-align: left; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0;">Item Name</th>
                                <th style="padding: 12px 15px; text-align: left; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0;">Property / Stock No.</th>
                                <th style="padding: 12px 15px; text-align: left; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0;">Date Acquired</th>
                                <th style="padding: 12px 15px; text-align: center; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0;">Condition</th>
                                <th style="padding: 12px 15px; text-align: center; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="monitor-table-body">
                            <!-- Rows will be populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="modal-footer" style="padding: 20px 30px; background: #f8fafc; border-top: 1px solid #e2e8f0; border-radius: 0 0 16px 16px; display: flex; justify-content: flex-end;">
            <button type="button" class="cancel-btn" onclick="closeSchoolMonitoringModal()" style="padding: 10px 24px;">Close</button>
        </div>
    </div>
</div>
