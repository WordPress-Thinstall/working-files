<?php
/*
Plugin Name: CD2 WordPress Hooks DUMP SQL Save
Description: This plugin is designed to save WordPress Hooks Log (must be generated elsewhere)
Author: CD2 Team
Version: 1.00
Author URI: https://www.codesign2.co.uk/
*/

add_action('shutdown', function() {
  global $cd2_hook_log_sql_entries;
  if(!is_array($cd2_hook_log_sql_entries)) {
    return;
  }
  $count = count($cd2_hook_log_sql_entries);
  if($count == 0 || $count % 6 != 0) {
    return;
  }
  $entryCount = (int)($count / 6);
  $valueEntries = [];
  for($i = 0; $i < $entryCount; $i++) {
    $valueEntries[] = '(?, ?, ?, ?, ?, ?)';
  }
  try {
			$dbh = new PDO('mysql:dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
			$stmt = $dbh->prepare('INSERT INTO new_table (tag, data, query, body, path, dt) VALUES '.implode(', ', $valueEntries));

			$jsonOpts = (JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_TAG|JSON_PRETTY_PRINT);

			$stmt->execute($cd2_hook_log_sql_entries);
	} catch( Exception $e ) {
		  die($e->getMessage());
	}
});
