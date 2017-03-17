<?php

namespace GisClient\Author;

use GisClient\MapProxy\Seed\Process as SeedProcess;
use GisClient\MapProxy\Seed\Task as SeedTask;
use GisClient\GDAL\Export\Process as GDALProcess;
use GisClient\GDAL\Export\SQLite\Task as SQLiteTask;
use GisClient\GDAL\Export\SQLite\Driver as SQLiteDriver;

class OfflineMap
{
    private $map;

    public function __construct(Map $map)
    {
        if (!defined('MAPPROXY_PATH')) {
            throw new \Exception('MapProxy is not configured', 1);
        }

        $this->map = $map;

        $name = $this->map->getName();
        $project = $this->map->getProject();

        $mapFile = "{$project}.{$name}.yaml";
        $seedFile = "{$project}/{$name}.seed.yaml";

        $binPath = MAPPROXY_PATH . 'bin/';
        $mapConfig = MAPPROXY_PATH . 'conf/' . $mapFile;
        $seedConfig = ROOT_PATH . 'map/' . $seedFile;


        $this->seedProcess = new SeedProcess($binPath, $mapConfig, $seedConfig);
        $this->gdalProcess = new GDALProcess(new SQLiteDriver());
    }

    /*
     * Fa partire il processo di seeding mapproxy per creare gli mbtiles
     * Fa partire il processo di generezione dei db spatial
     * Genera il file di configurazione del client
     */
    public function generate($only = null)
    {
        $logDir = DEBUG_DIR;

        if ($only == 'mbtiles' || empty($only)) {
            $task = new SeedTask('offline', $logDir);
            $this->seedProcess->start($task);
        }

        if ($only == 'sqlite' || empty($only)) {
            $layerGroups = $this->map->getLayerGroups();
            foreach ($layerGroups as $layerGroup) {
                if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                    $layers = $layerGroup->getLayers();
                    foreach ($layers as $layer) {
                        $task = new SQLiteTask($layer, $logDir);
                        $this->gdalProcess->start($task);
                    }
                }
            }
        }

        /*if ($only == 'config' || empty($only)) {
            file_put_contents(
                ROOT_PATH . "/var/config-{$name}.json",
                file_get_contents(PUBLIC_URL . "services/gcmapconfig.php?mapset={$name}&legend=1")
            );
        }*/
    }

    /*
     * Interrompe il processo di seeding se attivo -> facendo un start riprende da dove interrotto
     * Interrompe il processo di generazione spatial se attivo -> il db corrente va cancellato
     * Pulisce i file temporanei
     */
    public function stop($only = null)
    {
        $logDir = DEBUG_DIR;

        if ($only == 'mbtiles' || empty($only)) {
            $task = new SeedTask('offline', $logDir);
            $this->seedProcess->stop($task);
        }

        if ($only == 'sqlite' || empty($only)) {
            $layerGroups = $this->map->getLayerGroups();
            foreach ($layerGroups as $layerGroup) {
                if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                    $layers = $layerGroup->getLayers();
                    foreach ($layers as $layer) {
                        $task = new SQLiteTask($layer, $logDir);
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
    public function clear()
    {
        # code...
    }

    /*
     * Restituisce lo stato dell'intero processo
     * se è già pronto lo zip
     * o se ci sono dei processi ancora attivi (con percentuale)
     */
    public function status()
    {
        # code...
    }

    /*
     * Se lo zip è pronto restituisce la risorsa da scaricare
     */
    public function get()
    {
        $logDir = DEBUG_DIR;

        $zip = new \ZipArchive();
        $name = $this->map->getName();
        $zipFile = ROOT_PATH . 'var/' . $name . '.zip';

        if ($zip->open($zipFile, \ZipArchive::CREATE) !== true) {
            throw new \Exception("Failed to create zip file '{$zipFile}'", 1);
        }

        $themes = $this->map->getThemes();
        foreach ($themes as $theme) {
            $file = MAPPROXY_CACHE_PATH . $this->map->getProject() . '/' . $theme->getName() . '.mbtiles';
            $zip->addFile($file, basename($file));
        }

        $layerGroups = $this->map->getLayerGroups();
        foreach ($layerGroups as $layerGroup) {
            if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                $layers = $layerGroup->getLayers();
                foreach ($layers as $layer) {
                    $task = new SQLiteTask($layer, $logDir);
                    $zip->addFile($task->getFileName(), basename($task->getFileName()));
                }
            }
        }

        $zip->addFromString('config.json', file_get_contents(PUBLIC_URL . "services/gcmapconfig.php?mapset={$name}&legend=1"));

        $zip->close();

        return $zipFile;
    }
}
