<?php
require_once '../includes/auth.php';
require_once '../includes/database.php';

// Проверка прав администратора
if (!isLoggedIn() || getUserRole() !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = getDBConnection();
    
    // Добавление товара
    $stmt = $conn->prepare("INSERT INTO products (name, description, category, brand, price, color, material) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdss", 
        $_POST['name'],
        $_POST['description'],
        $_POST['category'],
        $_POST['brand'],
        $_POST['price'],
        $_POST['color'],
        $_POST['material']
    );
    
    if ($stmt->execute()) {
        $productId = $conn->insert_id;
        
        // Добавление размеров
        if (isset($_POST['sizes'])) {
            $sizeStmt = $conn->prepare("INSERT INTO product_sizes (product_id, size, quantity) VALUES (?, ?, ?)");
            foreach ($_POST['sizes'] as $size => $quantity) {
                if ($quantity > 0) {
                    $sizeStmt->bind_param("idi", $productId, $size, $quantity);
                    $sizeStmt->execute();
                }
            }
            $sizeStmt->close();
        }
        
        $message = "Товар успешно добавлен! ID: " . $productId;
    } else {
        $error = "Ошибка при добавлении товара: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить товар</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea, select { width: 100%; padding: 8px; }
        .size-row { display: flex; gap: 10px; margin-bottom: 5px; }
        .size-row input { width: 100px; }
    </style>
</head>
<body>
    <header>
        <h1>Добавление товара</h1>
        <nav>
            <ul>
                <li><a href="../index.php">Главная</a></li>
                <li><a href="index.php">Админ-панель</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <h2>Добавить новый товар</h2>
        
        <?php if (isset($message)): ?>
            <div style="color: green; padding: 10px; background: #d4edda;"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div style="color: red; padding: 10px; background: #f8d7da;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" style="max-width: 600px;">
            <div class="form-group">
                <label>Название товара *</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label>Категория *</label>
                <select name="category" required>
                    <option value="">Выберите категорию</option>
                    <option value="мужская">Мужская обувь</option>
                    <option value="женская">Женская обувь</option>
                    <option value="детская">Детская обувь</option>
                    <option value="унисекс">Унисекс</option>
                    <option value="спортивная">Спортивная</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Бренд</label>
                <input type="text" name="brand">
            </div>
            
            <div class="form-group">
                <label>Цена *</label>
                <input type="number" name="price" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Цвет</label>
                <input type="text" name="color">
            </div>
            
            <div class="form-group">
                <label>Материал</label>
                <input type="text" name="material">
            </div>
            
            <h3>Размеры и количество</h3>
            <div id="sizes-container">
                <div class="size-row">
                    <input type="number" name="sizes[38]" placeholder="Размер" step="0.5" min="20" max="50">
                    <input type="number" name="quantities[38]" placeholder="Количество" min="0">
                </div>
                <!-- JavaScript добавит больше строк -->
            </div>
            <button type="button" onclick="addSizeRow()">+ Добавить размер</button>
            
            <div style="margin-top: 20px;">
                <button type="submit" style="padding: 10px 20px;">Добавить товар</button>
            </div>
        </form>
    </main>
    
    <script>
        let sizeCounter = 39;
        
        function addSizeRow() {
            const container = document.getElementById('sizes-container');
            const row = document.createElement('div');
            row.className = 'size-row';
            row.innerHTML = `
                <input type="number" name="sizes[${sizeCounter}]" placeholder="Размер" step="0.5" min="20" max="50">
                <input type="number" name="quantities[${sizeCounter}]" placeholder="Количество" min="0">
            `;
            container.appendChild(row);
            sizeCounter++;
        }
    </script>
</body>
</html>
