<?php
function db(): ?PDO
{
    static $dbh = null;
    if ($dbh !== null) return $dbh;
    $dbh = new PDO("mysql:dbname=if0_41319385_XXX;host=sql100.infinityfree.com;charset=utf8;port=3306", "if0_41319385", "K1QjjXi2LrX", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $dbh;
}

function dbGetAll($table): array
{
    return db()->query("SELECT * FROM `{$table}`")->fetchAll();
}

function dbCount($table): int
{
    $q = "SELECT count(*) FROM `{$table}`";
    $stmt = (int)db()->query($q)->fetchColumn();
    return $stmt;
}

function dbPaginate(string $table, int $perPage = 10, int $page = 1): array
{
    $offset = ($page - 1) * $perPage;
    $query = "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}";
    return db()->query($query)->fetchAll();
}

function dbPages(string $table, int $perPage = 10): int
{
    $total = dbCount($table);
    return (int)ceil($total / $perPage);
}

function dbInsert(string $table, array $arr): void
{
    $fields = array_keys($arr);
    $q = "INSERT INTO {$table} ";
    $q .= "(`" . implode("`, `", $fields) . "`) ";
    $q .= "VALUES (:" . implode(", :", $fields) . ")";
    $stmt = db()->prepare($q);
    $stmt->execute($arr);
}

function dbGetById($table, $id)
{
    $db = db();
    $query = "SELECT * FROM `{$table}` WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch();
}

function dbUpdate($table, $id, $arr): void
{
    $id = (int)$id;
    $q = "UPDATE `{$table}` SET ";
    $fields = array_keys($arr);
    $q .= implode("=?, ", $fields) . "=? WHERE id={$id}";
    $stmt = db()->prepare($q);
    $stmt->execute(array_values($arr));
}
