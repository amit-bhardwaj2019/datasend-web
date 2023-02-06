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
        
    }
    public function login(Request $request, Response $response)
    {
        $arrData = $request->getParsedBody();
        $this->userGateway = new UserGateway($this->ci->get('db'));
        
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
}
?>