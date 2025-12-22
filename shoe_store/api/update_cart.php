<?php
require_once '../includes/auth.php';
require_once '../includes/cart_functions.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

// Получаем данные из POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['item_id']) || !isset($data['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Неполные данные']);
    exit();
}

$itemId = (int)$data['item_id'];
$quantity = (int)$data['quantity'];

// Проверяем права (только свой товар можно обновлять)
$userId = $_SESSION['user_id'];
$cartId = getCartId($userId);

$conn = getDBConnection();
$checkStmt = $conn->prepare("
    SELECT ci.id 
    FROM cart_items ci
    JOIN carts c ON ci.cart_id = c.id
    WHERE ci.id = ? AND c.user_id = ?
");
$checkStmt->bind_param("ii", $itemId, $userId);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден в вашей корзине']);
    $checkStmt->close();
    $conn->close();
    exit();
}
$checkStmt->close();

// Обновляем количество
if ($quantity <= 0) {
    // Удаляем товар
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $result = $stmt->execute();
    $message = "Товар удален из корзины";
} else {
    // Проверяем наличие
    $checkStock = $conn->prepare("
        SELECT ps.quantity as available 
        FROM cart_items ci
        JOIN product_sizes ps ON ci.size_id = ps.id
        WHERE ci.id = ?
    ");
    $checkStock->bind_param("i", $itemId);
    $checkStock->execute();
    $checkStock->bind_result($available);
    $checkStock->fetch();
    $checkStock->close();
    
    if ($available < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Недостаточно товара на складе']);
        $conn->close();
        exit();
    }
    
    // Обновляем количество
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $quantity, $itemId);
    $result = $stmt->execute();
    $message = "Количество обновлено";
}

if ($result) {
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка обновления']);
}

$stmt->close();
$conn->close();
?>
