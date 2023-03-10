<?php 
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\TableGateways\UserGateway;
use App\Classes\JwtHandler;
use Exception;
use Valitron\Validator;
class UserController {

    private $ci;    
    private $userGateway;
    private $returnErrors = [
        "code"  => 401
    ];
    private $returnData = [
        "code"      => 200,
        "success"   => true
    ];
    private $auth_status = 0; //0 Unathorized, 1 //Valid user
    private $user_id = null;
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
        $this->userGateway = new UserGateway($this->ci->get('db'));        

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
                $this->auth_status = 1;
            } else {
                throw new \Exception('Unauthorized Request!');
            }

        } catch(\Exception $e) {
            $this->auth_status = 0;
            /*
            $this->returnErrors['message'] = $e->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->ci->get('response')->getBody()->write($res['body']);
            unset($this->returnErrors['message']);
            return $this->ci->get('response')->withHeader('Content-Type', 'application/json');
            */
        }
    }
    /**
     * @SWG\Info(title="Get Edit user info details", version="1.0")
     */

    /**
     * @SWG\Get(
     *     path="/api/users",
     *     @SWG\Response(response="200", description="An example resource")
     * )
     */
    public function show(Request $request, Response $response)
    {        
        // get user id from jwt 
        if(!is_null($this->user_id)) {
            $user_id = $this->user_id;
            $user_details = $this->userGateway->find($user_id);
            if((int)$user_details['userlevel'] === 1) {
                $arrData = [
                    'name'		        => $user_details['name'],
                    'address1'	        => $user_details['address1'],
                    'address2'	        => $user_details['address2'],
                    'city'		        => $user_details['city'],
                    'country'	        => $user_details['country'],
                    'zipcode'	        => $user_details['zipcode'],
                    'email'		        => $user_details['email'],		
                    'phone'		        => $user_details['phone'],            
                    'uploadfolder'  => (int)$user_details['javauploadfolder'],
                    'totalspace'        => $user_details['totalspace'],
                ];
            } else {
                $arrData = [
                    'name'		        => $user_details['name'],
                    'address1'	        => $user_details['address1'],
                    'address2'	        => $user_details['address2'],
                    'city'		        => $user_details['city'],
                    'country'	        => $user_details['country'],
                    'zipcode'	        => $user_details['zipcode'],
                    'email'		        => $user_details['email'],		
                    'phone'		        => $user_details['phone']
                ];
            }
            $returnData = [
                'code'      => 200,
                'success'   => true,
                'user'      => $arrData
            ];
            $res['body']    = json_encode($returnData);
            $response->getBody()->write($res['body']);
            return $response->withHeader('Content-Type', 'application/json');
        }   else {
            $this->returnErrors['errors'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $response->getBody()->write(json_encode($this->returnErrors));
            $this->returnErrors = [
                "code"  => 400
            ];
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function update(Request $request, Response $response)
    {
        
        // get user id from jwt 
        if(!is_null($this->user_id)) {
            $user_id = $this->user_id;
            $user_details = $this->userGateway->find($user_id);
            $data = $request->getParsedBody();
            if($user_details['userlevel'] === 2 && is_null($data['uploadfolder'])) {
                $data['uploadfolder']   = "1";
            }
            
            $validate = new Validator($data);
            $validate->rule('required', ['name']);
            if($validate->validate()) {
                $ret = $this->userGateway->update($user_id, $data);
                if(isset($ret)) {
                    $response->getBody()->write($ret);
                    return $response->withHeader('Content-Type', 'application/json');
                } else {
                    $returnData = [
                        'code'      => 200,
                        'success'   => true,
                        'message'   => 'Record updated successfully!'
                    ];
                    $res['body']    = json_encode($returnData);
                    $response->getBody()->write($res['body']);
                    return $response->withHeader('Content-Type', 'application/json');
                }
            } else {
                // Errors
                $returnData = [
                    'code'  => 400,
                    'errors'   => $validate->errors() 
                ];
                $res['body']    = json_encode($returnData);
                $response->getBody()->write($res['body']);
                return $response->withHeader('Content-Type', 'application/json');
            }

        }   else {
            $this->returnErrors['errors'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $response->getBody()->write(json_encode($this->returnErrors));
            $this->returnErrors = [
                "code"  => 400
            ];
            return $response->withHeader('Content-Type', 'application/json');             
        }

    }
    
    public function verifyPin(Request $request, Response $response)
    {
        
        $arrData = $request->getParsedBody();
        // verify token 
        if(!is_null($this->user_id)) {
            $user_id = $this->user_id;
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
        }   else {
            $this->returnErrors['errors'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $response->getBody()->write(json_encode($this->returnErrors));
            $this->returnErrors = [
                "code"  => 400
            ];
            return $response->withHeader('Content-Type', 'application/json');            
        }
    }

    public function userDetails(Request $request, Response $response)
    {        
        // get user id from jwt 
        if($this->auth_status === 1 && !is_null($this->user_id)) {
            $user_id = $this->user_id;
            $user_details = $this->userGateway->find($user_id);
            $type_id = (int)$user_details['userlevel'];
            $user_type = $type_id === 1 ? 'user' : 'subuser';
            $data = [
                'name' => $user_details['name'],
                'email' => $user_details['email'],
                'type_id'   => $type_id,
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
        }   else {
            $this->returnErrors['errors'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $response->getBody()->write(json_encode($this->returnErrors));          
            unset($this->returnErrors['errors']);
            return $response->withHeader('Content-Type', 'application/json'); 
        }
    }

    public function dashboard(Request $request, Response $response)
    {        
        // get user id from jwt 
        if(!is_null(($this->user_id))) {
            $user_id = $this->user_id;
            $user_details = $this->userGateway->find($user_id);
            $qa = $user_details['isaccess'];
            
            if((int)$user_details['userlevel'] === 1) {
                $data = [
                    "Edit Information"      => true,
                    "Manage Root Folders"   => true,
                    "Manage Files"          => true,
                    "Manage Sub Users"      => true,
                    "Manage Groups"         => true,
                    "Change Password"       => true,
                    "Set Pin"               => true,
                    "Question and Answer Module"    => true,
                    "Contact Support"       => true
                ];
            } else {
                $data = [
                    "Edit Information" => true,
                    "Manage Files"          => true,
                    "Change Password"       => true,
                    "Set Pin"               => true,
                    "Question and Answer Module" => $qa===1? true:false
                ];
            }

            $returnData = [
                "code"  => 200,
                "details"   => $data
            ];
            $res['body']    = json_encode($returnData);
            $response->getBody()->write($res['body']);
            return $response->withHeader('Content-Type', 'application/json');
        }   else {
            $this->returnErrors['errors'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $response->getBody()->write(json_encode($this->returnErrors));
            $this->returnErrors = [
                "code"  => 400
            ];
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function changePassword(Request $request, Response $response)
    {
        
        // get user id from jwt 
        if(!is_null($this->user_id)) {
            $user_id = $this->user_id;
            $user_details = $this->userGateway->find($user_id);
            $input_data = $request->getParsedBody();
            $oldPass = $input_data['oldpass'];       
            $newPass = $input_data['newpass'];
            $confirmPass = $input_data['confirmpass'];
            $validate = new Validator(['oldpass' => $oldPass,'newpass' => $newPass, 'confirmpass' => $confirmPass]);
            
            $validate->rule('required', ['oldpass', 'newpass', 'confirmpass'])
                    ->rule('lengthMin','newpass', 9)
                    ->rule('equals', 'newpass', 'confirmpass');

            if($validate->validate()) {
                // password matches, here write the sql query to change
                $row_count = $this->userGateway->checkOldPass($oldPass, $user_id);
                
                if($row_count === 0) {
                    
                    $this->returnErrors['errors'] = "Your old password is incorrect.";
                    $res['body']    = json_encode($this->returnErrors);
                    $this->returnErrors = [
                        "code"  => 400
                    ];
                    $response->getBody()->write($res['body']);
                    return $response->withHeader('Content-Type', 'application/json');
                } else {
                    $res = $this->userGateway->updatePass($newPass,$user_id);
                    
                    if($res > 0) { 
                        $action = 'Change his password';
                        $this->userGateway->insertIntoTableLog($this->user_id, $action);
                        $this->returnData['message'] = "Password has been changed successfully.";
                        $r = json_encode($this->returnData);
                        $this->returnData = [
                            "code"      => 200,
                            "success"   => true
                        ];
                        $response->getBody()->write($r);
                        return $response->withHeader('Content-Type', 'application/json');
                    } else {                    
                        $this->returnErrors['errors'] = "Password is incorrect!";
                        $response->getBody()->write(json_encode($this->returnErrors));
                        $this->returnErrors = [
                            "code"  => 400
                        ];
                        return $response->withHeader('Content-Type', 'application/json');
                    }
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
        }   else {
            $this->returnErrors['errors'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $response->getBody()->write(json_encode($this->returnErrors));
            $this->returnErrors = [
                "code"  => 400
            ];
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function changePin(Request $request, Response $response)
    {
        if(!is_null($this->user_id)) {
            $user_details = $this->userGateway->find($this->user_id);
            $input_data = $request->getParsedBody();
            $setpin     = $input_data['setpin'];
            $pin        = $input_data['pin'];
            $confirmpin = $input_data['confirmpin'];
            if($setpin) {
            $validate = new Validator(['setpin' => $setpin,'pin' => $pin, 'confirmpin' => $confirmpin]);
            
            $validate->rule('requiredWith', 'pin', 'setpin')
                    ->rule('requiredWith', 'confirmpin', 'setpin')
                    ->rule('boolean', 'setpin')
                    ->rule('numeric', 'pin')
                    ->rule('numeric', 'confirmpin')
                    ->rule('min','pin', 100000)
                    ->rule('max','pin', 999999)
                    ->rule('equals', 'pin', 'confirmpin');

                if($validate->validate()) {
                    // passes validation check
                    $encrypted_pass = hash_hmac("sha256", $pin, getenv('DROOM_PIN_SALT'));
                    $row_affect = $this->userGateway->updatePin($encrypted_pass, $this->user_id, $setpin);
                    if($row_affect >0) {
                        $action = 'Change Two Factor Authentication';
                        $this->userGateway->insertIntoTableLog($this->user_id, $action);
                        $this->returnData['message'] = "Pin has been updated successfully.";
                        $r = json_encode($this->returnData);
                        $this->returnData = [
                            "code"      => 200,
                            "success"   => true
                        ];
                        $response->getBody()->write($r);
                        return $response->withHeader('Content-Type', 'application/json');
                    } else {
                        $this->returnErrors['errors'] = "You have entered the same pin as old one.";
                        $response->getBody()->write(json_encode($this->returnErrors));
                        $this->returnErrors = [
                            "code"  => 400
                        ];
                        return $response->withHeader('Content-Type', 'application/json');                                        
                    }
                } else {
                    // fail
                    $this->returnErrors['errors'] = $validate->errors();
                    $r    = json_encode($this->returnErrors);
                    $this->returnErrors = [
                        "code"  => 400
                    ];
                    $response->getBody()->write($r);
                    return $response->withHeader('Content-Type', 'application/json');
                }
            } else {
                $row_affect = $this->userGateway->disablePin($this->user_id);
                    if($row_affect >0) {
                        $action = 'Disable Two Factor Authentication';
                        $this->userGateway->insertIntoTableLog($this->user_id, $action);
                        $this->returnData['message'] = "Pin has been disabled successfully.";
                        $r = json_encode($this->returnData);
                        $this->returnData = [
                            "code"      => 200,
                            "success"   => true
                        ];
                        $response->getBody()->write($r);
                        return $response->withHeader('Content-Type', 'application/json');
                    } else {
                        $this->returnErrors['errors'] = "Something went wrong.";
                        $response->getBody()->write(json_encode($this->returnErrors));
                        $this->returnErrors = [
                            "code"  => 400
                        ];
                        return $response->withHeader('Content-Type', 'application/json');                                        
                    }
            }
        } else {
            $this->returnErrors['errors'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $response->getBody()->write(json_encode($this->returnErrors));
            $this->returnErrors = [
                "code"  => 400
            ];
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function getPinStatus(Request $request, Response $response)
    {
        if(!is_null($this->user_id)) {
            $user_details = $this->userGateway->find($this->user_id);
            $pin_auth = $user_details['pin_auth'] === "1" ? true : false;
            $this->returnData['status'] = $pin_auth;
            $r = json_encode($this->returnData);
            $this->returnData = [
                "code"  => 200
            ];
            $response->getBody()->write($r);
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $this->returnErrors['errors'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $response->getBody()->write(json_encode($this->returnErrors));
            $this->returnErrors = [
                "code"  => 400
            ];
            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
?>