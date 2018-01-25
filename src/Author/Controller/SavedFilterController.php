<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Callback;
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

    private function getForm()
    {
        $formFactory = $this->getFormFactory();

        $formBuilder = $formFactory->createBuilder(
            'Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType',
            null,
            [
                'allow_extra_fields' => true,
            ]
        )
        ->add('saved_filter_name', 'text', [
            'constraints' => [new Required(), new NotBlank()],
        ])
        ->add('mapset_name', 'text', [
            'constraints' => [new Required(), new NotBlank(), new Callback([
                'callback' => function ($value, $context) {
                    $db = \GCApp::getDB();
                    $sql = "SELECT * FROM ".DB_SCHEMA.".mapset WHERE mapset_name=?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$value]);
                    $data = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($data === false) {
                        $context->addViolation('The mapset does not exists!');
                    }
                }
            ])],
        ])
        ->add('layer_id', 'text', [
            'constraints' => [new Required(), new NotBlank(), new Callback([
                'callback' => function ($value, $context) {
                    $normData = $context->getRoot()->getNormData();

                    $db = \GCApp::getDB();
                    $sql = "
                        SELECT * FROM ".DB_SCHEMA.".mapset
                        INNER JOIN ".DB_SCHEMA.".mapset_layergroup USING(mapset_name)
                        INNER JOIN ".DB_SCHEMA.".layer USING(layergroup_id)
                        WHERE mapset_name=? AND layer_id=?
                    ";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$normData['mapset_name'], $normData['layer_id']]);
                    $data = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($data === false) {
                        $context->addViolation('The layer is not enabled on this mapset!');
                    }
                }
            ])],
        ])
        ->add('saved_filter_scope', 'text', [
            'constraints' => [new Required(), new NotBlank()],
        ])
        ->add('saved_filter_data', 'text', [
            'constraints' => [new Required(), new NotBlank(), new Callback([
                'callback' => function ($value, $context) {
                    if (is_string($value) && json_decode($value) === null) {
                        $context->addViolation('Not a valid json.');
                    }
                }
            ])],
        ]);

        $formBuilder->get('layer_id')
            ->addViewTransformer(new CallbackTransformer(
                function ($layerId) {
                    $composedLayerName = null;
                    if ($layerId !== null) {
                        $db = \GCApp::getDB();
                        $sql = "
                            SELECT layergroup_name||'.'||layer_name FROM ".DB_SCHEMA.".layergroup
                            INNER JOIN ".DB_SCHEMA.".layer USING(layergroup_id)
                            WHERE layer_id=?
                        ";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$layerId]);
                        $composedLayerName = $stmt->fetchColumn();
                        if ($composedLayerName === false) {
                            throw new TransformationFailedException(sprintf(
                                'No layer_id found for "%s"',
                                $layerId
                            ));
                        }
                    }
                    return $composedLayerName;
                },
                function ($composedLayerName) {
                    if (strpos($composedLayerName, '.') !== false) {
                        list($layergroupName, $layerName) = explode('.', $composedLayerName);
                    } else {
                        throw new TransformationFailedException(
                            'The layer_id must provied layergroup and layername separated by dot.'
                        );
                    }

                    $db = \GCApp::getDB();
                    $sql = "
                        SELECT layer_id FROM ".DB_SCHEMA.".layergroup
                        INNER JOIN ".DB_SCHEMA.".layer USING(layergroup_id)
                        WHERE layergroup_name=? AND layer_name=?
                    ";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$layergroupName, $layerName]);
                    $layerId = $stmt->fetchColumn();
                    if ($layerId === false) {
                        throw new TransformationFailedException(sprintf(
                            'No layer_id found for "%s"',
                            $composedLayerName
                        ));
                    }
                    
                    return $layerId;
                }
            ));

            $formBuilder->get('saved_filter_data')
            ->addViewTransformer(new CallbackTransformer(
                function ($jsonString) {
                    return $jsonString;
                },
                function ($json) {
                    if (is_array($json)) {
                        $jsonString = json_encode($json);
                    } else {
                        $jsonString = $json;
                    }
                    
                    return $jsonString;
                }
            ));

        return $formBuilder->getForm();
    }

    private function validate(array $data, array $originalData = [])
    {
        $errors = [];

        $form = $this->getForm();
        if (count($originalData) > 0) {
            $form->setData($originalData);
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
            17,
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
