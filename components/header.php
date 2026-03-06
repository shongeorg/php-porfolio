<header class="header">
    <div class="header__container">
        <a href="/?page=portfolio" class="header__logo">😎 Portfolio</a>
        <nav class="header__nav">
            <a href="/?page=portfolio" class="header__link">Проекты</a>
            <?php if (adminIsLoggedIn()): ?>
                <a href="/?page=admin" class="header__link">Админка</a>
                <a href="/?page=admin-logout" class="header__link">Выйти</a>
            <?php else: ?>
                <a href="/?page=admin-login" class="header__link">Вход</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
