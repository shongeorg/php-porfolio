<?php
/**
 * Admin login page
 */
// If already logged in, redirect to admin dashboard
if (adminIsLoggedIn()) {
    header('Location: /?page=admin');
    exit;
}

$errors = [];
$username = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $errors[] = 'Введите имя пользователя';
    }
    
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    }
    
    if (empty($errors)) {
        $admin = adminVerifyPassword($username, $password);
        
        if ($admin) {
            adminLogin($admin);
            header('Location: /?page=admin');
            exit;
        } else {
            $errors[] = 'Неверное имя пользователя или пароль';
        }
    }
}
?>

<section class="auth">
    <div class="auth__container">
        <div class="auth__card">
            <h1 class="auth__title">Вход для администратора</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="auth__errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="error-message">❌ <?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form class="auth__form" method="POST" action="/?page=admin-login">
                <div class="form-group">
                    <label class="form-group__label" for="username">Имя пользователя</label>
                    <input 
                        class="form-group__input" 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?= htmlspecialchars($username) ?>"
                        placeholder="admin"
                        required
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-group__label" for="password">Пароль</label>
                    <input 
                        class="form-group__input" 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••"
                        required
                    >
                </div>
                
                <button type="submit" class="auth__submit">
                    Войти
                </button>
            </form>
            
            <div class="auth__footer">
                <a href="/?page=portfolio" class="auth__link">← Вернуться на сайт</a>
            </div>
        </div>
    </div>
</section>
