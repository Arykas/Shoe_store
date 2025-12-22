<?php
require_once 'includes/cart.php';
if (!isLoggedIn()) redirect('login.php');
$userId = $_SESSION['user_id'];
$cartId = getCartId($userId);
$items = getCartItems($cartId);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Создать заказ в БД
    $conn = getDBConnection();
    $total = array_sum(array);}
    
