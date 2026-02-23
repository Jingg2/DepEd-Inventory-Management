$excelPath = "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\files\2026 MONTHLY INVENTORIES  & SEMI-EXP. PROPERTIES.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open($excelPath)

$sheets = @("JAN 2026 procurement (2)", "JAN 2026 procurement")

foreach ($sheetName in $sheets) {
    echo "--- Sheet: $sheetName ---"
    $sheet = $workbook.Sheets.Item($sheetName)
    for ($row = 1; $row -le 10; $row++) {
        $line = ""
        for ($col = 1; $col -le 15; $col++) {
            $val = $sheet.Cells.Item($row, $col).Text
            $line += "$val | "
        }
        echo $line
    }
}

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
