<?php

namespace GisClient\Author\Controller;

use GisClient\Author\Form\Type\FilterType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class SavedFilterController
{
    private function getFormFactory()
    {
        $validator = Validation::createValidator();

        $formFactory = Forms::createFormFactoryBuilder()
        ->addExtension(new HttpFoundationExtension())
        ->addExtension(new ValidatorExtension($validator))
        ->getFormFactory();
        return $formFactory;
    }

    private function getForm(array $data)
    {
        $formFactory = $this->getFormFactory();
        return $formFactory->create(FilterType::class, $data);
    }

    private function validate(array $data, $originalData = [])
    {
        $errors = [];

        $form = $this->getForm($originalData);
        if (count($originalData) > 0) {
            $form->submit($data, false);
        } else {
            $form->submit($data);
        }
        
        // check if is valid
        if (!$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $name = $error->getOrigin()->getName();
                if (!isset($errors[$name])) {
                    $errors[$name] = [];
                }
                $errors[$name][] = $error->getMessage();
            }
        }

        return [$form->getNormData(), $errors];
    }

    private function createBadRequestResponse(array $errors)
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Bad Request',
            'errors' => $errors
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    private function getSavedFilter($id)
    {
        $db = \GCApp::getDB();
        $sql = "SELECT * FROM ".DB_SCHEMA.".saved_filter WHERE saved_filter_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($data === false) {
            throw new NotFoundHttpException('Not Found');
        }

        return $data;
    }

    private function createOkResponse($id = null)
    {
        $ret = ['status' => 'ok'];
        if ($id > 0) {
            $ret['data'] = $this->getSavedFilter($id);
        }
        return new JsonResponse($ret);
    }

    /**
     * Get saved filter
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllAction($mapset, Request $request)
    {
        $rows = [];
        $authHandler = \GCApp::getAuthenticationHandler();
        $username = $authHandler->getToken()->getUser()->getUsername();

        $db = \GCApp::getDB();
        $sql = "
        SELECT saved_filter.*, layergroup_name||'.'||layer_name AS layer_id 
            FROM ".DB_SCHEMA.".saved_filter 
            INNER JOIN ".DB_SCHEMA.".layer USING(layer_id)
            INNER JOIN ".DB_SCHEMA.".layergroup USING(layergroup_id)
            INNER JOIN ".DB_SCHEMA.".theme USING(theme_id)
            WHERE mapset_name=? AND (username = ? OR saved_filter_scope = 'all')
        ";
        // TODO: support group scope!!
        $stmt = $db->prepare($sql);
        $stmt->execute([$mapset, $username]);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

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
        // retrieve data
        $data = json_decode($request->getContent(), true);
        
        // validate data
        list($values, $errors) = $this->validate($data);
        if (count($errors) > 0) {
            return $this->createBadRequestResponse($errors);
        }

        $authHandler = \GCApp::getAuthenticationHandler();
        $username = $authHandler->getToken()->getUser()->getUsername();

        // save data
        $db = \GCApp::getDB();
        $sql = "
        INSERT INTO ".DB_SCHEMA.".saved_filter (
            username, saved_filter_name, mapset_name, layer_id, 
            saved_filter_scope, saved_filter_data
        ) VALUES (?, ?, ?, ?, ?, ?) ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $username,
            $values['saved_filter_name'],
            $values['mapset_name'],
            $values['layer_id'],
            $values['saved_filter_scope'],
            $values['saved_filter_data']
        ]);
        $id = $db->lastInsertId(DB_SCHEMA.".saved_filter_saved_filter_id_seq");
        
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
        // check if filter exists
        $filter = $this->getSavedFilter($id);

        // check if user is owner of the filter
        $authHandler = \GCApp::getAuthenticationHandler();
        $username = $authHandler->getToken()->getUser()->getUsername();
        if ($filter['username'] !== $username) {
            throw new AccessDeniedHttpException('Cannot modify filter of another user.');
        }

        // retrieve data & validate
        $data = json_decode($request->getContent(), true);
        list($values, $errors) = $this->validate($data, $filter);
        if (count($errors) > 0) {
            return $this->createBadRequestResponse($errors);
        }

        // save data
        $db = \GCApp::getDB();
        $sql = "
        UPDATE ".DB_SCHEMA.".saved_filter SET
            saved_filter_name=?, 
            saved_filter_scope=?,
            saved_filter_data=?
        WHERE
            saved_filter_id=?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $values['saved_filter_name'],
            $values['saved_filter_scope'],
            $values['saved_filter_data'],
            $id
        ]);
        
        return $this->createOkResponse($id);
    }
    
    /**
     * Delete a saved filter
     *
     * @param integer $id
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction($id, Request $request)
    {
        // check if filter exists
        $filter = $this->getSavedFilter($id);

        // check if user is owner of the filter
        $authHandler = \GCApp::getAuthenticationHandler();
        $username = $authHandler->getToken()->getUser()->getUsername();
        if ($filter['username'] !== $username) {
            throw new AccessDeniedHttpException('Cannot delete filter of another user.');
        }

        // delete data
        $db = \GCApp::getDB();
        $sql = "DELETE FROM ".DB_SCHEMA.".saved_filter WHERE saved_filter_id=? ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new NotFoundHttpException('Not Found');
        }
        
        return $this->createOkResponse();
    }
}
