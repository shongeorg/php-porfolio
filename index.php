<?php

const URL_ROOT = "/";
define("DOC_ROOT", $_SERVER["DOCUMENT_ROOT"] . URL_ROOT);
const PAGE_PATH = DOC_ROOT . "pages/";
const COMPONENTS_PATH = DOC_ROOT . "components/";
const DATA_PATH = DOC_ROOT . "data/";
const FUNC_PATH = DOC_ROOT . "functions/";

$page = $_GET["page"] ?? "portfolio";
$file = $page . ".php";
$render_page = PAGE_PATH . (file_exists(PAGE_PATH . $file) ? $file : '404.php');

include FUNC_PATH . 'data-base.php';
include FUNC_PATH . 'seo.php';
?>


<!doctype html>
<html lang="uk">
<head>
    <?php seoRenderTags($page); ?>
    <link rel="stylesheet" href="/public/index.css">
</head>
<body>
<?php require COMPONENTS_PATH . 'header.php'; ?>
<main><?php require $render_page; ?></main>
<?php require COMPONENTS_PATH . 'footer.php'; ?>


<script src="/public/index.js" defer></script>
</body>
</html>
