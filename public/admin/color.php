<html> 
<head> 
<title>Table Color</title> 
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"> 
<style type="text/css"> 
td { 
font-size: 9px;; 
} 
.color{
	cursor:hand;
	cursor:pointer;
}
</style>
<script  type="text/javascript" src="./js/Author.js"></script>
<script>
	function set_color(col){
		$('capt_color').innerHTML=col;
		$('color').style.backgroundColor=col;
	}
</script>
</head>
<body>
<?php
for($col_r=0;$col_r<256;$col_r+=51){
	for($col_g=0;$col_g<256;$col_g+=51){
		for($col_b=0;$col_b<256;$col_b+=51){
			$i++;
			$red = strtoupper(dechex($col_r)); 
			$green = strtoupper(dechex($col_g)); 
			$blue = strtoupper(dechex($col_b)); 
			$color = str_pad($red, 2, '0', STR_PAD_LEFT)."".str_pad($green, 2, '0', STR_PAD_LEFT)."".str_pad($blue, 2, '0', STR_PAD_LEFT); 
			$cell[]="<td bgcolor=\"#$color\" class='color' onclick=\"set_color('#$color')\"  >&nbsp;</td>";
			if ((($i % 27)==0) && ($i>1)){
				$row[]=implode("\n\t\t",$cell);
				$cell=Array();
			}
		}
	}
}
$cell=Array();
for($col=16;$col<256;$col+=9){
	$red = strtoupper(dechex($col)); 
	$green = strtoupper(dechex($col)); 
	$blue = strtoupper(dechex($col)); 
	$color = str_pad($red, 2, '0', STR_PAD_LEFT)."".str_pad($green, 2, '0', STR_PAD_LEFT)."".str_pad($blue, 2, '0', STR_PAD_LEFT); 
	$cell[]="<td bgcolor=\"#$color\" class='color' onclick=\"set_color('#$color')\"  width=\"10\">&nbsp;</td>";
}
$row[]=implode("\n\t\t",$cell);
echo "<table border='0'>\n\t<tr height=\"10\">\n\t\t".implode("</tr>\n\t<tr height=\"10\">",$row)."\n\t</tr>\n</table>";
?>
<table width="100%">
	<caption><div style="text-align:center;font-weight:bold;" id="capt_color"></div></caption>
	<tr>
		<td id="color">&nbsp;</td>
	</tr>
</table>
</body> 
</html>