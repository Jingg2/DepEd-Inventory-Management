$excelPath = "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\files\2026 MONTHLY INVENTORIES  & SEMI-EXP. PROPERTIES.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open($excelPath)

$sheet = $workbook.Sheets.Item("JAN 2026 procurement")

echo "--- Sheet: JAN 2026 procurement ---"
echo "--- Row 11 ---"
$line = ""
for ($col = 1; $col -le 25; $col++) {
    $val = $sheet.Cells.Item(11, $col).Text
    $line += "[$col]: $val | "
}
echo $line

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
