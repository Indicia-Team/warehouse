<?php
class Database extends Database_Core {

  protected $in_trans = false; 
  
  public function __destruct() {
    self::rollback(); 
  } 
  
  public function begin() { 
    if ( !$this->in_trans ) 
      $this->query( 'BEGIN' );
    $this->in_trans = true;
  } 
  
  public function commit() { 
    if ( $this->in_trans ) 
      $this->query( 'COMMIT' ); 
    $this->in_trans = false; 
  } 
  
  public function rollback() {
    if ( $this->in_trans ) 
      $this->query( 'ROLLBACK' );
    $this->in_trans = false; 
  } 
  

  /**
  * Adds an "IN" condition to the where clause
  *
  * @param   string  Name of the column being examined
  * @param   mixed   An array or string to match against
  * @param   bool    Generate a NOT IN clause instead
  * @return  Database_Core  This Database object.
  */
  public function in($field, $values, $not = FALSE)
  {
    $null_value = false;
    if (is_array($values))
    {
      $escaped_values = array();
      foreach ($values as $v)
      {
        if (is_numeric($v))
        {
          $escaped_values[] = $v;
        }
        else if ($v == null)
        {
          $null_value = true;
        }
        else
        {
          $escaped_values[] = "'".$this->driver->escape_str($v)."'";
        }
      }
      $values = implode(",", $escaped_values);
    }
    if ($not === FALSE)
    {
      if (!$values =='')
        // there is a valid list, and a possible null value.
        $this->where('('.$this->driver->escape_column($field).' IN ('.$values.')'.($null_value ? ' OR '.$this->driver->escape_column($field).' IS NULL)' : ')'));
      else
        // List is empty (originally values contained an array of nothing or just entry "null")
        // If original list was completely empty, need to force nothing returned.
        $this->where($null_value ? $this->driver->escape_column($field).' IS NULL' : "'t' = 'f'");
    }
    else
    {
      // not is true
      if (!$values =='')
        // there is a valid list, and a possible null value. Note need to use AND rather than OR
        $this->where('('.$this->driver->escape_column($field).' NOT IN ('.$values.')'.($null_value ? ' AND '.$this->driver->escape_column($field).' IS NOT NULL)' : ')'));
      else if ($null_value)
        // Originally values only contained an array of just the entry "null"
        $this->where($this->driver->escape_column($field).' IS NOT NULL');
      // If original values list was completely empty, then don't need to add any where clause for NOT IN
    }

    return $this;
  }

  /**
  * Runs a query into the driver and returns the result. Overridden to wrap geometry fields as WKT.
  *
  * @param   string  SQL query to execute
  * @return  Database_Result
  */
  public function query($sql = '')
  {
    if ($sql == '') return FALSE;

    // No link? Connect!
    $this->link or $this->connect();

    // Start the benchmark
    $start = microtime(TRUE);

    if (func_num_args() > 1) //if we have more than one argument ($sql)
    {
      $argv = func_get_args();
      $binds = (is_array(next($argv))) ? current($argv) : array_slice($argv, 1);
    }

    // Compile binds if needed
    if (isset($binds))
    {
      $sql = $this->compile_binds($sql, $binds);
    }

    // Wrap geometry fields - and hope this regex works
    if ($this->select)
    {
      $sql = preg_replace('/(?<=[ \t])["|`|\']?([\w_]*\.?[\w_]*geom)["|`|\']?(?=[ \t,])/i', "ST_asText($1)", $sql);
    }

    // Fetch the result
    $result = $this->driver->query($this->last_query = $sql);

    // Stop the benchmark
    $stop = microtime(TRUE);

    if ($this->config['benchmark'] == TRUE)
    {
      // Benchmark the query
      self::$benchmarks[] = array('query' => $sql, 'time' => $stop - $start, 'rows' => count($result));
    }

    return $result;
  }

  /**
  * Chooses which column(s) to order the select query by.
  * Overridden to handle 'vague_date' fields which don't exist in the db but are presented in the
  * ORM controller.
  *
  * @param   string|array  column(s) to order on, can be an array, single column, or comma seperated list of columns
  * @param   string        direction of the order
  * @return  Database_Core        This Database object.
  */
  public function orderby($orderby, $direction = NULL)
  {
    if ( ! is_array($orderby))
    {
      $orderby = array($orderby => $direction);
    }

    foreach ($orderby as $column => $direction)
    {
      $direction = strtoupper(trim($direction));

      if ( ! in_array($direction, array('ASC', 'DESC', 'RAND()', 'RANDOM()', 'NULL')))
      {
        $direction = 'ASC';
      }

      if (strtolower(trim($column)) == 'vague_date')
      {
        $column = 'date_start';
      }

      $this->orderby[] = $this->driver->escape_column($column).' '.$direction;
    }

    return $this;
  }

}
