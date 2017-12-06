<?php

namespace GisClient\Author\Security;

use GisClient\Author\Security\User\UserInterface;

class LayerAuthorizationChecker
{
    /**
     * Database
     *
     * @var \PDO
     */
    private $db;
    
    /**
     * User
     *
     * @var UserInterface
     */
    private $user;
    
    /**
     * Constructor
     *
     * @param \PDO $db
     * @param UserInterface $user
     */
    public function __construct(\PDO $db, UserInterface $user)
    {
        $this->db = $db;
        $this->user = $user;
    }
    
    /**
     * Get map and authorized layers
     *
     * @param array $filter
     * @return boolean
     */
    public function getLayers(array $filter)
    {
        $result = array(
            'authorized_layers' => array(),
            'map_layers' => array(),
        );
        if (isset($filter['mapset_name'])) {
            $sqlFilter = 'mapset_name = :mapset_name';
            $sqlValues = array(':mapset_name'=>$filter['mapset_name']);
            $sql = 'select project_name from '.DB_SCHEMA.'.mapset where mapset_name=:mapset_name';
        } elseif (isset($filter['theme_name'])) {
            $sqlFilter = 'theme_name = :theme_name';
            $sqlValues = array(':theme_name'=>$filter['theme_name']);
            $sql = 'select project_name from '.DB_SCHEMA.'.theme where theme_name=:theme_name';
        } elseif (isset($filter['project_name'])) {
            $sqlFilter = 'project_name = :project_name';
            $sqlValues = array(':project_name'=>$filter['project_name']);
            $sql = 'select project_name from '.DB_SCHEMA.'.project where project_name=:project_name';
        } else {
            return false;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($sqlValues);
        $projectName = $stmt->fetchColumn(0);
        
        $groupFilter = '';
        if (empty($filter['show_as_public'])) {
            $isAdmin = ($this->user->isAdmin() || $this->user->isAdmin($projectName));
        } else {
            $isAdmin = false;
        }
        if (!$isAdmin) {
            $groups = $this->user->getGroups();
            if (!empty($groups)) {
                $in = array();
                foreach ($groups as $k => $groupId) {
                    array_push($in, ':group_param_'.$k);
                    $sqlValues[':group_param_'.$k] = $groupId;
                }
                $groupFilter = ' AND COALESCE (groupname, \'**NOGROUP**\')
                    IN (\'**NOGROUP**\' ,'.implode(',', $in).') ';
            } else {
                $groupFilter = ' AND 1=2 ';
            }
        }
        
        if (empty($filter['show_as_public'])) {
            $authClause = '(private=1 '.$groupFilter.' ) OR (coalesce(private,0)=0)';
        } else {
            //$authClause = '(coalesce(layer.private,0)=0 AND mapset.private=0)';
            $authClause = '(coalesce(private,0)=0)';
        }
        
        $layerAuthorizations = array();
        $sql = '
            SELECT
                theme.project_name, theme_name, layergroup_name, layergroup_single,
                layer.layer_id, layer.private, layer.layer_name, layergroup.layergroup_title,
                layer.layer_title, layer.maxscale, layer.minscale,layer.hidden,
                case when coalesce(layer.private,1) = 1 then wms else 1 end as wms,
                case when coalesce(layer.private,1) = 1 then wfs else 1 end as wfs,
                case when coalesce(layer.private,1) = 1 then wfst else 1 end as wfst,
                layer_order
            FROM '.DB_SCHEMA.'.theme
            INNER JOIN  '.DB_SCHEMA.'.layergroup USING (theme_id)
            INNER JOIN '.DB_SCHEMA.'.mapset_layergroup USING (layergroup_id)
            INNER JOIN (
                SELECT *
                FROM '.DB_SCHEMA.'.mapset
                LEFT JOIN '.DB_SCHEMA.'.mapset_groups USING (mapset_name)
                WHERE ' .
                    $authClause
                . ') AS mapset USING (mapset_name)
            INNER JOIN (
                SELECT *
                FROM '.DB_SCHEMA.'.layer
                LEFT JOIN '.DB_SCHEMA.'.layer_groups USING (layer_id, layer_name) 
                WHERE ' .
                    $authClause
                . ') as layer USING (layergroup_id)
            WHERE ('.$sqlFilter.') ORDER BY layer.layer_order DESC;';
            
        $stmt = $this->db->prepare($sql);
        $stmt->execute($sqlValues);
//echo nl2br($sql) . "<br>" . print_r($sqlValues, true) . "<br>";
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $featureType = $row['layergroup_name'].".".$row['layer_name'];
            if (!isset($layerAuthorizations[$row['project_name']])) {
                $layerAuthorizations[$row['project_name']] = [];
            }
            $layerAuthorizations[$row['project_name']][$featureType] = [
                'WMS' => $row['wms'],
                'WFS'=>$row['wfs'],
                'WFST'=>$row['wfst']
            ];

            if (!empty($row['layer_id'])) {
                $result['authorized_layers'][] = $row['layer_id'];
            }
       
            // create arrays if not exists
            if (!isset($result['map_layers'][$row['theme_name']])) {
                $result['map_layers'][$row['theme_name']] = array();
            }
            if (!isset($result['map_layers'][$row['theme_name']][$row['layergroup_name']])) {
                $result['map_layers'][$row['theme_name']][$row['layergroup_name']] = array();
            }
            if ($row['layergroup_single']==1) {
                $result['map_layers'][$row['theme_name']][$row['layergroup_name']] = array(
                    "name" => $row['layergroup_name'],
                    "title" => $row['layergroup_title'],
                    "grouptitle" => $row['layergroup_title']
                );
            } else {
                array_push($result['map_layers'][$row['theme_name']][$row['layergroup_name']], array(
                    "name" => $featureType,
                    "title" => $row['layer_title'] ? $row['layer_title'] : $row['layer_name'],
                    "grouptitle" => $row['layergroup_title'],
                    "minScale" => $row['minscale'],
                    "maxScale" => $row['maxscale'],
                    "hidden" => $row['hidden']
                ));
            }
        };
        //echo "<br><br>\n";
        //print_r($layerAuthorizations);
        //echo "<br><br>\n";
        \GCService::instance()->set('GISCLIENT_USER_LAYER', $layerAuthorizations);
        \GCService::instance()->getSession()->save();
        
        return $result;
    }
}
