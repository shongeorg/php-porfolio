<?php
/**
 * Security helpers: CSRF protection
 * Проста імплементація токенів на базі сесії
 */

/**
 * Отримати (або створити) CSRF-токен сесії
 * @return string
 */
function csrfToken(): string
{
    if (function_exists('ensureSession')) {
        ensureSession();
    } elseif (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * HTML hidden-поле з CSRF-токеном
 * @return string
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Перевірити CSRF-токен із POST-запиту
 * @return bool
 */
function csrfVerify(): bool
{
    if (function_exists('ensureSession')) {
        ensureSession();
    } elseif (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $posted = $_POST['csrf_token'] ?? '';
    return is_string($posted)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $posted);
}
