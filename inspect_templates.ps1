
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
try {
    Write-Host "--- Appendix 58 ---"
    $wb58 = $excel.Workbooks.Open('c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\files\Appendix 58 - SC.xls')
    $ws58 = $wb58.Sheets.Item(1)
    Write-Host ("Cell A1: " + $ws58.Cells.Item(1,1).Text)
    Write-Host ("Cell G1: " + $ws58.Cells.Item(1,7).Text)
    $wb58.Close($false)

    Write-Host "`n--- Appendix 69 ---"
    $wb69 = $excel.Workbooks.Open('c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\files\Appendix 69 - PC.xls')
    $ws69 = $wb69.Sheets.Item(1)
    
    # Check headers in a wider range
    Write-Host "`nHeaders for Appendix 69 (Row 10-25):"
    for ($r = 10; $r -le 25; $r++) {
        $line = "Row $r `: "
        for ($c = 1; $c -le 12; $c++) {
            $val = $ws69.Cells.Item($r,$c).Text
            if ($val) {
                $line += "[$val] "
            }
        }
        if ($line -ne "Row $r `: ") {
            Write-Host $line
        }
    }
    $wb69.Close($false)
} catch {
    Write-Host "Error: $($_.Exception.Message)"
} finally {
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
}
