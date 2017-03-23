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
    die;
}

if (empty($_REQUEST['project'])) {
    $ajax->error();
    die;
}

if (empty($_REQUEST['map'])) {
    $ajax->error();
    die;
}

$action = $_REQUEST['action'];
$project = $_REQUEST['project'];
$map = $_REQUEST['map'];

$map = new Map($project, $map);
$offline = new OfflineMap($map);

switch ($action) {
    case 'get-data':
        $result = array();

        $themes = $map->getThemes();
        $result['themes'] = array();
        foreach ($themes as $theme) {
            $themeStatus = $offline->status($theme)[$theme->getName()];
            $themeStatus['name'] = $theme->getName();
            $themeStatus['title'] = $theme->getTitle();
            $result['themes'][] = $themeStatus;
        }

        $ajax->success($result);
        break;

    case 'start':
        $result = array();

        $themeName = $_REQUEST['theme'];
        $target = $_REQUEST['target'];

        $theme = null;
        foreach ($map->getThemes() as $t) {
            if ($t->getName() == $themeName) {
                $theme = $t;
            }
        }

        $offline->start($theme, $target);

        $ajax->success($result);
        break;

    case 'stop':
        $result = array();

        $themeName = $_REQUEST['theme'];
        $target = $_REQUEST['target'];

        $theme = null;
        foreach ($map->getThemes() as $t) {
            if ($t->getName() == $themeName) {
                $theme = $t;
            }
        }

        $offline->stop($theme, $target);

        $ajax->success($result);
        break;

    case 'clear':
        $result = array();

        $themeName = $_REQUEST['theme'];
        $target = $_REQUEST['target'];

        $theme = null;
        foreach ($map->getThemes() as $t) {
            if ($t->getName() == $themeName) {
                $theme = $t;
            }
        }

        $offline->clear($theme, $target);

        $ajax->success($result);
        break;

    case 'download':
        $result = array();

        $result['file'] = $offline->get();

        $ajax->success($result);
        break;
}
