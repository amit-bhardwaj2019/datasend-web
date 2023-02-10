<?php 
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\TableGateways\UserGateway;
use App\Classes\JwtHandler;
use Carbon\Carbon;
use Exception;

class UserController {

    private $ci;    
    private $userGateway;
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
        $this->userGateway = new UserGateway($this->ci->get('db'));
    }

    public function show(Request $request, Response $response)
    {
        $user = $this->userGateway->find(345);
        return $response->getBody()->write('hello');
    }

    public function store(Request $request, Response $response)
    {

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
                
                    setcookie('token', $token, time()+86400, '/', 'softdemonew.info', false, true);
                $returnData = [
                    'code'      => 200,
                    'message'   => 'Success',
                    'token'     => $token,
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
        try {
            if($request->hasHeader('HTTP_AUTHORIZATION')) 
            {
                $token = str_replace('Bearer ', '', $request->getHeaderLine('HTTP_AUTHORIZATION'));
                $jwt = new JwtHandler();
                $decoded_object = $jwt->jwtDecodeData($token);
                if(gettype($decoded_object) === "string") 
                    throw new \Exception('Token is Expired!');
                else {
                    if($decoded_object->iss !== getenv('APP_URL')) throw new \Exception('Domain mismatch');
                }
            }

        } catch(\Exception $e) {
            $returnData = [
                'code' => 400,
                'message' => $e->getMessage()
            ];
            $res['body']    = json_encode($returnData);
            $response->getBody()->write($res['body']);
            return $response->withHeader('Content-Type', 'application/json');
        }
        $arrData = $request->getParsedBody();
        // verify token 
        $user_id = $decoded_object->data->id;
        $data = $this->userGateway->getAuthPinRelatedData($user_id);
		
		if(!empty($data))
		{
			$pin_hashed = $data[0]['pin'];
			$email      = $data[0]['email'];
			
			$pwd_peppered = hash_hmac("sha256", $arrData['pin'], getenv('DROOM_PIN_SALT'));
            
			
			if ($pin_hashed==$pwd_peppered) {
				
				$this->userGateway->insertIntoTableLog($user_id, 'Login');				
                $this->userGateway->insertIntoTableUserLog($email);

                $returnData = [
                    'code'      => 200,
                    'message'   => 'Success',
                    'success'   => true
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

    public function userDetails(Request $request, Response $response)
    {
        try {
            if($request->hasHeader('HTTP_AUTHORIZATION')) 
            {
                $token = str_replace('Bearer ', '', $request->getHeaderLine('HTTP_AUTHORIZATION'));
                $jwt = new JwtHandler();
                $decoded_object = $jwt->jwtDecodeData($token);
                if(gettype($decoded_object) === "string") 
                    throw new \Exception('Token is Expired!');
                else {
                    if($decoded_object->iss !== getenv('APP_URL')) throw new \Exception('Domain mismatch');
                }
            }

        } catch(\Exception $e) {
            $returnData = [
                'code' => 400,
                'message' => $e->getMessage()
            ];
            $res['body']    = json_encode($returnData);
            $response->getBody()->write($res['body']);
            return $response->withHeader('Content-Type', 'application/json');
        }
        // get user id from jwt 
        $user_id = $decoded_object->data->id;
        $user_details = $this->userGateway->find($user_id);
        $user_type = $user_details[0]['userlevel'] === 1 ? 'user' : 'subuser';
        $data = [
            'name' => $user_details[0]['name'],
            'email' => $user_details[0]['email'],
            'type_id'   => $user_details[0]['userlevel'],
            'type'   => $user_type
        ];
        $returnData = [
            'code'      => 200,
            'success'   => true,
            'user' => $data
        ];
        $res['body']    = json_encode($returnData);
        $response->getBody()->write($res['body']);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
?>