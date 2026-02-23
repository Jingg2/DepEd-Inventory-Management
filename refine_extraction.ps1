$excelPath = "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\files\2026 MONTHLY INVENTORIES  & SEMI-EXP. PROPERTIES.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open($excelPath)

function Get-SheetData($sheetName, $balanceCol, $unitCostCol) {
    $sheet = $workbook.Sheets.Item($sheetName)
    $currentCategory = ""
    $currentRecording = ""
    $items = @()
    
    for ($row = 11; $row -le 1000; $row++) {
        $recording = $sheet.Cells.Item($row, 1).Text.Trim()
        $stockNo = $sheet.Cells.Item($row, 2).Text.Trim()
        $itemText = $sheet.Cells.Item($row, 4).Text.Trim()
        $unit = $sheet.Cells.Item($row, 3).Text.Trim()
        $balance = $sheet.Cells.Item($row, $balanceCol).Text.Trim()
        $unitCost = $sheet.Cells.Item($row, $unitCostCol).Text.Trim()

        if ($recording -ne "" -and $recording -ne "RECORDING") {
            $currentRecording = $recording
        }
        
        # Category detection (bold text in col 2 or col 4, no units)
        if ($itemText -eq "" -and $stockNo -ne "" -and $unit -eq "" -and $balance -eq "") {
            $currentCategory = $stockNo
            continue
        }

        if ($itemText -ne "" -and $itemText -ne "ITEM/ DESCRIPTION" -and $itemText -notlike "*TOTAL*") {
            $items += [PSCustomObject]@{
                Recording = $currentRecording
                Category = $currentCategory
                StockNo = $stockNo
                Unit = $unit
                ItemDescription = $itemText
                Quantity = $balance
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

# Merge duplicates
$mergedData = @{}
foreach ($item in $allData) {
    # Unique key: item name + unit cost (since stock numbers might be slightly different or missing)
    # However, stock_no is usually reliable. Let's use name + cost as secondary.
    # Actually, the user says "remove redundant supply".
    $key = "$($item.ItemDescription)_$($item.UnitCost)"
    
    if ($mergedData.ContainsKey($key)) {
        # Keep the one with the higher quantity or just the first one?
        # Usually, if it's the same item in two sheets, we sum the quantities?
        # Or maybe it's just a duplicate.
        $existing = $mergedData[$key]
        $existingQty = [float]($existing.Quantity -replace ',', '')
        $newQty = [float]($item.Quantity -replace ',', '')
        # If it's the SAME sheet, it might be a split entry. If different sheets, it might be redundancy.
        # Let's just sum it to be safe, or just pick one if the quantity is the same.
        if ($existingQty -eq $newQty) {
            # Likely redundant, skip
        } else {
            # Sum them
            $existing.Quantity = ($existingQty + $newQty).ToString()
        }
    } else {
        $mergedData[$key] = $item
    }
}

$mergedData.Values | ConvertTo-Json | Out-File -FilePath "c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\refined_excel_data.json" -Encoding utf8

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
