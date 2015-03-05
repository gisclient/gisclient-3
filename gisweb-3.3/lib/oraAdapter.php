<?php
class OracleAdapter {
    private $schema;
    private $dsn;
    private $currentGeom;
    private $db;

    
    public function __construct($sPath = null) {
        if (!is_null($sPath)) {
            $this->setPath($sPath);
        }
    }

    public function xmin($geomColumn) {
        return "SDO_GEOM.SDO_MIN_MBR_ORDINATE($geomColumn, 1)";
    }

    public function ymin($geomColumn) {
        return "SDO_GEOM.SDO_MIN_MBR_ORDINATE($geomColumn, 2)";
    }

    public function xmax($geomColumn) {
        return "SDO_GEOM.SDO_MAX_MBR_ORDINATE($geomColumn, 1)";
    }

    public function ymax($geomColumn) {
        return "SDO_GEOM.SDO_MAX_MBR_ORDINATE($geomColumn, 2)";
    }

    public function setPath($sPath) {
        $pathInfo = explode("/",$sPath);
        //		if(count($pathInfo)==1){//Mancano le informazioni di connessione, ho solo lo schema e il db � quello del gisclient
        $this->dsn = "oci:";
        $this->schema = $pathInfo[0];
        //		}
/*		else{//Abbiamo db e schema
			$this->schema = $pathInfo[0];
			$connInfo=explode(" ",$pathInfo[1]);
			if(count($connInfo)==1) { //abbiamo il nome del db
                $this->dsn = "oci://".DB_HOST."/".$connInfo[0];
            } else {//abbiamo la stringa di connessione
				$this->dsn = $pathInfo[0];
            }
		} */
		if(defined('MAP_USER'))
			$this->db = new PDO($this->dsn, MAP_USER, MAP_PWD);
		else
			$this->db = new PDO($this->dsn, DB_USER, DB_PWD);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    }

    public function setDb(PDO $db){
        return $this->db = $db;
    }

    public function getDb(){
        return $this->db;
    }

    public function getSchema() {
        return $this->schema;
    }

    public function getDsn() {
        return $this->dsn;
    }


    public function intersects($geometryComlumn, $geometry) {
        return "SDO_ANYINTERACT($geometryComlumn, $geometry) = 'TRUE'";
    }


    static public function limit($sql, $limit, $offset = null) {
        if (is_null($offset)) {
            $max = $limit;
            $sql = <<<EOQ
SELECT *
FROM ($sql)
WHERE ROWNUM <= $max
EOQ;
        } else {
            $min = $offset + 1;
            $max = $offset + $limit;
            $sql = <<<EOQ
SELECT *
FROM (SELECT ROWNUM gc_rownum__,
      a.*
      FROM ($sql) a
      WHERE ROWNUM <= $max)
WHERE gc_rownum__ >= $min
EOQ;
        }
        return $sql;
    }
        /**
     * Return a string rappresentation of the geometry constructor for
     * a circle. oracle has a native circle geometry
     *
     * @param $X real world x
     * @param $Y real world y
     * @param $R real world snap radius
     * @param $geopixel size of a pixel in rwc
     * @param $geoX0 RS origin on x axis
     * @param, $geoY0 RS origin on y axis
     */
  	public function img2Circle($X,$Y,$R, $geopixel, $geoX0, $geoY0, $srid){

		$geoXc = $geoX0 + $X*$geopixel;
		$geoYc = $geoY0 - $Y*$geopixel;
		$r = $R*$geopixel;

        $west = ($geoXc - $r).",".$geoYc;
        $east = ($geoXc + $r).",".$geoYc;
        $north = $geoXc.",".($geoYc + $r);
		//fattore di scomposizione del cerchio trovato con le prove
        $this->currentGeom = <<<EOG
SDO_GEOMETRY (
    2003, $srid, NULL,
    SDO_ELEM_INFO_ARRAY(1, 1003, 4),
    SDO_ORDINATE_ARRAY ($west, $east, $north)
)
EOG;
		return $this->currentGeom;
	}

	public function img2Polygon(array $X,array $Y, $geopixel, $geoX0, $geoY0, $srid){
		$p=array();
		for($i=0;$i<count($X);$i++){
			$geoX = $geoX0 + $X[$i]*$geopixel;
			$geoY = $geoY0 - $Y[$i]*$geopixel;
			$p[] = "$geoX,$geoY";
		}
		$p[]=$p[0]; // close the ring

		$points = implode(",",$p);
        $this->currentGeom = <<<EOG
SDO_GEOMETRY (
    2003, $srid, NULL,
    SDO_ELEM_INFO_ARRAY(1, 1003, 1),
    SDO_ORDINATE_ARRAY ($points)
)
EOG;
		return $this->currentGeom;
	}

	public function img2Extent($xMin,$yMin,$xMax,$yMax, $srid){
        $this->currentGeom = <<<EOG
SDO_GEOMETRY (
    2003, $srid, NULL,
    SDO_ELEM_INFO_ARRAY(1, 1003, 3),
    SDO_ORDINATE_ARRAY ($xMin,$yMin,$xMax,$yMax)
)
EOG;
		return $this->currentGeom;
	}

    /**
     * Return the WKT rappresentation of an Oracle object
     * If no string is given, then the current object is used
     * 
     * @param string $oraGeom
     * @return string
     */
    public function getWKT($oraGeom = null){
        if (is_null($oraGeom)) {
            $oraGeom = $this->currentGeom;
        }
        
        $stmt = $this->db->prepare("SELECT {$oraGeom}.Get_WKT() FROM DUAL");
        $stmt->bindColumn(1, $wkt, PDO::PARAM_LOB);
        $stmt->fetch(PDO::FETCH_BOUND);
        return $wkt;
    }

}
?>
