<?php

namespace GisClient\MapServer\Writer;

/**
 * Prefetch cache for the optimized mapfile writer.
 *
 * Replaces the per-layer / per-class queries of the legacy writer
 * (gcFeature::initFeature, gcFeature::getLayerText, gcFeature::_getClassText,
 * gcMapfile::_writeTemplateWms) with a handful of batched queries.
 *
 * The batched SQL mirrors the legacy per-row SQL exactly (same columns,
 * same joins, same inner ORDER BY) so that the rows fed to the text
 * builders are identical to what the legacy writer sees.
 *
 * Holds data only — no mapfile text logic.
 */
class MapfileDataCache
{
    private const IN_CHUNK_SIZE = 1000;

    /**
     * @var \PDO
     */
    private $db;

    /**
     * field+relation+catalog rows per layer_id (in field_order), mirror of gcFeature::initFeature
     *
     * @var array<int|string, array[]>
     */
    private $fieldsByLayer = [];

    /**
     * layer+catalog+project row per layer_id, mirror of gcFeature::initFeature
     *
     * @var array<int|string, array>
     */
    private $layerRowByLayer = [];

    /**
     * class rows per layer_id (in class_order), mirror of gcFeature::getLayerText
     *
     * @var array<int|string, array[]>
     */
    private $classesByLayer = [];

    /**
     * style rows per class_id (in style_order DESC), mirror of gcFeature::_getClassText
     *
     * @var array<int|string, array[]>
     */
    private $stylesByClass = [];

    /**
     * Queryable template layers of the current project, mirror of gcMapfile::_writeTemplateWms
     * but filtered by project.
     *
     * @var array[]
     */
    private $templateLayers = [];

    /**
     * Template field rows per layer_id (in field_order), mirror of gcMapfile::_writeTemplateWms
     *
     * @var array<int|string, array[]>
     */
    private $templateFieldsByLayer = [];

    /**
     * mapset_scales per mapset_name
     *
     * @var array<string, string|null>
     */
    private $mapsetScales = [];

    /**
     * layer_ids already loaded by loadForLayers() — repeated loads (language
     * variants, per-layer mapfiles) must not append duplicate rows
     *
     * @var array<int|string, true>
     */
    private $loadedLayerIds = [];

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Batch-load everything needed to build the layer text for the given layers.
     */
    public function loadForLayers(array $layerIds)
    {
        $layerIds = array_values(array_unique($layerIds));
        $layerIds = array_values(array_filter(
            $layerIds,
            fn ($layerId) => !isset($this->loadedLayerIds[$layerId])
        ));
        if (empty($layerIds)) {
            return;
        }
        foreach ($layerIds as $layerId) {
            $this->loadedLayerIds[$layerId] = true;
        }

        $classIds = [];
        foreach (array_chunk($layerIds, self::IN_CHUNK_SIZE) as $chunk) {
            $in = \GCApp::prepareInStatement($chunk);

            // mirror of gcFeature::initFeature() field query
            $sql = "select field.*,
                relation.relation_name, relation_id, relationtype_id, data_field_1, data_field_2, data_field_3, table_field_1, table_field_2, table_field_3, table_name,
                catalog_path, catalog_url from " . DB_SCHEMA . ".field
                left join " . DB_SCHEMA . ".relation using (layer_id,relation_id)
                left join " . DB_SCHEMA . ".catalog using (catalog_id)
                where field.layer_id in ({$in['inQuery']})
                order by field.layer_id, field_order, field_id;";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($in['parameters']);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $this->fieldsByLayer[$row['layer_id']][] = $row;
            }

            // mirror of gcFeature::initFeature() feature query
            $sql = "select layer.*,connection_type,base_path,catalog_path,catalog_url
                from " . DB_SCHEMA . ".layer inner join " . DB_SCHEMA . ".catalog using (catalog_id)
                inner join " . DB_SCHEMA . ".project using(project_name)
                where layer.layer_id in ({$in['inQuery']});";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($in['parameters']);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $this->layerRowByLayer[$row['layer_id']] = $row;
            }

            // mirror of gcFeature::getLayerText() class query
            // (layer_id is selected for grouping only and stripped below)
            $sql = "select layer_id,class_id,class_name,class_title,class_text,class_image,legendtype_id,keyimage,expression,class.maxscale,class.minscale,label_font,label_angle,label_color,label_outlinecolor,label_bgcolor,label_size,label_minsize,label_maxsize,label_position,label_priority,label_buffer,label_force,label_wrap,label_def
                from " . DB_SCHEMA . ".class where layer_id in ({$in['inQuery']}) order by layer_id, class_order, class_id;";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($in['parameters']);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $layerId = $row['layer_id'];
                unset($row['layer_id']);
                $this->classesByLayer[$layerId][] = $row;
                $classIds[] = $row['class_id'];
            }
        }

        foreach (array_chunk($classIds, self::IN_CHUNK_SIZE) as $chunk) {
            $in = \GCApp::prepareInStatement($chunk);

            // mirror of gcFeature::_getClassText() style query
            // (class_id is selected for grouping only and stripped below)
            $sql = "select class_id,style_id,angle,color,outlinecolor,bgcolor,size,minsize,maxsize,minwidth,width,style_def,symbol.symbol_name, pattern_def
                    from " . DB_SCHEMA . ".style left join " . DB_SCHEMA . ".symbol using (symbol_name) left join " . DB_SCHEMA . ".e_pattern using(pattern_id)
                    where class_id in ({$in['inQuery']}) order by class_id, style_order DESC, style_id;";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($in['parameters']);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $classId = $row['class_id'];
                unset($row['class_id']);
                $this->stylesByClass[$classId][] = $row;
            }
        }
    }

    /**
     * Batch-load the WMS GetFeatureInfo template data, filtered by project
     * (the legacy query at gcMapfile::_writeTemplateWms has no project filter,
     * which floods every project dir with all other projects' templates).
     *
     * @param string $projectName
     */
    public function loadTemplateData($projectName)
    {
        // mirror of gcMapfile::_writeTemplateWms() layer query + project filter
        $sql = "SELECT DISTINCT layergroup_name, layer_id, layer_name, layer_title "
            . " FROM " . DB_SCHEMA . ".field "
            . " INNER JOIN " . DB_SCHEMA . ".layer USING(layer_id) "
            . " INNER JOIN " . DB_SCHEMA . ".layergroup USING (layergroup_id) "
            . " INNER JOIN " . DB_SCHEMA . ".theme USING (theme_id) "
            . " WHERE resultype_id <> 4 AND queryable = 1 AND project_name = :project_name "
            . " ORDER BY layergroup_name, layer_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':project_name' => $projectName,
        ]);
        $this->templateLayers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $layerIds = array_column($this->templateLayers, 'layer_id');
        foreach (array_chunk($layerIds, self::IN_CHUNK_SIZE) as $chunk) {
            $in = \GCApp::prepareInStatement($chunk);

            // mirror of gcMapfile::_writeTemplateWms() field query
            // (layer_id is selected for grouping only and stripped below)
            $sql = "SELECT layer_id, field_id, field_name, field_header "
                . " FROM " . DB_SCHEMA . ".field "
                . " INNER JOIN " . DB_SCHEMA . ".layer USING(layer_id) "
                . " WHERE resultype_id <> 4 AND layer_id in ({$in['inQuery']}) "
                . " ORDER BY layer_id, field_order, field_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($in['parameters']);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $layerId = $row['layer_id'];
                unset($row['layer_id']);
                $this->templateFieldsByLayer[$layerId][] = $row;
            }
        }
    }

    /**
     * @param int|string $layerId
     * @return array[] rows in field_order, [] when the layer has no fields
     */
    public function getFieldRows($layerId)
    {
        return $this->fieldsByLayer[$layerId] ?? [];
    }

    /**
     * @param int|string $layerId
     * @return array|null
     */
    public function getLayerRow($layerId)
    {
        return $this->layerRowByLayer[$layerId] ?? null;
    }

    /**
     * @param int|string $layerId
     * @return array[] rows in class_order, [] when the layer has no classes
     */
    public function getClassRows($layerId)
    {
        return $this->classesByLayer[$layerId] ?? [];
    }

    /**
     * @param int|string $classId
     * @return array[] rows in style_order DESC, [] when the class has no styles
     */
    public function getStyleRows($classId)
    {
        return $this->stylesByClass[$classId] ?? [];
    }

    /**
     * @return array[]
     */
    public function getTemplateLayers()
    {
        return $this->templateLayers;
    }

    /**
     * @param int|string $layerId
     * @return array[] rows in field_order
     */
    public function getTemplateFieldRows($layerId)
    {
        return $this->templateFieldsByLayer[$layerId] ?? [];
    }

    /**
     * mapset_scales for a mapset, queried once (the legacy writer re-runs this
     * inside the per-EPSG loop of gcMapfile::_getMapproxyGrids).
     *
     * @param string $mapsetName
     * @return string|null
     */
    public function getMapsetScales($mapsetName)
    {
        if (!array_key_exists($mapsetName, $this->mapsetScales)) {
            $sql = "SELECT mapset_scales FROM " . DB_SCHEMA . ".mapset WHERE mapset_name=?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mapsetName]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->mapsetScales[$mapsetName] = $row ? $row['mapset_scales'] : null;
        }
        return $this->mapsetScales[$mapsetName];
    }
}
