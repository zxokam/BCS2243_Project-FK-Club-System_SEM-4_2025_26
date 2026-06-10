<?php
/*
    Localhost database connection for FK Club System.

    Folder name: fkclub
    Database name: fkclub_db
    MySQL port: 3307
*/

mysqli_report(MYSQLI_REPORT_OFF);

$host = "127.0.0.1";
$user = "fkclub_user";
$password = "fkclub123";
$database = "fkclub_db";
$port = 3307;

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die(
        "<h2>Database connection failed</h2>" .
        "<p><b>Host:</b> $host</p>" .
        "<p><b>User:</b> $user</p>" .
        "<p><b>Database:</b> $database</p>" .
        "<p><b>Port:</b> $port</p>" .
        "<p><b>Error:</b> " . mysqli_connect_error() . "</p>"
    );
}
?>
