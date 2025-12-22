<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация
    if (empty($name)) {
        $errors[] = "Имя обязательно для заполнения";
    }
    
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Введите корректный email";
    }
    
    if (empty($phone)) {
        $errors[] = "Телефон обязателен для заполнения";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают";
    }
    
    if (empty($errors)) {
        if (registerUser($name, $email, $phone, $password)) {
            $success = true;
        } else {
            $errors[] = "Ошибка при регистрации. Возможно, email уже используется.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - SneakerStore</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
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
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="auth-container fade-in">
                <?php if ($success): ?>
                    <div class="success-container">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h1>Регистрация успешна!</h1>
                        <p>Ваш аккаунт был успешно создан. Теперь вы можете войти в систему.</p>
                        <div class="success-actions">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Войти в аккаунт
                            </a>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-home"></i> На главную
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-image">
                        <img src="https://images.unsplash.com/photo-1549298916-b41d501d3772?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                             alt="Новая обувь">
                        <div class="auth-overlay">
                            <h2>Присоединяйтесь к нам!</h2>
                            <p>Получайте скидки, отслеживайте заказы и создавайте список желаний</p>
                        </div>
                    </div>
                    
                    <div class="auth-form">
                        <div class="form-header">
                            <a href="index.php" class="back-link">
                                <i class="fas fa-arrow-left"></i> На главную
                            </a>
                            <h1>Создать аккаунт</h1>
                            <p>Заполните форму для регистрации</p>
                        </div>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php foreach ($errors as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" class="auth-form-content">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i> Полное имя
                                </label>
                                <input type="text" name="name" class="form-input" required 
                                       placeholder="Иван Иванов" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-envelope"></i> Email адрес
                                </label>
                                <input type="email" name="email" class="form-input" required 
                                       placeholder="example@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-phone"></i> Телефон
                                </label>
                                <input type="tel" name="phone" class="form-input" required 
                                       placeholder="+7 (999) 123-45-67" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-lock"></i> Пароль
                                </label>
                                <input type="password" name="password" class="form-input" required 
                                       placeholder="Минимум 6 символов">
                                <div class="password-toggle">
                                    <button type="button" class="toggle-btn" onclick="togglePassword(this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-lock"></i> Подтвердите пароль
                                </label>
                                <input type="password" name="confirm_password" class="form-input" required 
                                       placeholder="Повторите пароль">
                                <div class="password-toggle">
                                    <button type="button" class="toggle-btn" onclick="togglePassword(this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-options">
                                <label class="checkbox">
                                    <input type="checkbox" name="terms" required>
                                    <span>Я принимаю <a href="#" class="terms-link">условия использования</a></span>
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="newsletter" checked>
                                    <span>Подписаться на новости и акции</span>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-large btn-block">
                                <i class="fas fa-user-plus"></i> Зарегистрироваться
                            </button>
                            
                            <div class="auth-divider">
                                <span>или зарегистрироваться через</span>
                            </div>
                            
                            <div class="social-auth">
                                <button type="button" class="btn btn-outline btn-block">
                                    <i class="fab fa-google"></i> Google
                                </button>
                                <button type="button" class="btn btn-outline btn-block">
                                    <i class="fab fa-vk"></i> ВКонтакте
                                </button>
                            </div>
                            
                            <div class="auth-footer">
                                <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>SneakerStore</h3>
                    <p>Магазин качественной обуви с доставкой по всей России.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 SneakerStore. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
        function togglePassword(button) {
            const input = button.closest('.form-group').querySelector('input[type="password"]');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const cart = JSON.parse(localStorage.getItem('cart')) || { items: [] };
            const total = cart.items.reduce((sum, item) => sum + item.quantity, 0);
            const countElement = document.getElementById('cart-count');
            if (countElement) {
                countElement.textContent = total;
            }
        });
    </script>
</body>
</html>