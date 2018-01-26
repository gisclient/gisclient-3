<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LoginController
{
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
}
