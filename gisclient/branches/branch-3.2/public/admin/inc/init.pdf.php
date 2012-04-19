<?php

	if ($orient=="landscape"){
		switch($size){
			case "a0":
				define(FIRST_PAGE_COLS,4);
				define(COLS_NUM,10);
				define(ROW_CHAR,60);
				define(ROWS_NUM,40);
				define(FIRST_PAGE_ROWS,40);
				define(IMG_LEGEND_SIZE,8);
			break;
			case "a1":
				define(FIRST_PAGE_COLS,3);
				define(COLS_NUM,9);
				define(ROW_CHAR,60);
				define(ROWS_NUM,35);
				define(FIRST_PAGE_ROWS,35);
				define(IMG_LEGEND_SIZE,4);
			break;
			case "a2":
				define(FIRST_PAGE_COLS,2);
				define(COLS_NUM,8);
				define(ROW_CHAR,60);
				define(ROWS_NUM,30);
				define(FIRST_PAGE_ROWS,40);
				define(IMG_LEGEND_SIZE,4);
			break;
			case "a3":
				define(FIRST_PAGE_COLS,1);
				define(COLS_NUM,5);
				define(ROW_CHAR,60);
				define(ROWS_NUM,40);
				define(FIRST_PAGE_ROWS,25);
				define(IMG_LEGEND_SIZE,4);
			break;
			default:
				define(FIRST_PAGE_COLS,1);
				define(COLS_NUM,4);
				define(ROW_CHAR,60);
				define(ROWS_NUM,20);
				define(FIRST_PAGE_ROWS,15);
				define(IMG_LEGEND_SIZE,4);
			break;
		}
		
	}
	else{
		switch($size){
			case "a0":
				define(FIRST_PAGE_COLS,10);
				define(COLS_NUM,10);
				define(ROW_CHAR,40);
				define(ROWS_NUM,40);
				define(FIRST_PAGE_ROWS,10);
				define(IMG_LEGEND_SIZE,8);
			break;
			case "a1":
				define(FIRST_PAGE_COLS,9);
				define(COLS_NUM,9);
				define(ROW_CHAR,60);
				define(ROWS_NUM,35);
				define(FIRST_PAGE_ROWS,8);
				define(IMG_LEGEND_SIZE,4);
			break;
			case "a2":
				define(FIRST_PAGE_COLS,6);
				define(COLS_NUM,6);
				define(ROW_CHAR,80);
				define(ROWS_NUM,30);
				define(FIRST_PAGE_ROWS,10);
				define(IMG_LEGEND_SIZE,4);
			break;
			case "a3":
				define(FIRST_PAGE_COLS,5);
				define(COLS_NUM,5);
				define(ROW_CHAR,50);
				define(ROWS_NUM,40);
				define(FIRST_PAGE_ROWS,4);
				define(IMG_LEGEND_SIZE,4);
			break;
			default:
				define(FIRST_PAGE_COLS,4);
				define(COLS_NUM,4);
				define(ROW_CHAR,40);
				define(ROWS_NUM,20);
				define(FIRST_PAGE_ROWS,3);
				define(IMG_LEGEND_SIZE,4);
			break;
		}

	}
//}
function get_structure($arr,$char_dim){

/* ATTENZIONE ALLE  ICONE DELLA LEGENDA PERSONALIZZATE */
	$myMap=$_REQUEST["map"];
	$legPath = $_SESSION["MAP".$myMap]["legend_path"];
	foreach ($arr as $row){
		foreach ($row as $key=>$val){
			$gruppo=""; 
				if (is_array($val)){
					$gruppo=$val["gisclient_layer_description"]; 
					foreach($val as $k=>$v){
						if(!in_array($k,Array("gisclient_layer_description"))){
							$ris["cols_len"]=strlen(trim($v))+IMG_LEGEND_SIZE;
							$ris["rows_num"]=(int)($ris["cols_len"] / ROW_CHAR)+1;
							$l=($gruppo)?($v):("<b>$v</b>");
							$ris["label"]="<table width=\"100%\"><tr><td width=\"15%\"><img src=\"$legPath/$k.png\" width=\"".(IMG_LEGEND_SIZE*5)."px\"></img></td><td><font size=\"$char_dim pt\">$l</font></td></tr></table>";
							$ris["image"]=$k;
							$ris["group"]=$gruppo;
							$ris["page"]=0;
							$result[$gruppo][]=$ris;
						}					
					}
				}
				else{
					$gruppo=$row["gisclient_layer_description"];
					if(!in_array($key,Array("gisclient_category_description","gisclient_layer_description"))){
						$ris["cols_len"]=strlen(trim($val))+IMG_LEGEND_SIZE;
						$ris["rows_num"]=(int)($ris["cols_len"] / ROW_CHAR)+1;
						$l=($gruppo)?($val):("<b>$val</b>");
						$ris["label"]="<table width=\"100%\"><tr><td width=\"15%\"><img src=\"$legPath/$key.png\" width=\"".(IMG_LEGEND_SIZE*5)."px\"></img></td><td><font size=\"$char_dim pt\">$l</font></td></tr></table>";
						$ris["image"]=$key;
						$ris["group"]=$gruppo;
						$ris["page"]=0;
						$result[$gruppo][]=$ris;
						
					}
				}
		}
		
	}
	$page=0;
	$totrows=0;

	foreach($result as $key=>$val){
		if($key) {
			$totrows+=(int)(strlen($key) / ROW_CHAR)+1;
			$page=((FIRST_PAGE_ROWS*FIRST_PAGE_COLS)>=$totrows)?(1):(0);
			$r[]=Array("label"=>"<b><font size=\"".($char_dim+1)." pt\">".strtoupper($key)."</font></b>","page"=>$page,"title"=>1,"rows"=>(int)(strlen($key) / ROW_CHAR)+1);
		}
		foreach($val as $v){
			$totrows+=$v["rows_num"];
			$page=((FIRST_PAGE_ROWS*FIRST_PAGE_COLS)>=$totrows)?(1):(0);
			$result[$key][$k]["page"]=$page;
			$label=($key)?($v["label"]):("<b>".$v["label"]."</b>");
			$r[]=Array("label"=>$v["label"],"page"=>$page,"title"=>0,"rows"=>$v["rows_num"]);
		}
		$groups[$key]=Array("rows"=>$numrows,"page"=>$page);
	}
	return Array("gruppi"=>$groups,"legenda"=>$result,"new_legenda"=>$r);
}
function get_titolo($o,$char_dim,$legend_tab,$sMapUrl,$sScaleBarUrl,$titolo){
	if ($o=="landscape"){
		$tab="\t<tr>
		<td align=\"left\">
			<font size=\"".($char_dim+2)."\"><b>$titolo</b></font>
		</td>
		<td align=\"right\">
				<img src=\"$sScaleBarUrl\">
		</td>
		<td align=\"center\">
			<font size=\"".($char_dim+2)."\"><b>Legenda</b></font>
		</td>	
	</tr>
	<tr>
		<td colspan=\"2\"  rowspan=\"2\" align=\"center\"><center><img src=\"$sMapUrl\" width=\"100%\" height=\"100%\" align=\"center\"></img></center></td>
		<td></td>
	</tr>
	<tr height=\"100%\">
		<td valign=\"top\">
			$legend_tab
		</td>
	</tr>";
	}
	else{
		$tab="\t<tr>
		<td align=\"left\">
			<font size=\"".($char_dim+2)." pt\"><b>$titolo</b></font>
		</td>
		<td align=\"right\">
				<img src=\"$sScaleBarUrl\">
		</td>
	</tr>
	<tr>
		<td colspan=\"2\"><img src=\"$sMapUrl\"></img></td>
	</tr>
	<tr>
			<td colspan=\"2\" align=\"center\"><font size=\"".($char_dim+2)." pt\"><b>Legenda</b></font></td>
	</tr>
	<tr>
		<td colspan=\"2\">
			$legend_tab
		</td>
	</tr>";
	}
	return "<table cellspacing=\"2\" cellpadding=\"0\" border=0>
	$tab
</table>
<!--NewPage-->\n";
}
function get_first_page_legend($legenda,$char_dim){
	$j=0;
	for($i=0;$i<count($legenda);$i++){
		$elem=$legenda[$i];
		if ($elem["page"]){
			$num=FIRST_PAGE_COLS;
			if($elem["title"]){
				$j=0;
				if($c){
					$rows[]="\t<tr>".implode("",$c)."</tr>\n";
					$c=Array();
				}
				$title=$elem["label"];
				$rows[]="\n\t<tr>
		<td colspan=\"$num\" align=\"center\">".$elem["label"]."</td>
	</tr>";
			}
			else{
				$j++;
				if($j>$num){
					$j=1;
					$rows[]="\t<tr>".implode("",$c)."</tr>\n";
					$c=Array();
				}
				$c[]="
				<td  valign=\"top\" width=\"".((int)(100/$num))."%\">".$elem["label"]."</td>\n";
			}
			
		}
	}
	if($c){
		$rows[]="\t<tr>\n".implode("",$c)."\n</tr>\n";
	}
	return "<table width=\"100%\" border=\"0\">\n".implode("",$rows)."</table>\n";
}
function get_legend($legenda,$char_dim){
	$j=0;
	for($i=0;$i<count($legenda);$i++){
		$elem=$legenda[$i];
		if (!$elem["page"]){
			$num=COLS_NUM;
			if($elem["title"]){
				$j=0;
				if($c){
					$rows[]="\t<tr>".implode("",$c)."</tr>\n";
					$c=Array();
				}
				$title=$elem["label"];
				$rows[]="\n\t<tr>
		<td colspan=\"$num\" align=\"center\">".$elem["label"]."</td>
	</tr>";
			}
			else{
				$j++;
				
				if($j>$num){
					$j=1;
					$rows[]="\t<tr>".implode("",$c)."</tr>\n";
					$c=Array();
				}
				$c[]="
				<td  valign=\"top\" width=\"".((int)(100/$num))."%\">".$elem["label"]."</td>\n";
			}
		}
		
	}
	if($c){
		$rows[]="\t<tr>\n".implode("",$c)."\n</tr>\n";
	}
	return "<table width=\"99%\" cellspacing=\"2\" cellpadding=\"0\" border=0>\n".implode("",$rows)."</table>\n";
}
?>
