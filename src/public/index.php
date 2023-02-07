<?php 

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Dotenv\Dotenv;

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

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:8888')
            ->withHeader('Access-Control-Allow-Credentials', true
            )
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->group('/api', function () {
    $this->get('/hello/{name}', function (Request $request, Response $response, array $args) {
        var_dump($this->db);
        $name = $args['name'];
        $response->getBody()->write("Hello, $name");
    
        return $response;
    });
    
    $this->post('/login', \App\Controller\UserController::class.':login');
    
    $this->post('/auth', \App\Controller\UserController::class.':verifyAuthenticationPin');
});

$app->run();