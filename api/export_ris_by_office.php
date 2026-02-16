<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_ris_by_office.php
require_once __DIR__ . '/../db/database.php';
require_once __DIR__ . '/../model/requisitionModel.php';

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$deptId = $_GET['dept_id'] ?? '';

$db = new Database();
$conn = $db->getConnection();

$whereClause = "r.status = 'Approved' AND ri.issued_quantity > 0";
$params = [];

if (!empty($startDate) && !empty($endDate)) {
    $whereClause .= " AND DATE(r.approved_date) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $rangeLabel = date('M d, Y', strtotime($startDate)) . " - " . date('M d, Y', strtotime($endDate));
} else {
    $filterMonth = date('Y-m');
    $whereClause .= " AND r.approved_date LIKE ?";
    $params[] = $filterMonth . '%';
    $rangeLabel = date('F Y');
}

if (!empty($deptId)) {
    $whereClause .= " AND d.department_id = ?";
    $params[] = $deptId;
}

$sql = "SELECT ri.*, s.item as item_name, s.unit, s.unit_cost, s.stock_no, 
        r.requisition_no, r.approved_date, r.purpose, d.department_name
        FROM request_item ri
        JOIN supply s ON ri.supply_id = s.supply_id
        JOIN requisition r ON ri.requisition_id = r.requisition_id
        JOIN employee e ON r.employee_id = e.employee_id
        JOIN department d ON e.department_id = d.department_id
        WHERE $whereClause
        ORDER BY d.department_name ASC, r.approved_date DESC";
        
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by department
$groupedByDept = [];
foreach ($items as $item) {
    $dept = $item['department_name'] ?? 'Unknown Office';
    if (!isset($groupedByDept[$dept])) {
        $groupedByDept[$dept] = [
            'items' => [],
            'purpose' => $item['purpose'] // Use the last purpose as a representative
        ];
    }
    $groupedByDept[$dept]['items'][] = $item;
}

// Log the action
require_once __DIR__ . '/../model/SystemLogModel.php';
$logModel = new SystemLogModel();
$logModel->log("EXPORT_RIS_BY_OFFICE", "Exported formal RIS by Office for range: $rangeLabel" . ($deptId ? " (Dept ID: $deptId)" : " (All)"));

$filename = "RIS_Office_Report_" . (str_replace([' ', ','], '_', $rangeLabel)) . ".xls";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=' . $filename);
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .title { font-size: 16pt; font-weight: bold; text-align: center; }
        .header-cell { background-color: #f2f2f2; font-weight: bold; border: 0.5pt solid #000; text-align: center; vertical-align: middle; padding: 5px; font-size: 10pt; }
        .data-cell { border: 0.5pt solid #000; padding: 6px 4px; vertical-align: middle; font-size: 9pt; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .label { font-weight: bold; }
        .underline { border-bottom: 0.5pt solid #000; }
        .dept-title { font-size: 14pt; font-weight: bold; background-color: #e2e8f0; padding: 10px; border: 0.5pt solid #000; }
    </style>
</head>
<body>
    <?php if (empty($groupedByDept)): ?>
        <table style="width: 100%; border-collapse: collapse;">
            <tr><td colspan="8" class="text-center">No approved items found for the selected criteria.</td></tr>
        </table>
    <?php else: ?>
        <?php foreach ($groupedByDept as $deptName => $deptData): ?>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                <tr>
                    <td colspan="8" class="text-right"><i>Appendix 63</i></td>
                </tr>
                <tr>
                    <td colspan="8" class="title">REQUISITION AND ISSUE SLIP</td>
                </tr>
                <tr>
                    <td colspan="8" class="text-center" style="font-size: 10pt; padding-bottom: 10px;">Period: <?php echo htmlspecialchars($rangeLabel); ?></td>
                </tr>
                
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
                    <td colspan="3" class="underline"><?php echo htmlspecialchars($deptName); ?></td>
                    <td colspan="2" class="label" style="text-align: right; padding-right: 10px;">RIS No:</td>
                    <td colspan="2" class="underline" style="text-align: center;">VARIOUS</td>
                </tr>
                <tr><td colspan="8" style="height: 15px;"></td></tr>
                
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
                foreach ($deptData['items'] as $item): 
                    $count++;
                    $issued_qty = (int)($item['issued_quantity'] ?? 0);
                    $stock_yes = $issued_qty > 0 ? '✓' : '';
                    $stock_no = $issued_qty <= 0 ? '✓' : '';
                ?>
                    <tr>
                        <td class="data-cell text-center"><?php echo htmlspecialchars($item['stock_no'] ?? $count); ?></td>
                        <td class="data-cell text-center"><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td class="data-cell"><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td class="data-cell text-center"><?php echo (int)$item['quantity']; ?></td>
                        <td class="data-cell text-center"><?php echo $stock_yes; ?></td>
                        <td class="data-cell text-center"><?php echo $stock_no; ?></td>
                        <td class="data-cell text-center"><?php echo $issued_qty; ?></td>
                        <td class="data-cell"><?php echo htmlspecialchars($item['remarks'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>

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

                <tr>
                    <td colspan="8" class="data-cell" style="padding: 10px;">
                        <span class="label">Purpose:</span> SEE ATTACHED DISBURSEMENTS
                    </td>
                </tr>

                <tr>
                    <td colspan="1" class="header-cell"></td>
                    <td colspan="2" class="header-cell"> Requested by:</td>
                    <td colspan="2" class="header-cell"> Approved by:</td>
                    <td colspan="2" class="header-cell"> Issued by:</td>
                    <td colspan="1" class="header-cell"> Received by:</td>
                </tr>
                <tr>
                    <td colspan="1" class="data-cell label"> Signature</td>
                    <td colspan="2" class="data-cell" style="height: 40px;"></td>
                    <td colspan="2" class="data-cell"></td>
                    <td colspan="2" class="data-cell"></td>
                    <td colspan="1" class="data-cell"></td>
                </tr>
                <tr>
                    <td colspan="1" class="data-cell label">Printed Name</td>
                    <td colspan="2" class="data-cell text-center underline">
                        __________________________
                    </td>
                    <td colspan="2" class="data-cell text-center underline">
                        __________________________
                    </td>
                    <td colspan="2" class="data-cell text-center underline">
                        __________________________
                    </td>
                    <td colspan="1" class="data-cell text-center underline">
                        __________________________
                    </td>
                </tr>
                <tr>
                    <td colspan="1" class="data-cell label">Designation</td>
                    <td colspan="2" class="data-cell text-center">__________________________</td>
                    <td colspan="2" class="data-cell text-center">__________________________</td>
                    <td colspan="2" class="data-cell text-center">__________________________</td>
                    <td colspan="1" class="data-cell text-center">__________________________</td>
                </tr>
                <tr>
                    <td colspan="1" class="data-cell label">Date</td>
                    <td colspan="2" class="data-cell text-center">__________________________</td>
                    <td colspan="2" class="data-cell text-center">__________________________</td>
                    <td colspan="2" class="data-cell text-center">__________________________</td>
                    <td colspan="1" class="data-cell text-center">__________________________</td>
                </tr>
                <!-- Page Break for Next Department in Excel -->
                <tr style="height: 50px;"><td colspan="8" style="border: none;">&nbsp;</td></tr>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
