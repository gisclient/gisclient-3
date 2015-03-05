<?php
//FUNZIONE CHE RESTITUISCE UN ARRAY CON TUTTI I FILE DELLA DIRECTORY
function elenco_file($p, $ext = '', $fname = '') {
	if(!empty($ext) && is_string($ext)) $ext = array($ext);
	$files = array();
	if(!is_dir($p)) return false;
	if($dh = opendir($p)) {
		while (($file = readdir($dh)) !== false) {
			if(is_dir(addFinalSlash($p).$file)) continue;
			if(!empty($ext)) {
				$parts = explode('.', $file);
				if(count($parts) > 1) {
					$extension = $parts[count($parts)-1];
					if(!in_array(strtolower($extension), $ext)) continue;
				}
			}
			array_push($files, $file);
		}
		closedir($dh);
	}
	return $files;
}

function elenco_dir($p){
	$elenco = array();
	if (is_dir($p)) {
	    if ($dh = opendir($p)) {
	        while (($file = readdir($dh)) !== false) {
				if (is_dir($p."/".$file) && !in_array($file,Array(".","..")))
					$elenco[]=$file;
			}
			closedir($dh);
		}
	} else echo 'no dir';
	return $elenco;
}

function new_file_name($file){
	$arr=explode(".",$file);
	if (is_array($arr)){
		$ext=array_pop($arr);
		$ext=".".$ext;
		$filename=implode(".",$arr);
	}
	else{
		$ext="";
		$filename=$file;		
	}
	$index="";
	$i=0;
	while(file_exists($filename.$index.$ext)){
		$i++;
		$index=".$i";
	}
	return $filename.$index.$ext;
		
}