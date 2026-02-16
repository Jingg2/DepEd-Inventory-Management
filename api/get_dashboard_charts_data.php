<?php
// api/get_dashboard_charts_data.php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../model/supplyModel.php';
    require_once __DIR__ . '/../model/requisitionModel.php';
    
    $supplyModel = new SupplyModel();
    $reqModel = new RequisitionModel();



    // 3. Fetch Data
    $allSupplies = $supplyModel->getAllSupplies();


    // CRITICAL: Strip binary image data immediately to prevent memory issues and JSON errors
    foreach ($allSupplies as &$item) {
        if (isset($item['image'])) unset($item['image']);
        // We generally don't need image_base64 for charts either, strip it to save bandwidth
        if (isset($item['image_base64'])) unset($item['image_base64']);
    }
    unset($item);

    $topIssued = $supplyModel->getTopIssuedSupplies(999); // Get all items

    
    $employeeStats = $reqModel->getRequisitionsPerEmployee(999); // Get all employees


    // 4. Process Data for Charts

    // A. Stock Levels Analysis - Show ALL items
    $sortedByStock = $allSupplies;
    
    // Sort by urgency first (normal items first, urgent items last), then by quantity (highest first)
    usort($sortedByStock, function($a, $b) {
        $qtyA = (int)$a['quantity'];
        $qtyB = (int)$b['quantity'];
        $lowA = (int)($a['low_stock_threshold'] ?? 10);
        $lowB = (int)($b['low_stock_threshold'] ?? 10);
        $critA = (int)($a['critical_stock_threshold'] ?? 5);
        $critB = (int)($b['critical_stock_threshold'] ?? 5);
        
        // Calculate urgency level
        $urgencyA = 0; // Normal
        if ($qtyA <= 0) $urgencyA = 3; // Out of stock (most urgent)
        elseif ($qtyA <= $critA) $urgencyA = 2; // Critical
        elseif ($qtyA <= $lowA) $urgencyA = 1; // Caution
        
        $urgencyB = 0; // Normal
        if ($qtyB <= 0) $urgencyB = 3;
        elseif ($qtyB <= $critB) $urgencyB = 2;
        elseif ($qtyB <= $lowB) $urgencyB = 1;
        
        // Sort by urgency first (normal items first, urgent last)
        if ($urgencyA != $urgencyB) return $urgencyA - $urgencyB;
        // Then by quantity (highest first within same urgency)
        return $qtyB - $qtyA;
    });
    
    $stockLevels = [];
    foreach ($sortedByStock as $item) {
        
        $qty = (int)$item['quantity'];
        $low = (int)($item['low_stock_threshold'] ?? 10);
        $crit = (int)($item['critical_stock_threshold'] ?? 5);
        
        $urgency = 'Normal';
        if ($qty <= 0) $urgency = 'Out of Stock';
        elseif ($qty <= $crit) $urgency = 'Critical';
        elseif ($qty <= $low) $urgency = 'Caution';
        
        $stockLevels[] = [
            'name' => $item['item'],
            'qty' => $qty,
            'urgency' => $urgency
        ];
    }

    // B. Category Distribution & Inventory Value
    $categoryDistribution = [];
    $inventoryValue = [];
    
    foreach ($allSupplies as $item) {
        $cat = $item['category'];
        if (empty($cat)) $cat = 'Uncategorized';
        
        if (!isset($categoryDistribution[$cat])) {
            $categoryDistribution[$cat] = 0;
            $inventoryValue[$cat] = 0;
        }
        
        $categoryDistribution[$cat]++;
        $inventoryValue[$cat] += (float)($item['total_cost'] ?? 0);
    }

    // C. Low Stock Urgency
    $lowStockUrgency = ['out' => 0, 'critical' => 0, 'caution' => 0];
    foreach ($allSupplies as $item) {
        $qty = (int)$item['quantity'];
        $low = (int)($item['low_stock_threshold'] ?? 10);
        $crit = (int)($item['critical_stock_threshold'] ?? 5);
        
        if ($qty <= 0) {
            $lowStockUrgency['out']++;
        } elseif ($qty <= $crit) {
            $lowStockUrgency['critical']++;
        } elseif ($qty <= $low) {
            $lowStockUrgency['caution']++;
        }
    }


    // 5. Response
    echo json_encode([
        'success' => true,
        'data' => [
            'stockLevels' => $stockLevels,
            'categoryDistribution' => $categoryDistribution,
            'inventoryValue' => $inventoryValue,
            'employeeRequisitions' => $employeeStats,
            'turnover' => $topIssued,
            'lowStockUrgency' => $lowStockUrgency
        ]
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
