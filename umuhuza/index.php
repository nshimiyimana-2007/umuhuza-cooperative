<?php
// index.php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: login.php');
}
exit();
?>
