<?php

namespace GisClient\Author\Utils;

use GisClient\Author\Form\Type\FilterType;
use GisClient\Author\Security\AuthenticationHandler;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class SavedFilterHandler
{
    /**
     * @var \PDO
     */
    private $database;

    /**
     * @var AuthenticationHandler
     */
    private $authHandler;

    public function __construct()
    {
        $this->database = \GCApp::getDB();
        $this->authHandler = \GCApp::getAuthenticationHandler();
    }

    private function getUserName()
    {
        return $this->authHandler->getToken()->getUser()->getUsername();
    }

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

    public function validate(array $data, $originalData = [])
    {
        $errors = [];

        if (isset($data['saved_filter_data']) && is_array($data['saved_filter_data'])) {
            $data['saved_filter_data'] = json_encode($data['saved_filter_data']);
        }

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

    public function getSavedFilter($id)
    {
        $sql = "SELECT * FROM ".DB_SCHEMA.".saved_filter WHERE saved_filter_id = ?";
        $stmt = $this->database->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function isMyFilter(array $filter)
    {
        return $filter['username'] === $this->getUserName();
    }
    
    /**
     * Get list of saved filters for current user
     *
     * @param string $mapset
     * @return array
     */
    public function getList($mapset)
    {
        $data = [];
        $username = $this->getUserName();

        $sql = "
        SELECT saved_filter.*, layergroup_name||'.'||layer_name AS layer_id 
            FROM ".DB_SCHEMA.".saved_filter 
            INNER JOIN ".DB_SCHEMA.".layer USING(layer_id)
            INNER JOIN ".DB_SCHEMA.".layergroup USING(layergroup_id)
            INNER JOIN ".DB_SCHEMA.".theme USING(theme_id)
            WHERE mapset_name=? AND (username = ? OR saved_filter_scope = 'all')
        ";
        // TODO: support group scope!!
        $stmt = $this->database->prepare($sql);
        $stmt->execute([$mapset, $username]);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Add new saved filter for current user
     *
     * @param array $values
     * @return integer
     */
    public function add(array $values)
    {
        // save data
        $sql = "
        INSERT INTO ".DB_SCHEMA.".saved_filter (
            username, saved_filter_name, mapset_name, layer_id, 
            saved_filter_scope, saved_filter_data
        ) VALUES (?, ?, ?, ?, ?, ?) ";
        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            $this->getUserName(),
            $values['saved_filter_name'],
            $values['mapset_name'],
            $values['layer_id'],
            $values['saved_filter_scope'],
            $values['saved_filter_data']
        ]);
        return $this->database->lastInsertId(DB_SCHEMA.".saved_filter_saved_filter_id_seq");
    }

    /**
     * Modify saved filter for current user
     *
     * @param integer $id
     * @param array $values
     * @return integer
     */
    public function modify($id, array $values)
    {
        // save data
        $sql = "
        UPDATE ".DB_SCHEMA.".saved_filter SET
            saved_filter_name=?, 
            saved_filter_scope=?,
            saved_filter_data=?
        WHERE
            saved_filter_id=?
        ";
        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            $values['saved_filter_name'],
            $values['saved_filter_scope'],
            $values['saved_filter_data'],
            $id
        ]);
        return $id;
    }

    /**
     * Delete saved filter for current user
     *
     * @param integer $id
     * @return boolean
     */
    public function delete($id)
    {
        $sql = "DELETE FROM ".DB_SCHEMA.".saved_filter WHERE saved_filter_id=? ";
        $stmt = $this->database->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }
}
