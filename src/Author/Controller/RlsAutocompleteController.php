<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RlsAutocompleteController extends AutocompleteController
{
    /**
     * Autocomplete for fields in advanced search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function autocompleteAction(Request $request)
    {
        try {
            $q = $this->getAutocompleteQuery($request);
            $dataDb = $q["db"];
            $authHandler = \GCApp::getAuthenticationHandler();
            if ($authHandler->isAuthenticated()) {
                $user = $authHandler->getToken()->getUser();
                $extras = $user->getExtras();

                if (array_key_exists("us_db_role_name", $extras)) {
                    $dataDb->exec("SET ROLE {$extras["us_db_role_name"]}");
                }
            }
            $stmt = $dataDb->prepare($q["query"]);
            $stmt->execute($q["params"]);
            $results = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
            return new JsonResponse(["result" => "ok", "data" => $results]);
        } catch (HttpException $e) {
            return new JsonResponse(
                ["result" => "error", "error" => $e->getMessage()],
                $e->getStatusCode()
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ["result" => "error", "error" => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
