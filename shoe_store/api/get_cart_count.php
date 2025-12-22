<?php
require_once '../includes/auth.php';
require_once '../includes/cart_functions.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $cartId = getCartId($userId);
    if ($cartId) {
        $count = getCartItemCount($cartId);
        echo json_encode(['count' => $count ?? 0]);
    } else {
        echo json_encode(['count' => 0]);
    }
} else {
    // Для гостей - из localStorage (симулируем)
    echo json_encode(['count' => 0]);
}
?>
