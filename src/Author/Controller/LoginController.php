<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use GisClient\Author\Utils\GCMap;
use GisClient\Author\Utils\GWGCMap;
use GisClient\Author\Utils\R3GisGCMap;
use GisClient\Author\Utils\SenchaTouchUtils;

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
        return new JsonResponse([]);
    }
}
