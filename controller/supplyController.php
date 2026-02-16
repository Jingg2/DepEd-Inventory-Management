<?php
// filepath: c:\OJT DEVELOPMENT\Inventory_System\controller\supplyController.php
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../model/deliveryModel.php';

class SupplyController {
    private $model;

    public function __construct() {
        $this->model = new SupplyModel();
    }

    public function handleRequest() {
        $supplies = $this->model->getAllSupplies(); // Fetch data for view
        $fetchedCategories = $this->model->getAllCategories(); // Fetch unique categories directly from DB
        $message = '';
        
        // Calculate statistics for view
        $totalInventoryValue = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $alertItems = [];
        foreach ($supplies as $supply) {
            $qty = isset($supply['quantity']) ? (int)$supply['quantity'] : 0;
            $unitCost = isset($supply['unit_cost']) ? (float)$supply['unit_cost'] : 0;
            
            // Calculate total inventory value (Accuracy fix: unit_cost * quantity)
            $totalInventoryValue += ($unitCost * $qty);
            
            // Get item specific thresholds with fallbacks
            $lowThreshold = isset($supply['low_stock_threshold']) ? (int)$supply['low_stock_threshold'] : 10;
            $criticalThreshold = isset($supply['critical_stock_threshold']) ? (int)$supply['critical_stock_threshold'] : 5;
            
            // Count out of stock
            if ($qty <= 0) {
                $outOfStockCount++;
                $supply['alert_type'] = 'Out of Stock';
                $alertItems[] = $supply;
            }
            // Count low stock items (Custom threshold check)
            elseif ($qty <= $lowThreshold) {
                $lowStockCount++;
                $supply['alert_type'] = ($qty <= $criticalThreshold) ? 'Critical' : 'Low Stock';
                $alertItems[] = $supply;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Debug: Log all received data
            error_log("POST data received: " . print_r($_POST, true));
            error_log("FILES data received: " . print_r($_FILES, true));
            
            // Handle Multi-item Delivery
            if (isset($_POST['action']) && $_POST['action'] === 'save_delivery') {
                $deliveryModel = new DeliveryModel();
                $items = json_decode($_POST['items'], true);
                
                $result = $deliveryModel->saveDelivery($_POST, $items, $_SESSION['admin_id'] ?? 1);
                
                if (ob_get_level()) ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($result);
                exit();
            }

            // Check if this is an update operation
            $isUpdate = isset($_POST['supply_id']) && !empty($_POST['supply_id']);
            
            // Check if this is a delete operation
            if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                $force = isset($_POST['force']) && $_POST['force'] === '1';
                $deleteResult = $this->model->deleteSupply($id, $force);
                
                $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
                
                if ($deleteResult) {
                    // Database Logging
                    require_once __DIR__ . '/../model/SystemLogModel.php';
                    $logModel = new SystemLogModel();
                    $logModel->log("DELETE_SUPPLY", "Deleted supply item ID: $id");

                    if ($isAjax) {
                        if (ob_get_level()) ob_end_clean();
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => true, 'message' => 'Supply deleted successfully.']);
                        exit();
                    } else {
                        // Redirect
                        header("Location: inventory?deleted=1");
                        exit();
                    }
                } else {
                     if ($isAjax) {
                        if (ob_get_level()) ob_end_clean();
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Error deleting supply: ' . $this->model->lastError]);
                        exit();
                     } else {
                         $message = 'Error deleting supply.';
                     }
                }
                
                // If not ajax and failed, continue to load view
                // Include view and pass data
                $supplies = $this->model->getAllSupplies(); // Refresh list
                include __DIR__ . '/../view/supply.php';
                exit();
            }

            // Check if this is an export operation
            if (isset($_POST['action']) && $_POST['action'] === 'export') {
                $supplies = $this->model->getAllSupplies();
                
                // Clear output buffer
                if (ob_get_level()) ob_end_clean();
                
                // Set headers for download as Excel file
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename=monthly_inventory_' . date('Y-m-d') . '.xls');
                header('Pragma: no-cache');
                header('Expires: 0');
                
                // Organize data by category
                $groupedSupplies = [];
                foreach ($supplies as $supply) {
                    $category = $supply['category'] ?? 'Uncategorized';
                    $groupedSupplies[$category][] = $supply;
                }
                
                // Start HTML output for Excel
                echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
                echo '<head>';
                echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Inventory</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
                echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>
                        body { font-family: "Arial", sans-serif; font-size: 10pt; }
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid black; padding: 4px; vertical-align: middle; height: 25px; }
                        th { text-align: center; font-weight: bold; background-color: #ffffff; }
                        .header-title { font-weight: bold; text-align: center; border: none; font-size: 11pt; }
                        .no-border { border: none; }
                        .category-office { background-color: #FFFF00; font-weight: bold; text-align: left; }
                        .category-other { background-color: #F4B084; font-weight: bold; text-align: left; }
                        .footer-cell { border: none; text-align: left; padding-top: 20px; vertical-align: bottom; }
                        .text-right { text-align: right; }
                        .text-center { text-align: center; }
                      </style>';
                echo '</head>';
                echo '<body>';
                echo '<table>';
                
                // Header
                echo '<tr><td colspan="9" class="header-title">REPUBLIC OF THE PHILIPPINES</td></tr>';
                echo '<tr><td colspan="9" class="header-title">DEPARTMENT OF EDUCATION</td></tr>';
                echo '<tr><td colspan="9" class="header-title">REGION VII, CENTRAL VISAYAS</td></tr>';
                echo '<tr><td colspan="9" class="header-title">Buac, Cayang, Bogo City, Cebu</td></tr>';
                echo '<tr><td colspan="9" class="no-border">&nbsp;</td></tr>';
                echo '<tr><td colspan="9" class="header-title">STOCK BALANCE AS OF ' . strtoupper(date('F d, Y')) . '</td></tr>';
                echo '<tr><td colspan="9" class="no-border">&nbsp;</td></tr>';
                
                // Table Columns
                echo '<tr>
                        <th colspan="3">STOCK NO.</th>
                        <th style="width: 50px;">UNIT</th>
                        <th style="width: 200px;">ITEM</th>
                        <th style="width: 250px;">DESCRIPTION</th>
                        <th style="width: 100px;">BAL. AS OF<br>' . date('m/d/Y') . '</th>
                        <th style="width: 100px;">UNIT COST</th>
                        <th style="width: 120px;">TOTAL COST</th>
                      </tr>';
                
                $grandTotal = 0;
                
                // Loop through categories
                foreach ($groupedSupplies as $category => $items) {
                    $catLower = strtolower(trim($category));
                    $isOffice = strpos($catLower, 'office') !== false;
                    $catClass = $isOffice ? 'category-office' : 'category-other';
                    
                    // Category Header Row
                    echo '<tr>';
                    echo '<td colspan="9" class="' . $catClass . '">' . strtoupper($category) . '</td>';
                    echo '</tr>';
                    
                    foreach ($items as $supply) {
                        $qty = isset($supply['quantity']) ? (float)$supply['quantity'] : 0;
                        
                        // Skip out-of-stock items
                        if ($qty <= 0) {
                            continue;
                        }
                        
                        $unitCost = isset($supply['unit_cost']) ? (float)$supply['unit_cost'] : 0;
                        $totalCost = $qty * $unitCost;
                        $grandTotal += $totalCost;
                        
                        $fullStock = $supply['stock_no'] ?? '';
                        
                        // Split stock number for 3 columns
                        $stockPart1 = '';
                        $stockPart2 = '';
                        $stockPart3 = $fullStock;
                        
                        if (strlen($fullStock) > 7 && strpos($fullStock, '-') !== false) {
                             $stockPart1 = substr($fullStock, 0, 7);
                             $stockPart2 = substr($fullStock, 7);
                        }

                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($stockPart1) . '</td>';
                        echo '<td>' . htmlspecialchars($stockPart2) . '</td>';
                        echo '<td>' . htmlspecialchars($stockPart3) . '</td>';
                        echo '<td>' . htmlspecialchars($supply['unit'] ?? '') . '</td>';
                        echo '<td style="text-align: left;">' . htmlspecialchars($supply['item'] ?? '') . '</td>';
                        echo '<td style="text-align: left;">' . htmlspecialchars($supply['description'] ?? '') . '</td>';
                        echo '<td>' . number_format($qty, 2) . '</td>';
                        echo '<td class="text-right">' . number_format($unitCost, 2) . '</td>';
                        echo '<td class="text-right">' . number_format($totalCost, 2) . '</td>';
                        echo '</tr>';
                    }
                }
                
                // Total Row
                echo '<tr>';
                echo '<td colspan="8" style="font-weight: bold; text-align: left;">TOTAL</td>';
                echo '<td class="text-right" style="font-weight: bold;">' . number_format($grandTotal, 2) . '</td>';
                echo '</tr>';
                
                echo '<tr><td colspan="9" class="no-border" style="height: 40px;">&nbsp;</td></tr>';
                
                // Footer / Signatories
                echo '<tr>';
                echo '<td colspan="3" class="footer-cell">Prepared by:</td>';
                echo '<td colspan="3" class="footer-cell">Noted by:</td>';
                echo '<td colspan="3" class="footer-cell">Approved by:</td>';
                echo '</tr>';
                
                echo '<tr>';
                echo '<td colspan="3" class="footer-cell"><br><b><u>JESELLE A. DAMAYO</u></b><br>ADA VI</td>';
                echo '<td colspan="3" class="footer-cell"><br><b><u>INGRID B. CLEMENTE</u></b><br>ADOF IV</td>';
                echo '<td colspan="3" class="footer-cell"><br><b><u>LEAH P. NOVERAS, Ed.D, CESO VI</u></b><br>Schools Division Superintendent</td>';
                echo '</tr>';
                
                echo '</table>';
                echo '</body></html>';
                exit();
            }
            
            // Validate required fields first
            $errors = [];
            if (!isset($_POST['item']) || empty($_POST['item']) || trim($_POST['item']) === '') {
                $errors[] = 'Item Name is required';
            }
            if (!isset($_POST['category']) || empty($_POST['category']) || trim($_POST['category']) === '') {
                $errors[] = 'Category is required';
            }
            if (!isset($_POST['unit']) || empty($_POST['unit']) || trim($_POST['unit']) === '') {
                $errors[] = 'Unit is required (Please select a unit from the dropdown)';
            }
            if (!isset($_POST['quantity']) || $_POST['quantity'] === '' || $_POST['quantity'] === null) {
                $errors[] = 'Quantity is required';
            }
            
            if (!empty($errors)) {
                $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
                if ($isAjax) {
                    if (ob_get_level()) ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
                    exit();
                } else {
                    $message = implode('. ', $errors);
                }
            } else {
                // Handle custom category
                $category = trim($_POST['category']);
                if ($category === 'Other') {
                    if (isset($_POST['custom_category']) && !empty(trim($_POST['custom_category']))) {
                        $category = trim($_POST['custom_category']);
                    } else {
                        // This should be caught by client-side validation, but fallback here
                        $category = 'Unspecified';
                    }
                }
                
                $data = [
                    'stock_no' => !empty($_POST['stock_no']) && trim($_POST['stock_no']) !== '' ? trim($_POST['stock_no']) : null,
                    'item' => trim($_POST['item']),
                    'category' => $category,
                    'unit' => trim($_POST['unit']),
                    'description' => !empty($_POST['description']) ? trim($_POST['description']) : '',
                    'quantity' => isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0,
                    'add_stock' => isset($_POST['add_stock']) ? (int)$_POST['add_stock'] : 0,
                    'admin_id' => $_SESSION['admin_id'] ?? null,
                    'bal_as_of_date' => !empty($_POST['bal_as_of_date']) ? $_POST['bal_as_of_date'] : date('Y-m-d'),
                    'unit_cost' => isset($_POST['unit_cost']) && $_POST['unit_cost'] !== '' ? (float)$_POST['unit_cost'] : 0.00,
                    'total_cost' => isset($_POST['unit_cost']) && isset($_POST['quantity']) ? (float)$_POST['unit_cost'] * (int)$_POST['quantity'] : 0.00,
                    'status' => !empty($_POST['status']) ? $_POST['status'] : 'Available',
                    'property_classification' => !empty($_POST['property_classification']) ? $_POST['property_classification'] : null,
                    'image' => isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['image'] : null
                ];
                
                // Debug: Log prepared data
                error_log("Prepared data for model: " . print_r($data, true));
                
                $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
                
                if ($isUpdate) {
                    // UPDATE operation
                    $supplyId = (int)$_POST['supply_id'];
                    $updateResult = $this->model->updateSupply($supplyId, $data);
                    
                    error_log("Update result: " . ($updateResult ? 'SUCCESS' : 'FAILED'));
                    
                    if ($updateResult) {
                        // Database Logging
                        require_once __DIR__ . '/../model/SystemLogModel.php';
                        $logModel = new SystemLogModel();
                        $logModel->log("UPDATE_SUPPLY", "Updated supply item: " . $data['item'] . " (ID: $supplyId)");

                        if ($isAjax) {
                            // Return JSON for AJAX
                            // Fetch the updated supply
                            $allSupplies = $this->model->getAllSupplies();
                            $updatedSupply = null;
                            foreach ($allSupplies as $supply) {
                                if (isset($supply['supply_id']) && $supply['supply_id'] == $supplyId) {
                                    $updatedSupply = $supply;
                                    break;
                                }
                            }
                            
                            if ($updatedSupply) {
                                unset($updatedSupply['image']);
                                $json = json_encode(['success' => true, 'supply' => $updatedSupply, 'action' => 'update']);
                                if ($json === false) {
                                    $json = json_encode(['success' => false, 'message' => 'Could not encode response: ' . json_last_error_msg()]);
                                }
                                if (ob_get_level()) ob_end_clean();
                                header('Content-Type: application/json; charset=utf-8');
                                echo $json;
                            } else {
                                if (ob_get_level()) ob_end_clean();
                                header('Content-Type: application/json; charset=utf-8');
                                echo json_encode(['success' => false, 'message' => 'Supply updated but could not retrieve details.']);
                            }
                            exit();
                        } else {
                            $message = 'Supply updated successfully!';
                            header("Location: inventory?success=1");
                            exit();
                        }
                    } else {
                        if ($isAjax) {
                            if (ob_get_level()) ob_end_clean();
                            header('Content-Type: application/json; charset=utf-8');
                            $errorMessage = !empty($this->model->lastError) ? $this->model->lastError : 'Error updating supply.';
                            echo json_encode(['success' => false, 'message' => $errorMessage]);
                            exit();
                        } else {
                            $message = !empty($this->model->lastError) ? $this->model->lastError : 'Error updating supply.';
                        }
                    }
                } else {
                    // INSERT operation (existing code)
                    $insertResult = $this->model->insertSupply($data);
                
                error_log("Insert result: " . ($insertResult ? 'SUCCESS' : 'FAILED'));
                
                if ($insertResult) {
                    // Database Logging
                    require_once __DIR__ . '/../model/SystemLogModel.php';
                    $logModel = new SystemLogModel();
                    $logModel->log("CREATE_SUPPLY", "Added new supply item: " . $data['item']);

                    if ($isAjax) {
                        // Return JSON for AJAX
                        $newSupply = $this->model->getLastInsertedSupply();
                        if ($newSupply) {
                            // Remove raw binary image before JSON encode (causes "Unexpected end of JSON input")
                            unset($newSupply['image']);
                            $json = json_encode(['success' => true, 'supply' => $newSupply]);
                            if ($json === false) {
                                $json = json_encode(['success' => false, 'message' => 'Could not encode response: ' . json_last_error_msg()]);
                            }
                            if (ob_get_level()) ob_end_clean();
                            header('Content-Type: application/json; charset=utf-8');
                            echo $json;
                        } else {
                            if (ob_get_level()) ob_end_clean();
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['success' => false, 'message' => 'Supply added but could not retrieve details.']);
                        }
                        exit();
                    } else {
                        $message = 'Supply added successfully!';
                        header("Location: inventory?success=1"); // Redirect to avoid resubmission
                        exit();
                    }
                } else {
                    if ($isAjax) {
                        if (ob_get_level()) ob_end_clean();
                        header('Content-Type: application/json; charset=utf-8');
                        // Get last error from model if available
                        $errorMessage = 'Error adding supply.';
                        if (!empty($this->model->lastError)) {
                            // Clean up error message for user display
                            $dbError = $this->model->lastError;
                            // Check for common database errors
                            if (strpos($dbError, 'Duplicate entry') !== false) {
                                $errorMessage = 'This supply item already exists (duplicate stock number or item).';
                            } elseif (strpos($dbError, 'foreign key constraint') !== false) {
                                $errorMessage = 'Database constraint error. Please check that all required relationships exist.';
                            } elseif (strpos($dbError, 'Column') !== false && strpos($dbError, 'cannot be null') !== false) {
                                $errorMessage = 'Missing required field. Please fill in all required fields.';
                            } else {
                                $errorMessage = 'Database error: ' . htmlspecialchars(substr($dbError, 0, 100));
                            }
                        }
                        echo json_encode(['success' => false, 'message' => $errorMessage]);
                        exit();
                    } else {
                        $message = !empty($this->lastError) ? $this->model->lastError : 'Error adding supply.';
                    }
                }
                }
            }
        }

        // Include view and pass data
        include __DIR__ . '/../view/supply.php';
    }
}