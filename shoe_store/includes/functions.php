<?php
require_once 'database.php';

// Общие функции
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getProducts($filters = [], $limit = 12, $offset = 0) {
    $conn = getDBConnection();
    
    if (!$conn) {
        error_log("Failed to get DB connection in getProducts()");
        return [];
    }
    
    $query = "SELECT p.*, pi.image_url FROM products p 
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
              WHERE p.status = 'active'";
    
    $params = [];
    $types = '';
    
    if (!empty($filters['search'])) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $search_term = "%{$filters['search']}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ss';
    }
    
    if (!empty($filters['category'])) {
        $query .= " AND p.category = ?";
        $params[] = $filters['category'];
        $types .= 's';
    }
    
    if (!empty($filters['brand'])) {
        if (is_array($filters['brand'])) {
            $placeholders = str_repeat('?,', count($filters['brand']) - 1) . '?';
            $query .= " AND p.brand IN ($placeholders)";
            $params = array_merge($params, $filters['brand']);
            $types .= str_repeat('s', count($filters['brand']));
        } else {
            $query .= " AND p.brand = ?";
            $params[] = $filters['brand'];
            $types .= 's';
        }
    }
    
    if (!empty($filters['color'])) {
        $query .= " AND p.color = ?";
        $params[] = $filters['color'];
        $types .= 's';
    }
    
    if (!empty($filters['price_min'])) {
        $query .= " AND p.price >= ?";
        $params[] = $filters['price_min'];
        $types .= 'd';
    }
    
    if (!empty($filters['price_max'])) {
        $query .= " AND p.price <= ?";
        $params[] = $filters['price_max'];
        $types .= 'd';
    }
    
    // Сортировка
    $order_by = "p.created_at DESC";
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_asc':
                $order_by = "p.price ASC";
                break;
            case 'price_desc':
                $order_by = "p.price DESC";
                break;
            case 'popular':
                $order_by = "p.created_at DESC";
                break;
        }
    }
    $query .= " ORDER BY $order_by";
    
    if ($limit > 0) {
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
    }
    
    try {
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        $stmt->close();
        return $products;
        
    } catch (Exception $e) {
        error_log("Error in getProducts(): " . $e->getMessage());
        return [];
    } finally {
        $conn->close();
    }
}

// Вспомогательные функции для каталога
function getCategoryCount($category) {
    try {
        $conn = getDBConnection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category = ? AND status = 'active'");
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $conn->close();
            return $row['count'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Error in getCategoryCount(): " . $e->getMessage());
    }
    return 0;
}


function getBrandCount($brand) {
    try {
        $conn = getDBConnection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE brand = ? AND status = 'active'");
            $stmt->bind_param("s", $brand);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $conn->close();
            return $row['count'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Error in getBrandCount(): " . $e->getMessage());
    }
    return 0;
}

// Функция для получения HEX цвета из названия
function getColorHex($color_name) {
    $colors = [
        'черный' => '#000000',
        'белый' => '#FFFFFF',
        'красный' => '#FF0000',
        'синий' => '#0000FF',
        'зеленый' => '#00FF00',
        'желтый' => '#FFFF00',
        'коричневый' => '#A52A2A',
        'серый' => '#808080',
        'розовый' => '#FFC0CB',
        'оранжевый' => '#FFA500',
        'фиолетовый' => '#800080',
        'бежевый' => '#F5F5DC',
        'голубой' => '#87CEEB',
        'бордовый' => '#800000',
    ];
    
    return $colors[strtolower($color_name)] ?? '#CCCCCC';
}

// Вспомогательная функция для построения query string
function buildQuery($new_params = []) {
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    // Убираем пустые значения
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== null && $value !== [];
    });
    return http_build_query($params);
}
?>