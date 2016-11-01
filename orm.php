<?php
/**
 * ORM Module
 *
 * This file contains functions to perform Database Operations
 * It Utilises Redbean ORM (v 4.3.1).
 *
 * @version 1.0
 * @author CodeRythm Technology Pvt Ltd <www.coderythm.com>
 */

require_once 'redbean/rb.php';  //Require Main Redbean File

/**
* Database Connection String and connection procedure
* HOST,DATABSE,DB_USERNAME,DB_PASSWORD are set in Main config File
*/
R::setup('mysql:host='.DB_HOST.';dbname='.DB_NAME,DB_USER,DB_PASSWORD);
R::setAutoResolve( TRUE );    
R::debug(false);

/**
* Last Inserted Id
* @access global
* $var int
*/
$last_ID = 0;

/**
* BD Query Error
* @access global
* $var string
*/
$orm_error = '';


/**
* Insert into Database
* 
* Insert into any Table and sets Last insreted ID
* 
* @param string $tablename The table to Insert, $variable array which contains (field => value)
* 
* @return boolean true/false True if inserted properly. False if any Error
*/
function insert_orm($tablename,$variable){
	try{
      $orm_error = $GLOBALS["last_ID"] = $GLOBALS["orm_error"] = '';
    	$std = R::dispense($tablename);
    	foreach ($variable as $key => $value) {
    		$std->$key = $value;
    	}
    	$id = R::store($std);
      $GLOBALS["last_ID"]=$id;
    	return true;
  	}
  	catch(Exception $e){
			R::rollback();
      $GLOBALS["orm_error"] = $e;
			return false;
		}
}

/**
* Update Database
* 
* Update any table with given values
* 
* @param string $tablename The table to Insert, $variable array which contains (field => value), $id id of row to be updated
* 
* @return boolean true/false True if updated properly. False if any Error
*/
function update_orm($tablename,$variable,$id){
	try{
      $orm_error = $GLOBALS["last_ID"] = $GLOBALS["orm_error"] = '';
			$user = R::load($tablename, $id);
			foreach ($variable as $key => $value) {
	    		$user->$key = $value;
	    	}
			R::store($user);
			return true;
	}
  	catch(Exception $e){
			R::rollback();
      $GLOBALS["orm_error"] = $e;
			return false;
		}
}

/**
* Query Database
* 
* Preccess any Query to databse
* 
* @param string $query to be executed
* 
* @return Value/false, $value if executed properly, false eif any error
*/
function query_orm($query){
	try{
      $orm_error = $GLOBALS["last_ID"] = $GLOBALS["orm_error"] = '';
			$user = R::getAll($query);
			return $user;
	}catch(Exception $e){
			R::rollback();
      $GLOBALS["orm_error"] = $e;
			return false;
	}
}

/**
* Get All table
* 
* Get all values from a table
* 
* @param string $table of which to retrieve
* 
* @return value/false, $value if executed properly, false eif any error
*/
function all_orm($table){
	try{
      $orm_error = $GLOBALS["last_ID"] = $GLOBALS["orm_error"] = '';
			$value = R::getAll('SELECT * FROM '.$table);
			return $value;
	}
  	catch(Exception $e){
			R::rollback();
      $GLOBALS["orm_error"] = $e;
			return false;
		}
}

/**
* Query Database with Where condition
* 
* Preccess any Query to databse with Where condition
* 
* @param string $table The Table name, $condition The condition after Where
* 
* @return Value/false, $value if executed properly, false eif any error
*/
function where_orm($table,$condition){
	try{
      $orm_error = $GLOBALS["last_ID"] = $GLOBALS["orm_error"] = '';
			$user = R::getAll('SELECT * FROM '.$table.' WHERE '.$condition);
			return $user;
		}
    	catch(Exception $e){
			R::rollback();
      $GLOBALS["orm_error"] = $e;
			return false;
		}
}

/**
* Delete Value from table
* 
* Preccess any Query to delete data from table
* 
* @param string $tablename The Table name, $id The id to delete
* 
* @return boolean true/false True if updated properly. False if any Error
*/
function delete_orm($tablename,$id){
	try{
      $orm_error = $GLOBALS["last_ID"] = $GLOBALS["orm_error"] = '';
			$user = R::load($tablename, $id);
			R::trash($user);
			return true;
		}
    	catch(Exception $e){
			R::rollback();
      $GLOBALS["orm_error"] = $e;
			return false;
		}
}

/**
* Truncate table
* 
* Truncate any Table
* 
* @param string $tablename The Table name
* 
* @return boolean true/false True if updated properly. False if any Error
*/
function truncate_orm($tablename){
	try{
      $orm_error = $GLOBALS["last_ID"] = $GLOBALS["orm_error"] = '';
			R::wipe($tablename);
			return true;
		}
    	catch(Exception $e){
			R::rollback();
      $GLOBALS["orm_error"] = $e;
			return false;
		}
}

/**
* Drop table
* 
* Drop any Table
* 
* @param string $tablename The Table name
* 
* @return boolean true/false True if updated properly. False if any Error
*/
function delete_table_orm(){
	try{
      $orm_error = $GLOBALS["last_ID"] = $GLOBALS["orm_error"] = '';
			R::nuke();
			return true;
		}
    	catch(Exception $e){
			R::rollback();
      $GLOBALS["orm_error"] = $e;
			return false;
		}
}
