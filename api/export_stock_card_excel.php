<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_stock_card_excel.php
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
$logModel->log("EXPORT_STOCK_CARD", "Exported Stock Card Excel for " . $supply['item']);

// Set headers for download as Excel file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=Stock_Card_' . str_replace(' ', '_', $supply['item']) . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Start HTML output for Excel (Appendix 58 - Stock Card Format)
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Stock Card</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>
        body { font-family: "Arial", sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 4px; vertical-align: middle; }
        th { text-align: center; font-weight: bold; background-color: #f2f2f2; }
        .header-title { font-weight: bold; text-align: center; border: none; font-size: 12pt; }
        .no-border { border: none; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .appendix-label { text-align: right; font-style: italic; border: none; }
      </style>';
echo '</head>';
echo '<body>';
echo '<table>';

// Appendix Label
echo '<tr><td colspan="7" class="appendix-label">Appendix 58</td></tr>';

// Formal Header
echo '<tr><td colspan="7" class="header-title">STOCK CARD</td></tr>';
echo '<tr><td colspan="7" class="no-border">&nbsp;</td></tr>';

echo '<tr>
        <td colspan="4" class="no-border"><b>Entity Name:</b> CITY GOVERNMENT OF BOGO</td>
        <td colspan="3" class="no-border"><b>Fund Cluster:</b> ____________________</td>
      </tr>';
echo '<tr><td colspan="7" class="no-border">&nbsp;</td></tr>';

echo '<tr>
        <td colspan="4" class="no-border"><b>Item:</b> ' . htmlspecialchars($supply['item']) . '</td>
        <td colspan="3" class="no-border"><b>Stock No.:</b> ' . htmlspecialchars($supply['stock_no']) . '</td>
      </tr>';
echo '<tr>
        <td colspan="4" class="no-border"><b>Description:</b> ' . htmlspecialchars($supply['description']) . '</td>
        <td colspan="3" class="no-border"><b>Unit of Measurement:</b> ' . htmlspecialchars($supply['unit']) . '</td>
      </tr>';
echo '<tr><td colspan="7" class="no-border">&nbsp;</td></tr>';

// Table Columns
echo '<tr>
        <th rowspan="2">Date</th>
        <th rowspan="2">Reference</th>
        <th rowspan="2">Receipt<br>(Quantity)</th>
        <th colspan="2">Issuance</th>
        <th rowspan="2">Balance<br>(Quantity)</th>
        <th rowspan="2">No. of Days to Consume</th>
      </tr>';
echo '<tr>
        <th>Quantity</th>
        <th>Office</th>
      </tr>';

// Beginning Balance if filtered
if ($from || $to) {
    echo '<tr>';
    echo '<td class="text-center">' . ($from ? $from : '') . '</td>';
    echo '<td class="text-center"><i>Beginning Balance</i></td>';
    echo '<td class="text-center"></td>';
    echo '<td class="text-center"></td>';
    echo '<td class="text-center"></td>';
    echo '<td class="text-center">' . $beginningBalance . '</td>';
    echo '<td class="text-center"></td>';
    echo '</tr>';
}

// Transactions
foreach ($transactions as $t) {
    $dateFmt = date('Y-m-d', strtotime($t['date']));
    $received = (int)$t['received'];
    $issued = (int)$t['issued'];
    $displayLocation = $t['department_name'] ?? '-';
    $reference = $t['reference'];

    echo '<tr>';
    echo '<td class="text-center">' . $dateFmt . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($reference) . '</td>';
    echo '<td class="text-center">' . ($received > 0 ? $received : '') . '</td>';
    echo '<td class="text-center">' . ($issued > 0 ? $issued : '') . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($displayLocation) . '</td>';
    echo '<td class="text-center">' . $t['balance'] . '</td>';
    echo '<td class="text-center"></td>';
    echo '</tr>';
}

echo '</table>';
echo '</body>';
echo '</html>';
?>
