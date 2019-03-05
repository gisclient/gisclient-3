<?php

namespace GisClient\Author\Controller;

use GisClient\Author\Utils\SavedFilterHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SavedFilterController
{
    private function getHandler()
    {
        return new SavedFilterHandler();
    }

    private function createBadRequestResponse(array $errors)
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Bad Request',
            'errors' => $errors
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    private function createOkResponse($id = null)
    {
        $handler = $this->getHandler();

        $ret = ['status' => 'ok'];
        if ($id > 0) {
            $ret['data'] = $handler->getSavedFilter($id);
        }
        return new JsonResponse($ret);
    }

    /**
     * Get saved filter
     *
     * @param string $mapset
     * @return JsonResponse
     */
    public function getAllAction($mapset)
    {
        $handler = $this->getHandler();
        $rows = $handler->getList($mapset);
        return new JsonResponse([
            'status' => 'ok',
            'data' => [
                'rows' => $rows,
                'totals' => count($rows)
            ]
        ]);
    }

    /**
     * Create a new saved filter
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $handler = $this->getHandler();

        // retrieve data
        $data = json_decode($request->getContent(), true);
        
        // validate data
        list($values, $errors) = $handler->validate($data);
        if (count($errors) > 0) {
            return $this->createBadRequestResponse($errors);
        }

        // save data
        $id = $handler->add($values);
        return $this->createOkResponse($id);
    }

    /**
     * Modify a saved filter
     *
     * @param integer $id
     * @param Request $request
     * @return JsonResponse
     */
    public function modifyAction($id, Request $request)
    {
        $handler = $this->getHandler();

        // check if filter exists
        $filter = $handler->getSavedFilter($id);
        if ($filter === false) {
            throw new NotFoundHttpException('Not Found');
        }

        // check if user is owner of the filter
        if (!$handler->isMyFilter($filter)) {
            throw new AccessDeniedHttpException('Cannot modify filter of another user.');
        }

        // retrieve data & validate
        $data = json_decode($request->getContent(), true);
        list($values, $errors) = $handler->validate($data, $filter);
        if (count($errors) > 0) {
            return $this->createBadRequestResponse($errors);
        }

        // save data
        $handler->modify($id, $values);
        return $this->createOkResponse($id);
    }
    
    /**
     * Delete a saved filter
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function deleteAction($id)
    {
        $handler = $this->getHandler();

        // check if filter exists
        $filter = $handler->getSavedFilter($id);
        if ($filter === false) {
            throw new NotFoundHttpException('Not Found');
        }

        // check if user is owner of the filter
        if (!$handler->isMyFilter($filter)) {
            throw new AccessDeniedHttpException('Cannot modify filter of another user.');
        }

        if (!$handler->delete($id)) {
            throw new \Exception('Could not delete filter');
        }
        
        return $this->createOkResponse();
    }
}
