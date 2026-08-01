<?php
require_once '../functions/helpers.php';

// Unset all customer session variables
unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);

set_flash_message('success', 'You have been logged out.');
redirect('login.php');
?>
