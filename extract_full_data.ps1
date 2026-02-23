$excelPath = "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\files\2026 MONTHLY INVENTORIES  & SEMI-EXP. PROPERTIES.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open($excelPath)

$sheets = @("JAN 2026 procurement (2)", "JAN 2026 procurement")
$results = @()

foreach ($sheetName in $sheets) {
    $sheet = $workbook.Sheets.Item($sheetName)
    $currentCategory = ""
    
    # We start from row 10 which is where "OFFICE SUPPLIES" header was seen
    for ($row = 10; $row -le 500; $row++) {
        $col1 = $sheet.Cells.Item($row, 1).Text
        $col2 = $sheet.Cells.Item($row, 2).Text
        $col3 = $sheet.Cells.Item($row, 3).Text
        $col4 = $sheet.Cells.Item($row, 4).Text
        $col5 = $sheet.Cells.Item($row, 5).Text # Previous
        $col6 = $sheet.Cells.Item($row, 6).Text # Acquisition (Quantity)
        $col9 = $sheet.Cells.Item($row, 9).Text 
        $col10 = $sheet.Cells.Item($row, 10).Text # Unit Cost or something else? Let's check

        if ($col2 -ne "" -and $col3 -eq "" -and $col4 -eq "") {
            $currentCategory = $col2
            continue
        }
        
        if ($col4 -ne "" -and $col4 -ne "ITEM/ DESCRIPTION") {
            # Check column 6 for acquisition quantity
            $qty = $col6
            $unitCost = $sheet.Cells.Item($row, 9).Text # Looking at previous output, let's re-verify column for unit cost
            $totalCost = $sheet.Cells.Item($row, 10).Text
            
            # If Qty is empty or 0, we might still want it or skip it?
            # The user said "add new data", which usually means the "Acquisition" column.
            
            $item = [PSCustomObject]@{
                Sheet = $sheetName
                Category = $currentCategory
                StockNo = $col2
                Unit = $col3
                ItemDescription = $col4
                PrevBalance = $col5
                Acquisition = $col6
                UnitCost = $col9 # Based on typical layout where Unit Cost is before Total Cost
                TotalCost = $col10
            }
            $results += $item
        }
        
        # Stop if we see total or empty for a long time
        if ($col2 -eq "" -and $col4 -eq "" -and $row -gt 20) {
            $emptyCount++
            if ($emptyCount -gt 10) { break }
        } else {
            $emptyCount = 0
        }
    }
}

$results | ConvertTo-Json | Out-File -FilePath "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\excel_data.json" -Encoding utf8

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
