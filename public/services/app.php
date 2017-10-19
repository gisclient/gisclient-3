<?php

require_once __DIR__ . '/../../bootstrap.php';
require_once ROOT_PATH . 'lib/i18n.php';
require_once ADMIN_PATH . 'lib/functions.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

$gcService = \GCService::instance();
$gcService->startSession();

$locator = new FileLocator(array(
    ROOT_PATH . 'config'
));

// create request & context
$request = Request::createFromGlobals();
$requestContext = new RequestContext();
$requestContext->fromRequest($request);

try {
    $router = new Router(
        new YamlFileLoader($locator),
        'routing.yml',
        array(),
        $requestContext
    );
    
    $requestMatch = $router->match($request->getPathInfo());
    
    $security = Yaml::parse(ROOT_PATH . 'config/security.yml');
    $firewalls = $security['security']['firewalls'];
    foreach($firewalls as $firewall) {
        if (isset($firewall['pattern'])) {
            $firewallMatcher = new RequestMatcher($firewall['pattern']);
            if ($firewallMatcher->matches($request)) {
                break;
            }
        }
    }
    
    if (isset($firewall['provider'])) {
        $provider = new $firewall['provider'](\GCApp::getDB());
    } else {
        $provider = null;
    }
    
    if (isset($firewall['guard'])) {
        $guard = new $firewall['guard']();
    } else {
        $guard = null;
    }
    
    $authHandler = GCApp::getAuthenticationHandler($provider, $guard);
    $authHandler->login($request);
    
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
