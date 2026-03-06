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
?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="/public/index.css">
    <title>Document</title>
</head>
<body>
<?php require COMPONENTS_PATH . 'header.php'; ?>
<main><?php require $render_page; ?></main>
<?php require COMPONENTS_PATH . 'footer.php'; ?>


<script src="/public/index.js" defer></script>
</body>
</html>
