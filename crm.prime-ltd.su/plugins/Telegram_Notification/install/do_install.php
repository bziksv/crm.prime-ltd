<?php

ini_set('max_execution_time', 300); //300 seconds 

$product = "Telegram_Notification";
/**
include PLUGINPATH . "$product/install/verfiy_purchase_code.php";
if (!$verification || $verification != "verified") {
    echo json_encode(array("success" => false, "message" => "Please enter a valid purchase code."));
    exit();
}
*/
$db = db_connect('default');

//all input seems to be ok. check required files
if (!is_file(PLUGINPATH . "$product/install/database.sql")) {
    echo json_encode(array("success" => false, "message" => "The database.sql file could not found in install folder!"));
    exit();
}

//start installation
$sql = file_get_contents(PLUGINPATH . "$product/install/database.sql");
$sql = str_replace('Telegram_Notification-ITEM-PURCHASE-CODE', $item_purchase_code, $sql);

$dbprefix = get_db_prefix();

//set database prefix
$sql = str_replace('CREATE TABLE IF NOT EXISTS `', 'CREATE TABLE IF NOT EXISTS `' . $dbprefix, $sql);
$sql = str_replace('INSERT INTO `', 'INSERT INTO `' . $dbprefix, $sql);
$sql = str_replace('ALTER TABLE `', 'ALTER TABLE `' . $dbprefix, $sql);

$sql_explode = explode('#', $sql);
foreach ($sql_explode as $sql_query) {
    $sql_query = trim($sql_query);
    if ($sql_query) {
        $db->query($sql_query);
    }
}

