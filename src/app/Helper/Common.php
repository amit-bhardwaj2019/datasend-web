<?php 
namespace App\Helper;

use Psr\Container\ContainerInterface;
use App\TableGateways\UserGateway;

class Common {
    private $ci;    
    private $userGateway;
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
        $this->userGateway = new UserGateway($this->ci->get('db'));
    }

    public function test()
    {
        var_dump('Testing');
    }
}