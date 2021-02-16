<?php

namespace App\Controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class HelloController
{

   protected $container;

   // constructor receives container instance
   public function __construct($container) {
       $this->container = $container;
   }

    public function home(Request $request, Response $response, $args = [])
    {

        $name = $_ENV['ENV'];
        $response->getBody()->write("Hello, $name");

        return $response;
    }
}