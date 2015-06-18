<?php

abstract class AbstractUser {
    protected $options;
    protected $username;
    protected $groups;
    protected $adminUsername = SUPER_USER;
    protected $authorizedLayers = array();
    protected $mapLayers = array();
    
    
    function __construct(array $options = array()) {
        $defaultOptions = array();
        $this->options = array_merge($defaultOptions, $options);
        
        $sid = session_id();
        if(empty($sid)) {
            if(defined('GC_SESSION_NAME')) session_name(GC_SESSION_NAME);
            session_start();
        }
        
        if(!empty($_SESSION['USERNAME'])) {
            $this->username = $_SESSION['USERNAME'];
            if(!empty($_SESSION['GROUPS'])) $this->groups = $_SESSION['GROUPS'];
            else $this->_setSessionData();
        }
    }
    
    public function isAuthenticated() {
        return !empty($this->username);
    }
    
    public function isAdmin($project = null) {
        //$project serve a vedere se è admin del progetto
        if(!$project) {
            return ($this->username == $this->adminUsername);
        } else {
            $db = GCApp::getDB();
            $sql = 'select username from '.DB_SCHEMA.'.project_admin 
                where project_name = :project and username = :username';
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                'username'=>$this->username,
                'project'=>$project
            ));
            $result = $stmt->fetchColumn(0);
            return !empty($result);
        }
    }
    
    public function login($username, $password) {
        $db = GCApp::getDB();
        
        $sql = 'select username from '.DB_SCHEMA.'.users where username=:user and enc_pwd=:pass';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'user'=>$username,
            'pass'=>$password
        ));
        $usernameInDb = $stmt->fetchColumn(0);
        if(empty($usernameInDb)) {
			return false;
		}
        $this->username = $usernameInDb;
        $this->_setSessionData();
        return true;
    }
    
    public function logout() {
		session_destroy();
		unset($_SESSION);
        if(defined('GC_SESSION_NAME')) session_name(GC_SESSION_NAME);
        $this->username = null;
		session_start();
    }
    
    public function getUsername() {
        return $this->username;
    }

    protected function _setSessionData() {
        $_SESSION['USERNAME'] = $this->username;
        $this->_getUserGroups();
        $_SESSION['GROUPS'] = $this->groups;
    }
    
    protected function _getUserGroups() {
        $groups = $this->getUserGroups($this->username);
        $this->groups = empty($groups) ? array() : $groups;
    }
    
	public function setAuthorizedLayers(array $filter) {
		$db = GCApp::getDB();
		if(isset($filter['mapset_name'])) {
			$sqlFilter = 'mapset_name = :mapset_name';
			$sqlValues = array(':mapset_name'=>$filter['mapset_name']);
            $sql = 'select project_name from '.DB_SCHEMA.'.mapset where mapset_name=:mapset_name';
		} else if(isset($filter['theme_name'])) {
			$sqlFilter = 'theme_name = :theme_name';
			$sqlValues = array(':theme_name'=>$filter['theme_name']);
            $sql = 'select project_name from '.DB_SCHEMA.'.theme where theme_name=:theme_name';
		} else if(isset($filter['project_name'])) {
			$sqlFilter = 'project_name = :project_name';
			$sqlValues = array(':project_name'=>$filter['project_name']);
            $sql = 'select project_name from '.DB_SCHEMA.'.project where project_name=:project_name';
		} else {
			return false;
		}
		
        $stmt = $db->prepare($sql);
        $stmt->execute($sqlValues);
        $projectName = $stmt->fetchColumn(0);
        
        $groupFilter = '';
		if (empty($filter['show_as_public'])) {
			$isAdmin = ($this->isAdmin() || $this->isAdmin($projectName));
		} else {
			$isAdmin = false;
		}
        if(!$isAdmin) {
            if(!empty($this->groups)) {
                $in = array();
                foreach($this->groups as $k => $groupId) {
                    array_push($in, ':group_param_'.$k);
                    $sqlValues[':group_param_'.$k] = $groupId;
                }
                $groupFilter = ' and groupname in ('.implode(',',$in).') ';
            } else {
                $groupFilter = ' and 1=2 ';
            }
        }
        
		if (empty($filter['show_as_public'])) {
			$authClause = '(layer.private=1 '.$groupFilter.' ) OR (coalesce(layer.private,0)=0)';
		} else {
			$authClause = '(coalesce(layer.private,0)=0)';
		}
		
        $sql = ' SELECT project_name, theme_name, layergroup_name, layergroup_single, layer.layer_id, layer.private, layer.layer_name, layergroup.layergroup_title, layer.layer_title, layer.maxscale, layer.minscale,layer.hidden,
            case when coalesce(layer.private,1) = 1 then '.($isAdmin ? '1' : 'wms').' else 1 end as wms,
            case when coalesce(layer.private,1) = 1 then '.($isAdmin ? '1' : 'wfs').' else 1 end as wfs,
            case when coalesce(layer.private,1) = 1 then '.($isAdmin ? '1' : 'wfst').' else 1 end as wfst,
            layer_order
            FROM '.DB_SCHEMA.'.theme 
            INNER JOIN '.DB_SCHEMA.'.layergroup USING (theme_id) 
            INNER JOIN '.DB_SCHEMA.'.mapset_layergroup using (layergroup_id)
            LEFT JOIN '.DB_SCHEMA.'.layer USING (layergroup_id)
            LEFT JOIN '.DB_SCHEMA.'.layer_groups USING (layer_id)
            WHERE ('.$sqlFilter.') AND ('.$authClause.') ORDER BY layer.layer_order;';

        $stmt = $db->prepare($sql);
        $stmt->execute($sqlValues);

		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

			$featureType = $row['layergroup_name'].".".$row['layer_name'];
			$_SESSION['GISCLIENT_USER_LAYER'][$row['project_name']][$featureType] = array('WMS'=>$row['wms'],'WFS'=>$row['wfs'],'WFST'=>$row['wfst']);

			if(!empty($row['layer_id'])) {
				// se il filtro è richiesto e non è settato in sessione, escludi il layer
				if(isset($requiredAuthFilters[$row['layer_id']])) {
					$filterName = $requiredAuthFilters[$row['layer_id']];
					if(!isset($_SESSION['GISCLIENT']['AUTHFILTERS'][$filterName])) continue;
				}
				$this->authorizedLayers[] = $row['layer_id'];
			}
			// create arrays if not exists
			if(!isset($this->mapLayers[$row['theme_name']])) $this->mapLayers[$row['theme_name']] = array();
			if(!isset($this->mapLayers[$row['theme_name']][$row['layergroup_name']])) $this->mapLayers[$row['theme_name']][$row['layergroup_name']] = array();
			if($row['layergroup_single']==1)
                $this->mapLayers[$row['theme_name']][$row['layergroup_name']] = array("name" => $row['layergroup_name'], "title" => $row['layergroup_title'], "grouptitle" => $row['layergroup_title']);
            else
                array_push($this->mapLayers[$row['theme_name']][$row['layergroup_name']], array("name" => $featureType, "title" => $row['layer_title']?$row['layer_title']:$row['layer_name'], "grouptitle" => $row['layergroup_title'], "minScale" => $row['minscale'], "maxScale" => $row['maxscale'], "hidden" => $row['hidden']));

		};
	}
	
	public function getAuthorizedLayers(array $filter) { //TODO: controllare chi la usa
		if(empty($this->mapLayers)) $this->setAuthorizedLayers($filter);
		return $this->authorizedLayers;
	}
	
	public function getMapLayers(array $filter) { //TODO: controllare chi la usa
		if(empty($this->mapLayers)) $this->setAuthorizedLayers($filter);
		return $this->mapLayers;
	}
	
	public function saveUserOption($key, $value) {
		$db = GCApp::getDB();
		$sql = 'delete from '.DB_SCHEMA.'.users_options where option_key=:key and username=:username';
		$stmt = $db->prepare($sql);
		$stmt->execute(array('key'=>$key, 'username'=>$this->username));
		
		$sql = 'insert into '.DB_SCHEMA.'.users_options (username, option_key, option_value) '.
			' values (:username, :key, :value)';
		$stmt = $db->prepare($sql);
		$stmt->execute(array('username'=>$this->username, 'key'=>$key, 'value'=>$value));
	}
	
	public function setUserOptions() {
		$db = GCApp::getDB();
		$sql = 'select option_key, option_value from '.DB_SCHEMA.'.users_options where username=?';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($this->username));
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$_SESSION[$row['option_key']] = $row['option_value'];
		}
	}
    
    public static function getUsers() {
        $db = GCApp::getDB();
        
        $sql = 'select username, cognome, nome from '.DB_SCHEMA.'.users';
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function getUserData($username) {
        $db = GCApp::getDB();
        
        $sql = 'select username, cognome, nome from '.DB_SCHEMA.'.users where username=:user';
        $stmt = $db->prepare($sql);
        $stmt->execute(array('user'=>$username));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function getGroups() {
        $db = GCApp::getDB();
        
        $sql = 'select groupname, description from '.DB_SCHEMA.'.groups';
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function getUserGroups($username) {
        $db = GCApp::getDB();
        
        $sql = 'select groupname from '.DB_SCHEMA.'.user_group where username=:user';
        $stmt = $db->prepare($sql);
        $stmt->execute(array('user'=>$username));
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    public static function getGroupData($groupname) {
        $db = GCApp::getDB();
        
        $sql = 'select groupname, description from '.DB_SCHEMA.'.groups where groupname=:group';
        $stmt = $db->prepare($sql);
        $stmt->execute(array('group'=>$groupname));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}