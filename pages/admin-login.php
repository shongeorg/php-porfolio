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
        $errors[] = "Введіть ім'я користувача";
    }

    if (empty($password)) {
        $errors[] = 'Введіть пароль';
    }

    if (empty($errors)) {
        $admin = adminVerifyPassword($username, $password);

        if ($admin) {
            adminLogin($admin);
            header('Location: /?page=admin');
            exit;
        } else {
            $errors[] = "Невірне ім'я користувача або пароль";
        }
    }
}
?>

<section class="auth">
    <div class="auth__container">
        <div class="auth__card">
            <h1 class="auth__title">Вхід для адміністратора</h1>

            <?php if (!empty($errors)): ?>
                <div class="auth__errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="error-message">❌ <?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="auth__form" method="POST" action="/?page=admin-login">
                <div class="form-group">
                    <label class="form-group__label" for="username">Ім'я користувача</label>
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
                    Увійти
                </button>
            </form>

            <div class="auth__footer">
                <a href="/?page=portfolio" class="auth__link">← Повернутися на сайт</a>
            </div>
        </div>
    </div>
</section>
