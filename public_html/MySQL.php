<?php

class MySQL {
	
	// Base variables
	public $lastError;		// Holds the last error
	public $result;			// Holds the MySQL query result
	public $records;		// Holds the total number of records returned
	public $affected;		// Holds the total number of records affected
	public $rawResults;		// Holds raw 'arrayed' results
	public $arrayedResult;	// Holds an array of the result
	
	private $hostname;		// MySQL Hostname
	private $username;		// MySQL Username
	private $password;		// MySQL Password
	private $database;		// MySQL Database
	private $port;			// MySQL Port
	private $databaseLink;	// Database Connection Link
	
	/*
	 * ****************** Class Constructor *******************
	 */
	function __construct($database, $username, $password, $hostname = 'localhost', $port = 3306) {
		$this->database = $database;
		$this->username = $username;
		$this->password = $password;
		$this->hostname = $hostname;
		$this->port = $port;
		
		$this->Connect ();
	}
	
	function __destruct() {
		$this->CloseConnection();
	}
	
	// Connects class to database
	private function Connect() {
		$this->CloseConnection ();
		$this->databaseLink = mysqli_connect ( $this->hostname, $this->username, $this->password, $this->database, $this->port );
		
		if (! $this->databaseLink) {
			$this->lastError = 'Could not connect to server: ' . mysqli_error ( $this->databaseLink );
			return false;
		}
		
		return true;
	}
	
	
	// Performs a 'mysqli_real_escape_string' on the entire array/string
	public function SecureData($data) {
		if (is_array ( $data )) {
			foreach ( $data as $key => $val ) {
				if (! is_array ( $data [$key] )) {
					$data [$key] = mysqli_real_escape_string ( $this->databaseLink, $data [$key] );
				}
			}
		} else {
			$data = mysqli_real_escape_string ( $this->databaseLink, $data );
		}
		return $data;
	}
	
	/*
	 * ***************** Public Functions ******************
	 */
	
	// Executes MySQL query
// 	function ExecuteSQL($query) {
// 		if ($this->result = mysqli_query( $this->databaseLink, $query )) {
// 			$this->records = @mysqli_num_rows( $this->result );
// 			$this->affected = @mysqli_affected_rows( $this->databaseLink );
			
// 			if ($this->records) {
// 				$this->ArrayResults();
// 				return $this->arrayedResult;
// 			} else {
// 				return array();	// return an empty array
// 			}
// 		} else {
// 			$this->lastError = mysqli_error( $this->databaseLink );
// 			return false;
// 		}
// 	}


	/**
	 * Query 0-many rows, returning a numeric array of result arrays
	 * 
	 * Inputs:
	 * 		$query = the sql to execute
	 * 		$fieldKey = if present, arranges the resulting array by the value of the keys found in the results
	 * 
	 * Outputs:
	 * 		array[0] = array('field1'='value', 'field2'='value2', etc.)
	 * 		array[1] = array('field1'='value', 'field2'='value2', etc.)
	 * 
	 * 		example if $fieldKey == 'field1'
	 * 		array[orange] = array('field1'='orange', 'field2'='value2', etc.)
	 * 		array[apple] = array('field1'='apple', 'field2'='value2', etc.)
	 * 
	 * 		on failure:
	 * 		false
	 */
	function query($query, $fieldKey=FALSE) {
		
		if ($this->result = mysqli_query( $this->databaseLink, $query )) {
			$this->arrayedResult = array();
			$this->records = @mysqli_num_rows( $this->result );

			if ($this->records) {
				
				if ($fieldKey) {
					while ( $data = mysqli_fetch_assoc ( $this->result ) ) {
						$this->arrayedResult [$data[$fieldKey]] = $data;
					}
				} else {
					while ( $data = mysqli_fetch_assoc ( $this->result ) ) {
						$this->arrayedResult [] = $data;
					}
				}

			}
			mysqli_free_result($this->result);
			
			return $this->arrayedResult;
		} else {
			$this->lastError = mysqli_error( $this->databaseLink );
			error_log('query error: ' . $this->lastError . ', query=' . $query);
			return false;
		}

	}
	

	/**
	 * Query that returns 0 to 1 rows
	 * 
	 * Inputs:
	 * 		$query = the sql to execute
	 * 
	 * Outputs:
	 * 		array('field1'='value', 'field2'='value2', etc.)
	 * 
	 * 		on failure:
	 * 		false
	 */
	function one($query) {
		
		if ($this->result = mysqli_query( $this->databaseLink, $query )) {
			$this->records = @mysqli_num_rows( $this->result );

			if ($this->records) {
				$this->arrayedResult = mysqli_fetch_assoc ( $this->result );
			} else {
				$this->arrayedResult = array();	
			}
			mysqli_free_result($this->result);

			return $this->arrayedResult;
		} else {
			$this->lastError = mysqli_error( $this->databaseLink );
			error_log('one error: ' . $this->lastError . ', query=' . $query);
			return false;
		}
	}

	
	function update($query) {
		if ($this->result = mysqli_query( $this->databaseLink, $query )) {
			$this->affected = @mysqli_affected_rows( $this->databaseLink );
			return true;
		} else {
			$this->lastError = mysqli_error( $this->databaseLink );
			error_log('update error: ' . $this->lastError . ', query=' . $query);
			return false;
		}
	}
	
	function insert($query) {
		if ($this->result = mysqli_query( $this->databaseLink, $query )) {
			$this->affected = @mysqli_affected_rows( $this->databaseLink );
			return true;
		} else {
			$this->lastError = mysqli_error( $this->databaseLink );
			error_log('insert error: ' . $this->lastError . ', query=' . $query);
			return false;
		}
	}
	
	function delete($query) {
		if ($this->result = mysqli_query( $this->databaseLink, $query )) {
			$this->affected = @mysqli_affected_rows( $this->databaseLink );
			return true;
		} else {
			$this->lastError = mysqli_error( $this->databaseLink );
			error_log('delete error: ' . $this->lastError . ', query=' . $query);
			return false;
		}
	}
	
	// Adds a record to the database based on the array key names
// 	function Insertxxx($vars, $table, $exclude = '') {
		
// 		// Catch Exclusions
// 		if ($exclude == '') {
// 			$exclude = array ();
// 		}
		
// 		array_push ( $exclude, 'MAX_FILE_SIZE' ); // Automatically exclude this one
		                                       
// 		// Prepare Variables
// 		$vars = $this->SecureData ( $vars );
		
// 		$query = "INSERT INTO `{$table}` SET ";
// 		foreach ( $vars as $key => $value ) {
// 			if (in_array ( $key, $exclude )) {
// 				continue;
// 			}
// 			// $query .= '`' . $key . '` = "' . $value . '", ';
// 			$query .= "`{$key}` = '{$value}', ";
// 		}
		
// 		$query = substr ( $query, 0, - 2 );
		
// 		return $this->ExecuteSQL ( $query );
// 	}
	
	// Deletes a record from the database
// 	function Deletexxx($table, $where = '', $limit = '', $like = false) {
// 		$query = "DELETE FROM `{$table}` WHERE ";
// 		if (is_array ( $where ) && $where != '') {
// 			// Prepare Variables
// 			$where = $this->SecureData ( $where );
			
// 			foreach ( $where as $key => $value ) {
// 				if ($like) {
// 					// $query .= '`' . $key . '` LIKE "%' . $value . '%" AND ';
// 					$query .= "`{$key}` LIKE '%{$value}%' AND ";
// 				} else {
// 					// $query .= '`' . $key . '` = "' . $value . '" AND ';
// 					$query .= "`{$key}` = '{$value}' AND ";
// 				}
// 			}
			
// 			$query = substr ( $query, 0, - 5 );
// 		}
		
// 		if ($limit != '') {
// 			$query .= ' LIMIT ' . $limit;
// 		}
		
// 		return $this->ExecuteSQL ( $query );
// 	}
	
	// Gets a single row from $from where $where is true
// 	function Selectxxx($from, $where = '', $orderBy = '', $limit = '', $like = false, $operand = 'AND', $cols = '*') {
// 		// Catch Exceptions
// 		if (trim ( $from ) == '') {
// 			return false;
// 		}
		
// 		$query = "SELECT {$cols} FROM `{$from}` WHERE ";
		
// 		if (is_array ( $where ) && $where != '') {
// 			// Prepare Variables
// 			$where = $this->SecureData ( $where );
			
// 			foreach ( $where as $key => $value ) {
// 				if ($like) {
// 					// $query .= '`' . $key . '` LIKE "%' . $value . '%" ' . $operand . ' ';
// 					$query .= "`{$key}` LIKE '%{$value}%' {$operand} ";
// 				} else {
// 					// $query .= '`' . $key . '` = "' . $value . '" ' . $operand . ' ';
// 					$query .= "`{$key}` = '{$value}' {$operand} ";
// 				}
// 			}
			
// 			$query = substr ( $query, 0, - (strlen ( $operand ) + 2) );
// 		} else {
// 			$query = substr ( $query, 0, - 6 );
// 		}
		
// 		if ($orderBy != '') {
// 			$query .= ' ORDER BY ' . $orderBy;
// 		}
		
// 		if ($limit != '') {
// 			$query .= ' LIMIT ' . $limit;
// 		}
		
// 		return $this->ExecuteSQL ( $query );
// 	}
	
	// Updates a record in the database based on WHERE
// 	function Updatexxx($table, $set, $where, $exclude = '') {
// 		// Catch Exceptions
// 		if (trim ( $table ) == '' || ! is_array ( $set ) || ! is_array ( $where )) {
// 			return false;
// 		}
// 		if ($exclude == '') {
// 			$exclude = array ();
// 		}
		
// 		array_push ( $exclude, 'MAX_FILE_SIZE' ); // Automatically exclude this one
		
// 		$set = $this->SecureData ( $set );
// 		$where = $this->SecureData ( $where );
		
// 		// SET
		
// 		$query = "UPDATE `{$table}` SET ";
		
// 		foreach ( $set as $key => $value ) {
// 			if (in_array ( $key, $exclude )) {
// 				continue;
// 			}
// 			$query .= "`{$key}` = '{$value}', ";
// 		}
		
// 		$query = substr ( $query, 0, - 2 );
		
// 		// WHERE
		
// 		$query .= ' WHERE ';
		
// 		foreach ( $where as $key => $value ) {
// 			$query .= "`{$key}` = '{$value}' AND ";
// 		}
		
// 		$query = substr ( $query, 0, - 5 );
		
// 		return $this->ExecuteSQL ( $query );
// 	}
	
	// 'Arrays' a single result
// 	function ArrayResult() {
// 		$this->arrayedResult = mysqli_fetch_assoc ( $this->result ) or die ( mysqli_error ( $this->databaseLink ) );
// 		return $this->arrayedResult;
// 	}
	
// 	// 'Arrays' multiple result
// 	function ArrayResults() {
// 		if ($this->records == 1) {
// 			return $this->ArrayResult ();
// 		}
		
// 		$this->arrayedResult = array ();
// 		while ( $data = mysqli_fetch_assoc ( $this->result ) ) {
// 			$this->arrayedResult [] = $data;
// 		}
// 		return $this->arrayedResult;
// 	}
// 	function convertSingleArrayToMultidimensional() {
// 		// Since the above code will may return a single dimensional array OR multidimensional,
// 		// this will guarantee that a multidimension will always be returned for code that uses
// 		// foreach statements
// 		if ($this->records == 1) {
// 			$tempArray = array ();
// 			$tempArray [] = $this->arrayedResult;
// 			$this->arrayedResult = $tempArray;
// 		} else if ($this->records == 0) {
// 			return array ();
// 		}
// 		return $this->arrayedResult;
// 	}
	
	// 'Arrays' multiple results with a key
// 	function ArrayResultsWithKey($key = 'id') {
// 		if (isset ( $this->arrayedResult )) {
// 			unset ( $this->arrayedResult );
// 		}
// 		$this->arrayedResult = array ();
// 		while ( $row = mysqli_fetch_assoc ( $this->result ) ) {
// 			foreach ( $row as $theKey => $theValue ) {
// 				$this->arrayedResult [$row [$key]] [$theKey] = $theValue;
// 			}
// 		}
// 		return $this->arrayedResult;
// 	}
	
	// Returns last insert ID
	function LastInsertID() {
		return mysqli_insert_id ($this->databaseLink);
	}
	
	// Return number of rows
// 	function CountRows($from, $where = '') {
// 		$result = $this->Select ( $from, $where, '', '', false, 'AND', 'count(*)' );
// 		return $result ["count(*)"];
// 	}
	
	// Closes the connections
	function CloseConnection() {
		if ($this->databaseLink) {
			mysqli_close ( $this->databaseLink );
			$this->databaseLink = NULL;	// prevent a 2nd CloseConnection?
		}
	}
}

?>

