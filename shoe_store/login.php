<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (loginUser($email, $password)) {
        require_once 'includes/cart_functions.php';
        mergeCarts($_SESSION['user_id']);
        
        // Редирект на предыдущую страницу или на главную
        $return_to = $_SESSION['return_to'] ?? 'index.php';
        unset($_SESSION['return_to']);
        header('Location: ' . $return_to);
        exit();
    } else {
        $error = "Неверный email или пароль";
    }
    
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в аккаунт - SneakerStore</title>
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
                <div class="auth-image">
                    <img src="https://images.unsplash.com/photo-1560343090-f0409e92791a?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                         alt="Обувь">
                    <div class="auth-overlay">
                        <h2>С возвращением!</h2>
                        <p>Войдите в свой аккаунт, чтобы продолжить покупки</p>
                    </div>
                </div>
                
                <div class="auth-form">
                    <div class="form-header">
                        <a href="index.php" class="back-link">
                            <i class="fas fa-arrow-left"></i> На главную
                        </a>
                        <h1>Вход в аккаунт</h1>
                        <p>Введите свои данные для входа</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" class="auth-form-content">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-envelope"></i> Email адрес
                            </label>
                            <input type="email" name="email" class="form-input" required 
                                   placeholder="example@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-lock"></i> Пароль
                            </label>
                            <input type="password" name="password" class="form-input" required 
                                   placeholder="Введите пароль">
                            <div class="password-toggle">
                                <button type="button" class="toggle-btn" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-options">
                            <label class="checkbox">
                                <input type="checkbox" name="remember">
                                <span>Запомнить меня</span>
                            </label>
                            <a href="#" class="forgot-link">Забыли пароль?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-large btn-block">
                            <i class="fas fa-sign-in-alt"></i> Войти в аккаунт
                        </button>
                        
                        <div class="auth-divider">
                            <span>или войти через</span>
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
                            <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                        </div>
                    </form>
                </div>
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
        
        // Инициализация счетчика корзины
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