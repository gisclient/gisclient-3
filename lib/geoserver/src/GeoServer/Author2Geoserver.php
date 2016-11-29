<?php

namespace GisClient\GeoServer;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use GisClient\Logger\EmptyLogger;

class Author2GeoServer implements LoggerAwareInterface
{

    use LoggerAwareTrait;
    /**
     * @type \GisClient\GeoServer\GeoserverApi
     */
    private $geoserver;

    /**
     * @type array
     */
    private $filesToCopy = array();

    /**
     * Return the geoserver parameters from GisClient configuration
     * @return array
     */
    static public function getParameters()
    {
        $defaults = array(
            'scheme' => 'http',
            'host' => 'localhost',
            'port' => 8080,
            'user' => 'admin',
            'pass' => 'geoserver',
            'path' => '/geoserver',
            'options' => array(
            )
        );
        if (!defined('GEOSERVER_URL')) {
            throw new \Exception("WARNING: geoserver not enabled. Check configuration GEOSERVER_URL parameter");
        }
        if (!defined('GEOSERVER_PATH')) {
            throw new \Exception("WARNING: geoserver not enabled. Check configuration GEOSERVER_PATH parameter");
        }
        $opt = array_merge($defaults, parse_url(GEOSERVER_URL));
        if (!empty($opt['query'])) {
            foreach (explode('&', $opt['query']) as $param) {
                if (($p = strpos($param, '=')) === false) {
                    $opt['options'][$param] = null;
                } else {
                    $opt['options'][substr($param, 0, $p)] = substr($param, $p + 1);
                }
            }
            unset($opt['query']);
        }
        $opt['url'] = "{$opt['scheme']}://{$opt['host']}:{$opt['port']}{$opt['path']}";
        return $opt;
    }

    public function __construct()
    {
        $this->setLogger(new EmptyLogger());
    }

    /**
     * Add the given workspace to geoserver
     * @param string $workspaceName
     */
    private function addWorkspace($workspaceName)
    {
        $this->logger->debug("Add workspace \"{$workspaceName}\"");
        $this->geoserver->addWorkspace($workspaceName);
    }

    /**
     * Add the given datastore to geoserver
     * @param string $workspaceName
     * @param string $project
     * @param string $mapset
     */
    private function addDatastore($workspaceName, $projectName, $mapsetName)
    {
        foreach (AuthorWrapper::getCatalogListForMapset($projectName, $mapsetName) as $catalog) {
            if ($catalog['conntype_name'] == 'Postgis') {
                $datastoreName = "{$projectName}-{$catalog['catalog_name']}";
                $this->logger->debug("Workspace \"{$workspaceName}\": Add datastore \"{$datastoreName}\"");
                list($dbName, $dbSchema) = explode('/', $catalog['catalog_path']);
                $opt = array(
                    'description' => $catalog['catalog_description'],
                    'host' => DB_HOST,
                    'port' => DB_PORT,
                    'database' => $dbName,
                    'user' => DB_USER, // mapserver user
                    'passwd' => DB_PWD, // mapserver password
                    'schema' => $dbSchema
                );
                $this->geoserver->addDatastore($workspaceName, $datastoreName, $opt);
            } else {
                $this->logger->error("Unknown connection type \"{$catalog['conntype_name']}\" for catalog \"{$catalog['catalog_name']}\"");
            }
        }
    }

    /**
     * Add a new style
     * @param string $workspaceName
     * @param array $layerData
     */
    private function addStyle($workspaceName, array $layerData)
    {
        $styleName = "{$layerData['layergroup_name']}_{$layerData['layer_name']}";

        $sldClass = new AuthorSldGenerator(AuthorWrapper::getDb(), AuthorWrapper::getGcSchema());
        $sldClass->setLogger($this->logger);
        $sldData = $sldClass->generateSldForLayerAsText($layerData['layergroup_name'], $layerData['layer_name']);
        if ($sldData === null) {
            $this->logger->info("Workspace \"{$workspaceName}\": Skip style \"{$styleName}\"");
        } else {
            $this->logger->debug("Workspace \"{$workspaceName}\": Add style \"{$styleName}\"");
            $this->geoserver->addStyle($styleName, $sldData, $workspaceName);
            foreach ($sldClass->getFilesToCopy() as $file) {
                if (!in_array($file, $this->filesToCopy)) {
                    $this->filesToCopy[$workspaceName][] = $file;
                }
            }
        }
    }

    /**
     * Add a new layer
     *
     * @param string $workspaceName
     * @param string $datastoreName
     * @param string $tableName
     * @param string $layerName
     * @param string $title
     * @param string $description
     * @param string $sld
     */
    private function addLayer($workspaceName, $datastoreName, $tableName, $layerName = '', $title = '',
                              $description = '', $sldName = '')
    {
        $this->logger->debug("Workspace \"{$workspaceName}\": Add layer \"{$layerName}\" on datastore \"{$datastoreName}\"");
        $this->geoserver->addLayer(
            $workspaceName, $datastoreName, $tableName, $layerName, $title, $description, $sldName);
    }

    /**
     * Detete the given workspace from geoserver
     * @param string $workspaceName
     * @param boolean $cascade
     */
    private function delWorkspace($workspaceName, $cascade = false)
    {
        $force = $cascade;
        if ($cascade) {
            if ($this->geoserver->workspaceExists($workspaceName)) {
                $datastoreList = $this->geoserver->listDatastores("{$workspaceName}");

                // Delete layers
                foreach ($datastoreList as $datastore) {
                    $layerList = $this->geoserver->listLayers($workspaceName, $datastore['name']);
                    foreach ($layerList as $layer) {
                        $this->delLayer($workspaceName, $datastore['name'], $layer['name'], true);
                    }
                }

                // Delete styles
                $styleList = $this->geoserver->listStyles("{$workspaceName}");
                foreach ($styleList as $style) {
                    $this->delStyle($workspaceName, $style['name'], true);
                }

                // Delete data store
                foreach ($datastoreList as $datastore) {
                    $this->delDatastore($workspaceName, $datastore['name'], true);
                }
            }
        }
        $this->logger->debug("Delete workspace \"{$workspaceName}\"");
        $this->geoserver->delWorkspace($workspaceName, $force);
    }

    /**
     * Delete the datastore
     *
     * @param string $workspaceName
     * @param string $datastoreName
     * @param boolean $force
     */
    private function delDatastore($workspaceName, $datastoreName, $force = false)
    {
        $this->logger->debug("Workspace \"{$workspaceName}\": Delete datastore \"{$datastoreName}\"");
        $this->geoserver->delDatastore($workspaceName, $datastoreName, $force);
    }

    /**
     * Delete the given style
     *
     * @param string $workspaceName
     * @param string $styleName
     * @param boolean $force
     */
    private function delStyle($workspaceName, $styleName, $force = false)
    {
        $this->logger->debug("Workspace \"{$workspaceName}\": Delete style \"{$styleName}\"");
        $this->geoserver->delStyle($styleName, $workspaceName, $force);
    }

    private function delLayer($workspaceName, $datastoreName, $layerName, $force = false)
    {
        $this->logger->debug("Workspace \"{$workspaceName}\": Delete layer \"{$layerName}\" on datastore \"{$datastoreName}\"");
        $this->geoserver->delLayer($workspaceName, $datastoreName, $layerName, $force);
    }

    /**
     * Copy symbols to geoserver
     * 
     * @throws \Exception
     */
    private function copyStyles()
    {
        foreach ($this->filesToCopy as $workspace => $files) {
            $sldPath = AuthorWrapper::getGeoserverRootPath()."data_dir/workspaces/{$workspace}/styles/";
            foreach ($files as $srcFile) {
                $dstFile = $sldPath.basename($srcFile);
                if (!copy($srcFile, $dstFile)) {
                    throw new \Exception("Error coping style from \"{$srcFile}\" to \"{$dstFile}\"");
                }
            }
        }
    }

    /**
     * Process the synchronization
     *
     * @param string $projectName               The projet name
     * @param string $mapsetName                The mapset name. If null, all mapsets are re-synchronyzed
     * @param bool $publish                     If false, generate (temporary) workspace (name temp-)
     * @param bool $removeOnly                  If true, remove data only from geoserver
     */
    private function process($projectName, $mapsetName = null, $publish = false, $removeOnly = false)
    {
        $geoserverOptions = $this->getParameters();

        $this->geoserver = new GeoserverApi($geoserverOptions['url'], $geoserverOptions['user'],
            $geoserverOptions['pass'], $geoserverOptions['options']);

        $this->filesToCopy = array();

        AuthorWrapper::assertProjectExists($projectName);
        $mapsetList = array();
        if (empty($mapsetName)) {
            foreach (AuthorWrapper::getMapsetList($projectName) as $mapsetData) {
                $mapsetList[] = $mapsetData['mapset_name'];
            }
        } else {
            AuthorWrapper::assertMapsetExists($projectName, $mapsetName);
            $mapsetList[] = $mapsetName;
        }

        foreach ($mapsetList as $mapsetName) {
            $workspaceName = "{$projectName}-{$mapsetName}";
            if (!$publish) {
                $workspaceName = "tmp-{$workspaceName}";
            }
            $this->delWorkspace($workspaceName, true);
        }

        if (!$removeOnly) {
            // Add workspace
            foreach ($mapsetList as $mapsetName) {
                $workspaceName = "{$projectName}-{$mapsetName}";
                if (!$publish) {
                    $workspaceName = "tmp-{$workspaceName}";
                }

                $this->addWorkspace($workspaceName);
                $this->addDatastore($workspaceName, $projectName, $mapsetName);
                $layerList = AuthorWrapper::getLayerListForMapset($projectName, $mapsetList);

                // Create styles
                foreach ($layerList as $layerData) {
                    if ($layerData['conntype_name'] == 'Postgis') {
                        $datastoreName = "{$projectName}-{$layerData['catalog_name']}";
                        $styleName = "{$layerData['layergroup_name']}_{$layerData['layer_name']}";
                        $layerName = "{$layerData['layergroup_name']}_{$layerData['layer_name']}";
                        $layerDescription = "{$layerData['theme_name']}.{$layerData['layergroup_name']}.{$layerData['layer_name']}";
                        $this->addStyle($workspaceName, $layerData);
                        $this->addLayer($workspaceName, $datastoreName, $layerData['data'], $layerName,
                            $layerData['layer_title'], $layerDescription, $styleName);
                    }
                }
            }
            $this->copyStyles();
        }
    }

    /**
     * Remove data only
     *
     * @param string $projectName               The projet name
     * @param string $mapsetName                The mapset name. If null, all mapsets are re-synchronyzed
     * @param bool $publish                     If false, generate (temporary) workspace (name temp-)
     */
    public function removeOnly($projectName, $mapsetName = null, $publish = false)
    {
        return $this->process($projectName, $mapsetName, $publish, true);
    }

    /**
     * Synchronyze then author project/mapset with GeoServer by delete/insert all entities
     *
     * @param string $projectName               The projet name
     * @param string $mapsetName                The mapset name. If null, all mapsets are re-synchronyzed
     * @param bool $publish                     If false, generate (temporary) workspace (name temp-)
     */
    public function sync($projectName, $mapsetName = null, $publish = false)
    {
        return $this->process($projectName, $mapsetName, $publish, false);
    }
}