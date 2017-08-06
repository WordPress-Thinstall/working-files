<?php
  public function apply_filters( $value, $args ) {
    global $cd2_hook_log_sql_entries;
    if(!is_array($cd2_hook_log_sql_entries)) {
      $cd2_hook_log_sql_entries = [];
    }
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

    $jsonOpts = (JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_TAG|JSON_PRETTY_PRINT);
    $cd2_hook_log_sql_entries[] = $this->tag;
    $cd2_hook_log_sql_entries[] = json_encode($log, $jsonOpts);
    $cd2_hook_log_sql_entries[] = json_encode($_GET, $jsonOpts);
    $cd2_hook_log_sql_entries[] = json_encode($_POST, $jsonOpts);
    $cd2_hook_log_sql_entries[] = $_SERVER['REQUEST_URI'];
    $cd2_hook_log_sql_entries[] = date('Y-m-d H:i:s');

    unset( $this->iterations[ $nesting_level ] );
    unset( $this->current_priority[ $nesting_level ] );

    $this->nesting_level--;

    return $value;
  }
