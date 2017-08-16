<?php

require_once __DIR__ . '/../../bootstrap.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;

$locator = new FileLocator(array(
    __DIR__ . '/../../src/Author/Services/R3GisGisclientMap/Resources/config'
));
/*$loader = new YamlFileLoader($locator);
$routes = $loader->load('routes.yml');Ü/

/*
$routes = new RouteCollection();
$routes->add('hello', new Route('/hello'));*/


$request = Request::createFromGlobals();
/*
$context = new RequestContext();
$context->fromRequest($request);
$matcher = new UrlMatcher($routes, $context);*/

$requestContext = new RequestContext();
$requestContext->fromRequest($request);

try {
    $router = new Router(
        new YamlFileLoader($locator),
        'routes.yml',
        array(),
        $requestContext
    );
    
    $requestMatch = $router->match($request->getPathInfo());
    
    $request->attributes->add($requestMatch);

    $resolver = new ControllerResolver();
    $controller = $resolver->getController($request);
    $arguments = $resolver->getArguments($request, $controller);
    
    $response = call_user_func_array($controller, $arguments);
} catch (Routing\Exception\ResourceNotFoundException $e) {
    $response = new Response('Not Found', 404);
} catch (Exception $e) {
    $response = new Response('An error occurred: ' . $e->getMessage(), 500);
}

$response->send();
