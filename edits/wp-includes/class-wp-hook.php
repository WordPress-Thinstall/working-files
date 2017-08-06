<?php

	public function apply_filters( $value, $args ) {
		$log = [
			'old'=>[
				'value' => $value,
				'args'  => $args
			],
			'runs' => []
		]; $n = 0;

		if ( ! $this->callbacks ) {
			return $value;
		}

		$nesting_level = $this->nesting_level++;

		$this->iterations[ $nesting_level ] = array_keys( $this->callbacks );
		$num_args = count( $args );

		do {
			$this->current_priority[ $nesting_level ] = $priority = current( $this->iterations[ $nesting_level ] );

			foreach ( $this->callbacks[ $priority ] as $the_ ) {
				if( ! $this->doing_action ) {
					$args[ 0 ] = $value;
				}

				// Avoid the array_slice if possible.
				if ( $the_['accepted_args'] == 0 ) {
					$value = call_user_func_array( $the_['function'], array() );
				} elseif ( $the_['accepted_args'] >= $num_args ) {
					$value = call_user_func_array( $the_['function'], $args );
				} else {
					$value = call_user_func_array( $the_['function'], array_slice( $args, 0, (int)$the_['accepted_args'] ) );
				}
				$log["runs"][$n] = $value; $n++;
			}
		} while ( false !== next( $this->iterations[ $nesting_level ] ) );

		try {
			$dbh = new PDO('mysql:dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
			$stmt = $dbh->prepare('INSERT INTO new_table (tag, data, query, body, path, dt) VALUES (?, ?, ?, ?, ?, ?)');

			$jsonOpts = (JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_TAG|JSON_PRETTY_PRINT);

			$stmt->bindParam(1, $this->tag);
			$stmt->bindValue(2, json_encode($log, $jsonOpts));
			$stmt->bindValue(3, json_encode($_GET, $jsonOpts));
			$stmt->bindValue(4, json_encode($_POST, $jsonOpts));
			$stmt->bindValue(5, $_SERVER['REQUEST_URI']);
			$stmt->bindValue(6, date('Y-m-d H:i:s'));

			$stmt->execute();
		} catch( Exception $e ) {
			die($e->getMessage());
		}

		unset( $this->iterations[ $nesting_level ] );
		unset( $this->current_priority[ $nesting_level ] );

		$this->nesting_level--;

		return $value;
	}
