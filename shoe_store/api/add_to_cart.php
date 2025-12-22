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

// Также проверяем обычные POST данные (для обратной совместимости)
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

if (!isset($data['product_id']) || !isset($data['size_id']) || !isset($data['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Неполные данные']);
    exit();
}

$userId = $_SESSION['user_id'];
$productId = (int)$data['product_id'];
$sizeId = (int)$data['size_id'];
$quantity = (int)$data['quantity'];

// Получаем ID корзины
$cartId = getCartId($userId);
if (!$cartId) {
    echo json_encode(['success' => false, 'message' => 'Ошибка получения корзины']);
    exit();
}

// Проверяем наличие товара на складе
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к БД']);
    exit();
}

// Проверяем, существует ли товар и размер
$checkStmt = $conn->prepare("
    SELECT ps.quantity as available, p.name 
    FROM product_sizes ps
    JOIN products p ON ps.product_id = p.id
    WHERE ps.id = ? AND ps.product_id = ? AND p.status = 'active'
");
$checkStmt->bind_param("ii", $sizeId, $productId);
$checkStmt->execute();
$checkStmt->bind_result($available, $productName);
$checkStmt->fetch();
$checkStmt->close();

if (!$available) {
    echo json_encode(['success' => false, 'message' => 'Товар или размер не найдены']);
    $conn->close();
    exit();
}

// Получаем текущее количество в корзине
$currentQty = 0;
$existingItemId = null;
$checkCartStmt = $conn->prepare("
    SELECT id, quantity 
    FROM cart_items 
    WHERE cart_id = ? AND product_id = ? AND size_id = ?
");
$checkCartStmt->bind_param("iii", $cartId, $productId, $sizeId);
$checkCartStmt->execute();
$checkCartStmt->bind_result($existingItemId, $currentQty);
$checkCartStmt->fetch();
$checkCartStmt->close();

$newQuantity = $currentQty + $quantity;

// Проверяем, не превышает ли общее количество доступное
if ($newQuantity > $available) {
    $message = "Максимально доступное количество: $available шт. (уже в корзине: $currentQty шт.)";
    echo json_encode(['success' => false, 'message' => $message]);
    $conn->close();
    exit();
}

if ($existingItemId) {
    // Обновляем существующий товар
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $newQuantity, $existingItemId);
} else {
    // Добавляем новый товар
    $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, size_id, quantity) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $cartId, $productId, $sizeId, $quantity);
}

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Товар добавлен в корзину',
        'product_name' => $productName,
        'quantity' => $newQuantity
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении в корзину: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
