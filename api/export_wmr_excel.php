<?php
ob_start();
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../includes/security.php';

// Ensure session for auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized Access");
}

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$month = $_GET['month'] ?? null;

// Handle month filter shortcut
if ($month && !$startDate && !$endDate) {
    $startDate = $month . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));
}

$model = new SupplyModel();
$items = $model->getWasteItems($startDate, $endDate);

// Log export
require_once __DIR__ . '/../model/SystemLogModel.php';
$logModel = new SystemLogModel();
$rangeStr = ($startDate && $endDate) ? " ($startDate to $endDate)" : " (All Time)";
$logModel->log("EXPORT_WMR", "Exported Waste Materials Report" . $rangeStr);

$filename = "WMR_Appendix65_" . date('Ymd_His') . ".xls";

ob_clean();
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
            <td colspan="6" class="text-right" style="font-style: italic; font-weight: bold;">Appendix 65</td>
        </tr>
        <tr>
            <td colspan="6" class="title">WASTE MATERIALS REPORT</td>
        </tr>
        <tr><td colspan="6" style="height: 15px;">&nbsp;</td></tr>
        
        <tr>
            <td colspan="1" class="label">Entity Name:</td>
            <td colspan="3" class="underline">CITY GOVERNMENT OF BOGO</td>
            <td colspan="1" class="label" style="text-align: right;">Fund Cluster:</td>
            <td colspan="1" class="underline">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="1" class="label">Place of Storage:</td>
            <td colspan="3" class="underline">GSO Warehouse</td>
            <td colspan="1" class="label" style="text-align: right;">Date:</td>
            <td colspan="1" class="underline"><?php echo date('M d, Y'); ?></td>
        </tr>
        
        <tr><td colspan="6" style="height: 10px;">&nbsp;</td></tr>

        <!-- WMR Headers -->
        <tr>
            <th class="header-cell" colspan="4">ITEMS FOR DISPOSAL</th>
            <th class="header-cell" colspan="2">RECORD OF SALES</th>
        </tr>
        <tr>
            <th class="header-cell">Item</th>
            <th class="header-cell">Quantity</th>
            <th class="header-cell">Unit</th>
            <th class="header-cell">Description</th>
            <th class="header-cell">Official Receipt</th>
            <th class="header-cell">Amount</th>
        </tr>

        <?php 
        $count = 0;
        foreach ($items as $item): 
            $count++;
            $qty = (int)$item['quantity'];
            $desc = htmlspecialchars($item['description']);
            if (!empty($item['reason'])) {
                $desc .= " [Reason: " . htmlspecialchars($item['reason']) . "]";
            }
            if (!empty($item['stock_no'])) {
                $desc = "Stock No: " . htmlspecialchars($item['stock_no']) . " - " . $desc;
            }
        ?>
            <tr>
                <td class="data-cell"><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo $qty; ?></td>
                <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($item['unit']); ?></td>
                <td class="data-cell"><?php echo $desc; ?></td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
            </tr>
        <?php endforeach; ?>

        <!-- Fill up -->
        <?php for($i = $count; $i < 10; $i++): ?>
            <tr>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
                <td class="data-cell">&nbsp;</td>
            </tr>
        <?php endfor; ?>
        
        <tr>
            <td class="data-cell" colspan="4" style="text-align: right; font-weight: bold;">TOTAL</td>
            <td class="data-cell">&nbsp;</td>
            <td class="data-cell">&nbsp;</td>
        </tr>

        <tr><td colspan="6" style="height: 20px;">&nbsp;</td></tr>

        <!-- Signatories -->
        <tr>
            <td colspan="3" class="data-cell">
                <div class="label">Certified Correct:</div>
                <br><br>
                <div class="text-center underline" style="font-weight: bold;">&nbsp;</div>
                <div class="text-center" style="font-size: 8pt;">Supply and/or Property Custodian</div>
            </td>
            <td colspan="3" class="data-cell">
                <div class="label">Disposal Approved:</div>
                <br><br>
                <div class="text-center underline" style="font-weight: bold;">&nbsp;</div>
                <div class="text-center" style="font-size: 8pt;">Head of Agency or Authorized Representative</div>
            </td>
        </tr>
        
        <tr>
             <td colspan="6" class="data-cell">
                <div class="label">CERTIFICATE OF INSPECTION</div>
                <p style="font-size: 9pt; margin: 5px 0;">
                    I hereby certify that the property enumerated above was disposed of as follows:
                </p>
                <div style="font-size: 9pt; margin-left: 20px;">
                    Item ____________ Destroyed<br>
                    Item ____________ Sold at Private Sale<br>
                    Item ____________ Sold at Public Auction<br>
                    Item ____________ Transferred without cost to ____________
                </div>
                <br>
                <div style="display: flex; justify-content: space-between;">
                     <div style="width: 45%; display: inline-block; vertical-align: top;">
                        <div class="label">Certified Correct:</div>
                        <br><br>
                        <div class="text-center underline" style="font-weight: bold;">&nbsp;</div>
                        <div class="text-center" style="font-size: 8pt;">Inspection Officer</div>
                     </div>
                     <div style="width: 45%; display: inline-block; vertical-align: top;">
                        <div class="label">Witness to Disposal:</div>
                        <br><br>
                        <div class="text-center underline" style="font-weight: bold;">&nbsp;</div>
                        <div class="text-center" style="font-size: 8pt;">Witness</div>
                     </div>
                </div>
            </td>
        </tr>

    </table>
</body>
</html>
