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
    public function get()
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
            $taskName = $mapName . '_' . $theme->getName();
            $task = new SeedTask($this->map->getProject(), $taskName, $logDir);
            $zip->addFile($task->getFilePath(), basename($task->getFilePath()));

            $layerGroups = $theme->getLayerGroups();
            foreach ($layerGroups as $layerGroup) {
                if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                    $layers = $layerGroup->getLayers();
                    foreach ($layers as $layer) {
                        $taskName = $this->map->getName() . '_' . $layerGroup->getName() . '.' . $layer->getName();
                        $task = new SQLiteTask($layer, $taskName, $logDir);
                        $zip->addFile($task->getFilePath(), basename($task->getFilePath()));
                    }
                }
            }
        }

        $zip->addFromString(
            'config.json',
            file_get_contents(PUBLIC_URL . "services/gcmapconfig.php?mapset={$mapName}&legend=1")
        );

        $zip->close();

        return $zipFile;
    }
}
