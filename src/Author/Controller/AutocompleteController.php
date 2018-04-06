<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class AutocompleteController
{

    /**
     * Autocomplete for fields in advanced search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function autocompleteAction(Request $request)
    {
        $q = $this->getAutocompleteQuery($request);

        try {
            $dataDb = $q["db"];
            $stmt = $dataDb->prepare($q["query"]);
            $stmt->execute($q["params"]);
            $results = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        } catch (Exception $e) {
            return new JsonResponse(
                array(
                    "result" => "error",
                    "error" => $e->getMessage()
                ),
                500
            );
        }
        return new JsonResponse([
            "result" => "ok",
            "data" => $results
        ]);
    }

    protected function getAutocompleteQuery(Request $request)
    {
        // limit the number of results for the autocomplete option, since
        // the browser hangs, if thousands of items are sent
        $maxNumResults = 100;

        $db = \GCApp::getDB();

        if (!$request->query->has("field_id") ||
            (int)$request->query->get("field_id") != $request->query->get("field_id")
        ) {
            return new JsonResponse(
                array(
                    "result" => "error",
                    "error" => "No or invalid data for field_id."
                ),
                400
            );
        } else {
            $fieldId = (int)$request->query->get("field_id");
        }

        $lang = $request->query->get('lang', null);

        $sql = 'select field_id, field_name, relation_id, layer_id, formula
            from '.DB_SCHEMA.'.field
            where field_id=:id';
        $stmt = $db->prepare($sql);
        $stmt->execute(array('id'=>$fieldId));
        $field = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (empty($field)) {
            return new JsonResponse(
                array(
                    "result" => "error",
                    "error" => "Field $fieldId does not exist."
                ),
                404
            );
        }
        $isLayer = true;

        if (!empty($field['relation_id'])) {
            $sql = 'select catalog.project_name, catalog_path, table_name as table, relation_name as alias
                from '.DB_SCHEMA.'.catalog
                inner join '.DB_SCHEMA.'.relation using(catalog_id)
                where relation_id = :id';
            $params = array('id'=>$field['relation_id']);
            $isLayer = false;
        } else {
            $sql = 'select catalog.project_name, catalog_path, data as table, data_filter
                from '.DB_SCHEMA.'.catalog
                inner join '.DB_SCHEMA.'.layer using(catalog_id)
                where layer_id = :id';
            $params = array('id'=>$field['layer_id']);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $catalog = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (empty($catalog)) {
            return new JsonResponse(
                array(
                    "result" => "error",
                    "error" => "No catalog found for layer_id {$field["layer_id"]}."
                ),
                404
            );
        }

        if ($lang) {
            $sql = "select i18nf_id
                from ".DB_SCHEMA.".i18n_field
                where table_name='field' and field_name='field_name'";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $i18nFieldId = $stmt->fetchColumn(0);
            if ($i18nFieldId) {
                $sql = 'select value
                    from '.DB_SCHEMA.'.localization
                    where i18nf_id=:i18nf_id and pkey_id=:pkey and language_id=:lang';
                $stmt = $db->prepare($sql);
                $stmt->execute(array(
                    'i18nf_id'=>$i18nFieldId,
                    'pkey'=>$field['field_id'],
                    'lang'=>$lang
                ));
                $localized = $stmt->fetchColumn(0);
                if ($localized) {
                    $field['field_name'] = $localized;
                }
            }
        }

        $schema = \GCApp::getDataDBSchema($catalog['catalog_path']);

        $constraints = array();
        $params = array();

        $fieldName = $field['field_name'];
        $alias = 'aliastable';
        if ($isLayer) {
            if (!empty($catalog['data_filter'])) {
                $constraints[] = '('.$catalog['data_filter'].')';
            }
        } else {
            $alias = $catalog['alias'];
            $fieldName = $field['formula'];
        }

        if ($request->query->has('filter') && $request->query->get('filter')) {
            $constraints[] = ' '.$fieldName.' ilike :filter';
            $params['filter'] = '%'.$request->query->get('filter').'%';
        }
        if ($request->query->has("do_id") && $request->query->get("do_id")) {
            if ((int)$request->query->get("do_id") != $request->query->get("do_id")) {
                return new JsonResponse(
                    array(
                        "result" => "error",
                        "error" => "Invalid value for do_id."
                    ),
                    400
                );
            } else {
                $constraints[] = ' do_id = :do_id ';
                $params['do_id'] = (int) $request->query->get("do_id");
            }
        }

        $sql = 'select distinct '.$fieldName.' from '.$schema.'.'.$catalog['table'].' as '.$alias;
        if (!empty($constraints)) {
            $sql .= ' where '.implode(' and ', $constraints);
        }
        $sql .= ' order by '.$fieldName . ' LIMIT '.$maxNumResults;

        return [
            "db" => \GCApp::getDataDB($catalog['catalog_path']),
            "query"=> $sql,
            "params" => $params
        ];
    }
}
