<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$products = getProducts([], 12, 0);

// Функция для получения пути к изображению товара
function getProductImage($product, $index = 0) {
    // Если у товара есть свое изображение - используем его
    if (!empty($product['image_url'])) {
        return 'assets/images/' . htmlspecialchars($product['image_url']);
    }
    
    // Иначе используем дефолтные изображения из папки default
    $defaultImages = [
        'default/shoe1.jpg',
        'default/shoe2.jpg',
        'default/shoe3.jpg',
        'default/default.jpg'
    ];
    
    
    // Выбираем дефолтную картинку на основе ID товара или индекса
    $imageIndex = ($product['id'] ?? $index) % count($defaultImages);
    $defaultImage = $defaultImages[$imageIndex];
    
    // Проверяем существование файла
    if (file_exists('assets/images/' . $defaultImage)) {
        return 'assets/images/' . $defaultImage;
    }
    
    // Если файл не найден, используем базовый default.jpg
    return 'assets/images/default.jpg';
}

// Функция для получения иконки категории
function getCategoryIcon($category) {
    $icons = [
        'мужская' => '👞',
        'женская' => '👠',
        'детская' => '👟',
        'спортивная' => '🏃',
        'унисекс' => '👟'
    ];
    return $icons[$category] ?? '👟';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>SneakerStore - Магазин качественной обуви</title>
    <meta name="description" content="Магазин качественной обуви от ведущих брендов. Бесплатная доставка по всей России.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Стили для дефолтных изображений */
        .product-image-placeholder {
            position: relative;
            overflow: hidden;
        }
        
        .product-image-placeholder img {
            transition: transform 0.3s ease;
        }
        
        .product-image-placeholder:hover img {
            transform: scale(1.05);
        }
        
        .placeholder-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
            padding: 20px;
            text-align: center;
        }
        
        .product-image-placeholder:hover .placeholder-overlay {
            opacity: 1;
        }
        
        .placeholder-overlay .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .placeholder-overlay .text {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .placeholder-label {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
            z-index: 2;
        }
    </style>
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
                        <li><a href="index.php" class="active"><i class="fas fa-home"></i> Главная</a></li>
                        <li><a href="catalog.php"><i class="fas fa-shoe-prints"></i> Каталог</a></li>
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

    <!-- Герой-секция -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content fade-in">
                <h1 class="hero-title">Найдите свою идеальную пару</h1>
                <p class="hero-subtitle">Более 1000 моделей обуви от ведущих мировых брендов. Бесплатная доставка по всей России.</p>
                <div class="hero-search">
                    <input type="text" placeholder="Поиск обуви..." class="search-input">
                    <button class="search-btn"><i class="fas fa-search"></i></button>
                </div>
                <a href="catalog.php" class="btn btn-primary btn-large mt-20">
                    Смотреть каталог <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <main>
        <div class="container">
            <!-- Популярные категории -->
            <section class="categories-section mt-40">
                <div class="page-header">
                    <h2>Популярные категории</h2>
                    <p>Выберите обувь для любого случая</p>
                </div>
                
                <div class="categories-grid">
                    <a href="catalog.php?category=мужская" class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-male"></i>
                        </div>
                        <h3>Мужская обувь</h3>
                        <p>Кроссовки, ботинки, туфли</p>
                        <span class="category-count">128 моделей</span>
                    </a>
                    
                    <a href="catalog.php?category=женская" class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-female"></i>
                        </div>
                        <h3>Женская обувь</h3>
                        <p>Туфли, босоножки, сапоги</p>
                        <span class="category-count">96 моделей</span>
                    </a>
                    
                    <a href="catalog.php?category=спортивная" class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-running"></i>
                        </div>
                        <h3>Спортивная обувь</h3>
                        <p>Кроссовки, кеды, бутсы</p>
                        <span class="category-count">74 модели</span>
                    </a>
                    
                    <a href="catalog.php?category=детская" class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-child"></i>
                        </div>
                        <h3>Детская обувь</h3>
                        <p>Кроссовки, сандалии, сапоги</p>
                        <span class="category-count">52 модели</span>
                    </a>
                </div>
            </section>

            <!-- Популярные товары -->
            <section class="products-section mt-40">
                <div class="section-header">
                    <div>
                        <h2>Популярные товары</h2>
                        <p>Самые востребованные модели этой недели</p>
                    </div>
                    <a href="catalog.php" class="btn btn-secondary">Все товары <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="product-grid">
                    <?php foreach ($products as $index => $product): ?>
                        <div class="product-card fade-in">
                            <div class="product-badge">Хит продаж</div>
                            
                            <div class="product-image product-image-placeholder">
                                <img src="<?php echo getProductImage($product, $index); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onerror="this.src='assets/images/default.jpg'">
                                
                                <?php if (empty($product['image_url'])): ?>
                                    <div class="placeholder-label">
                                        <i class="fas fa-image"></i> Фото
                                    </div>
                                    <div class="placeholder-overlay">
                                        <div class="icon"><?php echo getCategoryIcon($product['category'] ?? ''); ?></div>
                                        <div class="text"><?php echo htmlspecialchars($product['name']); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-overlay">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Посмотреть
                                    </a>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <span class="product-category"><?php echo htmlspecialchars($product['category'] ?? 'Обувь'); ?></span>
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="product-price">
                                    <span class="price-current"><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</span>
                                </div>
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                    <button class="btn btn-primary" onclick="quickAddToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Преимущества -->
            <section class="features-section mt-40">
                <div class="page-header">
                    <h2>Почему выбирают нас</h2>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h3>Бесплатная доставка</h3>
                        <p>По всей России при заказе от 5000 ₽</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Гарантия качества</h3>
                        <p>30 дней на возврат и обмен товара</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>Поддержка 24/7</h3>
                        <p>Консультации и помощь в выборе</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <h3>Скидки и акции</h3>
                        <p>Регулярные распродажи и промокоды</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Футер -->
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

    <script src="assets/js/cart.js"></script>
    <script>
        function quickAddToCart(productId) {
            // Простое добавление без выбора размера (откроет страницу товара)
            window.location.href = `product.php?id=${productId}`;
        }
        
        // Инициализация счетчика корзины
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
        
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart')) || { items: [] };
            const total = cart.items.reduce((sum, item) => sum + item.quantity, 0);
            const countElement = document.getElementById('cart-count');
            if (countElement) {
                countElement.textContent = total;
            }
        }
    </script>
</body>
</html>