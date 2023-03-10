<?php 

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Dotenv\Dotenv;
use App\Controller\UserController;
use App\Controller\LoginController;
use App\Controller\AdminLoginController;
use App\Controller\AdminUserController;
use App\Helper\Common;

require __DIR__.'/../../vendor/autoload.php';

$dotenv = new Dotenv(__DIR__. '/../../');
$dotenv->load();

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$config['db']['host']   = getenv('DB_HOST');
$config['db']['user']   = getenv('DB_USERNAME');
$config['db']['pass']   = getenv('DB_PASSWORD');
$config['db']['dbname'] = getenv('DB_DATABASE');

$app = new \Slim\App(['settings' => $config]);

$container = $app->getContainer();

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['common'] = function($c) {
    $common = new Common($c);
    return $common;
};
/*
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new \Monolog\Logger('test-app');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
    return $logger;
};
*/
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response            
            ->withHeader('Access-Control-Allow-Credentials', true
            )            
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->group('/api', function () {
    $this->get('/hello/{name}', function (Request $request, Response $response, array $args) {
        
        $name = $args['name'];
        setcookie('act', 'abs1234', time()+3600,'/','softdemonew.info', false, true);
        $arrOptions = [
            'expires' => time()+3600,
            'path'      => '/',
            'domain'    => 'localhost',
            'secure'    => false,
            'httponly'  => true,
            'samesite'  => 'None'
        ];
        setcookie('qwe', 'qwer123',time()+3600, '/', 'softdemonew.info', false, true);
        $response->getBody()->write("Hello, $name");
    
        return $response;
    });
    
    $this->get('/test', function(Request $request, Response $response) {
        // $this->get('logger')->info('Hello world!');
        $cookie = $_COOKIE['act'];
        $cookie2 = $_COOKIE['qwe'];
        $response->getBody()->write('Hello '. $cookie . ' '. $cookie2);
        return $response;
    });
    $this->post('/login', LoginController::class.':login');
    
    $this->post('/auth', UserController::class.':verifyPin');

    $this->get('/user-details', UserController::class.':userDetails');

    $this->get('/users', UserController::class.':show');

    $this->post('/users', UserController::class.':update');
    
    $this->get('/dashboard', UserController::class.':dashboard');

    $this->post('/change-password', UserController::class.':changePassword');

    $this->get('/pin', UserController::class.':getPinStatus');

    $this->post('/change-pin', UserController::class.':changePin');

    $this->post('/forgot-password', LoginController::class.':forgotPassword');

    $this->post('/reset-password', LoginController::class.':resetPassword');

    $this->post('/forgot-pin', LoginController::class.':forgotPin');

    $this->post('/reset-pin', LoginController::class.':resetPin');

    

    $this->group('/admin', function () {
        // admin login
        $this->post('/login', AdminLoginController::class.':login');

        $this->get('/check', AdminUserController::class.':check');

        $this->get('/email', AdminUserController::class.':getEmail');

        $this->post('/email', AdminUserController::class.':changeEmail');

        $this->post('/update-password', AdminUserController::class.':updatePassword');

        $this->get('/manage-users', AdminUserController::class.':manageUsers');

        $this->post('/search-users', AdminUserController::class.':searchUsers');

        $this->post('/adduser', AdminUserController::class.':addUser');

        $this->get('/edituser/{id}', AdminUserController::class.':editUser');

        $this->put('/updateuser', AdminUserController::class.':updateUser');

        $this->delete('/users/{id}', AdminUserController::class.':destroyUser');

        $this->post('/delete-users', AdminUserController::class.':deleteAll');

        $this->get('/test', AdminUserController::class.':test');

        $this->get('/managespace', AdminUserController::class.':getSpace');

        $this->post('/managespace', AdminUserController::class.':setSpace');
    });
    
});

$app->run();