<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_rsmi_excel.php
require_once __DIR__ . '/../model/requisitionModel.php';

$id = $_GET['id'] ?? '';

$model = new RequisitionModel();
$db = new Database();
$conn = $db->getConnection();

if (!empty($id)) {
    // Export specific requisition
    $requisition = $model->getRequisitionById($id);
    $sql = "SELECT ri.*, s.item as item_name, s.unit, s.unit_cost, s.stock_no
            FROM request_item ri
            JOIN supply s ON ri.supply_id = s.supply_id
            WHERE ri.requisition_id = ? AND ri.issued_quantity > 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$requisition) {
        echo "Requisition not found";
        exit();
    }
    $serialNo = $requisition['requisition_no'];
    $exportDate = date('M d, Y', strtotime($requisition['approved_date'] ?? $requisition['request_date']));
    $approverName = trim(($requisition['approver_first'] ?? '') . ' ' . ($requisition['approver_last'] ?? ''));
    if (empty($approverName)) $approverName = $requisition['approver_username'] ?? 'ADMINISTRATOR';
    $filename = "RSMI_" . preg_replace('/[^A-Za-z0-9]/', '_', $requisition['first_name'] . '_' . $requisition['last_name']) . "_" . date('Ymd') . ".xls";
} else {
    // Export all approved items by month or date range (Summary Mode)
    $selectedMonth = $_GET['month'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $requisition = null;
    
    // Check if we should use snapshot (only if month is provided and no custom range)
    $useSnapshot = false;
    if (empty($startDate) && empty($endDate) && !empty($selectedMonth) && preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
        require_once __DIR__ . '/../model/snapshotModel.php';
        $snapshotModel = new SnapshotModel();
        if ($snapshotModel->rsmiSnapshotExists($selectedMonth)) {
            $items = $snapshotModel->getRSMISnapshotData($selectedMonth);
            $useSnapshot = true;
        }
    }

    if (!$useSnapshot) {
        $whereClause = "r.status = 'Approved' AND ri.issued_quantity > 0";
        $params = [];
        
        if (!empty($startDate) && !empty($endDate)) {
            // Custom Date Range
            $whereClause .= " AND DATE(r.approved_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        } else {
            // Default to current month if no month/range provided for live data
            $filterMonth = !empty($selectedMonth) ? $selectedMonth : date('Y-m');
            $whereClause .= " AND r.approved_date LIKE ?";
            $params[] = $filterMonth . '%';
        }

        $sql = "SELECT ri.*, s.item as item_name, s.unit, s.unit_cost, s.stock_no, r.requisition_no, r.approved_date
                FROM request_item ri
                JOIN supply s ON ri.supply_id = s.supply_id
                JOIN requisition r ON ri.requisition_id = r.requisition_id
                WHERE $whereClause
                ORDER BY r.approved_date DESC";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if (!empty($startDate) && !empty($endDate)) {
        $serialNo = "RANGE-" . str_replace('-', '', $startDate) . "-" . str_replace('-', '', $endDate);
        $filenameMonth = $startDate . "_to_" . $endDate;
        $exportDate = date('M d, Y', strtotime($startDate)) . " - " . date('M d, Y', strtotime($endDate));
    } else {
        $serialNo = "SUMMARY-" . (!empty($selectedMonth) ? str_replace('-', '', $selectedMonth) : date('Ymd'));
        $filenameMonth = !empty($selectedMonth) ? $selectedMonth : date('Y-m');
        $exportDate = date('M d, Y');
    }
    
    $approverName = "ADMINISTRATOR"; 
    $filename = "RSMI_Summary_" . $filenameMonth . ".xls";
}

// Log the action
require_once __DIR__ . '/../model/SystemLogModel.php';
$logModel = new SystemLogModel();
$logModel->log("EXPORT_RSMI", "Exported RSMI Excel " . (!empty($id) ? "for Requisition ID: $id" : "Summary for $selectedMonth"));

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=' . $filename);

?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: 'Times New Roman', serif; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        .title { font-size: 14pt; font-weight: bold; text-align: center; text-transform: uppercase; }
        .header-cell { border: 0.5pt solid windowtext; font-weight: bold; text-align: center; vertical-align: middle; padding: 5px; font-size: 10pt; background-color: #F2F2F2; }
        .data-cell { border: 0.5pt solid windowtext; padding: 4px; vertical-align: middle; font-size: 9pt; }
        .section-header { border: 0.5pt solid windowtext; font-style: italic; font-size: 8pt; font-weight: normal; text-align: center; height: 25px; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .label { font-weight: bold; font-size: 10pt; }
        .underline { border-bottom: 0.5pt solid #000; font-size: 10pt; }
        .signatory-label { font-size: 9pt; vertical-align: top; padding-top: 10px; }
        .signatory-name { font-weight: bold; text-decoration: underline; font-size: 10pt; }
        .signatory-title { font-size: 8pt; font-style: normal; }
    </style>
</head>
<body>
    <table border="0" style="border-collapse: collapse;">
        <!-- Column width suggestions for Excel -->
        <col width="100"> <!-- A: RIS No -->
        <col width="120"> <!-- B: Responsibility -->
        <col width="100"> <!-- C: Stock No -->
        <col width="300"> <!-- D: Item -->
        <col width="80">  <!-- E: Unit -->
        <col width="80">  <!-- F: Qty Issued / Recap Unit Cost -->
        <col width="100"> <!-- G: Unit Cost / Recap Total Cost -->
        <col width="120"> <!-- H: Amount / Recap UACS -->

        <tr>
            <td colspan="8" class="text-right" style="font-style: italic; font-weight: bold;">Appendix 64</td>
        </tr>
        <tr>
            <td colspan="8" class="title">REPORT OF SUPPLIES AND MATERIALS ISSUED</td>
        </tr>
        <tr><td colspan="8" style="height: 15px;">&nbsp;</td></tr>
        
        <tr>
            <td colspan="1" class="label">Entity Name:</td>
            <td colspan="4" class="underline">CITY GOVERNMENT OF BOGO</td>
            <td colspan="1">&nbsp;</td>
            <td colspan="1" class="label" style="text-align: right;">Serial No. :</td>
            <td colspan="1" class="underline"><?php echo htmlspecialchars($serialNo); ?></td>
        </tr>
        <tr>
            <td colspan="1" class="label">Fund Cluster:</td>
            <td colspan="4" class="underline">&nbsp;</td>
            <td colspan="1">&nbsp;</td>
            <td colspan="1" class="label" style="text-align: right;">Date :</td>
            <td colspan="1" class="underline"><?php echo htmlspecialchars($exportDate); ?></td>
        </tr>
        <tr><td colspan="8" style="height: 15px;">&nbsp;</td></tr>
        
        <!-- Main Table Headers -->
        <tr>
            <td colspan="6" class="section-header"><i>To be filled up by the Supply and/or Property Division/Unit</i></td>
            <td colspan="2" class="section-header"><i>To be filled up by the Accounting Div/Unit</i></td>
        </tr>
        <tr>
            <td class="header-cell">RIS No.</td>
            <td class="header-cell">Responsibility Center Code</td>
            <td class="header-cell">Stock No.</td>
            <td class="header-cell">Item</td>
            <td class="header-cell">Unit</td>
            <td class="header-cell">Quantity Issued</td>
            <td class="header-cell">Unit Cost</td>
            <td class="header-cell">Amount</td>
        </tr>

        <?php 
        $groupedItems = [];
        foreach ($items as $item) {
            $ris = $item['requisition_no'] ?? 'N/A';
            if (!isset($groupedItems[$ris])) $groupedItems[$ris] = [];
            $groupedItems[$ris][] = $item;
        }

        $totalAmount = 0;
        $allItemCount = 0;
        foreach ($groupedItems as $ris => $risItems):
            $rowCount = count($risItems);
            foreach ($risItems as $index => $item):
                $allItemCount++;
                $issued_qty = (int)($item['issued_quantity'] ?? 0);
                $unit_cost = (float)($item['unit_cost'] ?? 0);
                $amount = $item['total_amount'] ?? ($issued_qty * $unit_cost);
                $totalAmount += (float)$amount;
        ?>
            <tr>
                <?php if ($index === 0): ?>
                    <td class="data-cell" style="text-align: center;" rowspan="<?php echo $rowCount; ?>"><?php echo htmlspecialchars($ris); ?></td>
                    <td class="data-cell" rowspan="<?php echo $rowCount; ?>">&nbsp;</td>
                <?php endif; ?>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($item['stock_no'] ?? ''); ?></td>
                <td class="data-cell"><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($item['unit'] ?? '&nbsp;'); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo $issued_qty; ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo number_format($unit_cost, 2); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo number_format($amount, 2); ?></td>
            </tr>
        <?php endforeach; endforeach; ?>

        <?php 
        // Fill up to at least 10 rows
        for($i = $allItemCount; $i < 10; $i++): ?>
            <tr>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
            </tr>
        <?php endfor; ?>

        <tr>
            <td colspan="7" class="data-cell label text-right" style="border: none; text-align: right;">TOTAL&nbsp;</td>
            <td class="data-cell label" style="border: 0.5pt solid windowtext; text-align: center;"><?php echo number_format($totalAmount, 2); ?></td>
        </tr>

        <!-- Recap Table Spacing -->
        <tr><td colspan="8" style="height: 30px;">&nbsp;</td></tr>

        <!-- Recap Section (Accounting) -->
        <tr>
            <td class="header-cell">Stock No.</td>
            <td class="header-cell">Quantity</td>
            <td class="header-cell" colspan="3">&nbsp;</td>
            <td class="header-cell">Unit Cost</td>
            <td class="header-cell">Total Cost</td>
            <td class="header-cell">UACS Object Code</td>
        </tr>
        <?php 
        $recap = [];
        foreach ($items as $item) {
            $sn = $item['stock_no'] ?? 'N/A';
            if (!isset($recap[$sn])) {
                $recap[$sn] = [
                    'qty' => 0,
                    'unit_cost' => (float)($item['unit_cost'] ?? 0),
                    'total' => 0,
                    'item' => $item['item_name']
                ];
            }
            $recap[$sn]['qty'] += (int)($item['issued_quantity'] ?? 0);
            $recap[$sn]['total'] += (int)($item['issued_quantity'] ?? 0) * (float)($item['unit_cost'] ?? 0);
        }

        foreach ($recap as $sn => $data):
        ?>
            <tr>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($sn); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo $data['qty']; ?></td>
                <td class="data-cell" colspan="3"><?php echo htmlspecialchars($data['item']); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo number_format($data['unit_cost'], 2); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo number_format($data['total'], 2); ?></td>
                <td class="data-cell">&nbsp;</td>
            </tr>
        <?php endforeach; ?>

        <tr>
            <td colspan="6" class="data-cell label text-right" style="border: none; text-align: right;">TOTAL&nbsp;</td>
            <td class="data-cell label" style="border: 0.5pt solid windowtext; text-align: center;"><?php echo number_format($totalAmount, 2); ?></td>
            <td class="data-cell" style="border: none;">&nbsp;</td>
        </tr>

        <tr><td colspan="8" style="height: 30px;">&nbsp;</td></tr>

        <!-- Signatories -->
        <tr>
            <td colspan="4" class="signatory-label">I hereby certify to the correctness of the above information.</td>
            <td colspan="4" class="signatory-label">Posted by:</td>
        </tr>
        <tr>
            <td colspan="4" class="text-center" style="padding-top: 30px;">
                <span class="signatory-name"><?php echo htmlspecialchars($approverName); ?></span><br>
                <span class="signatory-title">Signature over Printed Name of Supply and/or Property Custodian</span>
            </td>
            <td colspan="4" class="text-center" style="padding-top: 30px;">
                <span class="signatory-name">ELVAE NIÑA JUANILLO</span><br>
                <span class="signatory-title">Signature over Printed Name of Designated Accounting Staff</span>
            </td>
        </tr>
        <tr>
            <td colspan="1" style="height: 30px;">&nbsp;</td>
            <td colspan="3">&nbsp;</td>
            <td colspan="1">&nbsp;</td>
            <td colspan="3" class="text-center" style="vertical-align: bottom;">
                <div style="border-top: 0.5pt solid #000; display: inline-block; width: 60%; margin-top: 20px;">Date</div>
            </td>
        </tr>
    </table>
</body>
</html>
