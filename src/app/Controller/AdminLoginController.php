<?php 
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\TableGateways\AdminGateway;
use App\Classes\JwtHandler;
use Valitron\Validator;

class AdminLoginController
{
    private $ci;
    private $adminGateway;
    private $returnErrors = [
        "code"=> 400
    ];
    private $returnData = [
        "code"  => 200
    ];
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
        $this->adminGateway = new AdminGateway($this->ci->get('db')); 
    }
    public function login(Request $request, Response $response)
    {
        $arrData = $request->getParsedBody();        
        
        $validate = new Validator(['email' => $arrData['email'], 'password' => $arrData['password']]);

        $validate->rule('required', ['email', 'password'])
                ->rule('email', 'email');

        if($validate->validate()) {
            $hash_pass = md5($arrData['password']);
            $result = $this->adminGateway->findByEmailAndPass($arrData['email'], $hash_pass); 
            if(!empty($result['email'])) {            
                $jwt = new JwtHandler();
                $token = $jwt->jwtEncodeData(
                    getenv('APP_URL'),
                    [
                        "id"                => $result['id'],
                        "email"             => $result['email'],
                        "contactusemail"    => $result['contactusemail'],
                        "filespace"         => $result['filespace']
                    ]); 
    
                $this->returnData['message']    = 'Success';
                $this->returnData['token']      = $token;
                $res['body'] = json_encode($this->returnData);
                unset($this->returnData['message'], $this->returnData['token']);            
            } else {
                // wrong input credentials            
                $this->returnErrors['message'] = 'Please enter valid credentials';
                $res['body'] = json_encode($this->returnErrors);
                unset($this->returnErrors['message']);            
            }
        } else {
            // validation fails
            $this->returnErrors['errors'] = $validate->errors();
            $res['body'] = json_encode($this->returnErrors);
            unset($this->returnErrors['errors']);
        }

        $response->getBody()->write($res['body']);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
?>