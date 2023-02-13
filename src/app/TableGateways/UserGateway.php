<?php
namespace App\TableGateways;

class UserGateway {

    private $db = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findAll()
    {
        $statement = "
            SELECT 
                id, addedBy, name, phone, email
            FROM
                tbl_user;
        ";

        try {
            $statement = $this->db->query($statement);
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function find($id)
    {
        $statement = "
            SELECT 
            *
            FROM
                tbl_user
            WHERE id = ?;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array($id));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function findByEmail($email)
    {
        $statement = "
            SELECT 
            id, addedBy, name, phone, email,password,pin_auth
            FROM
                tbl_user
            WHERE email = ? 
            AND status = ?;
        ";

        try {
            $status = 1;
            $statement = $this->db->prepare($statement);            
            $statement->bindParam(1, $email);
            $statement->bindParam(2, $status);
            $statement->execute();
            
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function getAuthPinRelatedData(int $id)
    {
        $statement = "
            SELECT * FROM tbl_user WHERE id = ? AND status = ?
        ";
        try {
            $status = 1;
            $obj = $this->db->prepare($statement);
            $obj->bindParam(1, $id);
            $obj->bindParam(2, $status);
            $obj->execute();
            $result = $obj->fetchAll(\PDO::FETCH_ASSOC);            
            return $result;
        } catch (\PDOException $e) {
            
        }
    }

    public function insertIntoTableLog(int $id, String $action)
    {
        $statement = "
        INSERT INTO tbl_logs SET loggedby = ?, ipaddress = ?, action= ?, createdon = ?
        ";
        try {
            $ipaddress = $_SERVER['REMOTE_ADDR'];            
            $created_on = time();
            $query = $this->db->prepare($statement)->execute([$id, $ipaddress, $action, $created_on]);
            

        } catch (\PDOException $e) {

        }
    }

    public function insertIntoTableUserLog(String $email)
    {
        $statement = "
        INSERT INTO tbl_userlog SET username = ?, session_id = ?, login= ?
        ";
        try {
            $session_id = uniqid();            
            $login = date('Y-m-d h:i:s');
            $query = $this->db->prepare($statement)->execute([$email, $session_id, $login]);
            

        } catch (\PDOException $e) {

        }
    }

    public function update(int $user_id, Array $data)
    {
        $statement = "
        UPDATE tbl_user SET name=:name, address1=:add1, address2=:add2, city=:city,country=:country,zipcode=:zip,phone=:phone, email=:email, javauploadfolder=:upd WHERE id=:user_id
        ";
        try {
            $obj = $this->db->prepare($statement);
            
            $obj->bindParam(':name', $data['name'], \PDO::PARAM_STR);
            $obj->bindParam(':add1', $data['address1'], \PDO::PARAM_STR);
            $obj->bindParam(':add2', $data['address2'], \PDO::PARAM_STR);
            $obj->bindParam(':city', $data['city'], \PDO::PARAM_STR);
            $obj->bindParam(':country', $data['country'], \PDO::PARAM_STR);
            $obj->bindParam(':zip', $data['zipcode'], \PDO::PARAM_STR);
            $obj->bindParam(':phone', $data['phone'], \PDO::PARAM_STR);
            $obj->bindParam(':email', $data['email'], \PDO::PARAM_STR);
            $obj->bindParam(':upd', $data['uploadfolder'], \PDO::PARAM_STR);
            $obj->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
            $obj->execute();
            
        } 
        catch(\PDOException $ex) {
            $returnData = [
                'code' => 400,
                'message' => $ex->getMessage()
            ];
            $res['body']    = json_encode($returnData);
            return $res['body'];
        }
		$action = getenv('UPDATE_PROFILE_MSG');

		$this->insertIntoTableLog($user_id, $action);
    }
}