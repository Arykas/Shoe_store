<?php
// Включить отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';
require_once 'includes/database.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    $_SESSION['return_to'] = 'profile.php';
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Получаем заказы пользователя
$orders = [];
$conn = getDBConnection();
if ($conn) {
    $stmt = $conn->prepare("
        SELECT o.*, 
               COUNT(oi.id) as items_count,
               SUM(oi.price * oi.quantity) as order_total
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    $conn->close();
}

// Статусы заказов с иконками
$statusIcons = [
    'created' => ['icon' => 'far fa-clock', 'color' => '#f39c12', 'text' => 'Оформлен'],
    'paid' => ['icon' => 'fas fa-check-circle', 'color' => '#2ecc71', 'text' => 'Оплачен'],
    'processing' => ['icon' => 'fas fa-cog', 'color' => '#3498db', 'text' => 'В обработке'],
    'shipped' => ['icon' => 'fas fa-shipping-fast', 'color' => '#9b59b6', 'text' => 'Отправлен'],
    'delivered' => ['icon' => 'fas fa-box-open', 'color' => '#27ae60', 'text' => 'Доставлен'],
    'cancelled' => ['icon' => 'fas fa-times-circle', 'color' => '#e74c3c', 'text' => 'Отменен']
];

// Обработка формы обновления профиля
$updateSuccess = false;
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Валидация
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Имя обязательно для заполнения";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Введите корректный email";
    }
    
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $errors[] = "Для смены пароля введите текущий пароль";
        }
        
        if (strlen($newPassword) < 6) {
            $errors[] = "Новый пароль должен содержать минимум 6 символов";
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = "Новые пароли не совпадают";
        }
    }
    
    if (empty($errors)) {
        // Проверяем, изменился ли email
        if ($email !== $userData['email']) {
            // Проверяем, не занят ли новый email
            $conn = getDBConnection();
            if ($conn) {
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $checkStmt->bind_param("si", $email, $userId);
                $checkStmt->execute();
                $checkStmt->store_result();
                
                if ($checkStmt->num_rows > 0) {
                    $errors[] = "Этот email уже используется другим пользователем";
                }
                $checkStmt->close();
                $conn->close();
            }
        }
        
        if (empty($errors)) {
            // Обновляем профиль
            $conn = getDBConnection();
            if ($conn) {
                // Проверяем текущий пароль, если меняем пароль
                if (!empty($newPassword)) {
                    $checkPassStmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $checkPassStmt->bind_param("i", $userId);
                    $checkPassStmt->execute();
                    $checkPassStmt->bind_result($dbPassword);
                    $checkPassStmt->fetch();
                    $checkPassStmt->close();
                    
                    if (!password_verify($currentPassword, $dbPassword)) {
                        $errors[] = "Текущий пароль неверен";
                    } else {
                        // Обновляем с новым паролем
                        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                        $stmt->bind_param("ssssi", $name, $email, $phone, $hashedPassword, $userId);
                    }
                } else {
                    // Обновляем без смены пароля
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $email, $phone, $userId);
                }
                
                if (isset($stmt) && $stmt->execute()) {
                    $updateSuccess = true;
                    // Обновляем данные в сессии
                    $_SESSION['user_name'] = $name;
                    // Получаем обновленные данные
                    $userData = getUserData($userId);
                } else {
                    $updateError = "Ошибка при обновлении профиля";
                }
                
                if (isset($stmt)) $stmt->close();
                $conn->close();
            }
        }
    }
    
    if (!empty($errors)) {
        $updateError = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет - SneakerStore</title>
    <meta name="description" content="Ваш личный кабинет. Управление профилем, просмотр заказов и истории покупок.">
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
                        <li><a href="cart.php" class="cart-link">
                            <i class="fas fa-shopping-cart"></i>
                            Корзина <span class="cart-count" id="cart-count">0</span>
                        </a></li>
                        <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Профиль</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Хлебные крошки -->
            <div class="breadcrumb mt-20">
                <a href="index.php">Главная</a> / <span>Личный кабинет</span>
            </div>

            <!-- Заголовок -->
            <div class="profile-header mt-40">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <?php echo strtoupper(mb_substr($userData['name'], 0, 1)); ?>
                    </div>
                    <div class="profile-welcome">
                        <h1>Привет, <?php echo htmlspecialchars($userData['name']); ?>!</h1>
                        <p>Добро пожаловать в ваш личный кабинет</p>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo count($orders); ?></span>
                            <span class="stat-label">Заказов</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">
                                <?php 
                                $completedOrders = array_filter($orders, function($order) {
                                    return $order['status'] == 'delivered';
                                });
                                echo count($completedOrders);
                                ?>
                            </span>
                            <span class="stat-label">Завершено</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">5.0</span>
                            <span class="stat-label">Рейтинг</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-layout">
                <!-- Левая колонка - навигация -->
                <aside class="profile-sidebar">
                    <nav class="profile-nav">
                        <a href="#profile" class="profile-nav-item active" onclick="showSection('profile')">
                            <i class="fas fa-user-circle"></i>
                            <span>Профиль</span>
                        </a>
                        
                        <a href="#orders" class="profile-nav-item" onclick="showSection('orders')">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Мои заказы</span>
                            <?php if (count($orders) > 0): ?>
                                <span class="nav-badge"><?php echo count($orders); ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <a href="#addresses" class="profile-nav-item" onclick="showSection('addresses')">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Адреса доставки</span>
                        </a>
                        
                        <a href="#wishlist" class="profile-nav-item" onclick="showSection('wishlist')">
                            <i class="fas fa-heart"></i>
                            <span>Избранное</span>
                            <span class="nav-badge">3</span>
                        </a>
                        
                        <a href="#settings" class="profile-nav-item" onclick="showSection('settings')">
                            <i class="fas fa-cog"></i>
                            <span>Настройки</span>
                        </a>
                        
                        <div class="profile-nav-divider"></div>
                        
                        <a href="logout.php" class="profile-nav-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Выход</span>
                        </a>
                    </nav>
                    
                    <div class="profile-help">
                        <h4>Нужна помощь?</h4>
                        <p>Наша служба поддержки всегда готова помочь вам</p>
                        <a href="#" class="btn-help">
                            <i class="fas fa-headset"></i> Связаться с поддержкой
                        </a>
                    </div>
                </aside>

                <!-- Основной контент -->
                <div class="profile-content">
                    <!-- Секция профиля -->
                    <div id="profile-section" class="profile-section active">
                        <div class="section-header">
                            <h2><i class="fas fa-user-circle"></i> Личная информация</h2>
                            <p>Управление вашими персональными данными</p>
                        </div>
                        
                        <?php if ($updateSuccess): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Профиль успешно обновлен!
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($updateError): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $updateError; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" class="profile-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Полное имя
                                    </label>
                                    <input type="text" 
                                           name="name" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($userData['name']); ?>"
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i> Email
                                    </label>
                                    <input type="email" 
                                           name="email" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($userData['email']); ?>"
                                           required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i> Телефон
                                    </label>
                                    <input type="tel" 
                                           name="phone" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user-tag"></i> Роль
                                    </label>
                                    <input type="text" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars(ucfirst($userData['role'])); ?>"
                                           disabled>
                                    <span class="form-hint"><?php echo $userData['role'] == 'admin' ? 'Администратор' : ($userData['role'] == 'manager' ? 'Менеджер' : 'Пользователь'); ?></span>
                                </div>
                            </div>
                            
                            <div class="form-divider">
                                <span>Смена пароля</span>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-lock"></i> Текущий пароль
                                    </label>
                                    <input type="password" 
                                           name="current_password" 
                                           class="form-input">
                                    <span class="form-hint">Оставьте пустым, если не хотите менять пароль</span>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-key"></i> Новый пароль
                                    </label>
                                    <input type="password" 
                                           name="new_password" 
                                           class="form-input">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-key"></i> Подтвердите пароль
                                    </label>
                                    <input type="password" 
                                           name="confirm_password" 
                                           class="form-input">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Сохранить изменения
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Отмена
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Секция заказов -->
                    <div id="orders-section" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-shopping-bag"></i> История заказов</h2>
                            <p>Все ваши покупки в одном месте</p>
                        </div>
                        
                        <?php if (empty($orders)): ?>
                            <div class="empty-section">
                                <div class="empty-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <h3>У вас еще нет заказов</h3>
                                <p>Совершите первую покупку, чтобы увидеть историю заказов здесь</p>
                                <a href="catalog.php" class="btn btn-primary">
                                    <i class="fas fa-shoe-prints"></i> Перейти в каталог
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($orders as $order): ?>
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="order-info">
                                                <h3>Заказ #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                                <div class="order-meta">
                                                    <span class="order-date">
                                                        <i class="far fa-calendar"></i>
                                                        <?php echo date('d.m.Y', strtotime($order['created_at'])); ?>
                                                    </span>
                                                    <span class="order-items">
                                                        <i class="fas fa-cube"></i>
                                                        <?php echo $order['items_count']; ?> товар(ов)
                                                    </span>
                                                    <span class="order-total">
                                                        <i class="fas fa-ruble-sign"></i>
                                                        <?php echo number_format($order['order_total'] ?? 0, 0, '', ' '); ?> ₽
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="order-status">
                                                <?php 
                                                $status = $order['status'];
                                                $statusInfo = $statusIcons[$status] ?? $statusIcons['created'];
                                                ?>
                                                <span class="status-badge" style="background-color: <?php echo $statusInfo['color']; ?>20; color: <?php echo $statusInfo['color']; ?>;">
                                                    <i class="<?php echo $statusInfo['icon']; ?>"></i>
                                                    <?php echo $statusInfo['text']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="order-details">
                                            <div class="order-actions">
                                                <a href="order.php?id=<?php echo $order['id']; ?>" class="btn-order-action">
                                                    <i class="fas fa-eye"></i> Подробнее
                                                </a>
                                                <button class="btn-order-action" onclick="repeatOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-redo"></i> Повторить заказ
                                                </button>
                                                <?php if ($order['status'] == 'created' || $order['status'] == 'paid'): ?>
                                                    <button class="btn-order-action cancel" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-times"></i> Отменить
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="pagination">
                                <a href="#" class="page-nav disabled">
                                    <i class="fas fa-chevron-left"></i> Назад
                                </a>
                                <a href="#" class="page-number active">1</a>
                                <a href="#" class="page-number">2</a>
                                <a href="#" class="page-number">3</a>
                                <a href="#" class="page-nav">
                                    Далее <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Секция адресов -->
                    <div id="addresses-section" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-map-marker-alt"></i> Адреса доставки</h2>
                            <p>Управление вашими адресами доставки</p>
                        </div>
                        
                        <div class="addresses-grid">
                            <div class="address-card">
                                <div class="address-header">
                                    <h3>Основной адрес</h3>
                                    <span class="address-default">По умолчанию</span>
                                </div>
                                <div class="address-content">
                                    <p><strong>Иван Иванов</strong></p>
                                    <p>ул. Примерная, д. 123, кв. 45</p>
                                    <p>Москва, 123456</p>
                                    <p>Россия</p>
                                    <p><i class="fas fa-phone"></i> +7 (999) 123-45-67</p>
                                </div>
                                <div class="address-actions">
                                    <button class="btn-address-action">
                                        <i class="fas fa-edit"></i> Изменить
                                    </button>
                                    <button class="btn-address-action remove">
                                        <i class="fas fa-trash"></i> Удалить
                                    </button>
                                </div>
                            </div>
                            
                            <div class="address-card add-new">
                                <div class="add-address">
                                    <i class="fas fa-plus-circle"></i>
                                    <h3>Добавить новый адрес</h3>
                                    <p>Добавьте дополнительный адрес для доставки</p>
                                    <button class="btn btn-primary" onclick="showAddressForm()">
                                        <i class="fas fa-plus"></i> Добавить адрес
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Секция избранного -->
                    <div id="wishlist-section" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-heart"></i> Избранное</h2>
                            <p>Товары, которые вы сохранили для покупки позже</p>
                        </div>
                        
                        <div class="wishlist-grid">
                            <div class="wishlist-empty">
                                <div class="empty-icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <h3>Ваш список избранного пуст</h3>
                                <p>Добавляйте понравившиеся товары в избранное, чтобы не потерять их</p>
                                <a href="catalog.php" class="btn btn-primary">
                                    <i class="fas fa-shoe-prints"></i> Найти товары
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Секция настроек -->
                    <div id="settings-section" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-cog"></i> Настройки аккаунта</h2>
                            <p>Управление настройками вашего аккаунта</p>
                        </div>
                        
                        <div class="settings-grid">
                            <div class="setting-card">
                                <div class="setting-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="setting-content">
                                    <h3>Уведомления</h3>
                                    <p>Управление email и push-уведомлениями</p>
                                </div>
                                <div class="setting-switch">
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="setting-card">
                                <div class="setting-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="setting-content">
                                    <h3>Рассылка</h3>
                                    <p>Новости, акции и специальные предложения</p>
                                </div>
                                <div class="setting-switch">
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="setting-card">
                                <div class="setting-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="setting-content">
                                    <h3>Безопасность</h3>
                                    <p>Двухфакторная аутентификация и история входов</p>
                                </div>
                                <a href="#" class="btn-setting-action">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            
                            <div class="setting-card">
                                <div class="setting-icon">
                                    <i class="fas fa-trash"></i>
                                </div>
                                <div class="setting-content">
                                    <h3>Удаление аккаунта</h3>
                                    <p>Безвозвратное удаление вашего аккаунта и всех данных</p>
                                </div>
                                <button class="btn-setting-action delete" onclick="showDeleteConfirm()">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
        // Переключение секций профиля
        function showSection(sectionId) {
            // Скрываем все секции
            document.querySelectorAll('.profile-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Показываем выбранную секцию
            document.getElementById(sectionId + '-section').classList.add('active');
            
            // Обновляем активную навигацию
            document.querySelectorAll('.profile-nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            event.target.closest('.profile-nav-item').classList.add('active');
            
            return false;
        }
        
        // Повтор заказа
        function repeatOrder(orderId) {
            if (confirm('Повторить этот заказ?')) {
                // AJAX запрос для повторения заказа
                fetch('api/repeat_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ order_id: orderId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Заказ добавлен в корзину!');
                        window.location.href = 'cart.php';
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
        
        // Отмена заказа
        function cancelOrder(orderId) {
            if (confirm('Вы уверены, что хотите отменить этот заказ?')) {
                fetch('api/cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ order_id: orderId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Заказ отменен!');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
        
        // Показать форму добавления адреса
        function showAddressForm() {
            alert('Форма добавления адреса будет реализована позже');
        }
        
        
        // Подтверждение удаления аккаунта
        function showDeleteConfirm() {
            if (confirm('ВНИМАНИЕ: Это действие удалит ваш аккаунт и все данные без возможности восстановления. Продолжить?')) {
                alert('Удаление аккаунта будет реализовано позже');
            }
        }
        
        // Обновление счетчика корзины
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
        
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
    </script>
</body>
</html>