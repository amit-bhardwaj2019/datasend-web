<?php
namespace App\TableGateways;

use PDO;

class AdminGateway {

    private $db = null;
    private $returnErrors = [
        "code"  => 400
    ];

    public function __construct($db)
    {
        $this->db = $db;
    }

    

    public function find($id)
    {
        $statement = "
            SELECT 
            *
            FROM
                tbl_admin
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

    public function findByEmailAndPass($email, $pass)
    {
        $statement = "
            SELECT 
            id, email, password, contactusemail
            FROM
                tbl_admin
            WHERE email=:email 
            AND password=:pass
        ";

        try {
            $status = 1;
            $statement = $this->db->prepare($statement);            
            $statement->bindParam(':email', $email, \PDO::PARAM_STR);
            $statement->bindParam(':pass', $pass, \PDO::PARAM_STR);
            $statement->execute();
            
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
    
    public function updateAdminEmail($email, $id)
    {
        $statement = "
        UPDATE tbl_admin SET contactusemail=:cemail,email=:email 
        WHERE id=:id
        ";
        try {
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':cemail', $email, \PDO::PARAM_STR);
            $obj->bindParam(':email', $email, \PDO::PARAM_STR);
            $obj->bindParam(':id', $id, \PDO::PARAM_INT);
            $obj->execute();
            return $obj->rowCount();
        } catch (\PDOException $ex) {
            return $ex->getMessage();
        }
    }

    public function checkOldPass(String $password, int $id)
    {        
        $statement	= "
        SELECT * FROM tbl_admin WHERE password=:pass
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
            return $e->getMessage();
        }
    }

    public function updatePass(String $password, int $id)
    {
        $statement = "
        UPDATE tbl_admin SET password=:pass WHERE id=:id
        ";
        $pass = md5($password);
        try{
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':pass', $pass, PDO::PARAM_STR);
            $obj->bindParam(':id', $id, PDO::PARAM_INT);
            $obj->execute();               
            return $obj->rowCount(); 
        } catch(\PDOException $e) {  
            return $e->getMessage();
        }
    }

    public function paginate(int $offset, int $page_limit)
    {
        $statement = "
        SELECT * FROM tbl_user WHERE 1 and userlevel=:userlevel Order By name LIMIT :offset, :page_limit
        ";
        $userlevel = "1";
        try {
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':userlevel', $userlevel, PDO::PARAM_STR);
            $obj->bindParam(':offset', $offset, PDO::PARAM_INT);
            $obj->bindParam(':page_limit', $page_limit, PDO::PARAM_INT);
            $obj->execute();
            $result = $obj->fetchAll(\PDO::FETCH_ASSOC);                 
            return $result;
        } catch (\PDOException $ex) {
            return $ex->getMessage();
        }
    }

    public function findByEmail($email)
    {
        $pattern = '%' .$email. '%';
        $statement = "
        SELECT * FROM tbl_user WHERE 1 and userlevel=:userlevel AND email LIKE :email
        ";
        $userlevel = "1";

        try {
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':userlevel', $userlevel, \PDO::PARAM_STR);
            $obj->bindParam(':email', $pattern, \PDO::PARAM_STR);
            $obj->execute();
            $result = $obj->fetchAll(\PDO::FETCH_ASSOC);                     
            return $result;
        } catch (\PDOException $ex) {
            return $ex->getMessage();
        }
    }

    public function defualtSapce() {
        $statement = "
        SELECT filespace as k FROM tbl_admin
        ";
        try {
            $obj = $this->db->prepare($statement);
            $obj->execute();
            return $obj->fetch();
        } catch (\PDOException $ex) {
            return $ex->getMessage();
        }
    }

    public function checkForExistingAccount($email) {
        $statement = "
        SELECT id FROM tbl_user WHERE email = :email
        ";
        try {
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':email', $email, \PDO::PARAM_STR);
            $obj->execute();            
            return $obj->rowCount();
        } catch (\PDOException $ex) {
            return $ex->getMessage();
        }
    }

    public function insertMainUser(array $arrInputData) {        
        $statement = "
        INSERT INTO tbl_user SET name=:name ,address1=:add1, address2=:add2, city=:city,country=:country, zipcode=:zipcode, phone=:phone, email=:email, password=:pass,status=:status, userlevel=:userlevel, createdon=:time, token=:token, url_name=:url_name,totalspace=:totalspace, addedby=:addby, pin=:pin, isaccess=:isaccess, emailnotify=:emailnotify
        ";
        $password = md5($arrInputData['Email']);
        $createdon = time();
        $addby = 0;
        $pin = '';
        $isaccess = 0;
        $emailnotify = 0;
        try {
            $obj = $this->db->prepare($statement);
            $obj->bindParam(':name', $arrInputData['name'], \PDO::PARAM_STR);
            $obj->bindParam(':addby', $addby, \PDO::PARAM_INT);
            $obj->bindParam(':pin', $pin, \PDO::PARAM_STR);
            $obj->bindParam(':isaccess', $isaccess, \PDO::PARAM_INT);
            $obj->bindParam(':emailnotify', $emailnotify, \PDO::PARAM_INT);
            $obj->bindParam(':add1', $arrInputData['address1'], \PDO::PARAM_STR);
            $obj->bindParam(':add2', $arrInputData['address2'], \PDO::PARAM_STR);
            $obj->bindParam(':city', $arrInputData['city'], \PDO::PARAM_STR);
            $obj->bindParam(':country', $arrInputData['country'], \PDO::PARAM_STR);
            $obj->bindParam(':zipcode', $arrInputData['zipcode'], \PDO::PARAM_STR);
            $obj->bindParam(':phone', $arrInputData['phone'], \PDO::PARAM_STR);
            $obj->bindParam(':email', $arrInputData['email'], \PDO::PARAM_STR);
            $obj->bindParam(':pass', $password, \PDO::PARAM_STR);
            $obj->bindParam(':status', $arrInputData['status'], \PDO::PARAM_STR);
            $obj->bindParam(':userlevel', $arrInputData['userlevel'], \PDO::PARAM_STR);
            $obj->bindParam(':time', $createdon);
            $obj->bindParam(':token', $arrInputData['token'], \PDO::PARAM_STR);
            $obj->bindParam(':url_name', $arrInputData['url_name'], \PDO::PARAM_STR);
            $obj->bindParam(':totalspace', $arrInputData['totalspace'], \PDO::PARAM_INT);
            $obj->execute();            
            return $this->db->lastInsertId();
        } catch (\PDOException $ex) {
            return $ex->getMessage();
        }
    }

    
}