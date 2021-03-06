<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class MUsers extends CI_Model {
	public function __construct() {
		parent::__construct();
	}
	
	public function checkExistUsername($username)
	{
		$sql = "SELECT * FROM dbo.att_users
			WHERE username = ?";
		return $this->db->query($sql, array($username));
	}

	public function checkExistUserId($userId)
	{
		$sql = "SELECT * FROM dbo.att_users
			WHERE userid = ?";
		return $this->db->query($sql, array($userId));
	}

	public function checkUser($username,$password,$domain,$status) {
		$query = $this->db->get_where('att_users', array('username'=>$username,'password'=>$password,'domain'=>$domain,'status'=>$status));
		return $query->num_rows();
	}
	
	public function getUsers()
	{
		$sql = "SELECT *, c.NAME as nama FROM dbo.att_users a, dbo.att_role b, dbo.USERINFO c
			WHERE 1=1
			AND a.roleid = b.role_id
			AND a.userid = c.USERID";
		return $this->db->query($sql);
	}
	
	public function checkUserAD($username,$domain,$status)
	{
		$query = $this->db->get_where('att_users', array('username'=>$username,'domain'=>$domain,'status'=>$status));
		return $query->num_rows();
	}
	
	public function addUser($data)
	{
		return $this->db->insert('att_users', $data); 
	}
	
	public function getUserAD($username,$domain,$status)
	{
		$query = $this->db->get_where('att_users', array('username'=>$username,'domain'=>$domain,'status'=>$status));
		return $query;
	}
	
	public function do_delete($user_id)
	{
		$this->db->delete('dbo.att_users', array('id' => $user_id));	
	}
}

?>
