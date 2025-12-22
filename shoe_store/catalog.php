<?php

// Включить отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Получаем параметры фильтров
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$brand = $_GET['brand'] ?? '';
$color = $_GET['color'] ?? '';

// Формируем фильтры для функции
$filters = [];
if (!empty($search)) $filters['search'] = $search;
if (!empty($category)) $filters['category'] = $category;
if (!empty($price_min)) $filters['price_min'] = $price_min;
if (!empty($price_max)) $filters['price_max'] = $price_max;
if (!empty($brand)) $filters['brand'] = $brand;
if (!empty($color)) $filters['color'] = $color;

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Получаем товары
$products = getProducts($filters, $limit, $offset);

// Получаем общее количество товаров для пагинации
$total_products = count(getProducts($filters, 0, 0));
$total_pages = ceil($total_products / $limit);

// Получаем уникальные бренды, цвета и категории для фильтров
$brands = [];
$colors = [];
$categories = [];

try {
    $conn = getDBConnection();
    
    if ($conn && $conn instanceof mysqli) {
        // Бренды
        $brands_result = $conn->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");
        if ($brands_result) {
            $brands = $brands_result->fetch_all(MYSQLI_ASSOC);
            $brands_result->free();
        }
        
        // Цвета
        $colors_result = $conn->query("SELECT DISTINCT color FROM products WHERE color IS NOT NULL AND color != '' ORDER BY color");
        if ($colors_result) {
            $colors = $colors_result->fetch_all(MYSQLI_ASSOC);
            $colors_result->free();
        }
        
        // Категории
        $categories_result = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");
        if ($categories_result) {
            $categories = $categories_result->fetch_all(MYSQLI_ASSOC);
            $categories_result->free();
        }
        
        $conn->close();
    } else {
        throw new Exception("Не удалось подключиться к базе данных");
    }
} catch (Exception $e) {
    // Продолжаем работу даже при ошибке БД
    error_log("Ошибка базы данных в каталоге: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог обуви - SneakerStore</title>
    <meta name="description" content="Каталог качественной обуви от ведущих брендов. Фильтры по цене, размеру, цвету и категории.">
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
                        <li><a href="catalog.php" class="active"><i class="fas fa-shoe-prints"></i> Каталог</a></li>
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
            <!-- Хлебные крошки -->
            <div class="breadcrumb mt-20">
                <a href="index.php">Главная</a> / <span>Каталог</span>
            </div>

            <!-- Заголовок каталога -->
            <div class="catalog-header mt-40">
                <div class="page-header">
                    <h1>Каталог обуви</h1>
                    <p>Найдено <?php echo $total_products; ?> товаров</p>
                </div>
                
                <div class="catalog-controls">
                    <div class="view-controls">
                        <button class="view-btn active" onclick="changeView('grid')">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="view-btn" onclick="changeView('list')">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    
                    <div class="sort-controls">
                        <label>Сортировка:</label>
                        <select id="sort-select" onchange="sortProducts()">
                            <option value="newest">По новизне</option>
                            <option value="price_asc">По возрастанию цены</option>
                            <option value="price_desc">По убыванию цены</option>
                            <option value="popular">По популярности</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="catalog-content">
                <!-- Боковая панель фильтров -->
                <aside class="filters-sidebar">
                    <div class="filters-header">
                        <h3><i class="fas fa-filter"></i> Фильтры</h3>
                        <?php if (!empty($search) || !empty($category) || !empty($price_min) || !empty($price_max) || !empty($brand) || !empty($color)): ?>
                            <a href="catalog.php" class="clear-filters">Сбросить все</a>
                        <?php endif; ?>
                    </div>
                    
                    <form method="get" class="filters-form">
                        <!-- Поиск -->
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-search"></i> Поиск
                            </label>
                            <div class="search-filter">
                                <input type="text" 
                                       name="search" 
                                       placeholder="Название товара..." 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       class="filter-input">
                                <button type="submit" class="search-filter-btn">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        
                        <!-- Цена -->
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-tag"></i> Цена, ₽
                            </label>
                            <div class="price-range">
                                <input type="number" 
                                       name="price_min" 
                                       placeholder="от 0" 
                                       value="<?php echo htmlspecialchars($price_min); ?>"
                                       class="price-input" min="0">
                                <span class="range-separator">—</span>
                                <input type="number" 
                                       name="price_max" 
                                       placeholder="до 50000" 
                                       value="<?php echo htmlspecialchars($price_max); ?>"
                                       class="price-input" min="0">
                            </div>
                        </div>

                        <!-- Категории -->
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-shoe-prints"></i> Категория
                            </label>
                            <div class="category-filters">
                                <?php if (!empty($categories)): ?>
                                    <label class="checkbox-filter">
                                        <input type="radio" 
                                               name="category" 
                                               value=""
                                               <?php echo empty($category) ? 'checked' : ''; ?>
                                               onchange="this.form.submit()">
                                        <span class="checkbox-custom"></span>
                                        <span class="filter-text">Все категории</span>
                                    </label>
                                    <?php foreach ($categories as $cat): ?>
                                        <label class="checkbox-filter">
                                            <input type="radio" 
                                                   name="category" 
                                                   value="<?php echo htmlspecialchars($cat['category']); ?>"
                                                   <?php echo ($category == $cat['category']) ? 'checked' : ''; ?>
                                                   onchange="this.form.submit()">
                                            <span class="checkbox-custom"></span>
                                            <span class="filter-text"><?php echo htmlspecialchars($cat['category']); ?></span>
                                            <span class="filter-count"><?php echo getCategoryCount($cat['category']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="filter-empty">Нет категорий</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Бренды -->
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-copyright"></i> Бренд
                            </label>
                            <div class="brand-filters">
                                <?php if (!empty($brands)): ?>
                                    <label class="checkbox-filter">
                                        <input type="radio" 
                                               name="brand" 
                                               value=""
                                               <?php echo empty($brand) ? 'checked' : ''; ?>
                                               onchange="this.form.submit()">
                                        <span class="checkbox-custom"></span>
                                        <span class="filter-text">Все бренды</span>
                                    </label>
                                    <?php foreach ($brands as $brand_item): ?>
                                        <label class="checkbox-filter">
                                            <input type="radio" 
                                                   name="brand" 
                                                   value="<?php echo htmlspecialchars($brand_item['brand']); ?>"
                                                   <?php echo ($brand == $brand_item['brand']) ? 'checked' : ''; ?>
                                                   onchange="this.form.submit()">
                                            <span class="checkbox-custom"></span>
                                            <span class="filter-text"><?php echo htmlspecialchars($brand_item['brand']); ?></span>
                                            <span class="filter-count"><?php echo getBrandCount($brand_item['brand']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="filter-empty">Нет брендов</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Цвета -->
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-palette"></i> Цвет
                            </label>
                            <div class="color-filters">
                                <?php if (!empty($colors)): ?>
                                    <label class="color-filter" title="Все цвета">
                                        <input type="radio" 
                                               name="color" 
                                               value=""
                                               <?php echo empty($color) ? 'checked' : ''; ?>
                                               onchange="this.form.submit()">
                                        <span class="color-circle" style="background-color: #FFFFFF; border: 2px solid #ddd;">Все</span>
                                    </label>
                                    <?php foreach ($colors as $color_item): ?>
                                        <label class="color-filter" 
                                               title="<?php echo htmlspecialchars($color_item['color']); ?>">
                                            <input type="radio" 
                                                   name="color" 
                                                   value="<?php echo htmlspecialchars($color_item['color']); ?>"
                                                   <?php echo ($color == $color_item['color']) ? 'checked' : ''; ?>
                                                   onchange="this.form.submit()">
                                            <span class="color-circle" style="background-color: <?php echo getColorHex($color_item['color']); ?>"></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="filter-empty">Нет цветов</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Кнопки фильтра -->
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-check"></i> Применить фильтры
                            </button>
                        </div>
                    </form>
                </aside>

                <!-- Основной контент каталога -->
                <div class="catalog-main">
                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h2 class="empty-title">Товары не найдены</h2>
                            <p class="empty-description">Попробуйте изменить параметры поиска или сбросить фильтры</p>
                            <a href="catalog.php" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Сбросить фильтры
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Сетка товаров -->
                        <div class="product-grid" id="product-grid">
                            <?php foreach ($products as $product): ?>
                                <div class="product-card fade-in">
                                    <?php if (rand(0, 1)): ?>
                                        <div class="product-badge"><?php echo rand(0, 1) ? 'Хит' : 'Новинка'; ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="product-image">
                                        <img src="assets/images/<?php echo htmlspecialchars($product['image_url'] ?? 'default.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             onerror="this.src='assets/images/default.jpg'">
                                        <div class="product-overlay">
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-eye"></i> Посмотреть
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="product-info">
                                        <span class="product-category"><?php echo htmlspecialchars($product['category'] ?? 'Без категории'); ?></span>
                                        <h3 class="product-title">
                                            <a href="product.php?id=<?php echo $product['id']; ?>">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </h3>
                                        <p class="product-description-short">
                                            <?php 
                                            $description = $product['description'] ?? 'Описание отсутствует';
                                            echo mb_substr(htmlspecialchars($description), 0, 60); 
                                            if (mb_strlen($description) > 60): ?>...<?php endif; ?>
                                        </p>
                                        
                                        <div class="product-price">
                                            <span class="price-current"><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</span>
                                        </div>
                                        
                                        <div class="product-rating-small">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= rand(3,5) ? 'active' : ''; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="rating-count">(<?php echo rand(10, 150); ?>)</span>
                                        </div>
                                        
                                        <div class="product-actions">
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                            <button class="btn btn-primary" onclick="quickAddToCart(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                            <button class="btn btn-outline" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                                <i class="far fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Пагинация -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination mt-40">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo buildQuery(['page' => $page - 1]); ?>" class="page-nav">
                                        <i class="fas fa-chevron-left"></i> Назад
                                    </a>
                                <?php endif; ?>
                                
                                <div class="page-numbers">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                            <a href="?<?php echo buildQuery(['page' => $i]); ?>" 
                                               class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                            <span class="page-dots">...</span>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo buildQuery(['page' => $page + 1]); ?>" class="page-nav">
                                        Далее <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
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
        function changeView(view) {
            const grid = document.getElementById('product-grid');
            const buttons = document.querySelectorAll('.view-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            if (view === 'list') {
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
            }
        }
        
        function sortProducts() {
            const sortSelect = document.getElementById('sort-select');
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortSelect.value);
            window.location.href = url.toString();
        }
        
        function quickAddToCart(productId) {
            window.location.href = `product.php?id=${productId}`;
        }
        
        function addToWishlist(productId) {
            alert('Товар добавлен в избранное!');
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