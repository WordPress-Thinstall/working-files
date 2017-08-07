<?php
/*
Plugin Name: CD2 WordPress Hooks DUMP SQL Save
Description: This plugin is designed to save WordPress Hooks Log (must be generated elsewhere)
Author: CD2 Team
Version: 1.05
Author URI: https://www.codesign2.co.uk/
*/

register_activation_hook(__FILE__, function() {
  $sql = 'CREATE TABLE IF NOT EXISTS `wp_hook_log` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `tag` varchar(255) NOT NULL,
    `data` text NOT NULL,
    `dt` datetime NOT NULL,
    `query` text NOT NULL,
    `body` text NOT NULL,
    `path` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8';
  try {
    $dbh = new PDO('mysql:dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $dbh->query($sql);
  } catch( Exception $e ) {
    die($e->getMessage());
	}
} );

register_deactivation_hook( __FILE__, function() {
  $sql = 'DROP TABLE IF EXISTS `wp_hook_log`';
  try {
    $dbh = new PDO('mysql:dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $dbh->query($sql);
  } catch( Exception $e ) {
    die($e->getMessage());
	}
} );

add_action('all', function() {
  global $cd2_hook_log_sql_entries;
  if(!is_array($cd2_hook_log_sql_entries)) {
    $cd2_hook_log_sql_entries = [];
  }
  $args = func_get_args();
  $log = [
    'args' => $args
  ]; $n = 0;

  $jsonOpts = (JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_TAG|JSON_PRETTY_PRINT);

  $cd2_hook_log_sql_entries[] = current_filter() ?? 'unknown';
  $cd2_hook_log_sql_entries[] = json_encode($log, $jsonOpts);
  $cd2_hook_log_sql_entries[] = json_encode($_GET, $jsonOpts);
  $cd2_hook_log_sql_entries[] = json_encode($_POST, $jsonOpts);
  $cd2_hook_log_sql_entries[] = $_SERVER['REQUEST_URI'];
  $cd2_hook_log_sql_entries[] = date('Y-m-d H:i:s');
} );

add_action('shutdown', function() {
  global $cd2_hook_log_sql_entries;
  if(!is_array($cd2_hook_log_sql_entries)) {
    return;
  }
  $count = count($cd2_hook_log_sql_entries);
  if($count == 0) {
    return;
  }
  $entryCount = (int)($count / 6);
  $valueEntries = [];
  for($i = 0; $i < $entryCount; $i++) {
    $valueEntries[] = '(?, ?, ?, ?, ?, ?)';
  }
  try {
    $dbh = new PDO('mysql:dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $stmt = $dbh->prepare('INSERT INTO wp_hook_log (tag, data, query, body, path, dt) VALUES '.implode(', ', $valueEntries));
    $stmt->execute($cd2_hook_log_sql_entries);
  } catch( Exception $e ) {
    die($e->getMessage());
	}
} );
