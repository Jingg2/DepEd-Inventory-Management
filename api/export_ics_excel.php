<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_ics_excel.php
require_once __DIR__ . '/../model/requisitionModel.php';
require_once __DIR__ . '/../model/employeeModel.php';

$id = $_GET['id'] ?? ''; // Requisition ID
$empId = $_GET['employee_id'] ?? ''; // Employee ID

if (empty($id) && empty($empId)) {
    echo "Requisition ID or Employee ID is required";
    exit();
}

if (!empty($id)) {
    $model = new RequisitionModel();
    $requisition = $model->getRequisitionById($id);
    $items = $model->getRequisitionItemsForICS($id);
    $subject = $requisition;
} else {
    $empModel = new EmployeeModel();
    $subject = $empModel->getEmployeeById($empId);
    $items = $empModel->getEmployeeHeldItemsForICS($empId);
    // Standardize field for consistency with requisition-based export
    if ($subject) {
        $subject['approved_date'] = $items[0]['approved_date'] ?? null;
    }
}

if (!$subject) {
    echo "Subject (Employee/Requisition) not found";
    exit();
}

if (empty($items)) {
    echo "No semi-expendable items found for this " . (!empty($empId) ? "employee" : "requisition") . ".";
    exit();
}

// Log the action
require_once __DIR__ . '/../model/SystemLogModel.php';
$logModel = new SystemLogModel();
$logModel->log("EXPORT_ICS", "Exported ICS Excel for " . ($empId ? "Employee ID: $empId" : "Requisition ID: $id"));

$filename = "ICS_" . preg_replace('/[^A-Za-z0-9]/', '_', $subject['first_name'] . '_' . $subject['last_name']) . "_" . date('Ymd') . ".xls";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=' . $filename);
header('Pragma: no-cache');
header('Expires: 0');

?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: 'Times New Roman', serif; }
        .title { font-size: 14pt; font-weight: bold; text-align: center; }
        .header-cell { border: 0.5pt solid windowtext; font-weight: bold; text-align: center; vertical-align: middle; padding: 5px; font-size: 10pt; background-color: #F2F2F2; }
        .data-cell { border: 0.5pt solid windowtext; padding: 4px; vertical-align: middle; font-size: 9pt; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .label { font-weight: bold; font-size: 10pt; }
        .underline { border-bottom: 0.5pt solid #000; font-size: 10pt; }
    </style>
</head>
<body>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td colspan="7" class="text-right" style="font-style: italic; font-weight: bold;">Appendix 59</td>
        </tr>
        <tr>
            <td colspan="7" class="title">INVENTORY CUSTODIAN SLIP</td>
        </tr>
        <tr><td colspan="7" style="height: 15px;">&nbsp;</td></tr>
        
        <tr>
            <td colspan="1" class="label">Entity Name:</td>
            <td colspan="3" class="underline">CITY GOVERNMENT OF BOGO</td>
            <td colspan="1">&nbsp;</td>
            <td colspan="1" class="label" style="text-align: right;">Fund Cluster:</td>
            <td colspan="1" class="underline">&nbsp;</td>
        </tr>
        <tr><td colspan="7" style="height: 10px;">&nbsp;</td></tr>

        <!-- ICS Table Headers -->
        <tr>
            <th class="header-cell" rowspan="2" style="width: 10%;">Quantity</th>
            <th class="header-cell" rowspan="2" style="width: 10%;">Unit</th>
            <th class="header-cell" colspan="2" style="width: 50%;">Amount</th>
            <th class="header-cell" rowspan="2" style="width: 30%;">Description</th>
            <th class="header-cell" rowspan="2" style="width: 15%;">Inventory Item No.</th>
            <th class="header-cell" rowspan="2" style="width: 10%;">Estimated Useful Life</th>
        </tr>
        <tr>
            <th class="header-cell">Unit Cost</th>
            <th class="header-cell">Total Cost</th>
        </tr>

        <?php 
        $count = 0;
        foreach ($items as $item): 
            $count++;
            $qty = (int)$item['issued_quantity'];
            $cost = (float)$item['unit_cost'];
            $total = $qty * $cost;
        ?>
            <tr>
                <td class="data-cell" style="text-align: center;"><?php echo $qty; ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($item['unit']); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo number_format($cost, 2); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo number_format($total, 2); ?></td>
                <td class="data-cell"><?php echo htmlspecialchars($item['item_name'] . ' - ' . $item['description']); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($item['stock_no'] ?? ''); ?></td>
                <td class="data-cell" style="text-align: center;">&nbsp;</td>
            </tr>
        <?php endforeach; ?>

        <!-- Fill up to at least 10 rows -->
        <?php for($i = $count; $i < 10; $i++): ?>
            <tr>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
            </tr>
        <?php endfor; ?>

        <tr><td colspan="7" style="height: 30px;">&nbsp;</td></tr>

        <!-- Signatories -->
        <tr>
            <td colspan="3" class="header-cell" style="background: none; text-align: left; border: none; font-weight: normal;">Received from:</td>
            <td colspan="4" class="header-cell" style="background: none; text-align: left; border: none; font-weight: normal;">Received by:</td>
        </tr>
        <tr>
            <td colspan="3" class="data-cell" style="height: 60px; border: 0.5pt solid #000; vertical-align: bottom; text-align: center;">
                <div style="font-weight: bold; text-decoration: underline;">ADMINISTRATOR</div>
                <div style="font-size: 8pt;">Signature over Printed Name</div>
            </td>
            <td colspan="4" class="data-cell" style="height: 60px; border: 0.5pt solid #000; vertical-align: bottom; text-align: center;">
                <div style="font-weight: bold; text-decoration: underline;"><?php echo strtoupper($subject['first_name'] . ' ' . $subject['last_name']); ?></div>
                <div style="font-size: 8pt;">Signature over Printed Name</div>
            </td>
        </tr>
        <tr>
            <td colspan="1" class="data-cell" style="border: 0.5pt solid #000; text-align: center;">Designation</td>
            <td colspan="2" class="data-cell" style="border: 0.5pt solid #000; text-align: center;">Supply Officer</td>
            <td colspan="2" class="data-cell" style="border: 0.5pt solid #000; text-align: center;">Designation</td>
            <td colspan="2" class="data-cell" style="border: 0.5pt solid #000; text-align: center;"><?php echo htmlspecialchars($subject['department_name'] ?? $subject['position'] ?? ''); ?></td>
        </tr>
        <tr>
            <td colspan="1" class="data-cell" style="border: 0.5pt solid #000; text-align: center;">Date</td>
            <td colspan="2" class="data-cell" style="border: 0.5pt solid #000; text-align: center;"><?php echo date('M d, Y'); ?></td>
            <td colspan="2" class="data-cell" style="border: 0.5pt solid #000; text-align: center;">Date</td>
            <td colspan="2" class="data-cell" style="border: 0.5pt solid #000; text-align: center;"><?php echo !empty($subject['approved_date']) ? date('M d, Y', strtotime($subject['approved_date'])) : ''; ?></td>
        </tr>
    </table>
</body>
</html>
