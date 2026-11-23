<?php
require __DIR__ . '/../includes/init.php';
require_admin();
$reportSeller = isset($_GET['seller']) && (int)$_GET['seller'] > 0 ? (int)$_GET['seller'] : null;
$panelRole = 'admin';
include __DIR__ . '/../includes/sales-report.php';
