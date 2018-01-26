<?php

namespace GisClient\Author;

use GisClient\MapProxy\Seed\Process as SeedProcess;
use GisClient\MapProxy\Seed\Task as SeedTask;
use GisClient\GDAL\Export\Process as GDALProcess;
use GisClient\GDAL\Export\SQLite\Task as SQLiteTask;
use GisClient\GDAL\Export\SQLite\Driver as SQLiteDriver;

class OfflineMap
{
    protected $map;

    public function __construct(Map $map)
    {
        if (!defined('MAPPROXY_PATH')) {
            throw new \Exception('MapProxy is not configured', 1);
        }

        $this->map = $map;

        $name = $this->map->getName();
        $project = $this->map->getProject();

        $mapFile = "{$project}/{$name}.yaml";
        $seedFile = "{$project}/{$name}.seed.yaml";

        $binPath = MAPPROXY_PATH . 'bin/';
        $mapConfig = ROOT_PATH . 'map/' . $mapFile;
        $seedConfig = ROOT_PATH . 'map/' . $seedFile;


        $this->seedProcess = new SeedProcess($binPath, $mapConfig, $seedConfig);
        $this->gdalProcess = new GDALProcess(new SQLiteDriver());
    }

    /*
     * Fa partire il processo di seeding mapproxy per creare gli mbtiles
     * Fa partire il processo di generezione dei db spatial
     * Genera il file di configurazione del client
     */
    public function start(Theme $theme = null, $only = null)
    {
        $logDir = DEBUG_DIR;

        if ($only == 'mbtiles' || empty($only)) {
            if (empty($theme)) {
                $task = new SeedTask($this->map->getProject(), 'offline', $logDir);
            } else {
                $taskName = $this->map->getName() . '_' . $theme->getName();
                $task = new SeedTask($this->map->getProject(), $taskName, $logDir);
            }
            $this->seedProcess->start($task);
        }

        if ($only == 'sqlite' || empty($only)) {
            if (empty($theme)) {
                $layerGroups = $this->map->getLayerGroups();
            } else {
                $layerGroups = $theme->getLayerGroups();
            }

            foreach ($layerGroups as $layerGroup) {
                if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                    $layers = $layerGroup->getLayers();
                    foreach ($layers as $layer) {
                        $taskName = $this->map->getName() . '_' . $layerGroup->getName() . '.' . $layer->getName();
                        $task = new SQLiteTask($layer, $taskName, $logDir);
                        $this->gdalProcess->start($task);
                    }
                }
            }
        }
    }

    /*
     * Interrompe il processo di seeding se attivo -> facendo un start riprende da dove interrotto
     * Interrompe il processo di generazione spatial se attivo -> il db corrente va cancellato
     * Pulisce i file temporanei
     */
    public function stop(Theme $theme = null, $only = null)
    {
        $logDir = DEBUG_DIR;

        if ($only == 'mbtiles' || empty($only)) {
            if (empty($theme)) {
                $task = new SeedTask($this->map->getProject(), 'offline', $logDir);
            } else {
                $taskName = $this->map->getName() . '_' . $theme->getName();
                $task = new SeedTask($this->map->getProject(), $taskName, $logDir);
            }

            $this->seedProcess->stop($task);
        }

        if ($only == 'sqlite' || empty($only)) {
            if (empty($theme)) {
                $layerGroups = $this->map->getLayerGroups();
            } else {
                $layerGroups = $theme->getLayerGroups();
            }

            foreach ($layerGroups as $layerGroup) {
                if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                    $layers = $layerGroup->getLayers();
                    foreach ($layers as $layer) {
                        $taskName = $this->map->getName() . '_' . $layerGroup->getName() . '.' . $layer->getName();
                        $task = new SQLiteTask($layer, $taskName, $logDir);
                        $this->gdalProcess->stop($task);
                    }
                }
            }
        }
    }

    /*
     * In base ai parametri cancella un mbtiles o uno spatial o il file di configurazione.
     * Vengono cancellati anche i relativi file temporanei
     */
    public function clear(Theme $theme = null, $only = null)
    {
        $logDir = DEBUG_DIR;

        if (!empty($theme)) {
            $themes = array($theme);
        } else {
            $themes = $this->map->getThemes();
        }

        foreach ($themes as $theme) {
            if ($only == 'mbtiles' || empty($only)) {
                $taskName = $this->map->getName() . '_' . $theme->getName();
                $task = new SeedTask($this->map->getProject(), $taskName, $logDir);
                $task->cleanup();
            }

            if ($only == 'sqlite' || empty($only)) {
                $layerGroups = $theme->getLayerGroups();
                foreach ($layerGroups as $layerGroup) {
                    if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                        $layers = $layerGroup->getLayers();
                        foreach ($layers as $layer) {
                            $taskName = $this->map->getName() . '_' . $layerGroup->getName() . '.' . $layer->getName();
                            $task = new SQLiteTask($layer, $taskName, $logDir);
                            $task->cleanup();
                        }
                    }
                }
            }
        }
    }

    /*
     * Restituisce lo stato dell'intero processo
     * se è già pronto lo zip
     * o se ci sono dei processi ancora attivi (con percentuale)
     */
    public function status(Theme $theme = null, $only = null)
    {
        $logDir = DEBUG_DIR;
        $result = array();

        if (!empty($theme)) {
            $themes = array($theme);
        } else {
            $themes = $this->map->getThemes();
        }

        foreach ($themes as $t) {
            $result[$t->getName()] = array(
                'mbtiles' => array(),
                'sqlite' => array()
            );

            if ($only == 'mbtiles' || empty($only)) {
                $taskName = $this->map->getName() . '_' . $t->getName();
                $task = new SeedTask($this->map->getProject(), $taskName, $logDir);
                if (!file_exists($task->getFilePath())) {
                    $mbTilesState = 'to-do';
                } else {
                    if ($this->seedProcess->isRunning($task)) {
                        $mbTilesState = 'running';
                    } else {
                        $mbTilesState = 'stopped';
                    }
                }
                
                $result[$t->getName()]['mbtiles'] = array(
                    'state' => $mbTilesState,
                    'progress' => $task->getProgress()
                );
            }

            if ($only == 'sqlite' || empty($only)) {
                $sqliteState = null;
                foreach ($t->getLayerGroups() as $layerGroup) {
                    if ($sqliteState == 'running') {
                        break;
                    }
                    if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                        $sqliteState = 'to-do';
                        $layers = $layerGroup->getLayers();
                        foreach ($layers as $layer) {
                            $taskName = $this->map->getName() . '_' . $layerGroup->getName() . '.' . $layer->getName();
                            $task = new SQLiteTask($layer, $taskName, $logDir);
                            if (file_exists($task->getFilePath())) {
                                if ($this->gdalProcess->isRunning($task)) {
                                    $sqliteState = 'running';
                                    break;
                                } else {
                                    $sqliteState = 'stopped';
                                }
                            }
                        }
                        $result[$t->getName()]['sqlite'] = array(
                            'state' => $sqliteState
                        );
                    }
                }
            }
        }

        return $result;
    }

    /*
     * Se lo zip è pronto restituisce la risorsa da scaricare
     */
    public function get($mbtiles = true, $sqlite = true)
    {
        $logDir = DEBUG_DIR;

        $zip = new \ZipArchive();
        $mapName = $this->map->getName();
        $zipFile = ROOT_PATH . 'var/' . $mapName . '.zip';

        if ($zip->open($zipFile, \ZipArchive::CREATE) !== true) {
            throw new \Exception("Failed to create zip file '{$zipFile}'", 1);
        }

        $themes = $this->map->getThemes();
        foreach ($themes as $theme) {
            $themeStatus = $this->status($theme);

            if ($mbtiles && count($themeStatus[$theme->getName()]['mbtiles'])) {
                $img = $this->getLegendForTheme($theme);
                if ($img) {
                    $zip->addFromString($theme->getName() . '.png', $img);
                }

                $taskName = $mapName . '_' . $theme->getName();
                $task = new SeedTask($this->map->getProject(), $taskName, $logDir);
                $zip->addFile($task->getFilePath(), basename($task->getFilePath()));
            }

            if ($sqlite && count($themeStatus[$theme->getName()]['sqlite'])) {
                $img = $this->getLegendForTheme($theme);
                if ($img) {
                    $zip->addFromString($theme->getName() . '.png', $img);
                }

                $layerGroups = $theme->getLayerGroups();
                foreach ($layerGroups as $layerGroup) {
                    if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                        $layers = $layerGroup->getLayers();
                        foreach ($layers as $layer) {
                            $taskName = $this->map->getName() . '_' . $layerGroup->getName() . '.' . $layer->getName();
                            $task = new SQLiteTask($layer, $taskName, $logDir);
                            $zip->addFile($task->getFilePath(), basename($task->getFilePath()));

                            $catalogId = $layer->getCatalogId();
                            foreach ($layer->getFields() as $field) {
                                $lookupTable = $field->getLookupTable();
                                $lookupId = $field->getLookupId();
                                $lookupName = $field->getLookupName();
                                if ($catalogId && $lookupTable && $lookupId && $lookupName) {
                                    $json = $this->getLookupValues($catalogId, $lookupTable, $lookupId, $lookupName);
                                    if ($json) {
                                        $zip->addFromString(
                                            "{$catalogId}{$lookupTable}.json",
                                            $json
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $zip->addFromString(
            'config.json',
            $this->getMapConfig($mapName)
        );

        $zip->addFromString(
            'saved_filter.json',
            $this->getSavedFilter($mapName)
        );

        $zip->close();

        return $zipFile;
    }

    protected function getSavedFilter($mapset)
    {
        $url = PUBLIC_URL . "services/saved_filter/".$mapset;
        return $this->getFile($url);
    }

    protected function getLookupValues($catalogId, $lookupTable, $lookupId, $lookupName)
    {
        $url = PUBLIC_URL . "services/lookup.php";
        $params = array(
            'catalog' => $catalogId,
            'table' => $lookupTable,
            'id' => $lookupId,
            'name' => $lookupName
        );

        return $this->getFile($url, $params);
    }

    protected function getMapConfig($mapName)
    {
        $url = PUBLIC_URL . "services/gcmapconfig.php";
        $params = array(
            'mapset' => $mapName,
            'legend' => 1
        );

        return $this->getFile($url, $params);
    }

    protected function getFile($url, array $params = [])
    {
        if (count($params) > 0) {
            $stringParams = array();

            foreach ($params as $key => $value) {
                $stringParams[] = $key . '=' . $value;
            }
            $url .= substr($url, -1) !== '?'? '?' : '';
            $url .= implode("&", $stringParams);
        }

        /*using CURL*/
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    protected function getLegendForTheme($theme)
    {
        $img = null;
        $symbol = $theme->getSymbolName();
        if ($symbol) {
            $img = $this->getSymbolImage($symbol);
        }
        return $img;
    }

    private function getSymbolImage($symbolName)
    {
        /* TODO: migrate Symbol class to src (namespaces) */
        require_once ADMIN_PATH . "lib/gcSymbol.class.php";

        $smb = new \Symbol('symbol');
        $smb->filter = "symbol.symbol_name='{$symbolName}'";
        $img = $smb->createIcon();

        return $img;
    }
}
