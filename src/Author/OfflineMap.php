<?php

namespace GisClient\Author;

class OfflineMap
{
    /**
     * Map
     *
     * @var Map
     */
    protected $map;

    /**
     * List of available offline data formats
     *
     * @var Offline\OfflineDataInterface[]
     */
    private $offlineDataFormats = [];

    /**
     * Constructor
     *
     * @param Map $map
     */
    public function __construct(\Traversable $offlineDataFormats)
    {
        $this->offlineDataFormats = $offlineDataFormats;
    }

    /**
     * Start offline data generation
     *
     * @param Map $map
     * @param Theme|null $theme
     * @param string|null $only
     */
    public function start(Map $map, Theme $theme = null, $only = null)
    {
        $themes = $map->getThemes();
        if (!empty($theme)) {
            $themes = array($theme);
        }

        foreach ($themes as $theme) {
            foreach ($this->offlineDataFormats as $offlineDataFormat) {
                $format = $offlineDataFormat->getName();

                // check the format has to be processed
                if ((!empty($only) && $only !== $format)
                    || !$offlineDataFormat->supports($theme)
                ) {
                    continue;
                }

                $offlineDataFormat->start($map, $theme);
            }
        }
    }

    /**
     * Stop offline data generation
     *
     * @param Map $map
     * @param Theme|null $theme
     * @param string|null $only
     */
    public function stop(Map $map, Theme $theme = null, $only = null)
    {
        $themes = $map->getThemes();
        if (!empty($theme)) {
            $themes = array($theme);
        }

        foreach ($themes as $theme) {
            foreach ($this->offlineDataFormats as $offlineDataFormat) {
                $format = $offlineDataFormat->getName();

                // check the format has to be processed
                if ((!empty($only) && $only !== $format)
                    || !$offlineDataFormat->supports($theme)
                ) {
                    continue;
                }

                $offlineDataFormat->stop($map, $theme);
            }
        }
    }

    /**
     * Delete offline data
     *
     * @param Map $map
     * @param Theme|null $theme
     * @param string|null $only
     */
    public function clear(Map $map, Theme $theme = null, $only = null)
    {
        $themes = $map->getThemes();
        if (!empty($theme)) {
            $themes = array($theme);
        }

        foreach ($themes as $theme) {
            foreach ($this->offlineDataFormats as $offlineDataFormat) {
                $format = $offlineDataFormat->getName();

                // check the format has to be processed
                if ((!empty($only) && $only !== $format)
                    || !$offlineDataFormat->supports($theme)
                ) {
                    continue;
                }

                $offlineDataFormat->clear($map, $theme);
            }
        }
    }

    /**
     * Get current status of offline data
     *
     * @param Map $map
     * @param Theme|null $theme
     * @param string|null $only
     */
    public function status(Map $map, Theme $theme = null, $only = null)
    {
        $result = array();

        $themes = $map->getThemes();
        if (!empty($theme)) {
            $themes = array($theme);
        }

        foreach ($themes as $theme) {
            $themeName = $theme->getName();
            $result[$themeName] = [];

            foreach ($this->offlineDataFormats as $offlineDataFormat) {
                $format = $offlineDataFormat->getName();
                $result[$themeName][$format] = [];

                // check the format has to be processed
                if ((!empty($only) && $only !== $format)
                    || !$offlineDataFormat->supports($theme)
                ) {
                    continue;
                }

                $result[$themeName][$format] = [
                    'state' => $offlineDataFormat->getState($map, $theme),
                    'progress' => $offlineDataFormat->getProgress($map, $theme),
                ];
            }
        }

        return $result;
    }

    /**
     * Create zip containing the offline data
     *
     * @param Map $map
     * @param string $zipFile
     * @param array $formats
     */
    public function createZip(Map $map, $zipFile, array $formats = [])
    {
        $supportedFormats = [];
        foreach ($this->offlineDataFormats as $offlineDataFormat) {
            $supportedFormats[$offlineDataFormat->getName()] = true;
        }
        $requestFormats = array_merge($supportedFormats, $formats);

        $zip = new \ZipArchive();
        $mapName = $map->getName();

        if ($zip->open($zipFile, \ZipArchive::CREATE) !== true) {
            throw new \Exception("Failed to create zip file '{$zipFile}'", 1);
        }

        $themes = $map->getThemes();
        foreach ($themes as $theme) {
            // add legend
            $img = $this->getLegendForTheme($theme);
            if ($img) {
                $zip->addFromString($theme->getName() . '.png', $img);
            }

            foreach ($this->offlineDataFormats as $offlineDataFormat) {
                $format = $offlineDataFormat->getName();

                // check the format has to be processed
                if (!in_array($format, $requestFormats)
                    || !$offlineDataFormat->supports($theme)
                ) {
                    continue;
                }

                // TODO: check if status is not running??

                $files = $offlineDataFormat->getOfflineFiles($map, $theme);
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }
            }

            // add lookups
            foreach ($theme->getLayerGroups() as $layerGroup) {
                if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                    foreach ($layerGroup->getLayers() as $layer) {
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
                                } else {
                                    throw new \Exception(sprintf(
                                        "Could not create json with lookup values for %s",
                                        $field->getName()
                                    ));
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
    }

    protected function getSavedFilter($mapset)
    {
        $url = INTERNAL_URL . "services/saved_filter/".$mapset;
        return $this->getFile($url);
    }

    protected function getLookupValues($catalogId, $lookupTable, $lookupId, $lookupName)
    {
        $url = INTERNAL_URL . "services/lookup.php";
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
        $url = INTERNAL_URL . "services/gcmapconfig.php";
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
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        if (isset($_COOKIE['gisclient3'])) {
            curl_setopt($curl, CURLOPT_COOKIE, 'gisclient3='.$_COOKIE['gisclient3']);
        }
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
        return $this->getFile(INTERNAL_URL . "services/symbol.php", [
            'table' => 'symbol',
            'id' => $symbolName
        ]);
    }
}
