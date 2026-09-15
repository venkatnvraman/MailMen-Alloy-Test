<?php
$configs = include("config.php");

$dbhost = $configs["host"];
$dbname = $configs["name"];
$dbuser = $configs["user"];
$dbpw = $configs["pw"];

try {
  $db = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpw, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);;
} catch (PDOException $e) {
  echo $e->getMessage();
}
