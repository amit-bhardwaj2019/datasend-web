<?php 
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\TableGateways\AdminGateway;
use App\TableGateways\UserGateway;
use App\Classes\JwtHandler;
use Valitron\Validator;

class AdminUserController {
    private $ci;    
    private $adminGateway;
    private $userGateway;
    private $returnErrors = [
        "code"  => 400
    ];
    private $returnData = [
        "code"      => 200,
        "success"   => true
    ];
    private $user_id = null;
    private $email = null;
    private $auth_status = 0; //0 Unathorized, 1 //Valid user
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
        $this->adminGateway = new AdminGateway($this->ci->get('db'));        
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
                $this->auth_status = 1;
                $this->user_id = $decoded_object->data->id;
                $this->email = $decoded_object->data->contactusemail;
            }

        } catch(\Exception $e) {
            $this->auth_status = 0;
            /*
            $this->returnErrors['message'] = $e->getMessage();
            $res['body']    = json_encode($this->returnErrors);
            $this->ci->get('response')->getBody()->write($res['body']);
            unset($this->returnErrors['message'], $res['body']);
            return $this->ci->get('response')->withHeader('Content-Type', 'application/json');
            */
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

    public function manageUsers(Request $request, Response $response)
    {
        $query_params = $request->getQueryParams();
        $page_num = (int)$query_params['Page'];
        $page_limit = (int)$query_params['PageLimit'];
        $offset = $page_limit * ($page_num-1);
        $totalRecords = $this->adminGateway->totalRecords();        
        $last_page = ceil($totalRecords/$page_limit);        
        $results = $this->adminGateway->paginate($offset, $page_limit);
        if(count($results) > 0) {
            $users = [];
            foreach($results AS $key=>$value) {
                $users[$key]['id']      = $value['id'];
                $users[$key]['name']    = $value['name'];
                $users[$key]['email']   = $value['email'];
                $users[$key]['status']  = $value['status'] === 0 ? 'Inactive' : 'Active';
            } 
            
            $this->returnData['users'] = $users;
            $this->returnData['last_page'] = $last_page;
            $r = json_encode($this->returnData);
            unset($this->returnData['users'], $this->returnData['last_page']);
        } else {
            $this->returnErrors['errors'] = "No records found!";
            $r = json_encode($this->returnErrors);
            unset($this->returnErrors['detail']);
        }

        $response->getBody()->write($r);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function check(Request $request, Response $response)
    {
        if(!is_null($this->user_id)) {
            $this->returnData['is_admin'] = true;
            $r = json_encode($this->returnData);
        } else {
            $this->returnErrors['is_admin'] = false;
            $r = json_encode($this->returnErrors);
        }

        $response->getBody()->write($r);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function searchUsers(Request $request, Response $response)
    {
        $input_data = $request->getParsedBody();

        $validate = new Validator(['email' => $input_data['email']]);
        $validate->rule('required', 'email');

        if($validate->validate()) {
            // passes
            $results = $this->adminGateway->findByEmail($input_data['email']);
            if(count($results) > 0) {
                $users = [];
                foreach($results AS $key=>$value) {
                    $users[$key]['id']      = $value['id'];
                    $users[$key]['name']    = $value['name'];
                    $users[$key]['email']   = $value['email'];
                    $users[$key]['status']  = $value['status'] === 0 ? 'Inactive' : 'Active';
                }                 
                $this->returnData['users'] = $users;
                $r = json_encode($this->returnData);
                unset($this->returnData['users']);
            } else {
                $this->returnErrors['users'] = "No record(s) found.";
                $r = json_encode($this->returnErrors);
                unset($this->returnErrors['users']);
            }
        } else {
            // fails
            $this->returnErrors['errors'] = $validate->errors();
            $r = json_encode($this->returnErrors);            
        }

        $response->getBody()->write($r);
        return $response->withHeader('Content-Type', 'application/json');        
    }

    public function addUser(Request $request, Response $response)
    {
        if($this->auth_status === 1) {
            $ret                = $this->adminGateway->defualtSapce();
            $allocated_space    = (int)$ret['k'];
            $input_data         = $request->getParsedBody();
            $validate           = new Validator(['name' => $input_data['name'], 'email' => $input_data['email'], 'url' => $input_data['url_name'], 'totalspace' => $input_data['totalspace']]);

            $validate->rule('required', 'name')->message('Please enter {field}.')
                    ->rule('required', 'email')->message('Please enter {field}.')
                    ->rule('email', 'email')->message('Please enter valid {field}.')
                    ->rule('required', 'url')->message('Please enter {field}.')
                    ->rule('required', 'totalspace')->message('Please enter {field}.')
                    ->rule('integer', 'totalspace')
                    ->rule('min', 'totalspace', $allocated_space)
                    ->message('{field} can not be less than default space '.$allocated_space .' Mb.');
            $validate->labels([
                'url'           => 'URL Name',
                'totalspace'    => 'Total allocated space'
            ]);

            if($validate->validate()){
                // passes
                $checkInExistingAccount = $this->adminGateway->checkForExistingAccount($input_data['email']);
                if(is_int($checkInExistingAccount) && $checkInExistingAccount === 1) {
                    $this->returnErrors['message'] = 'This email address is already in use by another user.';
                    $r = json_encode($this->returnErrors);
                    unset($this->returnErrors['message']);
                } else {
                    // add new user
                    $token = $this->ci->get('common')->createToken();                
                    $input_data['token'] = $token;
                    $result = (int)$this->adminGateway->insertMainUser($input_data);
                    // var_export($this->ci->get('common')->SendSubUserLoginEmail($result));
                    $this->returnData['message'] = 'User added successfully.';
                    $r =  json_encode($this->returnData);
                    unset($this->returnData['message']);
                }
            } else {
                // fails
                $this->returnErrors['errors'] = $validate->errors();
                $r = json_encode($this->returnErrors);
                unset($this->returnErrors['errors']);
            }
        } else {
            $this->returnErrors['errors'] = $this->ci->get('common')::INVALID_CREDENTIAL;
            $r = json_encode($this->returnErrors);          
            unset($this->returnErrors['errors']);
        }
        $response->getBody()->write($r);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function test(Request $request, Response $response)
    {
       /* $input_data = $request->getQueryParams();
        var_export($this->adminGateway->checkForExistingAccount($input_data['email']));
        */
        var_export($this->userGateway->GetAssignGroup($request->getQueryParams()['id']));
    }
}
?>