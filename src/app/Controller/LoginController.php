<?php 
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\TableGateways\UserGateway;
use App\Classes\JwtHandler;
use Valitron\Validator;

class LoginController
{
    private $ci;
    private $userGateway;
    private $returnErrors = [
        "code"=> 400
    ];
    private $returnData = [
        "code"  => 200
    ];
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
                
                    // setcookie('token', $token, time()+86400, '/', 'softdemonew.info', false, true);
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

    public function forgotPassword(Request $request, Response $response)
    {
        $input_data = $request->getParsedBody();
        $email = $input_data['email'];
        $validate = new Validator(['email' => $email]);
        $validate->rule('required','email')
                ->rule('email', 'email');

        if($validate->validate()) {
            // passes
            $token = $this->ci->get('common')->createToken();
            $record = $this->userGateway->getByEmail($email);
            if(is_array($record)) {
                $id		= $record['id'];
                $email	= $record['email'];
                $name	= $record['name'];
                $this->userGateway->updateToken($token, $id);
                $mail_func = $this->ci->get('common')->sendForgotPassEmail($name, $email, $token);
                if($mail_func) {
                    $this->returnData['message'] = "An email with password reset link is sent to you";
                    $r = json_encode($this->returnData);
                    $this->returnData = [
                        "code"  => 200
                    ];
                    $response->getBody()->write($r);
                    return $response->withHeader('Content-Type', 'application/json');
                } else {
                    $this->returnErrors['errors'] = 'There\'s some error while send email.';
                    $r    = json_encode($this->returnErrors);
                    $this->returnErrors = [
                        "code"  => 400
                    ];
                    $response->getBody()->write($r);
                    return $response->withHeader('Content-Type', 'application/json');
                }
            } else {
                $this->returnErrors['errors'] = 'This email address is not registered with us.';
                $r    = json_encode($this->returnErrors);
                $this->returnErrors = [
                    "code"  => 400
                ];
                $response->getBody()->write($r);
                return $response->withHeader('Content-Type', 'application/json');
            }
        }   else {
            // fail
            $this->returnErrors['errors'] = $validate->errors();
            $r    = json_encode($this->returnErrors);
            $this->returnErrors = [
                "code"  => 400
            ];
            $response->getBody()->write($r);
            return $response->withHeader('Content-Type', 'application/json');
        }

    }

    public function resetPassword(Request $request, Response $response)
    {
        if($request->getQueryParam('token') !== NULL) {
            $token          = $request->getQueryParam('token');
            $input_data     = $request->getParsedBody();
            $password       = $input_data['password'];
            $confirmpass    = $input_data['confirmpass'];
            $validate = new Validator(['password' => $password, 'confirmpass' => $confirmpass]);
            $validate->rule('required', ['password', 'confirmpass'])
                    ->rule('lengthMin','password', 9)
                    ->rule('equals', 'password', 'confirmpass');

            if($validate->validate()) {
                // passes
                $user_data = $this->userGateway->getByToken($token);
                $id = $user_data['id'];
                $hash_pass = md5($password);
                $affected_record = $this->userGateway->updateResetPass($hash_pass, $id);
                if($affected_record > 0) {
                    $this->returnData['message'] = 'Your Password has been changed successfully.Now you can login with this password.';
                    $this->returnData['success']    = true;
                    $r = json_encode($this->returnData);
                    $this->returnData = [
                        "code"  => 200
                    ];
                    $response->getBody()->write($r);
                    return $response->withHeader('Content-Type', 'application/json');
                } else {
                    $this->returnErrors['errors'] = "Invalid request!";
                    $r    = json_encode($this->returnErrors);
                    $this->returnErrors = [
                        "code"  => 400
                    ];
                    $response->getBody()->write($r);
                    return $response->withHeader('Content-Type', 'application/json');
                }
            } else {
                $this->returnErrors['errors'] = $validate->errors();
                $r    = json_encode($this->returnErrors);
                $this->returnErrors = [
                    "code"  => 400
                ];
                $response->getBody()->write($r);
                return $response->withHeader('Content-Type', 'application/json');
            }
        } else {
            // Incorrect link.
            $this->returnErrors['errors'] = "Invalid request!";
            $r    = json_encode($this->returnErrors);
            $this->returnErrors = [
                "code"  => 400
            ];
            $response->getBody()->write($r);
            return $response->withHeader('Content-Type', 'application/json');
        }
        
        var_dump($request->getParsedBody());
    }
}
?>