<?php
require_once '../config.php';
class Login extends DBConnection {
	private $settings;
	private $password_algo;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		$this->password_algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;

		parent::__construct();
		ini_set('display_error', 1);
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function index(){
		echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
	}
	private function is_legacy_md5($hash){
		return is_string($hash) && preg_match('/^[a-f0-9]{32}$/i', $hash);
	}
	private function verify_password($password, $hash){
		if(empty($hash)) return false;
		if($this->is_legacy_md5($hash)){
			return hash_equals(strtolower($hash), md5($password));
		}
		return password_verify($password, $hash);
	}
	private function hash_password($password){
		return password_hash($password, $this->password_algo);
	}
	private function should_rehash_password($hash){
		if($this->is_legacy_md5($hash)) return true;
		return password_needs_rehash($hash, $this->password_algo);
	}
	public function login(){
		extract($_POST);
		$username = $this->conn->real_escape_string($username);
		$qry = $this->conn->query("SELECT * from users where username = '{$username}' limit 1");
		if($qry->num_rows > 0){
			$user = $qry->fetch_array();
			if(!$this->verify_password($password, $user['password'])){
				return json_encode(array('status'=>'incorrect'));
			}
			if($this->should_rehash_password($user['password'])){
				$new_hash = $this->conn->real_escape_string($this->hash_password($password));
				$this->conn->query("UPDATE users set password = '{$new_hash}' where id = '{$user['id']}'");
			}
			foreach($user as $k => $v){
				if(!is_numeric($k) && $k != 'password'){
					$this->settings->set_userdata($k,$v);
				}

			}
			$this->settings->set_userdata('login_type',1);
			// If the user has pending assigned tasks, set a session flag to show flash once after login
			$user_id = $this->settings->userdata('id');
			if(!empty($user_id)){
				$task_q = $this->conn->query("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = '{$user_id}' AND status IN ('pending','in_progress')");
				if($task_q){
					$task_count = $task_q->fetch_assoc()['count'];
					if($task_count > 0){
							session_start();
							$_SESSION['show_tasks_flash'] = 1;
						}
				}
			}
		return json_encode(array('status'=>'success'));
		}else{
		return json_encode(array('status'=>'incorrect'));
		}
	}
	public function logout(){
		if($this->settings->sess_des()){
			redirect('admin/login.php');
		}
	}
	function login_user(){
		extract($_POST);
		$username = $this->conn->real_escape_string($username);
		$qry = $this->conn->query("SELECT * from users where username = '{$username}' and `type` = 2 limit 1");
		if($qry->num_rows > 0){
			$user = $qry->fetch_array();
			if(!$this->verify_password($password, $user['password'])){
				return json_encode(array('status'=>'incorrect'));
			}
			if($this->should_rehash_password($user['password'])){
				$new_hash = $this->conn->real_escape_string($this->hash_password($password));
				$this->conn->query("UPDATE users set password = '{$new_hash}' where id = '{$user['id']}'");
			}
			foreach($user as $k => $v){
				$this->settings->set_userdata($k,$v);
			}
			$this->settings->set_userdata('login_type',2);
		$resp['status'] = 'success';
		}else{
		$resp['status'] = 'incorrect';
		}
		if($this->conn->error){
			$resp['status'] = 'failed';
			$resp['_error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	public function logout_user(){
		if($this->settings->sess_des()){
			redirect('./');
		}
	}
}
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
	case 'login':
		echo $auth->login();
		break;
	case 'login_user':
		echo $auth->login_user();
		break;
	case 'logout':
		echo $auth->logout();
		break;
	case 'logout_user':
		echo $auth->logout_user();
		break;
	default:
		echo $auth->index();
		break;
}

