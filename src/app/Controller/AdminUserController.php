<?php 
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\TableGateways\AdminGateway;
use App\Classes\JwtHandler;
use Valitron\Validator;

class AdminUserController {
    private $ci;    
    private $adminGateway;
    private $returnErrors = [
        "code"  => 400
    ];
    private $returnData = [
        "code"      => 200,
        "success"   => true
    ];
    private $user_id = null;
    private $email = null;
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
        $this->adminGateway = new AdminGateway($this->ci->get('db'));        

        try {
            if($this->ci->get('request')->hasHeader('HTTP_AUTHORIZATION')) 
            {
                $token = str_replace('Bearer ', '', $this->ci->get('request')->getHeaderLine('HTTP_AUTHORIZATION'));
                $jwt = new JwtHandler();
                $decoded_object = $jwt->jwtDecodeData($token);
                if(gettype($decoded_object) === "string") 
                    throw new \Exception('Token is Expired!');
                else {
                    if($decoded_object->iss !== getenv('APP_URL')) throw new \Exception('Domain mismatch');
                }
                $this->user_id = $decoded_object->data->id;
                $this->email = $decoded_object->data->contactusemail;
            }

        } catch(\Exception $e) {
            
            $this->returnErrors['message'] = $e->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->ci->get('response')->getBody()->write($res['body']);
            unset($this->returnErrors['message'], $res['body']);
            return $this->ci->get('response')->withHeader('Content-Type', 'application/json');
        }
    }

    public function getEmail(Request $request, Response $response)
    {
        if(!is_null($this->email)) {
            $this->returnData['details'] = ['email' => $this->email];
            $this->returnData['success'] = true;
            $r = json_encode($this->returnData);
            unset($this->returnData['details'], $this->returnData['success']);
        } else {
            $this->returnErrors['message'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $r = json_encode($this->returnErrors);
            unset($this->returnErrors['message']);
        }
        $response->getBody()->write($r);
        unset($r);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function changeEmail(Request $request, Response $response)
    {
        $input_data = $request->getParsedBody();
        $email = $input_data['email'];
        $validate = new Validator(['email' => $email]);
        $validate->rule('required', 'email')
                ->rule('email', 'email');

        if($validate->validate()) {
            $affected_row = $this->adminGateway->updateAdminEmail($email, $this->user_id);
            if($affected_row > 0) {
                $this->returnData['message'] = 'Email updated successfully!';
                $r = json_encode($this->returnData);
                unset($this->returnData['message']);
            } else {
                $this->returnErrors['errors'] = 'Old and new email are same.';
                $r = json_encode($this->returnErrors);
                unset($this->returnErrors['errors']);
            }
        } else {
            // validation fails
            $this->returnErrors['errors'] = $validate->errors();
            $r = json_encode($this->returnErrors);
            unset($this->returnErrors['errors']);
        }

        $response->getBody()->write($r);
        unset($r);
        return $response->withHeader('Content-Type', 'application/json');
        
    }

    public function updatePassword(Request $request, Response $response)
    {
        $user_id = $this->user_id;        
        $input_data = $request->getParsedBody();
        $oldPass = $input_data['oldpass'];       
        $newPass = $input_data['newpass'];
        $confirmPass = $input_data['confirmpass'];
        $validate = new Validator(['oldpass' => $oldPass,'newpass' => $newPass, 'confirmpass' => $confirmPass]);
        
        $validate->rule('required', ['oldpass', 'newpass', 'confirmpass'])
                ->message('{field} is required.')
                ->labels([
                    'oldpass' => 'Old Password',
                    'newpass'   => 'New Password',
                    'confirmpass'   => 'Confirm Password'
                ])
                ->rule('lengthMin','newpass', 9)
                ->rule('equals', 'newpass', 'confirmpass');

        if($validate->validate()) {
            // password matches, here write the sql query to change
            $row_count = $this->adminGateway->checkOldPass($oldPass, $user_id);
            
            if(is_int($row_count) && $row_count > 0) {
                $res = $this->adminGateway->updatePass($newPass,$user_id); 
                              
                if($res > 0) {                     
                    $this->returnData['message'] = "Password has been changed successfully.";
                    $r = json_encode($this->returnData);
                    unset($this->returnData['message']);                    
                } else {                    
                    $this->returnErrors['errors'] = "Old and new password can't be same.";
                    $r = json_encode($this->returnErrors);
                    unset($this->returnErrors['errors']);
                }                
            } else {
                $this->returnErrors['errors'] = "Your old password is incorrect.";
                $r    = json_encode($this->returnErrors);
                unset($this->returnErrors['errors']);                
            }
        } else {            
            
            $this->returnErrors['errors'] = $validate->errors();
            $r    = json_encode($this->returnErrors);            
            unset($this->returnErrors['errors']);            
        }

        $response->getBody()->write($r);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
?>