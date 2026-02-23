$excelPath = "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\files\2026 MONTHLY INVENTORIES  & SEMI-EXP. PROPERTIES.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open($excelPath)

$sheets = @("JAN 2026 procurement (2)", "JAN 2026 procurement")

foreach ($sheetName in $sheets) {
    echo "--- Checking markers for $sheetName ---"
    $sheet = $workbook.Sheets.Item($sheetName)
    $foundMarkers = @{}
    for ($row = 10; $row -le 500; $row++) {
        $marker = $sheet.Cells.Item($row, 1).Text.Trim()
        if ($marker -ne "" -and $marker -ne "RECORDING") {
            if (-not $foundMarkers.ContainsKey($marker)) {
                $foundMarkers[$marker] = 1
                echo "Found marker: $marker at row $row"
            }
        }
    }
}

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
