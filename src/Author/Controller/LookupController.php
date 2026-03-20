<?php

namespace GisClient\Author\Controller;

use GisClient\Author\Utils\LookupUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LookupController
{
    /**
     * Get the symbol image
     *
     * @return JsonResponse
     */
    public function getLookupAction(Request $request)
    {
        $catalog = $request->query->get('catalog');
        $table = $request->query->get('table');
        $id = $request->query->get('id');
        $name = $request->query->get('name');
        
        // TODO: return error response, when one of this variable are empty!

        $utils = new LookupUtils();
        return new JsonResponse([
            'result' => 'ok',
            'data' => $utils->getList($catalog, $table, $id, $name),
        ]);
    }
}
