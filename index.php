<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: admin/dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>