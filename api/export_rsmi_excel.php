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

// Flatten and paginate items before outputting
$groupedItems = [];
$flattenedItems = [];

foreach ($items as $item) {
    $ris = $item['requisition_no'] ?? 'N/A';
    if (!isset($groupedItems[$ris])) $groupedItems[$ris] = [];
    $groupedItems[$ris][] = $item;
}

foreach ($groupedItems as $ris => $risItems) {
    foreach ($risItems as $index => $item) {
        $item['is_first_in_group'] = ($index === 0);
        $item['group_rowspan'] = count($risItems);
        $item['group_ris'] = $ris;
        $flattenedItems[] = $item;
    }
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: 'Times New Roman', serif; }
        table { border-collapse: collapse; width: 100%; border: 0.5pt solid windowtext; }
        .title { font-size: 14pt; font-weight: bold; text-align: center; text-transform: uppercase; border: none; }
        
        /* Official Table Header & Data Styling */
        .header-cell { 
            border: 0.5pt solid windowtext; 
            font-weight: bold; 
            text-align: center; 
            vertical-align: middle; 
            padding: 5px; 
            font-size: 10pt; 
            background-color: #E7E7E7; 
        }
        .data-cell { 
            border: 0.5pt solid windowtext; 
            padding: 6px; 
            vertical-align: middle; 
            font-size: 10pt; 
            height: 30px; 
            text-align: center; 
            mso-number-format: "\@";
        }
        .section-header { 
            border: 0.5pt solid windowtext; 
            font-style: italic; 
            font-size: 9pt; 
            text-align: center; 
            height: 30px; 
            vertical-align: middle; 
            background-color: #F8F8F8; 
        }
        
        .label { font-weight: bold; font-size: 11pt; border: none; }
        .underline { border-bottom: 0.5pt solid #000; font-size: 11pt; }
        
        /* Number Formats */
        .num-fmt { mso-number-format: "\#\,\#\#0\.00"; text-align: center; border: 0.5pt solid windowtext; }
        .qty-fmt { mso-number-format: "\#\,\#\#0"; text-align: center; border: 0.5pt solid windowtext; }
        
        .no-border { border: none !important; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
    </style>
</head>
<body style="font-family: 'Times New Roman', serif;">
    <table border="1">
        <col width="100"> <!-- A: RIS No -->
        <col width="120"> <!-- B: Responsibility -->
        <col width="100"> <!-- C: Stock No -->
        <col width="320"> <!-- D: Item Description -->
        <col width="60">  <!-- E: Unit -->
        <col width="80">  <!-- F: Quantity Issued -->
        <col width="100"> <!-- G: Unit Cost -->
        <col width="110"> <!-- H: Amount -->

        <tr><td colspan="8" class="text-right no-border" style="font-style: italic; font-weight: bold; border:none;">Appendix 64</td></tr>
        <tr><td colspan="8" class="title no-border" style="border:none;">REPORT OF SUPPLIES AND MATERIALS ISSUED</td></tr>
        <tr><td colspan="8" class="no-border" style="height: 15px; border:none;">&nbsp;</td></tr>
        
        <tr>
            <td colspan="1" class="label text-left no-border" style="border:none;">Entity Name:</td>
            <td colspan="4" class="underline no-border" style="border:none; border-bottom: 0.5pt solid #000;">CITY GOVERNMENT OF BOGO</td>
            <td colspan="1" class="no-border" style="border:none;">&nbsp;</td>
            <td colspan="1" class="label text-right no-border" style="border:none; text-align: right;">Serial No. :</td>
            <td colspan="1" class="underline no-border" style="border:none; border-bottom: 0.5pt solid #000;"><?php echo htmlspecialchars($serialNo); ?></td>
        </tr>
        <tr>
            <td colspan="1" class="label text-left no-border" style="border:none;">Fund Cluster:</td>
            <td colspan="4" class="underline no-border" style="border:none; border-bottom: 0.5pt solid #000;">&nbsp;</td>
            <td colspan="1" class="no-border" style="border:none;">&nbsp;</td>
            <td colspan="1" class="label text-right no-border" style="border:none; text-align: right;">Date :</td>
            <td colspan="1" class="underline no-border" style="border:none; border-bottom: 0.5pt solid #000;"><?php echo htmlspecialchars($exportDate); ?></td>
        </tr>
        <tr><td colspan="8" class="no-border" style="height: 15px; border:none;">&nbsp;</td></tr>
        
        <tr>
            <td colspan="6" class="section-header"><i>To be filled up by the Supply and/or Property Division/Unit</i></td>
            <td colspan="2" class="section-header"><i>To be filled up by the Accounting Div/Unit</i></td>
        </tr>
        <tr>
            <td class="header-cell">RIS No.</td>
            <td class="header-cell">Responsibility Center Code</td>
            <td class="header-cell">Stock No.</td>
            <td class="header-cell">Item Description</td>
            <td class="header-cell">Unit</td>
            <td class="header-cell">Quantity Issued</td>
            <td class="header-cell">Unit Cost</td>
            <td class="header-cell">Amount</td>
        </tr>
        <?php 
        $totalAmount = 0;
        foreach ($flattenedItems as $item):
            $issued_qty = (int)($item['issued_quantity'] ?? 0);
            $unit_cost = (float)($item['unit_cost'] ?? 0);
            $amount = $item['total_amount'] ?? ($issued_qty * $unit_cost);
            $totalAmount += (float)$amount;
            
            $printRis = false;
            $rowspan = 1;
            if ($item['is_first_in_group']) {
                $printRis = true;
                $rowspan = $item['group_rowspan'];
            }
        ?>
            <tr>
                <?php if ($printRis): ?>
                    <td class="data-cell" rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($item['group_ris']); ?></td>
                    <td class="data-cell" rowspan="<?php echo $rowspan; ?>">&nbsp;</td>
                <?php endif; ?>
                <td class="data-cell"><?php echo htmlspecialchars($item['stock_no'] ?? ''); ?></td>
                <td class="data-cell text-left" style="text-align: left; mso-number-format: '\@';"><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td class="data-cell"><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                <td class="data-cell qty-fmt"><?php echo $issued_qty; ?></td>
                <td class="data-cell num-fmt"><?php echo number_format($unit_cost, 2); ?></td>
                <td class="data-cell num-fmt"><?php echo number_format($amount, 2); ?></td>
            </tr>
        <?php endforeach; ?>

        <tr>
            <td colspan="7" class="data-cell label text-right" style="border: 0.5pt solid windowtext; text-align: right; font-weight: bold; background-color: #F8F8F8;">TOTAL</td>
            <td class="data-cell label num-fmt" style="font-weight: bold; background-color: #F8F8F8;"><?php echo number_format($totalAmount, 2); ?></td>
        </tr>
        
        <tr><td colspan="8" class="no-border" style="height: 30px; border:none;">&nbsp;</td></tr>

        <tr><td colspan="8" class="label text-center no-border" style="border:none; text-align: center; text-decoration: underline; font-size: 13pt;">R E C A P I T U L A T I O N</td></tr>
        <tr><td colspan="8" class="no-border" style="height: 10px; border:none;">&nbsp;</td></tr>
        <tr>
            <td class="header-cell">Stock No.</td>
            <td class="header-cell" colspan="2">Item Description</td>
            <td class="header-cell">Quantity</td>
            <td class="header-cell">Unit Cost</td>
            <td class="header-cell">Total Cost</td>
            <td class="header-cell" colspan="2">UACS Object Code</td>
        </tr>
        <?php 
        $recap = [];
        foreach ($flattenedItems as $item) {
            $sn = $item['stock_no'] ?? 'N/A';
            if (!isset($recap[$sn])) {
                $recap[$sn] = ['qty' => 0, 'cost' => (float)($item['unit_cost'] ?? 0), 'tot' => 0, 'name' => $item['item_name']];
            }
            $recap[$sn]['qty'] += (int)($item['issued_quantity'] ?? 0);
            $recap[$sn]['tot'] += (int)($item['issued_quantity'] ?? 0) * (float)($item['unit_cost'] ?? 0);
        }
        foreach ($recap as $sn => $rd):
        ?>
            <tr>
                <td class="data-cell"><?php echo htmlspecialchars($sn); ?></td>
                <td class="data-cell text-left" colspan="2" style="text-align: left;"><?php echo htmlspecialchars($rd['name']); ?></td>
                <td class="data-cell qty-fmt"><?php echo $rd['qty']; ?></td>
                <td class="data-cell num-fmt"><?php echo number_format($rd['cost'], 2); ?></td>
                <td class="data-cell num-fmt"><?php echo number_format($rd['tot'], 2); ?></td>
                <td class="data-cell" colspan="2">&nbsp;</td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="5" class="data-cell label text-right" style="text-align: right; border: none;">TOTAL AMOUNT&nbsp;</td>
            <td class="data-cell label num-fmt" style="font-weight: bold; background-color: #F8F8F8;"><?php echo number_format($totalAmount, 2); ?></td>
            <td class="data-cell no-border" colspan="2" style="border:none;">&nbsp;</td>
        </tr>

        <tr><td colspan="8" class="no-border" style="height: 40px; border:none;">&nbsp;</td></tr>

        <tr>
            <td colspan="4" class="signatory-label text-left no-border" style="border:none;">I hereby certify to the correctness of the above information.</td>
            <td colspan="4" class="signatory-label text-left no-border" style="border:none;">Posted by:</td>
        </tr>
        <tr>
            <td colspan="4" class="text-center no-border" style="border:none; padding-top: 30px; text-align: center;">
                <span style="font-weight: bold; text-decoration: underline; font-size: 11pt; text-transform: uppercase;"><?php echo htmlspecialchars($approverName); ?></span><br>
                <span style="font-size: 9pt;">Signature over Printed Name of Supply and/or Property Custodian</span>
            </td>
            <td colspan="4" class="text-center no-border" style="border:none; padding-top: 30px; text-align: center;">
                <span style="font-weight: bold; text-decoration: underline; font-size: 11pt; text-transform: uppercase;">ELVAE NIÑA JUANILLO</span><br>
                <span style="font-size: 9pt;">Signature over Printed Name of Designated Accounting Staff</span>
            </td>
        </tr>
        <tr><td colspan="8" class="no-border" style="height: 40px; border:none;">&nbsp;</td></tr>
        <tr>
            <td colspan="1" class="label text-left no-border" style="border:none;">Date:</td>
            <td colspan="3" class="underline no-border" style="border:none; border-bottom: 0.5pt solid #000;">&nbsp;</td>
            <td colspan="1" class="label text-left no-border" style="border:none;">Date:</td>
            <td colspan="3" class="underline no-border" style="border:none; border-bottom: 0.5pt solid #000;">&nbsp;</td>
        </tr>
    </table>
</body>
</html>



