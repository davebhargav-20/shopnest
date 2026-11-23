<?php
require __DIR__ . '/../includes/init.php';
$cpId = require_customer();
$cpTable = 'tblcustomer';
$panelRole = 'customer';
include __DIR__ . '/../includes/change-password-form.php';
