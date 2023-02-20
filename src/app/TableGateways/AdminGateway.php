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
}