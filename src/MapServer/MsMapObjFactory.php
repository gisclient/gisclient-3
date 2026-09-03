<?php

namespace GisClient\MapServer;

use GisClient\Author\Utils\OwsHandler;
use GisClient\MapServer\Connection\MapDatabaseRouter;
use GisClient\MapServer\Connection\OgcRequestClassifier;

class MsMapObjFactory
{
    /**
     * Create a mapObj instance
     *
     * @param string $project
     * @param string $map
     * @param boolean $temporary
     * @param string|null $lang
     * @return mapObj
     * @throws \Exception
     */
    public function create($project, $map, $temporary = false, $lang = null)
    {
        $mapFileDir = ROOT_PATH . 'map';
        $mapFileBasename = $map . '.map';
        
        // check project directory
        $projectDir = $mapFileDir . DIRECTORY_SEPARATOR . $project;
        if (strpos(realpath($projectDir), (string) realpath($mapFileDir)) !== 0) {
            // if the the project directory is not a subdir of map/, something
            // bad is happening
            print_debug(sprintf(
                'project map files dir "%s" is not in "%s"',
                realpath($projectDir),
                realpath($mapFileDir)
            ), null, 'system');
            throw new \Exception("Invalid PROJECT name");
        }
        
        // check if using mapfile for another language
        if (!is_null($lang)) {
            $mapFileWithLang = $projectDir . DIRECTORY_SEPARATOR . $map . '_' . $lang . '.map';
            if (strpos(realpath($mapFileWithLang), (string) realpath($projectDir)) !== 0) {
                print_debug(sprintf(
                    'mapfile "%s" is not in project dir "%s"',
                    $mapFileWithLang,
                    realpath($projectDir)
                ), null, 'system');
            } else {
                $mapFileBasename = $map . '_' . $lang . '.map';
            }
        }
        
        // check if using temporary mapfile
        if ($temporary) {
            $mapFileBasename = 'tmp.' . $mapFileBasename;
        }

        // check if mapfile is in project dir
        $mapFile = $projectDir . DIRECTORY_SEPARATOR . $mapFileBasename;
        if (strpos(realpath($mapFile), (string) realpath($projectDir)) !== 0) {
            // if the the map is not in the project dir, something
            // bad is happening
            print_debug(sprintf(
                'mapfile "%s" is not in project dir "%s"',
                $mapFile,
                realpath($projectDir)
            ), null, 'system');
            throw new \Exception("Invalid MAP name");
        }
        
        // check if mapfile is readable
        if (!is_readable($mapFile)) {
            // map file not found
            print_debug('mapfile ' . $mapFile . ' not readable', null, 'system');
            throw new \Exception('Invalid MAP name');
        }

        $oMap = ms_newMapObjFromString(file_get_contents($mapFile));
        print_debug('opened mapfile "' . realpath($mapFile) . '": ' . get_class($oMap), null, 'system');
        
        // update metadata
        $url = OwsHandler::currentPageURL();
        $onlineResource = $url . '?project=' . $project . '&map=' . $map . '&tmp=' . $temporary;
        if (!is_null($lang)) {
            $onlineResource .= '&lang=' . $lang;
        }
        $oMap->setMetaData("ows_onlineresource", $onlineResource);
        
        return $oMap;
    }

    public function from(\OwsrequestObj $request): \mapObj
    {
        /** @var string $project */
        $project = $request->getvaluebyname('project');
        /** @var string $map */
        $map = $request->getvaluebyname('map');
        /** @var boolean $temporary */
        $temporary = !empty($request->getvaluebyname('tmp'));

        /** @var string|null $temporary */
        $lang = $request->getvaluebyname('lang');
        if (empty($lang)) {
            $lang = null;
        }

        $oMap = $this->create($project, $map, $temporary, $lang);

        // Route read-only OGC operations to the replica. Done here rather than
        // in the decorator so that layers without a SETROLE directive are
        // covered too: the decorator only rewrites RLS layers, everything else
        // would keep the primary connection baked into the mapfile.
        if (OgcRequestClassifier::isReadOnlyRequest($request, $_SERVER, $_REQUEST)) {
            MapDatabaseRouter::applyTo($oMap);
        } elseif (MapDatabaseRouter::isConfigured()) {
            print_debug(sprintf(
                'read-only routing: not applied (service=%s request=%s method=%s), staying on the primary',
                $request->getValueByName('service') ?: '(none)',
                $request->getValueByName('request') ?: '(none)',
                $_SERVER['REQUEST_METHOD'] ?? '(none)'
            ), null, 'ows');
        }

        return $oMap;
    }
}
