<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LoginController
{
    /**
     * Route to refresh cookie lifetime
     *
     * @return JsonResponse
     */
    public function refreshAction()
    {
        return new JsonResponse([
            'status' => 'ok'
        ]);
    }

    /**
     * Route for Username/Password Authenticator
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginAction(Request $request)
    {
        if ($request->query->has('redirect')) {
            header(sprintf("Location: %s", $request->query->get('redirect')));
            exit();
        } else {
            return new JsonResponse([
                'status' => 'ok'
            ]);
        }
    }

    /**
     * Route to logout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAction(Request $request)
    {
        $authHandler = \GCApp::getAuthenticationHandler();
        $authHandler->logout();

        return new JsonResponse([
            'status' => 'ok'
        ]);
    }
}
