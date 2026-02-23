$excelPath = "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\files\2026 MONTHLY INVENTORIES  & SEMI-EXP. PROPERTIES.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open($excelPath)

$sheet = $workbook.Sheets.Item("JAN 2026 procurement (2)")

echo "--- Row 8 (Headers) ---"
$line = ""
for ($col = 1; $col -le 20; $col++) {
    $val = $sheet.Cells.Item(8, $col).Text
    $line += "[$col]: $val | "
}
echo $line

echo "`n--- Row 10 (First category?) ---"
$line = ""
for ($col = 1; $col -le 20; $col++) {
    $val = $sheet.Cells.Item(10, $col).Text
    $line += "[$col]: $val | "
}
echo $line

echo "`n--- Row 11 (First item?) ---"
$line = ""
for ($col = 1; $col -le 20; $col++) {
    $val = $sheet.Cells.Item(11, $col).Text
    $line += "[$col]: $val | "
}
echo $line

echo "`n--- Row 15 (An item with acquisition?) ---"
# Searching for an item with col 6 not empty
for ($r = 11; $r -le 100; $r++) {
    if ($sheet.Cells.Item($r, 6).Text -ne "") {
        echo "Found at row $r"
        $line = ""
        for ($col = 1; $col -le 20; $col++) {
            $val = $sheet.Cells.Item($r, $col).Text
            $line += "[$col]: $val | "
        }
        echo $line
        #break
    }
}

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
