<?php
$dbhost = "ts-db.c3rix1f2geko.eu-central-1.rds.amazonaws.com:3306";
$dbname = "ts_statistics";
$dbuser = "ts_db_user";
$dbpw = "UHV0emVyZmlzY2g";

try {
  $statdb = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpw,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
  $statdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
} catch(PDOException $e) {
  echo $e->getMessage();
}

?>
