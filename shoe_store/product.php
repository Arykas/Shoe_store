<?php
// Включить отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php'; // Подключаем auth.php
// ... остальной код


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: catalog.php');
    exit();
}

$id = (int)$_GET['id'];
$conn = getDBConnection();

// Запрос товара
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "Товар не найден";
    exit();
}

// Запрос размеров
$sizeStmt = $conn->prepare("SELECT id, size, quantity FROM product_sizes WHERE product_id = ?");
$sizeStmt->bind_param("i", $id);
$sizeStmt->execute();
$sizes = $sizeStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Запрос изображений
$imageStmt = $conn->prepare("SELECT image_url, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
$imageStmt->bind_param("i", $id);
$imageStmt->execute();
$images = $imageStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - SneakerStore</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Шапка сайта -->
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <span class="logo-icon">👟</span>
                    <span>SneakerStore</span>
                </a>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php">Главная</a></li>
                        <li><a href="catalog.php">Каталог</a></li>
                        <li><a href="cart.php" class="cart-link">
                            <i class="fas fa-shopping-cart"></i>
                            Корзина <span class="cart-count" id="cart-count">0</span>
                        </a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="profile.php"><i class="fas fa-user"></i> Профиль</a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
                        <?php else: ?>
                            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Вход</a></li>
                            <li><a href="register.php"><i class="fas fa-user-plus"></i> Регистрация</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="product-detail-page fade-in">
                <!-- Левая колонка - галерея -->
                <div class="product-gallery">
                    <div class="main-gallery">
                        <?php if (!empty($images)): ?>
                            <img src="assets/images/<?php echo htmlspecialchars($images[0]['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 id="main-product-image">
                        <?php else: ?>
                            <img src="assets/images/default.jpg" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 id="main-product-image">
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                        <div class="gallery-thumbs">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeMainImage('assets/images/<?php echo htmlspecialchars($image['image_url']); ?>')">
                                    <img src="assets/images/<?php echo htmlspecialchars($image['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?> - изображение <?php echo $index + 1; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Правая колонка - информация -->
                <div class="product-content">
                    <div class="breadcrumb">
                        <a href="index.php">Главная</a> / 
                        <a href="catalog.php">Каталог</a> / 
                        <a href="catalog.php?category=<?php echo urlencode($product['category']); ?>">
                            <?php echo htmlspecialchars($product['category']); ?>
                        </a> / 
                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                    </div>
                    
                    <div class="product-header">
                        <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <div class="product-meta">
                            <span class="product-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                                <span>4.5 (128 отзывов)</span>
                            </span>
                            <span class="product-sku">Артикул: SNK-<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                    
                    <div class="product-price-section">
                        <div class="price-display"><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</div>
                        <div class="price-info">Бесплатная доставка по всей России</div>
                    </div>
                    
                    <div class="product-sizes">
                        <h3 class="size-title">Выберите размер</h3>
                        <div class="size-grid" id="size-grid">
                            <?php if (!empty($sizes)): ?>
                                <?php foreach ($sizes as $size): ?>
                                    <div class="size-option <?php echo $size['quantity'] <= 0 ? 'unavailable' : ''; ?>"
                                         data-size-id="<?php echo $size['id']; ?>"
                                         data-size="<?php echo $size['size']; ?>"
                                         onclick="selectSize(this)"
                                         title="<?php echo $size['quantity'] > 0 ? 'В наличии: ' . $size['quantity'] . ' шт.' : 'Нет в наличии'; ?>">
                                        <?php echo $size['size']; ?>
                                        <?php if ($size['quantity'] <= 0): ?>
                                            <br><small>нет</small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="alert alert-error">Нет доступных размеров</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="product-actions-large">
                        <button id="add-to-cart-btn" class="btn btn-large btn-primary btn-icon" onclick="addToCart()" disabled>
                            <i class="fas fa-shopping-cart"></i>
                            Добавить в корзину
                        </button>
                        <button id="buy-now-btn" class="btn btn-large btn-danger btn-icon" onclick="buyNow()" disabled>
                            <i class="fas fa-bolt"></i>
                            Купить сейчас
                        </button>
                    </div>
                    
                    <div class="product-description">
                        <h2 class="description-title">Описание</h2>
                        <div class="description-content">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                    </div>
                    
                    <div class="product-specs">
                        <h3 class="description-title">Характеристики</h3>
                        <div class="specs-grid">
                            <div class="spec-item">
                                <span class="spec-label">Бренд:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($product['brand']); ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Категория:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($product['category']); ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Цвет:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($product['color']); ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Материал:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($product['material']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>SneakerStore</h3>
                    <p>Магазин качественной обуви с доставкой по всей России. Более 1000 моделей от ведущих брендов.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-vk"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-telegram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Категории</h3>
                    <ul>
                        <li><a href="catalog.php?category=мужская">Мужская обувь</a></li>
                        <li><a href="catalog.php?category=женская">Женская обувь</a></li>
                        <li><a href="catalog.php?category=детская">Детская обувь</a></li>
                        <li><a href="catalog.php?category=спортивная">Спортивная обувь</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Помощь</h3>
                    <ul>
                        <li><a href="#">Доставка и оплата</a></li>
                        <li><a href="#">Размерная сетка</a></li>
                        <li><a href="#">Возврат и обмен</a></li>
                        <li><a href="#">Контакты</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Контакты</h3>
                    <ul>
                        <li><i class="fas fa-phone"></i> +7 (999) 123-45-67</li>
                        <li><i class="fas fa-envelope"></i> info@sneakerstore.ru</li>
                        <li><i class="fas fa-map-marker-alt"></i> Москва, ул. Примерная, 123</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 SneakerStore. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
    let selectedSizeId = null;
    let selectedSize = null;
    
    function changeMainImage(src) {
        document.getElementById('main-product-image').src = src;
        document.querySelectorAll('.thumb').forEach(thumb => {
            thumb.classList.remove('active');
        });
        event.target.closest('.thumb').classList.add('active');
    }
    
    function selectSize(element) {
        if (element.classList.contains('unavailable')) return;
        
        // Убираем выделение у всех размеров
        document.querySelectorAll('.size-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        // Выделяем выбранный размер
        element.classList.add('selected');
        
        // Сохраняем выбранные данные
        selectedSizeId = element.getAttribute('data-size-id');
        selectedSize = element.getAttribute('data-size');
        
        
        // Активируем кнопки
        document.getElementById('add-to-cart-btn').disabled = false;
        document.getElementById('buy-now-btn').disabled = false;
        
        // Обновляем текст кнопки
        document.getElementById('add-to-cart-btn').innerHTML = 
            `<i class="fas fa-shopping-cart"></i> Добавить в корзину (${selectedSize})`;
    }
    
    // Функция добавления в корзину
    function addToCart() {
        if (!selectedSizeId) {
            alert('Пожалуйста, выберите размер');
            return;
        }
        
        const productId = <?php echo $id; ?>;
        
        console.log('Добавление в корзину:', {
            productId: productId,
            sizeId: selectedSizeId,
            size: selectedSize
        });
        
        <?php if (isLoggedIn()): ?>
            // Для авторизованных пользователей - AJAX запрос
            addToCartAjax(productId, selectedSizeId, 1);
        <?php else: ?>
            // Для гостей - localStorage
            addToCartLocal(productId, selectedSizeId, selectedSize, 1);
        <?php endif; ?>
    }
    
    // Функция для AJAX добавления (авторизованные)
    function addToCartAjax(productId, sizeId, quantity) {
        fetch('api/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                size_id: sizeId,
                quantity: quantity
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Ответ от сервера:', data);
            if (data.success) {
                showSuccessMessage('Товар добавлен в корзину!');
                updateCartCount();
            } else {
                showErrorMessage('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage('Ошибка соединения с сервером');
        });
    }
    
    // Функция для localStorage (гости)
    function addToCartLocal(productId, sizeId, size, quantity) {
        let cart = JSON.parse(localStorage.getItem('cart')) || { items: [] };
        
        // Проверяем, есть ли уже такой товар
        const existingItemIndex = cart.items.findIndex(item => 
            item.product_id == productId && item.size_id == sizeId);
        
        if (existingItemIndex !== -1) {
            // Увеличиваем количество
            cart.items[existingItemIndex].quantity += quantity;
        } else {
            // Добавляем новый товар
            cart.items.push({
                product_id: productId,
                size_id: sizeId,
                size: size,
                quantity: quantity,
                added_at: new Date().toISOString()
            });
        }
        
        // Сохраняем в localStorage
        localStorage.setItem('cart', JSON.stringify(cart));
        
        console.log('Корзина в localStorage:', cart);
        showSuccessMessage('Товар добавлен в корзину!');
        updateCartCount();
        
        // Показываем уведомление о слиянии при авторизации
        showMergeNotification();
    }
    
    // Функция "Купить сейчас"
    function buyNow() {
        addToCart();
        // Даем время на добавление в корзину
        setTimeout(() => {
            window.location.href = 'cart.php';
        }, 500);
    }
    
    // Обновление счетчика корзины
    function updateCartCount() {
        <?php if (isLoggedIn()): ?>
            // Для авторизованных - запрос к API
            fetch('api/get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const countElement = document.getElementById('cart-count');
                    if (countElement && data.count !== undefined) {
                        countElement.textContent = data.count;
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
        <?php else: ?>
            // Для гостей - из localStorage
            const cart = JSON.parse(localStorage.getItem('cart')) || { items: [] };
            const total = cart.items.reduce((sum, item) => sum + item.quantity, 0);
            const countElement = document.getElementById('cart-count');
            if (countElement) {
                countElement.textContent = total;
            }
        <?php endif; ?>
    }
    
    // Показать уведомление об успехе
    function showSuccessMessage(message) {
        // Создаем уведомление
        const notification = document.createElement('div');
        notification.className = 'cart-notification success';
        notification.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        `;
        
        // Добавляем стили
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2ecc71;
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 10000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Автоматически удаляем через 3 секунды
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 3000);
    }
    
    // Показать сообщение об ошибке
    function showErrorMessage(message) {
        const notification = document.createElement('div');
        notification.className = 'cart-notification error';
        notification.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #e74c3c;
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 10000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
    
    // Показать уведомление о слиянии корзин
    function showMergeNotification() {
        if (!document.getElementById('merge-notification')) {
            const notification = document.createElement('div');
            notification.id = 'merge-notification';
            notification.innerHTML = `
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #3498db;">
                    <p style="margin: 0; color: #2c3e50;">
                        <i class="fas fa-info-circle" style="color: #3498db;"></i>
                        <strong>Совет:</strong> После регистрации/входа товары из этой корзины автоматически перенесутся в ваш аккаунт!
                    </p>
                </div>
            `;
            
            // Находим контейнер для уведомления
            const container = document.querySelector('.product-actions-large')?.parentElement;
            if (container) {
                container.appendChild(notification);
            }
        }
    }
    
    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
        
        // Добавляем анимацию для уведомлений
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .cart-notification button {
                background: transparent;
                border: none;
                color: white;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0.8;
            }
            
            .cart-notification button:hover {
                opacity: 1;
            }
        `;
        document.head.appendChild(style);
    });
</script>