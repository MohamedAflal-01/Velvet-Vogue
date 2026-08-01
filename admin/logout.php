<?php
require_once '../functions/helpers.php';

// Unset all admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);

set_flash_message('success', 'You have been logged out.');
redirect('login.php');
?>
