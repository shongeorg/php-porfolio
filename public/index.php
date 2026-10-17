<?php

// Herd (як і Valet) використовує папку public/ як корінь сайту,
// якщо вона існує. Цей файл — front controller, який підключає
// основний index.php з кореня проєкту.
require dirname(__DIR__) . '/index.php';
