<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_ris_excel.php
require_once __DIR__ . '/../model/requisitionModel.php';

$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo "Requisition ID is required";
    exit();
}

$model = new RequisitionModel();
$requisition = $model->getRequisitionById($id);
$items = $model->getRequisitionItemsWithStock($id);

if (!$requisition) {
    echo "Requisition not found";
    exit();
}

// Log the action
require_once __DIR__ . '/../model/SystemLogModel.php';
$logModel = new SystemLogModel();
$logModel->log("EXPORT_RIS", "Exported RIS Excel for Requisition ID: $id (No: " . ($requisition['requisition_no'] ?? 'N/A') . ")");

// Now set headers since we have the data
$filename = "RIS_" . preg_replace('/[^A-Za-z0-9]/', '_', $requisition['first_name'] . '_' . $requisition['last_name']) . "_" . date('Ymd') . ".xls";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=' . $filename);

// Basic HTML Table layout for Excel
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .title { font-size: 16pt; font-weight: bold; text-align: center; }
        .header-cell { background-color: #f2f2f2; font-weight: bold; border: 0.5pt solid #000; text-align: center; vertical-align: middle; padding: 5px; }
        .data-cell { border: 0.5pt solid #000; padding: 6px 4px; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .label { font-weight: bold; }
        .underline { border-bottom: 0.5pt solid #000; }
    </style>
</head>
<body>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td colspan="8" class="text-right"><i>Appendix 63</i></td>
        </tr>
        <tr>
            <td colspan="8" class="title">REQUISITION AND ISSUE SLIP</td>
        </tr>
        <tr><td colspan="8" style="height: 15px;"></td></tr>
        <tr>
            <td colspan="1" class="label">Entity Name:</td>
            <td colspan="3" class="underline">CITY GOVERNMENT OF BOGO</td>
            <td colspan="2" class="label" style="text-align: right; padding-right: 10px;">Fund Cluster:</td>
            <td colspan="2" class="underline"></td>
        </tr>
        <tr>
            <td colspan="1" class="label">Division:</td>
            <td colspan="3" class="underline">BOGO CITY</td>
            <td colspan="2" class="label" style="text-align: right; padding-right: 10px;">Responsibility Center Code:</td>
            <td colspan="2" class="underline"></td>
        </tr>
        <tr>
            <td colspan="1" class="label">Office:</td>
            <td colspan="3" class="underline"><?php echo htmlspecialchars($requisition['department_name']); ?></td>
            <td colspan="2" class="label" style="text-align: right; padding-right: 10px;">RIS No:</td>
            <td colspan="2" class="underline"><strong><?php echo htmlspecialchars($requisition['requisition_no']); ?></strong></td>
        </tr>
        <tr><td colspan="8" style="height: 15px;"></td></tr>
        
        <!-- Requisition Table Header -->
        <tr>
            <td colspan="4" class="header-cell">Requisition</td>
            <td colspan="2" class="header-cell">Stock Available?</td>
            <td colspan="2" class="header-cell">Issuance</td>
        </tr>
        <tr>
            <td class="header-cell" style="width: 10%;">Stock No.</td>
            <td class="header-cell" style="width: 10%;">Unit</td>
            <td class="header-cell" style="width: 40%;">Description</td>
            <td class="header-cell" style="width: 10%;">Quantity</td>
            <td class="header-cell" style="width: 10%;">Yes</td>
            <td class="header-cell" style="width: 10%;">No</td>
            <td class="header-cell" style="width: 10%;">Quantity</td>
            <td class="header-cell" style="width: 20%;">Remarks</td>
        </tr>

        <?php 
        $count = 0;
        foreach ($items as $item): 
            $count++;
            $issued_qty = (int)($item['issued_quantity'] ?? 0);
            $stock_yes = $issued_qty > 0 ? '✓' : '';
            $stock_no = $issued_qty <= 0 ? '✓' : '';
        ?>
            <tr>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($item['stock_no'] ?? $count); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($item['unit']); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo (int)$item['quantity']; ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo $stock_yes; ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo $stock_no; ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo $issued_qty; ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($item['remarks'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>

        <!-- Fill extra rows for professional look (total 10 rows) -->
        <?php for($i = $count; $i < 10; $i++): ?>
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

        <!-- Purpose -->
        <tr>
            <td colspan="8" class="data-cell" style="padding: 10px;">
                <span class="label">Purpose:</span> <?php echo htmlspecialchars($requisition['purpose'] ?: 'N/A'); ?>
            </td>
        </tr>

        <!-- Signatories Header -->
        <tr>
            <td colspan="1" class="header-cell"></td>
            <td colspan="2" class="header-cell"> Requested by:</td>
            <td colspan="2" class="header-cell"> Approved by:</td>
            <td colspan="2" class="header-cell"> Issued by:</td>
            <td colspan="1" class="header-cell"> Received by:</td>
        </tr>
        <tr>
            <td colspan="1" class="data-cell label"> Signature</td>
            <td colspan="2" class="data-cell"></td>
            <td colspan="2" class="data-cell"></td>
            <td colspan="2" class="data-cell"></td>
            <td colspan="1" class="data-cell"></td>
        </tr>
        <tr>
            <td colspan="1" class="data-cell label">Printed Name</td>
            <td colspan="2" class="data-cell text-center" style="font-weight: bold; text-decoration: underline;">
                <?php echo htmlspecialchars($requisition['first_name'] . ' ' . $requisition['last_name']); ?>
            </td>
            <td colspan="2" class="data-cell text-center" style="font-weight: bold; text-decoration: underline;">
                <?php 
                $approverName = trim(($requisition['approver_first'] ?? '') . ' ' . ($requisition['approver_last'] ?? ''));
                if (empty($approverName)) {
                    $approverName = $requisition['approver_username'] ?? 'ADMINISTRATOR';
                }
                echo htmlspecialchars($approverName); 
                ?>
            </td>
            <td colspan="2" class="data-cell"></td>
            <td colspan="1" class="data-cell"></td>
        </tr>
        <tr>
            <td colspan="1" class="data-cell label">Designation</td>
            <td colspan="2" class="data-cell text-center"><?php echo htmlspecialchars($requisition['department_name']); ?></td>
            <td colspan="2" class="data-cell text-center">BOGO CITY</td>
            <td colspan="2" class="data-cell"></td>
            <td colspan="1" class="data-cell"></td>
        </tr>
        <tr>
            <td colspan="1" class="data-cell label">Date</td>
            <td colspan="2" class="data-cell text-center"><?php echo date('Y-m-d', strtotime($requisition['request_date'])); ?></td>
            <td colspan="2" class="data-cell text-center">
                <?php echo !empty($requisition['approved_date']) ? date('M d, Y', strtotime($requisition['approved_date'])) : ''; ?>
            </td>
            <td colspan="2" class="data-cell"></td>
            <td colspan="1" class="data-cell"></td>
        </tr>
    </table>
</body>
</html>
