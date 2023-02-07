<?php 
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\TableGateways\UserGateway;
use App\Classes\JwtHandler;

class UserController {

    private $ci;    
    private $userGateway;
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
        $this->userGateway = new UserGateway($this->ci->get('db'));
    }
    public function login(Request $request, Response $response)
    {
        $arrData = $request->getParsedBody();        
        
        //
        $result = $this->userGateway->findByEmail($arrData['email']);
        if(!empty($result[0]['email'])) {
            $check_password = password_verify($result[0]['password'], password_hash(md5($arrData['password']), PASSWORD_DEFAULT));
            if($check_password){

                $jwt = new JwtHandler();
                $token = $jwt->jwtEncodeData(
                    getenv('APP_URL'),
                    [
                        "id"=> $result[0]['id'],
                        "name" => $result[0]['name'],
                        "phone" => $result[0]['phone']
                    ]);
                   /* setcookie('token', $token,[
                        'Expires' => time()+86400,
                        'Path'      => '/',
                        'domain'    => 'localhost:8888',
                        'Secure'    => true,
                        'HttpOnly'  => true,
                        'SameSite'  => 'None'
                    ]);*/
                    $expires = time()+86400;
                    header ("Set-Cookie: token=$token; expires=$expires;path=/; domain=localhost:8888; secure=true; httponly=true; samesite=None");
                    
                $returnData = [
                    'code'      => 200,
                    'message'   => 'Success',
                    'pin_auth'  => $result[0]['pin_auth'] === "1" ? true : false
                ];
                
                $res['body'] = json_encode($returnData);
            } else {
                // wrong pass
                $returnData = [
                    'code' => 400,
                    'message' => 'Please enter valid credentials'
                ];
                $res['body'] = json_encode($returnData);
            }
        } else {
            // wrong credentials
            $returnData = [
                'code' => 400,
                'message' => 'Please enter valid credentials'
            ];
            $res['body'] = json_encode($returnData);

        }
        $response->getBody()->write($res['body']);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function verifyAuthenticationPin(Request $request, Response $response)
    {
        var_dump(getallheaders());
        $arrData = $request->getParsedBody();
        // verify token 
        
        $data = $this->userGateway->getAuthPinRelatedData($arrData['userid']);
		
		if(!empty($data))
		{
			$pin_hashed = $data[0]['pin'];
			$email      = $data[0]['email'];
			
			$pwd_peppered = hash_hmac("sha256", $arrData['pin'], getenv('DROOM_PIN_SALT'));
            
			
			if ($pin_hashed==$pwd_peppered) {
				
				$this->userGateway->insertIntoTableLog($arrData['userid'], 'Login');				
                $this->userGateway->insertIntoTableUserLog($email);

                $returnData = [
                    'code'      => 200,
                    'message'   => 'Success'
                ];
                $res['body'] = json_encode($returnData);
				
			}
			else{
				$returnData = [
                    'code' => 400,
                    'message' => 'Please enter valid pin.'
                ];
                $res['body'] = json_encode($returnData);
			}
            
		} else {
            $returnData = [
                'code' => 400,
                'message' => 'Please enter valid pin.'
            ];
            $res['body'] = json_encode($returnData);
            
        }

        $response->getBody()->write($res['body']);
        return $response->withHeader('Content-Type', 'application/json');

    }
}
?>