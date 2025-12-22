<?php
// Включить отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';
require_once 'includes/cart_functions.php'; // Проверьте этот путь!

// Проверяем авторизацию
if (!isLoggedIn()) {
    // Редирект на логин с сохранением текущей страницы
    $_SESSION['return_to'] = 'cart.php';
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$cartId = getCartId($userId); // Теперь функция должна быть доступна
$items = getCartItems($cartId);


// Расчет итогов
$subtotal = 0;
$shipping = 0;
$total = 0;

if (!empty($items)) {
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Расчет доставки (условно)
    $shipping = $subtotal > 5000 ? 0 : 500;
    $total = $subtotal + $shipping;
}

// Обработка обновления количества
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_quantity'])) {
        $itemId = $_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity > 0) {
            updateCartItemQuantity($itemId, $quantity);
        } else {
            removeCartItem($itemId);
        }
        
        // Обновляем страницу
        header('Location: cart.php');
        exit();
    }
    
    if (isset($_POST['remove_item'])) {
        $itemId = $_POST['item_id'];
        removeCartItem($itemId);
        
        // Обновляем страницу
        header('Location: cart.php');
        exit();
    }
    
    if (isset($_POST['clear_cart'])) {
        clearCart($cartId);
        
        // Обновляем страницу
        header('Location: cart.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корзина - SneakerStore</title>
    <meta name="description" content="Ваша корзина покупок. Просмотрите выбранные товары, измените количество и перейдите к оформлению заказа.">
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
                        <li><a href="index.php"><i class="fas fa-home"></i> Главная</a></li>
                        <li><a href="catalog.php"><i class="fas fa-shoe-prints"></i> Каталог</a></li>
                        <li><a href="cart.php" class="active cart-link">
                            <i class="fas fa-shopping-cart"></i>
                            Корзина <span class="cart-count" id="cart-count"><?php echo array_sum(array_column($items, 'quantity')); ?></span>
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
            <!-- Хлебные крошки -->
            <div class="breadcrumb mt-20">
                <a href="index.php">Главная</a> / <span>Корзина</span>
            </div>

            <!-- Заголовок -->
            <div class="cart-header mt-40">
                <h1><i class="fas fa-shopping-cart"></i> Ваша корзина</h1>
                <p>Просмотрите выбранные товары перед оформлением заказа</p>
            </div>

            <?php if (empty($items)): ?>
                <!-- Пустая корзина -->
                <div class="empty-cart fade-in">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2>Ваша корзина пуста</h2>
                    <p>Добавьте товары из каталога, чтобы сделать покупку</p>
                    <div class="empty-cart-actions">
                        <a href="catalog.php" class="btn btn-primary btn-large">
                            <i class="fas fa-shoe-prints"></i> Перейти в каталог
                        </a>
                        <a href="index.php" class="btn btn-secondary btn-large">
                            <i class="fas fa-home"></i> На главную
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <!-- Левая колонка - товары -->
                    <div class="cart-items-section">
                        <div class="cart-items-header">
                            <h2>Товары (<?php echo count($items); ?>)</h2>
                            <form method="post" style="display: inline;">
                                <button type="submit" name="clear_cart" class="btn-clear-cart" 
                                        onclick="return confirm('Вы уверены, что хотите очистить корзину?')">
                                    <i class="fas fa-trash"></i> Очистить корзину
                                </button>
                            </form>
                        </div>
                        
                        <div class="cart-items-list">
                            <?php foreach ($items as $item): ?>
                                <div class="cart-item-card fade-in">
                                    <div class="cart-item-image">
                                        <img src="assets/images/<?php echo htmlspecialchars($item['image_url'] ?? 'default.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             onerror="this.src='assets/images/default.jpg'">
                                    </div>
                                    
                                    <div class="cart-item-info">
                                        <div class="cart-item-header">
                                            <h3>
                                                <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            </h3>
                                            <form method="post">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="remove_item" class="cart-item-remove">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div class="cart-item-details">
                                            <div class="cart-item-attribute">
                                                <span class="attribute-label">Размер:</span>
                                                <span class="attribute-value"><?php echo htmlspecialchars($item['size']); ?></span>
                                            </div>
                                            
                                            <div class="cart-item-attribute">
                                                <span class="attribute-label">Артикул:</span>
                                                <span class="attribute-value">SNK-<?php echo str_pad($item['product_id'], 4, '0', STR_PAD_LEFT); ?></span>
                                            </div>
                                            
                                            <div class="cart-item-attribute">
                                                <span class="attribute-label">Наличие:</span>
                                                <span class="attribute-value in-stock">
                                                    <i class="fas fa-check-circle"></i> В наличии
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="cart-item-price">
                                            <div class="price-quantity">
                                                <div class="quantity-controls">
                                                    <form method="post" class="quantity-form">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="button" class="quantity-btn minus" 
                                                                onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <input type="text" 
                                                               name="quantity" 
                                                               value="<?php echo $item['quantity']; ?>" 
                                                               class="quantity-input"
                                                               id="quantity-<?php echo $item['id']; ?>"
                                                               readonly>
                                                        <button type="button" class="quantity-btn plus" 
                                                                onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                <div class="price-info">
                                                    <div class="price-unit">
                                                        <?php echo number_format($item['price'], 0, '', ' '); ?> ₽ / шт.
                                                    </div>
                                                    <div class="price-total">
                                                        <?php echo number_format($item['price'] * $item['quantity'], 0, '', ' '); ?> ₽
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Правая колонка - итоги -->
                    <div class="cart-summary-section">
                        <div class="summary-card">
                            <div class="summary-header">
                                <h3><i class="fas fa-receipt"></i> Итоги заказа</h3>
                            </div>
                            
                            <div class="summary-content">
                                <div class="summary-row">
                                    <span class="summary-label">Товары (<?php echo array_sum(array_column($items, 'quantity')); ?> шт.)</span>
                                    <span class="summary-value"><?php echo number_format($subtotal, 0, '', ' '); ?> ₽</span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Доставка</span>
                                    <span class="summary-value">
                                        <?php if ($shipping == 0): ?>
                                            <span class="free-shipping">Бесплатно</span>
                                        <?php else: ?>
                                            <?php echo number_format($shipping, 0, '', ' '); ?> ₽
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="summary-divider"></div>
                                
                                <div class="summary-row total">
                                    <span class="summary-label">Итого к оплате</span>
                                    <span class="summary-value total-price"><?php echo number_format($total, 0, '', ' '); ?> ₽</span>
                                </div>
                                
                                <div class="shipping-info">
                                    <div class="shipping-icon">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                    <div class="shipping-text">
                                        <strong>Бесплатная доставка</strong> при заказе от 5 000 ₽
                                        <?php if ($subtotal < 5000): ?>
                                            <div class="shipping-progress">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" 
                                                         style="width: <?php echo min(100, ($subtotal / 5000) * 100); ?>%"></div>
                                                </div>
                                                <span class="progress-text">
                                                    Добавьте товаров на <?php echo number_format(5000 - $subtotal, 0, '', ' '); ?> ₽ для бесплатной доставки
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="success-text">Бесплатная доставка активирована!</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="summary-actions">
                                <a href="catalog.php" class="btn btn-secondary btn-block">
                                    <i class="fas fa-arrow-left"></i> Продолжить покупки
                                </a>
                                
                                <a href="checkout.php" class="btn btn-primary btn-block btn-large">
                                    <i class="fas fa-lock"></i> Перейти к оформлению
                                </a>
                                
                                <div class="secure-checkout">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Безопасная оплата • SSL шифрование</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Промокод -->
                        <div class="promo-card">
                            <h4><i class="fas fa-tag"></i> Промокод</h4>
                            <div class="promo-form">
                                <input type="text" placeholder="Введите промокод" class="promo-input">
                                <button type="button" class="btn-promo">Применить</button>
                            </div>
                            <div class="promo-suggestions">
                                <span class="promo-suggestion" onclick="applyPromo('SUMMER10')">SUMMER10 -10%</span>
                                <span class="promo-suggestion" onclick="applyPromo('FREE500')">FREE500 бесплатная доставка</span>
                            </div>
                        </div>
                        
                        <!-- Гарантии -->
                        <div class="guarantees-card">
                            <h4><i class="fas fa-shield-check"></i> Гарантии</h4>
                            <div class="guarantees-list">
                                <div class="guarantee-item">
                                    <i class="fas fa-undo"></i>
                                    <span>30 дней на возврат</span>
                                </div>
                                <div class="guarantee-item">
                                    <i class="fas fa-truck-fast"></i>
                                    <span>Быстрая доставка</span>
                                </div>
                                <div class="guarantee-item">
                                    <i class="fas fa-headset"></i>
                                    <span>Поддержка 24/7</span>
                                </div>
                                <div class="guarantee-item">
                                    <i class="fas fa-lock"></i>
                                    <span>Безопасная оплата</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Футер -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>SneakerStore</h3>
                    <p>Магазин качественной обуви с доставкой по всей России.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-vk"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-telegram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 SneakerStore. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
        // Обновление количества через AJAX
        function updateQuantity(itemId, change) {
            const input = document.getElementById('quantity-' + itemId);
            let newQuantity = parseInt(input.value) + change;
            
            if (newQuantity < 1) newQuantity = 1;
            if (newQuantity > 99) newQuantity = 99;
            
            // Обновляем значение
            input.value = newQuantity;
            
            // Отправляем AJAX запрос
            fetch('api/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId,
                    quantity: newQuantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем счетчик корзины
                    updateCartCount();
                    // Перезагружаем страницу для обновления сумм
                    setTimeout(() => location.reload(), 300);
                } else {
                    alert('Ошибка обновления: ' + data.message);
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                location.reload();
            });
        }
        
        // Применение промокода
        function applyPromo(code) {
            document.querySelector('.promo-input').value = code;
            alert('Промокод "' + code + '" применен!');
        }
        
        // Обновление счетчика корзины
        function updateCartCount() {
            fetch('api/get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const countElement = document.getElementById('cart-count');
                    if (countElement && data.count !== undefined) {
                        countElement.textContent = data.count;
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>