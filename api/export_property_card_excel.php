<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_property_card_excel.php
require_once __DIR__ . '/../model/supplyModel.php';

$id = $_GET['id'] ?? null;
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

if (!$id) {
    die("Supply ID is required");
}

$model = new SupplyModel();
$data = $model->getSupplyTransactionHistory($id, $from, $to);

if (!$data) {
    die("Supply not found or error fetching history");
}

$supply = $data['supply'];
$transactions = $data['transactions'];
$beginningBalance = $data['beginning_balance'] ?? 0;

// Log the action
require_once __DIR__ . '/../model/SystemLogModel.php';
$logModel = new SystemLogModel();
$logModel->log("EXPORT_PROPERTY_CARD", "Exported Semi-Expendable Property Card Excel for " . $supply['item']);

// Set headers for download as Excel file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=Semi_Expendable_Property_Card_' . str_replace(' ', '_', $supply['item']) . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Start HTML output for Excel (Appendix 69 - Semi-Expendable Property Card Format)
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Property Card</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>
        body { font-family: "Arial", sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; border: 1px solid black; }
        th, td { border: 1px solid black; padding: 4px; vertical-align: middle; }
        th { text-align: center; font-weight: bold; background-color: #ffffff; font-size: 9pt; }
        .header-title { font-weight: bold; text-align: center; border: none; font-size: 12pt; text-transform: uppercase; }
        .no-border { border: none; }
        .border-bottom { border-bottom: 1px solid black !important; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .annex-label { text-align: right; font-style: italic; border: none; font-size: 9pt; }
        .appendix-label { text-align: center; border: none; margin-top: 10px; font-size: 10pt; }
      </style>';
echo '</head>';
echo '<body>';
echo '<table>';

// Appendix Label
echo '<tr><td colspan="12" class="annex-label">Appendix 69</td></tr>';
echo '<tr><td colspan="12" class="no-border">&nbsp;</td></tr>';

// Official Header
echo '<tr><td colspan="12" class="header-title">SEMI-EXPENDABLE PROPERTY CARD</td></tr>';
echo '<tr><td colspan="12" class="no-border">&nbsp;</td></tr>';

$entityName = !empty($supply['school']) ? htmlspecialchars(strtoupper($supply['school'])) : "DEPED DIVISION OF CITY OF BOGO";

echo '<tr>
        <td colspan="8" class="no-border"><b>Entity Name :</b> ' . $entityName . '</td>
        <td colspan="4" class="no-border"><b>Fund Cluster:</b> ____________________</td>
      </tr>';
echo '<tr><td colspan="12" class="no-border" style="height:5px;"></td></tr>';
echo '<tr>
        <td colspan="8" class="no-border"><b>Semi-expendable Property :</b> ' . htmlspecialchars(strtoupper($supply['item'])) . '</td>
        <td colspan="4" class="no-border"><b>Semi-expendable Property Number:</b> ____________________</td>
      </tr>';
echo '<tr>
        <td colspan="12" class="no-border"><b>Description :</b> ' . htmlspecialchars(strtoupper($supply['description'])) . '</td>
      </tr>';
echo '<tr><td colspan="12" class="no-border" style="height:10px;"></td></tr>';

// Table Columns (12 columns structure)
echo '<tr>
        <th rowspan="2" style="width: 80px;">Date</th>
        <th rowspan="2" style="width: 120px;">Reference</th>
        <th colspan="3">Receipt</th>
        <th rowspan="2" style="width: 60px;">Receipt Qty.</th>
        <th colspan="3">Issue/Transfer/ Disposal</th>
        <th rowspan="2" style="width: 60px;">Balance Qty.</th>
        <th rowspan="2" style="width: 100px;">Amount</th>
        <th rowspan="2" style="width: 150px;">Remarks</th>
      </tr>';
echo '<tr>
        <th style="width: 40px;">Qty.</th>
        <th style="width: 80px;">Unit Cost</th>
        <th style="width: 100px;">Total Cost</th>
        <th style="width: 60px;">Item No.</th>
        <th style="width: 40px;">Qty.</th>
        <th style="width: 120px;">Office/Officer</th>
      </tr>';

// Beginning balance if date filtered
if ($from || $to) {
    echo '<tr>';
    echo '<td class="text-center">' . ($from ? $from : '') . '</td>';
    echo '<td class="text-center"><i>Beginning Balance</i></td>';
    echo '<td class="text-center"></td>';
    echo '<td class="text-center"></td>';
    echo '<td class="text-center"></td>';
    echo '<td class="text-center"></td>';
    echo '<td class="text-center"></td>';
    echo '<td class="text-center"></td>';
    echo '<td class="text-center">' . $beginningBalance . '</td>';
    echo '<td class="text-right">' . number_format($beginningBalance * (float)($supply['unit_cost'] ?? 0), 2) . '</td>';
    echo '<td></td>';
    echo '</tr>';
}

// Transactions
$unitCost = (float)($supply['unit_cost'] ?? 0);

foreach ($transactions as $t) {
    $dateFmt = date('m.d.y', strtotime($t['date']));
    $received = (int)$t['received'];
    $issued = (int)$t['issued'];
    
    $officerName = '';
    if (!empty($t['first_name']) || !empty($t['last_name'])) {
        $officerName = trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
    }
    
    $displayLocation = $officerName ?: ($t['department_name'] ?? '-');
    $reference = $t['reference'];
    
    $totalReceiptCost = $received > 0 ? ($received * $unitCost) : 0;
    $moveAmount = ($received > 0 ? $received : $issued) * $unitCost;

    echo '<tr>';
    echo '<td class="text-center">' . $dateFmt . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($reference) . '</td>';
    
    // Receipt Qty, Unit Cost, Total Cost
    echo '<td class="text-center">' . ($received > 0 ? $received : '') . '</td>';
    echo '<td class="text-right">' . ($received > 0 ? number_format($unitCost, 2) : '') . '</td>';
    echo '<td class="text-right">' . ($received > 0 ? number_format($totalReceiptCost, 2) : '') . '</td>';
    
    // Column 6: Receipt Qty. (usually blank or subtotal)
    echo '<td class="text-center"></td>';
    
    // Issue/Transfer/Disposal: Item No, Qty, Office/Officer
    echo '<td class="text-center"></td>'; // Item No blank
    echo '<td class="text-center">' . ($issued > 0 ? $issued : '') . '</td>';
    echo '<td class="text-center">' . htmlspecialchars(strtoupper($displayLocation)) . '</td>';
    
    // Balance Qty
    echo '<td class="text-center">' . $t['balance'] . '</td>';
    
    // Amount (based on movement)
    echo '<td class="text-right">' . number_format($moveAmount, 2) . '</td>';
    
    // Remarks
    echo '<td>' . htmlspecialchars(strtoupper($t['remarks'] ?? '')) . '</td>';
    echo '</tr>';
}

// Add blank rows to match form look
for ($i = 0; $i < 5; $i++) {
    echo '<tr>' . str_repeat('<td>&nbsp;</td>', 12) . '</tr>';
}

echo '</table>';

// Footer Appendix Label
echo '<div class="appendix-label"><i>Appendix 69</i></div>';

echo '</body>';
echo '</html>';
?>
