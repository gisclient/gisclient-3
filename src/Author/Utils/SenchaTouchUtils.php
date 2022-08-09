<?php

namespace GisClient\Author\Utils;

class SenchaTouchUtils
{
    /**
     * Convert the standard mapOptions to SenchaTouch format
     *
     * @param array $mapOptions   the $mapOptions
     * @return array              the converted mapOptions
     * @throws Exception
     */
    public static function toSenchaTouch(array $mapOptions)
    {
        $themes = $mapOptions['theme'];
        unset($mapOptions['theme']);

        $olMapConfig = array();
        foreach ($mapOptions as $key => $val) {
            if (in_array($key, array(
                    'units', 'projection', 'displayProjection', 'resolutions', 'minZoomLevel', 'maxResolution',
                    'minResolution', 'maxExtent', 'restrictedExtent'))) {
                $olMapConfig[$key] = $val;
                unset($mapOptions[$key]);
            }
        }
        $mapOptions['olMapConfig'] = $olMapConfig;
        $layers = array();
        foreach ($themes as $theme) {
            $lastThemeType = null;
            foreach ($theme as $key => $val) {
                if (in_array($key, array('title', 'radio'))) {
                    continue;
                }
                if ($lastThemeType === null) {
                    $lastThemeType = $val['type'];
                } elseif ($lastThemeType <> $val['type']) {
                    throw new Exception(sprtinf(
                        'Mixed layer types not allowed in mobile theme "%s" [From type %s to type %s]',
                        $key,
                        $lastThemeType,
                        $val['type']
                    ));
                }
            }
            switch ($lastThemeType) {
                case 1:
                    $layer = array(
                        'layerType' => 'WMS',
                        'name' => $theme['title'],
                        'url' => null,
                        'parameters' => array(),
                        'options' => array());

                    $layerNames = array();
                    foreach ($theme as $key => $val) {
                        if (in_array($key, array('title', 'radio'))) {
                            continue;
                        }
                        $layer['parameters'] = array_merge(
                            $layer['parameters'],
                            $val['parameters']
                        );
                        unset($val['options']['featureTypes']);
                        $layer['options'] = array_merge(
                            $layer['options'],
                            $val['options']
                        );
                        $layer['url']        = $val['url'];
                        if (!empty($val['parameters']['layers'])) {
                            foreach ($val['parameters']['layers'] as $layerName) {
                                array_push($layerNames, $layerName);
                            }
                        } else {
                            continue;
                        }
                    }
                    if (!empty($layerNames)) {
                        $layer['parameters']['layers'] = $layerNames;
                        array_push($layers, $layer);
                    }
                    break;
                case 6:
                    foreach ($theme as $key => $val) {
                        if (in_array($key, array('title', 'radio'))) {
                            continue;
                        }
                        unset($val['options']['serviceVersion']);
                        $layer = array(
                            'layerType' => 'TMS',
                            'name' => $val['title'],
                            'url' => $val['url'],
                            'tileOrigin' => array($olMapConfig['restrictedExtent'][0],
                                $olMapConfig['restrictedExtent'][1]),
                            'layername' => $val['options']['layers']);
                        $layer = array_merge($val['options'], $layer);
                        array_push($layers, $layer);
                    }
                    break;
            }
        }
        $mapOptions['layers'] = array_reverse($layers);
        return $mapOptions;
    }
}
