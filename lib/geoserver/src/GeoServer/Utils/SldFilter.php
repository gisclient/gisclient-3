<?php

namespace GisClient\GeoServer\Utils;

class SldFilter
{
    
    /**
     * Remove Parenthesis from text
     *
     * @param string $text
     * @return string
     */
    static private function removeParenthesisFromText($text)
    {
        $text = trim($text);
        if ($text == '') {
            return '';
        }
        if ($text[0] == '(' && $text[strlen($text) - 1] == ')') {
            $text = trim(substr($text, 1, -1));
        }
        return $text;
    }

    /**
     * Divide a string filter by the given operators
     *
     * @param array $validOperators
     * @param type $filter
     * @return type
     * @throws \Exception
     */
    static private function splitForOperators(array $validOperators, $filter)
    {
        $filter = trim($filter);
        $filterParts = array();
        $operatorFound = 0;
        $operator = null;
        $parsedFilter = $filter;
        foreach ($validOperators as $op) {
            if (($p = strpos($parsedFilter, $op)) !== false) {
                $parsedFilter = substr($filter, 0, $p).substr($filter, $p + strlen($op));
                $operatorFound++;
                $operator = $op;
            }
        }
        if ($operatorFound > 1) {
            throw new \Exception("Multiple operator not allowed on layer filter");
        }
        $parts = array();
        $dataPart = $operator === null ? array($filter) : explode($operator, $filter);
        foreach ($dataPart as $part) {
            $part = trim($part);
            if (strlen($part) > 0) {
                $parts[] = self::removeParenthesisFromText($part);
            }
        }
        $result = array(
            'operator' => trim($operator),
            'parts' => $parts);
        return $result;
    }

    /**
     * Parse for expression
     *
     * @param string $expression
     * @return array
     */
    static private function parseForExpressions($expression)
    {
        $expression = self::removeParenthesisFromText($expression);
        $result = array();
        $filterParts = self::splitForOperators(array(' and ', ' or '), $expression);
        $op = $filterParts['operator'];
        foreach ($filterParts['parts'] as $part) {
            $sqlFilterParts = self::splitForOperators(array(' eq ', '==', '>=', '<=', '!=', '<', '> '), $part);  // Don't remove space
            foreach ($sqlFilterParts['parts'] as $k => $v) {
                $sqlFilterParts['parts'][$k] = str_replace(array("'[", "]'", '[', ']'), '', $sqlFilterParts['parts'][$k]);
            }
            $result[$op][] = $sqlFilterParts;
        }
        return $result;
    }

    /**
     * Parse for filter
     *
     * @param string $filter
     * @return array
     */
    static private function parseForFilters($filter)
    {
        $result = array();

        $filter = str_replace(' is true', '=true', $filter);
        $filter = str_replace(' is false', '=false', $filter);
        $filter = self::removeParenthesisFromText($filter);

        $filterParts = self::splitForOperators(array(' and ', ' or '), $filter);
        $op = $filterParts['operator'];
        foreach ($filterParts['parts'] as $part) {
            $sqlFilterParts = self::splitForOperators(array('!=', '=', '>=', '<=', '>', '<'), $part);
            $result[$op][] = $sqlFilterParts;
        }
        return $result;
    }


    /**
     * Parse filter and merge filter and expression
     *
     * @param string $filter
     * @param string $expression
     * @return array
     * @throws \Exception
     */
    static public function parseForFiltersAndExpression($filter, $expression)
    {
        $parsedFilter = array();
        $parsedExpression = array();
        if (!empty($filter)) {
            $parsedFilter = self::parseForFilters($filter);
        }
        if (!empty($expression)) {
            $parsedExpression = self::parseForExpressions($expression);
        }
        $mergedFilter = array();
        if (count($filter) > 0 && count($expression) > 0) {
            $k1 = key($parsedFilter);
            $k2 = key($parsedExpression);
            if (($k1 <> '' && $k1 <> 'and') or ( $k2 <> '' && $k2 <> 'and')) {
                throw new \Exception("Layer filter and expression must have the same logical operator (both and or both or)");
            }
            $mergedFilter['and'] = array_merge(current($parsedFilter), current($parsedExpression));
        } else if (count($filter) > 0) {
            $mergedFilter = $parsedFilter;
        } else if (count($expression) > 0) {
            $mergedFilter = $parsedExpression;
        }
        return $mergedFilter;
    }

}