<?php
$boundary = "----=_Boundary_" . uniqid();

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=test.xls');

echo "MIME-Version: 1.0\n";
echo "X-Document-Type: Workbook\n";
echo "Content-Type: multipart/related; boundary=\"$boundary\"\n\n";

// Workbook HTML
echo "--$boundary\n";
echo "Content-Location: file:///C:/workbook.htm\n";
echo "Content-Transfer-Encoding: 8bit\n";
echo "Content-Type: text/html; charset=\"utf-8\"\n\n";

echo <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Sheet 1</x:Name>
    <x:WorksheetSource HRef="Sheet1.htm"/>
   </x:ExcelWorksheet>
   <x:ExcelWorksheet>
    <x:Name>Sheet 2</x:Name>
    <x:WorksheetSource HRef="Sheet2.htm"/>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
</head>
</html>
HTML;
echo "\n\n";

// Sheet 1 HTML
echo "--$boundary\n";
echo "Content-Location: file:///C:/Sheet1.htm\n";
echo "Content-Transfer-Encoding: 8bit\n";
echo "Content-Type: text/html; charset=\"utf-8\"\n\n";
echo "<html><body><table><tr><td>Page 1</td></tr></table></body></html>\n\n";

// Sheet 2 HTML
echo "--$boundary\n";
echo "Content-Location: file:///C:/Sheet2.htm\n";
echo "Content-Transfer-Encoding: 8bit\n";
echo "Content-Type: text/html; charset=\"utf-8\"\n\n";
echo "<html><body><table><tr><td>Page 2</td></tr></table></body></html>\n\n";

echo "--$boundary--\n";
?>
