<header class="header">
    <div class="header__container">
        <a href="/?page=portfolio" class="header__logo">😎 Портфоліо</a>
        <nav class="header__nav">
            <a href="/?page=portfolio" class="header__link">Проєкти</a>
            <?php if (adminIsLoggedIn()): ?>
                <a href="/?page=admin" class="header__link">Адмінка</a>
                <a href="/?page=admin-logout" class="header__link">Вийти</a>
            <?php else: ?>
                <a href="/?page=admin-login" class="header__link">Вхід</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
