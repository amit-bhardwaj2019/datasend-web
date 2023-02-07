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

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    var_dump($this->db);
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");

    return $response;
});

$app->post('/login', \App\Controller\UserController::class.':login');

$app->post('/auth', \App\Controller\UserController::class.':verifyAuthenticationPin');
$app->run();