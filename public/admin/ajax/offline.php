<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';

require_once '../../../bootstrap.php';
use GisClient\Author\Map;
use GisClient\Author\LayerGroup;
use GisClient\Author\OfflineMap;

$ajax = new GCAjax();

if (empty($_REQUEST['action'])) {
    $ajax->error();
}

switch ($_REQUEST['action']) {
    case 'get-data':
        $result = array();

        $map = new Map($_REQUEST['project'], $_REQUEST['mapset']);
        $offline = new OfflineMap($map);

        $themes = $map->getThemes();
        $result['themes'] = array();
        foreach ($themes as $theme) {
            $layerGroups = $theme->getLayerGroups();

            $hasSqlite = false;
            $hasMbTiles = false;
            foreach ($layerGroups as $layerGroup) {
                switch ($layerGroup->getType()) {
                    case LayerGroup::WFS_LAYER_TYPE:
                        $hasSqlite = true;
                        break;
                    
                    case LayerGroup::WMS_LAYER_TYPE:
                        $hasMbTiles = true;
                        break;
                }
            }

            //check MbTiles
            if (!file_exists(MAPPROXY_CACHE_PATH . $map->getProject() . '/' . $theme->getName() . '.mbtiles')) {
                $mbTilesState = 'to-do';
            } else {
                if ($offline->status('mbtiles')) {
                    $mbTilesState = 'running';
                } else {
                    $mbTilesState = 'stopped';
                }
            }

            $result['themes'][] = array(
                'name' => $theme->getName(),
                'title' => $theme->getTitle(),
                'hasMbTiles' => $hasMbTiles,
                'hasSqlite' => $hasSqlite,
                'mbTilesState' => $mbTilesState
            );
        }

        $ajax->success($result);
        break;
}
