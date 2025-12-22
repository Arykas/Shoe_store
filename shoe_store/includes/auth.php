<?php
require_once 'database.php';

// Автоматически запускаем сессию только если она еще не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Хэширование пароля
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Проверка пароля
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Регистрация пользователя
function registerUser($name, $email, $phone, $password) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $hashedPassword = hashPassword($password);
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Авторизация
function loginUser($email, $password) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $role);
    
    if ($stmt->fetch() && verifyPassword($password, $hash)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['role'] = $role;
        $stmt->close();
        $conn->close();
        return true;
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Получение роли
function getUserRole() {
    return $_SESSION['role'] ?? 'guest';
}

// Выход
function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}


// Получение данных пользователя
function getUserData($userId) {
    $conn = getDBConnection();
    if (!$conn) return null;
    
    $stmt = $conn->prepare("SELECT name, email, phone, role FROM users WHERE id = ?");
    if (!$stmt) {
        $conn->close();
        return null;
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Проверка существования email
function emailExists($email) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    $conn->close();
    
    return $exists;
}

// Обновление профиля пользователя
function updateUserProfile($userId, $name, $email, $phone) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("sssi", $name, $email, $phone, $userId);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}
?>