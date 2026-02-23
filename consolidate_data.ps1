$excelPath = "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\files\2026 MONTHLY INVENTORIES  & SEMI-EXP. PROPERTIES.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open($excelPath)

function Get-SheetData($sheetName, $balanceCol, $unitCostCol) {
    $sheet = $workbook.Sheets.Item($sheetName)
    $currentCategory = ""
    $items = @()
    
    for ($row = 10; $row -le 1000; $row++) {
        $stockNo = $sheet.Cells.Item($row, 2).Text.Trim()
        $itemText = $sheet.Cells.Item($row, 4).Text.Trim()
        $unit = $sheet.Cells.Item($row, 3).Text.Trim()
        $balance = $sheet.Cells.Item($row, $balanceCol).Text.Trim()
        $unitCost = $sheet.Cells.Item($row, $unitCostCol).Text.Trim()
        $acquisition = $sheet.Cells.Item($row, 6).Text.Trim()

        # Category detection
        if ($stockNo -eq "" -and $itemText -eq "" -and $sheet.Cells.Item($row, 2).Font.Bold) {
             # Sometimes categories are just bold text in col 2 or col 4? 
             # Let's check based on previous output: OFFICE SUPPLIES was in col 2.
        }
        
        # If it's a category header (e.g. Row 10 in previous output)
        if ($itemText -eq "" -and $stockNo -ne "" -and $unit -eq "" -and $balance -eq "") {
            $currentCategory = $stockNo
            continue
        }

        if ($itemText -ne "" -and $itemText -ne "ITEM/ DESCRIPTION" -and $itemText -notlike "*TOTAL*") {
            $items += [PSCustomObject]@{
                Category = $currentCategory
                StockNo = $stockNo
                Unit = $unit
                ItemDescription = $itemText
                Quantity = $balance
                Acquisition = $acquisition
                UnitCost = $unitCost
                Sheet = $sheetName
            }
        }
        
        # Stop condition
        if ($row -gt 50 -and $stockNo -eq "" -and $itemText -eq "" -and $unit -eq "") {
            $emptyCount++
            if ($emptyCount -gt 20) { break }
        } else {
            $emptyCount = 0
        }
    }
    return $items
}

$allData = @()
$allData += Get-SheetData "JAN 2026 procurement (2)" 17 20
$allData += Get-SheetData "JAN 2026 procurement" 17 18

$allData | ConvertTo-Json | Out-File -FilePath "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\final_excel_data.json" -Encoding utf8

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
