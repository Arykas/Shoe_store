// Управление корзиной для гостей (localStorage)
let cart = JSON.parse(localStorage.getItem('cart')) || { items: [] };

function addToCart(productId, sizeId, quantity) {
    const item = cart.items.find(i => i.product_id == productId && i.size_id == sizeId);
    if (item) {
        item.quantity += quantity;
    } else {
        cart.items.push({ product_id: productId, size_id: sizeId, quantity });
    }
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
}

function updateCartDisplay() {
    // Обновить UI корзины (например, счетчик товаров)
    const count = cart.items.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cart-count').textContent = count;
}

// Для AJAX (авторизованные пользователи)
function ajaxAddToCart(productId, sizeId, quantity) {
    fetch('/api/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, size_id: sizeId, quantity })
    }).then(response => response.json()).then(data => {
        if (data.success) updateCartDisplay();
    });
}
