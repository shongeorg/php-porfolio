<?php

// Сесія стартує ДО будь-якого виводу, щоб не ловити
// "session cannot be started after headers sent" у header.php/seo-тегах
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DOC_ROOT обчислюємо від розташування index.php, а НЕ від DOCUMENT_ROOT:
// корінь сайту в Herd може бути public/, тоді DOCUMENT_ROOT вказує туди.
define("DOC_ROOT", __DIR__ . "/");

// Префікс шляхів для ассетів (/public/...), залежно від того,
// що є коренем у поточному сервері: корінь проєкту чи public/.
define(
    "ASSET_BASE",
    (realpath($_SERVER["DOCUMENT_ROOT"] ?? "") === __DIR__) ? "/public/" : "/"
);

const PAGE_PATH = DOC_ROOT . "pages/";
const COMPONENTS_PATH = DOC_ROOT . "components/";
const DATA_PATH = DOC_ROOT . "data/";
const FUNC_PATH = DOC_ROOT . "functions/";

$page = $_GET["page"] ?? "portfolio";
$file = $page . ".php";
$page_exists = file_exists(PAGE_PATH . $file);
$render_page = PAGE_PATH . ($page_exists ? $file : '404.php');

// Невідома сторінка — віддаємо чесний 404, а не 200 з текстом "not found".
if (!$page_exists) {
    http_response_code(404);
}

// Для SEO-тегів у невідомої сторінки використовуємо набір "404".
$seo_page = $page_exists ? $page : '404';

include FUNC_PATH . 'data-base.php';
include FUNC_PATH . 'security.php';
include FUNC_PATH . 'seo.php';
include FUNC_PATH . 'github-live.php';
?>


<!doctype html>
<html lang="uk">
<head>
    <?php seoRenderTags($seo_page); ?>
    <link rel="stylesheet" href="<?= ASSET_BASE ?>index.css">
</head>
<body data-theme="<?= githubLiveTheme() ?>">
<?php require COMPONENTS_PATH . 'header.php'; ?>
<main><?php require $render_page; ?></main>
<?php require COMPONENTS_PATH . 'footer.php'; ?>


<script src="<?= ASSET_BASE ?>index.js" defer></script>
</body>
</html>
