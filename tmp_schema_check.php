<?php
$mysqli = new mysqli('sql203.infinityfree.com','if0_42252139','UYqLZ6QobQJhGlk','if0_42252139_hvfapp');
if ($mysqli->connect_error) {
    echo 'CONNECT_ERR: ' . $mysqli->connect_error . PHP_EOL;
    exit(1);
}
$tables = ['clients','stock','stock_categories','orders'];
foreach ($tables as $table) {
    echo 'TABLE ' . $table . PHP_EOL;
    $res = $mysqli->query('SHOW COLUMNS FROM `' . $table . '`');
    if (!$res) {
        echo 'ERR: ' . $mysqli->error . PHP_EOL;
        continue;
    }
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . PHP_EOL;
    }
    echo '---' . PHP_EOL;
}
$mysqli->close();
