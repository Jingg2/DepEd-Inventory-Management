<?php
// Entry point for Supply Management
ob_start(); // Prevent stray output from breaking JSON responses
require_once __DIR__ . '/controller/supplyController.php';
$controller = new SupplyController();
$controller->handleRequest();
