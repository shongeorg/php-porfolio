<?php
// Генерация хеша для пароля админа
// Запусти этот файл один раз в браузере: https://pan-kit.infinityfreeapp.com/generate-hash.php

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Хеш для пароля '{$password}':\n";
echo $hash . "\n\n";

echo "SQL запрос для обновления:\n";
echo "UPDATE `admin` SET `password` = '{$hash}' WHERE `username` = 'admin';";
