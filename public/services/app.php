<?php

$container = null;
require_once __DIR__ . '/../../bootstrap.php';
require_once ROOT_PATH . 'lib/i18n.php';
require_once ADMIN_PATH . 'lib/functions.php';

use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use GisClient\Author\Security\Firewall;
use GisClient\Author\Security\Guard\GuardAuthenticatorInterface;
use GisClient\Author\Security\Token\TokenStorageInterface;
use GisClient\Author\Security\User\UserProviderInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Symfony\Component\Yaml\Yaml;

$gcService = \GCService::instance();

// create request & context
$request = Request::createFromGlobals();
$requestContext = new RequestContext();
$requestContext->fromRequest($request);

$sessionStarted = false;
try {
    $security = Yaml::parse(
        file_get_contents(ROOT_PATH . 'config/security.yml')
    );
    $firewall = new Firewall(
        $security['security'],
        \GCApp::getDB(),
        new PdoApiTokenStore(\GCApp::getDB()),
        fn (?UserProviderInterface $provider, ?GuardAuthenticatorInterface $guard, ?TokenStorageInterface $tokenStorage) => \GCApp::getAuthenticationHandler($provider, $guard, null !== $tokenStorage, $tokenStorage)
    );

    // stateless requests (API Token) must neither start a session nor send a session cookie
    if (!$firewall->isStateless($request)) {
        $gcService->startSession();
        $sessionStarted = true;
    }

    $router = $container->get(Router::class);
    $router->setContext($requestContext);
    
    $requestMatch = $router->match($request->getPathInfo());
    
    $response = $firewall->authenticate($request);

    if (null === $response) {
        $request->attributes->add($requestMatch);

        $controllerResolver = new ControllerResolver();
        $argumentResolver = new ArgumentResolver();
        $controller = $controllerResolver->getController($request);
        $arguments = $argumentResolver->getArguments($request, $controller);
        if ($controller[0] instanceof ContainerAwareInterface) {
            $controller[0]->setContainer($container);
        }

        $response = call_user_func_array($controller, $arguments);
    }
} catch (ResourceNotFoundException $e) {
    $response = new Response('Not Found', Response::HTTP_NOT_FOUND);
} catch (HttpException $e) {
    if (strpos($request->headers->get('accept'), 'application/json') !== false) {
        $response = new JsonResponse([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], $e->getStatusCode(), $e->getHeaders());
    } else {
        $response = new Response(
            $e->getMessage(),
            $e->getStatusCode(),
            array_merge($e->getHeaders(), [
                'content-type' => 'text/plain',
            ])
        );
    }
} catch (Exception $e) {
    \Sentry\captureException($e);
    if (strpos($request->headers->get('accept'), 'application/json') !== false) {
        $response = new JsonResponse([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    } else {
        $response = new Response(
            'An error occurred: ' . $e->getMessage(),
            Response::HTTP_INTERNAL_SERVER_ERROR,
            [
                'content-type' => 'text/plain',
            ]
        );
    }
}

// close the session, because all relevant data are already writte into it
if ($sessionStarted) {
    $gcService->saveAndClose();
}

$response->send();
