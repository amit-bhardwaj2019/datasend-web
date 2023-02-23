<?php
namespace App\TableGateways;

use PDO;

class UserGateway {

    private $db = null;
    private $returnErrors = [
        "code"  => 400
    ];

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
            $this->returnErrors['errors'] = $ex->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->returnErrors = [
                "code"  => 400
            ];
            return $res['body'];
        }
		$action = getenv('UPDATE_PROFILE_MSG');

		$this->insertIntoTableLog($user_id, $action);
    }

    public function checkOldPass(String $password, int $id)
    {        
        $statement	= "
        SELECT * FROM tbl_user WHERE password=:pass
         AND id=:id
         ";
         $pass = md5($password);
        try {
            
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':pass', $pass, PDO::PARAM_STR);
            $obj->bindParam(':id', $id, PDO::PARAM_INT);
            $obj->execute();
            $result = $obj->rowCount();                        
            return $result;
        } catch (\PDOException $e) {
            var_dump($e->getMessage());
        }

    }

    public function updatePass(String $password, int $id)
    {
        $statement = "
        UPDATE tbl_user SET password=:pass WHERE id=:id
        ";
        $pass = md5($password);
        try{
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':pass', $pass, PDO::PARAM_STR);
            $obj->bindParam(':id', $id, PDO::PARAM_INT);
            $obj->execute();               
            return $obj->rowCount(); 
        } catch(\PDOException $e) {            
            $this->returnErrors['errors'] = $e->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->returnErrors = [
                "code"  => 400
            ];
            return $res['body'];
        }
    }

    public function updatePin(String $pin, int $id, $pin_auth)
    {
        $statement = "
        UPDATE tbl_user SET pin=:pin,  pin_auth=:pin_auth WHERE id=:id
        ";    
        $pin_auth = $pin_auth === true ? '1' : '0';    
        try{
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':pin', $pin, PDO::PARAM_STR);
            $obj->bindParam(':id', $id, PDO::PARAM_INT);
            $obj->bindParam(':pin_auth', $pin_auth, PDO::PARAM_STR);
            $obj->execute();               
            return $obj->rowCount(); 
        } catch(\PDOException $e) {            
            $this->returnErrors['errors'] = $e->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->returnErrors = [
                "code"  => 400
            ];
            return $res['body'];
        }
    }

    public function getByEmail($email)
    {
        $statement = "
            SELECT 
            id, addedBy, name, phone, email,password,pin_auth
            FROM
                tbl_user
            WHERE email = ?
        ";

        try {
            $statement = $this->db->prepare($statement);            
            $statement->bindParam(1, $email);
            $statement->execute();            
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function updateToken($token, $id)
    {
        $statement = "
        UPDATE tbl_user SET token=:token WHERE id=:id
        ";
        try{
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':token', $token, PDO::PARAM_STR);
            $obj->bindParam(':id', $id, PDO::PARAM_INT);
            $obj->execute();               
            return $obj->rowCount(); 
        } catch(\PDOException $e) {            
            $this->returnErrors['errors'] = $e->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->returnErrors = [
                "code"  => 400
            ];
            return $res['body'];
        }
    }

    public function updateResetPass($password, $id)
    {
        $statement = "
        UPDATE tbl_user SET password=:pass, token=:emptok WHERE id=:id
        ";
        $emptok = '';
        $pass = $password;
        try{
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':pass', $password, PDO::PARAM_STR);
            $obj->bindParam(':emptok', $emptok, PDO::PARAM_STR);
            $obj->bindParam(':id', $id, PDO::PARAM_INT);
            $obj->execute();               
            return $obj->rowCount(); 
        } catch(\PDOException $e) {            
            $this->returnErrors['errors'] = $e->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->returnErrors = [
                "code"  => 400
            ];
            return $res['body'];
        }
    }

    public function updateResetPin($pin_arg, $id)
    {
        $statement = "
        UPDATE tbl_user SET pin=:pin, token=:emptok WHERE id=:id
        ";
        $emptok = '';
        $pin = $pin_arg;
        try{
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':pin', $pin, PDO::PARAM_STR);
            $obj->bindParam(':emptok', $emptok, PDO::PARAM_STR);
            $obj->bindParam(':id', $id, PDO::PARAM_INT);
            $obj->execute();               
            return $obj->rowCount(); 
        } catch(\PDOException $e) {            
            $this->returnErrors['errors'] = $e->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->returnErrors = [
                "code"  => 400
            ];
            return $res['body'];
        }
    }

    public function getByToken($token)
    {
        $statement = "
            SELECT 
            id, addedBy, name, phone, email,password,pin_auth
            FROM
                tbl_user
            WHERE token=:token
        ";

        try {
            $statement = $this->db->prepare($statement);            
            $statement->bindParam(':token', $token, \PDO::PARAM_STR);
            $statement->execute();            
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }  
    }

    public function disablePin($id)
    {
        $statement = "
        UPDATE tbl_user SET pin_auth=:pin_auth WHERE id=:id
        ";    
        $pin_auth = '0';    
        try{
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':id', $id, PDO::PARAM_INT);
            $obj->bindParam(':pin_auth', $pin_auth, PDO::PARAM_STR);
            $obj->execute();               
            return $obj->rowCount(); 
        } catch(\PDOException $e) {            
            $this->returnErrors['errors'] = $e->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->returnErrors = [
                "code"  => 400
            ];
            return $res['body'];
        }
    }

    public function GetAssignGroup($ID) {
        $query = "
        SELECT groupname as k FROM tbl_groups WHERE user LIKE '%::" . $ID . "::%' 
        ";
        try {
            $obj = $this->db->query($query);            
            $num = $obj->rowCount();
            $NameArr = array();
            $records = $obj->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach($records AS $record) {
                $NameArr[] = $record['k'];                
            }
            $Name = @implode(",", $NameArr);        
            return $Name;
        } catch (\PDOException $ex) {
            return $ex->getMessage();
        }     
    }
}