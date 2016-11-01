<?php
/**
 * User Module
 *
 * This class is used to perform common user functionalities, Roles, Permissions, Password, Login Etc
 *
 * @version 1.0
 * @author Amjid Backer <amjid@coderythm.com>
 */
class UserClass
{
	/**
	* User Id
	* @access private
	* $var int
	*/
	private $_id;

	/**
	* User Table
	* @access private
	* $var string
	*/
	private $_usertable;

	/**
	* Username
	* @access private
	* $var string
	*/
	private $_username;

	/**
	* Entered Password Value
	* @access private
	* $var string
	*/
	private $_password;

	/**
	* Hashed Password
	* @access private
	* $var hashed string
	*/
	private $_passhash;

	/**
	* User Role
	* @access private
	* $var int
	*/
	private $_role;

	/**
	* Hashing algorithm, 
	* @access private
	* $var int
	*/
	private $_algorithm = PASSWORD_DEFAULT; //Going for Default, so that PHP chooses the best algorithm for Us

	/**
	* Hashing options
	* @access private
	* $var array
	*/
	private $_options;

	/**
	* Errors List
	* @access private
	* $var array
	*/
	private $_errors;

	/**
	* Access Flag
	* @access private
	* $var boolean
	*/
	private $_access;

	/**
	* Login Form
	* @access private
	* $var string
	*/
	private $_login;

	/**
	* Form Token
	* @access private
	* $var string
	*/
	private $_token;

	/**
	* List All Roles
	* @access public
	* $var array
	*/
	public $allroles;

	/**
	* Role Name
	* @access public
	* $var string
	*/
	public $rolename;

	/**
	* Permission Name
	* @access public
	* $var string
	*/
	public $permissionname;

	/**
	* Permission Id
	* @access public
	* $var int
	*/
	public $permissionid;

	/**
	* List All Permissions
	* @access public
	* $var array
	*/
	public $allpermissions;

	/**
	* Permission Id
	* @access public
	* $var int
	*/
	public $rolePermissions;

	/**
	* List All Users
	* @access public
	* $var array
	*/
	public $allusers;

	/**
	* User Details
	* @access public
	* $var array
	*/
	public $userdetails;

	/**
	* Constructor
	* 
	* Sets up class options
	*
	* Initialises variables 
	**/
	public function __construct() {
		$this->_usertable	=	'users';
		$this->_errors		=	array();
		$this->_login		=	(isset($_POST['formbase']) && $_POST['formbase']=='login')? true : false;
		$this->_access		=	false;
		$this->_options 	= 	array();	// Add SALT and cost options here if desired
		$this->_token		=	(isset($_POST['token']))? $_POST['token'] : 0;
		$this->_id			=	($this->_login)? false : ((isset($_SESSION['user_id']))? $_SESSION['user_id'] : 0);
		$this->_username	=	($this->_login)? $this->filter($_POST['username']) : ((isset($_SESSION['user_username']))? $_SESSION['user_username'] : 0);
		$this->_password	=	($this->_login)? $this->filter($_POST['password']) : '';
		$this->_passhash	=	($this->_login)? $this->hash( $this->_password ) : ((isset($_SESSION['user_password']))? $_SESSION['user_password'] : 0);
		$this->_role		=	($this->_login)? false : ((isset($_SESSION['user_role']))? $_SESSION['user_role'] : 0);
	}

	/** Checks if LoggedIn or Not **/
	public function isLoggedIn()
	{

		($this->_login) ? $this->verifyPost() : $this->verifySession();
		return $this->_access;
	}/** isLoggedIn **/

	/** Verify $_POST data **/
	public function verifyPost()
	{
		// $MEKACLASS = new MekaClass();
		try {
			if (!$this->isTokenValid()) {
				throw new Exception("Invalid Form Submission");
			}
			if (!$this->isLoginDataValid()) {
				throw new Exception("Please check the form Data you Entered");
			}
			if (!$this->verifyLoginData()) {
				throw new Exception("Invalid Username/Password");
			}

			$this->_access	=	true;
			$this->registerSession();

		} catch (Exception $e) {
			$this->_errors[]	=	$e->getMessage();
		}

	}/** verifyPost **/

	/** Verify $_SESSION data **/
	public function verifySession()
	{
		if($this->sessionExist())
		// if($this->sessionExist() && $this->verifyLoginData())
			$this->_access	=	true;
	}/** verifySession **/

	/** Verify if user exist or Not **/
	public function verifyLoginData()
	{
		$data="SELECT * FROM users WHERE username = '$this->_username'";
		$ret=query_orm($data);
		if($ret){
			$this->_passhash = $ret[0]['password'];
			$this->_id=$ret[0]['id'];
			$this->_role=$ret[0]['role'];
			if (!$this->get_and_verify()) {
				return false;
			}else{				
				return true;
			}
			// 
		}else{
			return false;
		}
	}/** verifyLoginData **/

	/** Validate Username and Password **/
	public function isLoginDataValid()
	{
		if(!fnValidateAlphanumeric($this->_username)){
			$this->_errors[]	=	'Invalid Characteres in Username';
		}

		if(!fnValidateAlphanumeric($this->_password)){
			$this->_errors[]	=	'Invalid Characteres in Password';
		}

		if (sizeof($this->_errors) == 0) {
			return true;
		}else{
			return false;
		}
	}/** isLoginDataValid **/

	/** Checks if token is valid **/
	public function isTokenValid()
	{
		return (!isset($_SESSION['token']) || $this->_token != $_SESSION['token'])? false : true;
	}/** isTokenValid **/

	/** Function to Register Session **/
	public function registerSession()
	{
		$this->getUser($this->_id);
		// Get all user variables and register as session with prefix user
		foreach ($this->userdetails[0] as $key => $value) {
			$_SESSION['user_' . $key]		=	$value;
		}

	}/** registerSession **/

	/** Checks if sessionExist **/
	public function sessionExist()
	{
		return (isset($_SESSION['user_username']) && isset($_SESSION['user_password']))? true : false;
	}/** sessionExist **/

	/** Function to return Errors **/
	public function getErrors()
	{
		$errmsg = '';

        foreach($this->_errors as $key => $error) {
            // set up error messages to display with each field
            $errmsg .= " - {$error}<br>";
        }
		return $errmsg;
	}/** getErrors **/

	/** Function for LogOut **/
	public function logout()
	{
		$this->verifySession();
		if(!$this->_access){
			$this->_errors[]	=	'Invalid Session Data';
			return false;
		}else{
			foreach ($_SESSION as $key => $value) {
				unset($_SESSION[$key]);
			}
			session_destroy();
			return true;
		}
	}/** logout **/

	/** Function to Filter Variables for unwanted characters **/
	public function filter($var)
	{
		return htmlspecialchars(stripslashes(trim($var)));
	}/** filter **/

	/**
	* Hash and store password
	* 
	* Hashes the password then stores it in the database
	* 
	* @param string $password The password to hash and store
	* 
	* @return boolen true/false True if hash and store is successful. False if not
	*/
	public function hash_and_store() {

		// Hash the password
		$hash = $this->hash( $this->_password );

		// Check for successful hashing
		if ( ! $hash ) {
			return false;
		}

		$this->_passhash = $hash;

		$var = array(
						'password' => $this->_passhash,
					);
		$ret=update_orm($this->_usertable,$var, $this->_id);

		return true;
	}

	/**
	* Verify password
	* 
	* Gets the hash from the database and verifies user-submitted password
	* 
	* @param string $password The password to check
	* 
	* @return boolean true/false True if password matches. False if not
	*/
	public function get_and_verify() {
		
		// Check if user-submitted password matches hash
		if ( $this->verify() ) {
		  
			// Check if password needs rehashed
			if ( $this->needs_rehash() ) {

				// Rehash and store new hash
				if ( ! $this->hash_and_store() ) {
					return false;
				}

			}

			return true;
		}

		return false;
	}

	/**
	* Hashing method
	* 
	* Hashes a password
	* 
	* @param string $password The password to hash
	* 
	* @return $string $hash The hashed password
	*/
	public function hash() {
		return password_hash( $this->_password, PASSWORD_DEFAULT, array() );
	}

	/**
	* Verify method
	* 
	* Verifies a password matches the hash
	* 
	* @param string $password The plain text password to check
	* @param string $hash The hash to check against
	* 
	* @return boolean true/false True if it matches. False if not
	*/
	public function verify() {
		$this->_errors[]	=	$this->_password . ',' . $this->_passhash;
		return password_verify($this->_password, $this->_passhash);
	}

	/**
	* Needs rehash method
	* 
	* Checks if a hash needs rehashed. For new algorithms/options
	* 
	* @param string $hash The hash to check for rehashing
	* 
	* @return boolean true/false True if needs rehashing. False if not
	*/
	public function needs_rehash() {
		return password_needs_rehash($this->_passhash, $this->_algorithm, $this->_options );
	}

	/**
	* Get hash info
	* 
	* Gets the hashing algorithm and options used while hashing
	* 
	* @param string $hash The hash to get info from
	* 
	* @return array $info An associate array containing the hashing info
	*/
	public function get_info() {
		return password_get_info($this->_passhash);
	}

	/**
	* Find cost
	* 
	* This code will benchmark your server to determine how high of a cost you can
	* afford. You want to set the highest cost that you can without slowing down
	* you server too much. 8-10 is a good baseline, and more is good if your servers
	* are fast enough. The code below aims for ≤ 50 milliseconds stretching time,
	* which is a good baseline for systems handling interactive logins.
	* 
	* @param int $baseline Baseline cost to start testing from
	* 
	* @return int $cost Cost to use for server
	*/
	public function find_cost($baseline = 8) {
		// Target time. 50 milliseconds is a good baseline
		$time_target = 0.05;
		$cost = $baseline;

		// Run test
		do {
		  $cost++;
		  $start = microtime( true );
		  password_hash( 'test', $this->_algorithm, array( 'cost' => $cost ) );
		  $end = microtime( true );
		} while( ( $end - $start ) < $time_target );

		return $cost;
	}

	/** Function to add role **/
	public function  addRole($rolevalue)
	{
		if($this->isPermitted($this->_id,'Add/Edit/Delete Roles'))
		{
			
			if ($this->isRole($rolevalue['role_name'])) {
				$this->_errors[]	=	'Cannot Add Existing Role';
			}

			if (sizeof($this->_errors) == 0) {
				$ret=insert_orm('usersroles',$rolevalue);
				if($ret){
					return true;
				}else{
					$this->_errors[]	=	'Could not add in Database';
					//$this->_errors[]	=	$ret;
					return false;
				}
			}else{
				return false;
			}
		
		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** addRole **/

	/** Get the Role Name by giving role id **/
	public function getRole($id)
	{
		$data="SELECT role_name FROM usersroles WHERE id = '" . $id . "'";
		$ret=query_orm($data);
		if($ret){
			$this->rolename = $ret[0]['role_name'];
			return true;
		}else{
			return false;
		}
	}/** getRole **/

	/** Function to Get Roles **/
	public function viewAllRoles()
	{
		if($this->isPermitted($this->_id, 'View Roles'))
		{
			$ret=all_orm('usersroles');
			if($ret){
				$this->allroles = $ret;
				return true;
			}else{
				$this->_errors[]	=	'Nothing Fetched';
				return false;
			}
		}else{
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** viewAllRoles **/

	/** Check if Role exists **/
	public function isRole($role)
	{
		$data="SELECT id FROM usersroles WHERE role_name = '$role'";
		$ret=query_orm($data);
		if($ret){
			return true;
		}else{
			return false;
		}
	}/** isRole **/

	/** Function to Delete Role **/
	public function  deleteRole($roleid)
	{
		if($this->isPermitted($this->_id, 'Add/Edit/Delete Roles'))
		{
			if($roleid!='1'){
				$data="SELECT id FROM $this->_usertable WHERE role = '$roleid'";
				$ret=query_orm($data);
				if($ret){
					$this->_errors[]	=	'Role is assigned to User, Cannot Delete';
					return false;					
				}else{
					$data="DELETE FROM userspermissionrole WHERE role_id = ".$roleid;
					$ret=query_orm($data);
					$ret=delete_orm('usersroles',$roleid);
					if($ret){
						return true;
					}else{
						$this->_errors[]	=	'Cannot delete from database';
						return false;
					}
				}
			}else {
				$this->_errors[]	=	'You Cannot Delete Superadmin';
				return false;
			}
		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** deleteRole **/

	/** Function to Add Permission **/
	public function  addPermission($module,$perm,$desc)
	{
		if (!$this->is_permission_existing($module,$perm)) {
			$var = array(
							'modulename' => $module,
							'permission' => $perm,
							'comment' => $desc
						);
			$ret=insert_orm('userspermissions',$var);
			if($ret){
				return true;
			}else{
				return false;
			}
			
		}else{
			return true;
		}
	}/** addPermission **/

	/** Function to check if permission exists **/	
	public function is_permission_existing($module,$perm){
		$ret=query_orm("SELECT * FROM userspermissions WHERE ((modulename = '$module') AND (permission = '$perm'))");
		if($ret){
			return true;
		}else{
			return false;
		}
	}/** is_permission_existing **/


	/** Function to Get All Permissions **/
	public function viewAllPermissions()
	{
		if($this->isPermitted($this->_id, 'Add/Edit/Delete Users'))
		{
			$ret=query_orm("SELECT * FROM userspermissions ORDER BY modulename ASC ");
			if($ret){
				$this->allpermissions = $ret;
				return true;
			}else{
				$this->_errors[]	=	'Nothing Fetched';
				return false;
			}
		}else{
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** viewAllPermissions **/

	/** Get the Permission Name by giving permission id **/
	public function getPermissionName($id)
	{
		$data="SELECT permission FROM userspermissions WHERE id = '$id'";
		$ret=query_orm($data);
		if($ret){
			$this->permissionname = $ret[0]['permission'];
			return true;
		}else{
			return false;
		}
	}/** getPermissionName **/

	/** Get the Permission Id by giving permission Name **/
	public function getPermissionId($name)
	{
		$data="SELECT * FROM userspermissions WHERE permission = '".$name."'";
		$ret=query_orm($data);
		if($ret){
			$this->permissionid = $ret[0]['id'];
			return true;
		}else{
			return false;
		}
	}/** getPermissionId **/

	/** Function to Add Permission to Role **/
	public function  addRolePermission($role_id,$permission_id)
	{
		if($this->isPermitted($this->_id, 'Add/Edit/Delete Role Permissions'))
		{
			if(!$this->getRole($role_id)) {
				$this->_errors[]	=	'No Such Role Exist';
			}

			if(!$this->getPermissionName($permission_id)) {
				$this->_errors[]	=	'No Such Permission Exist';
			}

			if (sizeof($this->_errors) == 0) {
				$data="INSERT INTO userspermissionrole (role_id,permission_id) VALUES ('".$role_id."', '".$permission_id."')";
				$ret=query_orm($data);
				if($ret != 'false'){
					return true;
				}else{
					$this->_errors[]	=	$ret;
					return false;
				}
			}else{
				return false;
			}
		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** addRolePermission **/

	/** Function to Remove Permission to Role **/
	public function  removeRolePermission($role_id,$permission_id)
	{
		if($this->isPermitted($this->_id, 'Add/Edit/Delete Role Permissions'))
		{
			$data="DELETE FROM userspermissionrole WHERE permission_id = ".$permission_id." AND role_id = ".$role_id;
			$ret=query_orm($data);
			if($ret != 'false'){
				return true;
			}else{
				$this->_errors[]	=	$ret;
				return false;
			}
		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** removeRolePermission **/

	/** Function to Get Permissions of a Role **/
	public function  getRolePermissions($role_id)
	{
		if($this->isPermitted($this->_id, 'View Role Permissions'))
		{
			$data="SELECT * FROM userspermissionrole WHERE role_id = '$role_id'";
			$ret=query_orm($data);
			if($ret){
				$this->rolePermissions = $ret;
				return true;
			}else{
				return false;
			}
		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** getRolePermissions **/

	/** Checks if a user has permission **/
	public function isPermitted($role_id,$module)
	{
		if($_SESSION['user_id']==1){
			return true;
		}else {
			if($this->getPermissionId($module)){
				$perid=$this->permissionid;
				$ret=query_orm("SELECT * FROM userspermissionrole WHERE (role_id = '".$role_id."') AND (permission_id = '".$perid."')");
				if(sizeof($ret)>0) {
					return true;
				}else{
					return false;
				}
			}else {
				return false;
			}
		}
		// return true;
	}/** isPermitted **/

	/** Function to Get All Users **/
	public function viewAllUsers()
	{
		if($this->isPermitted($this->_role, 'View Users'))
		{
			$ret=all_orm($this->_usertable);
			if($ret){
				$this->allusers = $ret;
				return true;
			}else{
				$this->_errors[]	=	'Nothing Fetched';
				return false;
			}
		}else{
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** viewAllUsers **/

	/** Check if user exists **/
	public function isUser($un)
	{
		$data="SELECT id FROM users WHERE username = '$un'";
		$ret=query_orm($data);
		if($ret){
			return true;
		}else{
			return false;
		}
	}/** isUser **/

	/** Function to Add User **/
	public function  addUser($userdetails,$password)
	{
		if($this->isPermitted($this->_id, 'Add/Edit/Delete Users'))
		{
				$userdetails	= _clean($userdetails);
				$password	=	_clean($password);

				if ($this->isUser($userdetails['username'])) {
					$this->_errors[]	=	'Username not Available';
				}

				if ($userdetails['role']=='1') {
					$this->_errors[]	=	'You cannot Create Superadmin';
				}

				if (sizeof($this->_errors) == 0) {
					$ret=insert_orm($this->_usertable,$userdetails);
					if($ret){
						$this->_id=$GLOBALS["last_ID"];	//Get Last Updated Id
						$this->_password = $password;
						if ($this->hash_and_store()) {
							return true;
						}else{
							delete_orm($this->_usertable,$this->_id);
							return false;
						}
					}else{
						$this->_errors[]	=	'Could not add in Database';
						return false;
					}					
				}else{
					return false;
				}
			
		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** adduser **/

	/** Function to register User **/
	public function  registerUser()
	{
		if($this->isPermitted($this->_id, 'Add/Edit/Delete Users'))
		{
			if ($this->isTokenValid()) {
				$a_username	= _clean($_POST['a_username']);
				$a_password	=	_clean($_POST['a_password']);
				$a_role			=	_clean($_POST['a_role']);

				if(!fnValidateUsername($a_username)){
					$this->_errors[]	=	'Invalid Characteres in Username';
				}

				if(!fnValidatePassword($a_password)){
					$this->_errors[]	=	'Invalid Characteres in Password';
				}

				if(!fnValidateAlphanumeric($a_role)){
					$this->_errors[]	=	'Invalid Characteres in Role';
				}

				if ($this->isUser($a_username)) {
					$this->_errors[]	=	'Username not Available';
				}

				if ($a_role=='1') {
					$this->_errors[]	=	'You cannot Create Superadmin';
				}

				if (sizeof($this->_errors) == 0) {
					$var = array(
												'username' => $a_username,
												// 'password' => hash('sha512',$a_password),
												'role' => $a_role
											);
					$ret=insert_orm($this->_usertable,$var);
					if($ret){
						$this->_id=$GLOBALS["last_ID"];	//Get Last Updated Id
						$this->_password = $a_password;
						if ($this->hash_and_store()) {
							return true;
						}else{
							delete_orm($this->_usertable,$this->_id);
							return false;
						}
					}else{
						$this->_errors[]	=	'Could not add in Database';
						return false;
					}
				}else{
					return false;
				}
			}else {
				$this->_errors[]	=	'Invalid Token';
				return false;
			}

		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** registerUser **/

	/** Function to Delete User **/
	public function  deleteUser($id)
	{
		if($this->isPermitted($this->_id, 'Add/Edit/Delete Users'))
		{
			if($id!='1'){
				$ret=delete_orm($this->_usertable,$id);
				if($ret){
					return true;
				}else{
					$this->_errors[]	=	'Cannot delete from database';
					return false;
				}
			}else {
				$this->_errors[]	=	'You Cannot Delete Superadmin';
				return false;
			}
		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** deleteUser **/

	/** Function to Update User **/
	public function  updateUser($id,$userdetails)
	{
		if($this->isPermitted($this->_id, 'Add/Edit/Delete Users'))
		{

			$userdetails = _clean($userdetails);

			if(!fnValidateUsername($userdetails['username'])){
				$this->_errors[]	=	'Invalid Characteres in Username';
			}

			if(!fnValidateAlphanumeric($userdetails['role'])){
				$this->_errors[]	=	'Invalid Characteres in Role';
			}

			if (sizeof($this->_errors) == 0) {
					$ret=update_orm($this->_usertable,$userdetails,$id);
					if($ret){
						return true;
					}else{
						$this->_errors[]	=	'Cannot Update database';
						return false;
					}
			}else{
				return false;
			}
		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** updateUser **/

	/** Function to Update User Password**/
	public function  updateUserPassword($a_id,$a_password)
	{
		if($this->isPermitted($this->_id, 'Add/Edit/Delete Users'))
		{

			if(!fnValidatePassword($a_password)){
				$this->_errors[]	=	'Invalid Characteres in Password';
			}

			if (sizeof($this->_errors) == 0) {
					$this->_password = $a_password;
					$this->_id = $a_id;
					if ($this->hash_and_store()) {
						return true;
					}else{
						$this->_errors[]	=	'Cannot Update password';
						return false;
					}
					
			}else{
				return false;
			}
		}else {
			$this->_errors[]	=	'You are not Permitted';
			return false;
		}
	}/** updateUserPassword **/

	/** Check if user exists **/
	public function getUser($id)
	{
		$data="SELECT * FROM users WHERE id = $id";
		$ret=query_orm($data);
		if($ret){
			$this->userdetails=$ret;
			return true;
		}else{

			return false;
		}
	}/** getUser **/


}