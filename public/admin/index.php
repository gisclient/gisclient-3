<?php

/*
GisClient map browser

Copyright (C) 2008 - 2009  Roberto Starnini - Gis & Web S.r.l. -info@gisweb.it

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

require_once __DIR__ . '/../../bootstrap.php';
include_once ROOT_PATH . "lib/i18n.php";

use Symfony\Component\HttpFoundation\Request;

header("Content-Type: text/html; Charset=" . CHAR_SET);
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
header("Expires: " . gmdate('D, d M Y H:i:s', time()) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Pragma: no-cache");

$Errors = array();
$Notice = array();

$gcService = GCService::instance();
$gcService->startSession();
$session = $gcService->getSession();

$authHandler = GCApp::getAuthenticationHandler();

if (!empty($_REQUEST['logout'])) {
    $authHandler->logout();
    header('Location: ../');
    exit;
}

if (!$authHandler->isAuthenticated()) {
    include_once ADMIN_PATH . "enter.php";
    exit;
}

include ADMIN_PATH . "lib/page.class.php";

$param = array();
$arr_action = array("salva", "aggiungi", "cancella", "elimina", "genera mappa", "copia", "sposta");
$arr_noaction = array("chiudi", "annulla", "avvia importazione");
if (!empty($_REQUEST["parametri"]))
    $param = $_REQUEST["parametri"];

$p = new page($authHandler, $_REQUEST);

$p->get_conf();

if (in_array(strtolower($p->action), $arr_action) || in_array(strtolower($p->action), $arr_noaction)) {

    include_once ADMIN_PATH . "lib/savedata.class.php";
    if (empty($_POST["savedata"]) || !file_exists(ADMIN_PATH . "db/db." . $_POST["savedata"] . ".php"))
        include ADMIN_PATH . "db/db.save.php";
    else
        include ADMIN_PATH . "db/db." . $_POST["savedata"] . ".php";
}

if (!empty($_REQUEST['publish_tmp_mapfiles']) && $_REQUEST['publish_tmp_mapfiles'] == 1) {
    GCAuthor::refreshMapfiles($p->parametri['project'], false); //refresh tmp mapfiles
    GCAuthor::refreshMapfiles($p->parametri['project'], true); //refresh public mapfiles
}


$initI18n = 'false';
if ($p->initI18n()) $initI18n = 'true';

$initDataManager = 'false';
$db = GCApp::getDB();
if (defined('USE_DATA_IMPORT') && USE_DATA_IMPORT == true && $p->livello == 'catalog' && $p->mode == 0) {
    $sql = 'select connection_type from ' . DB_SCHEMA . '.catalog where catalog_id=?';
    $stmt = $db->prepare($sql);
    $stmt->execute(array($p->parametri['catalog']));
    $catalogType = $stmt->fetchColumn(0);
    if ($catalogType == 6) $initDataManager = 'true';
}

$initPreviewMap = 'false';
$previewMapUrl = 'previewmap/';
if (in_array($p->livello, array('layer', 'layergroup')) && $p->mode == 0) {
    $initPreviewMap = 'true';
}
$isAuthor = true;

$initOgcServices = 'false';
$layerList = array();
if (isset($p->parametri['project'])) {
    $mapsets = GCAuthor::getMapsets($p->parametri['project']);
    $layerList = GCAuthor::getLayerList($p->parametri['project']);
    $initOgcServices = 'true';
}
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Author</title>
    <LINK media="screen" href="css/styles.css" type="text/css" rel="stylesheet">
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=<?php echo CHAR_SET ?>">
    <link type="text/css" href="css/jquery-ui/start/jquery-ui-1.8.16.custom.css" rel="stylesheet" />
    <script type="text/javascript" src="js/jquery/jquery.js"></script>
    <script type="text/javascript" src="js/jquery/jquery-ui.js"></script>
    <script type="text/javascript" src="js/jquery/layout.js"></script>
    <script type="text/javascript" src="js/i18n.js"></script>
    <script type="text/javascript" src="js/list.js"></script>
    <script type="text/javascript" src="js/copy.js"></script>
    <script type="text/javascript" src="js/mapfiles.js"></script>
    <script type="text/javascript" src="js/offline.js"></script>
    <script type="text/javascript" src="js/administrator.js"></script>
    <script type="text/javascript" src="js/layout.js"></script>
    <script type="text/javascript" src="js/options.js"></script>
    <?php if ($initDataManager == 'true') { ?>
        <script type="text/javascript" src="js/datamanager.js"></script>
    <?php } ?>
    <?php if ($initPreviewMap == 'true') { ?>
        <script type="text/javascript" src="js/previewMap.js"></script>
    <?php } ?>

    <script type="text/javascript">
        var initI18n = <?php echo $initI18n ?>;
        var initDataManager = <?php echo $initDataManager ?>;
        var initPreviewMap = <?php echo $initPreviewMap ?>;
        var initOgcServices = <?php echo $initOgcServices ?>;
        var previewMapUrl = '<?php echo $previewMapUrl ?>';
        var currentLevel = '<?php echo $p->livello ?>';
        <?php
        $errors = GCError::get();
        if (!empty($errors)) {
            foreach ($errors as &$error) $error = str_replace(array('"', "\n"), array('\"', '<br>'), $error);
            unset($error);
            ?>var errors = ["<?php echo implode('","', $errors); ?>"];
        <?php
        } ?>
    </script>
    <script type="text/javascript" src="js/opentype/opentype.min.js"></script>
</head>

<body>
    <div id="container">
        <div class="ui-layout-north">
            <?php include ADMIN_PATH . "inc/inc.admin.page_header.php"; ?>
            <?php $p->writeMenuNav(); ?>
        </div>
        <div class="ui-layout-center">
            <div id="containment" style="position: relative;">
                <?php /* FIXME: signature accepts only one parameter */ $p->writePage($Errors, $Notice); ?>
                <form method="POST" id="frm_param" name="frm_param"><?php $p->write_parameter(); ?></form>
                <?php include ADMIN_PATH . "inc/inc.window.php"; ?>
            </div>
            <div id="i18n_inline">
                <table cellpadding="5" border="1">
                </table>
            </div>
        </div>
        <div class="ui-layout-south">
            GisClient<span class="color">Author</span>
            <?php
            $sql = "SELECT version_name FROM " . DB_SCHEMA . ".vista_version";
            $version = $db->query($sql)->fetchColumn(0);
            echo sprintf("{$version} - 2009 - %d", date('Y'));
            if (function_exists('ms_GetVersionInt')) {
                $msVersion = ms_GetVersionInt();
                $msVersionMajor = (int) ($msVersion / 10000);
                $msVersionMinor = (int) (($msVersion - $msVersionMajor * 10000) / 100);
                echo " | MapServer {$msVersionMajor}.{$msVersionMinor}";
            }
            ?>
        </div>
    </div>
    </div>
    <div id="error_dialog" style="display:none;color:red;"></Div>
</body>

</html>