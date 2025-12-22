<?php
require_once 'database.php';
require_once 'auth.php';

// Получение ID корзины для пользователя
function getCartId($userId = null) {
    $conn = getDBConnection();
    if (!$conn) {
        error_log("Failed to get DB connection in getCartId()");
        return false;
    }
    
    if ($userId) {
        // Для авторизованных пользователей
        $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
        if (!$stmt) {
            $conn->close();
            return false;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($cartId);
        
        if ($stmt->fetch()) {
            $stmt->close();
            $conn->close();
            return $cartId;
        }
        $stmt->close();
        
        // Создаем новую корзину, если не нашли
        $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
        if (!$stmt) {
            $conn->close();
            return false;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $cartId = $conn->insert_id;
        $stmt->close();
    } else {
        // Для гостей - используем session_id
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionId = session_id();
        
        $stmt = $conn->prepare("SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL");
        if (!$stmt) {
            $conn->close();
            return false;
        }
        
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $stmt->bind_result($cartId);
        
        if (!$stmt->fetch()) {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO carts (session_id) VALUES (?)");
            if (!$stmt) {
                $conn->close();
                return false;
            }
            
            $stmt->bind_param("s", $sessionId);
            $stmt->execute();
            $cartId = $conn->insert_id;
        }
        $stmt->close();
    }
    
    $conn->close();
    return $cartId;
}

// Получение товаров из корзины
function getCartItems($cartId) {
    $conn = getDBConnection();
    if (!$conn) {
        error_log("Failed to get DB connection in getCartItems()");
        return [];
    }
    
    $stmt = $conn->prepare("
        SELECT 
            ci.id,
            ci.product_id,
            ci.size_id,
            ci.quantity,
            p.name,
            p.price,
            p.category,
            p.brand,
            ps.size,
            pi.image_url
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        JOIN product_sizes ps ON ci.size_id = ps.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE ci.cart_id = ?
        ORDER BY ci.id DESC
    ");
    
    if (!$stmt) {
        $conn->close();
        return [];
    }
    
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $items;
}

// Слияние корзин (гость → авторизованный)
function mergeCarts($userId) {
    // Проверяем куки с локальной корзиной
    if (isset($_COOKIE['cart'])) {
        $localCart = json_decode($_COOKIE['cart'], true);
        if (!empty($localCart['items'])) {
            $cartId = getCartId($userId);
            $conn = getDBConnection();
            
            if (!$conn) {
                return false;
            }
            
            foreach ($localCart['items'] as $item) {
                // Проверяем, есть ли уже такой товар в корзине
                $stmt = $conn->prepare("
                    SELECT id, quantity 
                    FROM cart_items 
                    WHERE cart_id = ? 
                    AND product_id = ? 
                    AND size_id = ?
                ");
                
                if (!$stmt) {
                    continue;
                }
                
                $stmt->bind_param("iii", $cartId, $item['product_id'], $item['size_id']);
                $stmt->execute();
                $stmt->bind_result($existingId, $existingQty);
                
                if ($stmt->fetch()) {
                    // Обновляем количество
                    $stmt->close();
                    $newQty = $existingQty + $item['quantity'];
                    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
                    $stmt->bind_param("ii", $newQty, $existingId);
                } else {
                    // Добавляем новый товар
                    $stmt->close();
                    $stmt = $conn->prepare("
                        INSERT INTO cart_items (cart_id, product_id, size_id, quantity) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iiii", $cartId, $item['product_id'], $item['size_id'], $item['quantity']);
                }
                
                $stmt->execute();
                $stmt->close();
            }
            
            // Очищаем куки
            setcookie('cart', '', time() - 3600, '/');
            $conn->close();
        }
    }
    return true;
}

// Обновление количества товара в корзине
function updateCartItemQuantity($itemId, $quantity) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Проверяем, существует ли товар в корзине
    $checkStmt = $conn->prepare("SELECT id FROM cart_items WHERE id = ?");
    if (!$checkStmt) {
        $conn->close();
        return false;
    }
    
    $checkStmt->bind_param("i", $itemId);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows == 0) {
        $checkStmt->close();
        $conn->close();
        return false;
    }
    $checkStmt->close();
    
    // Обновляем количество
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("ii", $quantity, $itemId);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Удаление товара из корзины
function removeCartItem($itemId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("i", $itemId);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Очистка корзины
function clearCart($cartId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("i", $cartId);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Получение количества товаров в корзине
function getCartItemCount($cartId) {
    $conn = getDBConnection();
    if (!$conn) return 0;
    
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
    if (!$stmt) {
        $conn->close();
        return 0;
    }
    
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
    
    return $total ?? 0;
}

// Добавление товара в корзину
function addToCart($cartId, $productId, $sizeId, $quantity = 1) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Проверяем наличие товара на складе
    $checkStmt = $conn->prepare("
        SELECT ps.quantity as available 
        FROM product_sizes ps
        WHERE ps.id = ? AND ps.product_id = ?
    ");
    
    if (!$checkStmt) {
        $conn->close();
        return false;
    }
    
    $checkStmt->bind_param("ii", $sizeId, $productId);
    $checkStmt->execute();
    $checkStmt->bind_result($available);
    $checkStmt->fetch();
    $checkStmt->close();
    
    if ($available < $quantity) {
        $conn->close();
        return false;
    }
    
    // Проверяем, есть ли уже такой товар в корзине
    $existingStmt = $conn->prepare("
        SELECT id, quantity 
        FROM cart_items 
        WHERE cart_id = ? AND product_id = ? AND size_id = ?
    ");
    
    if (!$existingStmt) {
        $conn->close();
        return false;
    }
    
    $existingStmt->bind_param("iii", $cartId, $productId, $sizeId);
    $existingStmt->execute();
    $existingStmt->bind_result($existingId, $existingQty);
    
    if ($existingStmt->fetch()) {
        // Обновляем количество
        $existingStmt->close();
        $newQty = $existingQty + $quantity;
        
        // Проверяем, не превышает ли новое количество доступное
        if ($newQty > $available) {
            $conn->close();
            return false;
        }
        
        $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        if (!$updateStmt) {
            $conn->close();
            return false;
        }
        
        $updateStmt->bind_param("ii", $newQty, $existingId);
        $result = $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Добавляем новый товар
        $existingStmt->close();
        $insertStmt = $conn->prepare("
            INSERT INTO cart_items (cart_id, product_id, size_id, quantity) 
            VALUES (?, ?, ?, ?)
        ");
        
        if (!$insertStmt) {
            $conn->close();
            return false;
        }
        
        $insertStmt->bind_param("iiii", $cartId, $productId, $sizeId, $quantity);
        $result = $insertStmt->execute();
        $insertStmt->close();
    }
    
    $conn->close();
    return $result;
}

// Получение информации о товаре
function getProductInfo($productId, $sizeId) {
    $conn = getDBConnection();
    if (!$conn) return null;
    
    $stmt = $conn->prepare("
        SELECT p.name, p.price, ps.quantity as available, ps.size
        FROM products p
        JOIN product_sizes ps ON p.id = ps.product_id
        WHERE p.id = ? AND ps.id = ? AND p.status = 'active'
    ");
    
    if (!$stmt) {
        $conn->close();
        return null;
    }
    
    $stmt->bind_param("ii", $productId, $sizeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $product;
}

// Проверка существования корзины
function cartExists($cartId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("SELECT id FROM carts WHERE id = ?");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    $conn->close();
    
    return $exists;
}

// Получение общей стоимости корзины
function getCartTotal($cartId) {
    $conn = getDBConnection();
    if (!$conn) return 0;
    
    $stmt = $conn->prepare("
        SELECT SUM(p.price * ci.quantity) as total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
    ");
    
    if (!$stmt) {
        $conn->close();
        return 0;
    }
    
    
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
    
    return $total ?? 0;
}
?>