<!-- ### STANDARD  PAGE  HEADER  INIZIO ##################################################### -->
			<div id="intestazione">
				<div id="gisClientAuthorLogo">
					<b class="shadow">GisClient</b><strong class="color">Author</strong>
					
				</div>
				<div id="clientLogo" class="shadow">
					<?php if(defined('CLIENT_LOGO') && CLIENT_LOGO != null) { ?>
						<img src="<?php echo CLIENT_LOGO ?>" height="60">
					<?php } else { ?>
						Logo Cliente
					<?php } ?>
				</div>
				<div id="topMenu">
					<?php if ($user->isAuthenticated()) { ?>
						<?php if(!empty($isAuthor)) { ?>
						<a class="button" href="../">Home</a>
						<a class="button" data-action="data_manager" style="display:none;">Data manager</a>
						<a class="button" data-action="preview_map" style="display:none;">Preview Map</a>
						<a class="button" data-action="options">Options</a>
						<a class="button" data-action="ogc_services" style="display:none;"><?php echo GCAuthor::t('ogc_services'); ?></a>
                        <?php
                        if(!empty($p->parametri['project'])) {
                            echo '<a class="button" data-action="mapfiles_manager">'.GCAuthor::t('online_maps').'</a>';
                            if(defined('MAPPROXY') && MAPPROXY) {
                                echo ' <a class="button" data-action="cache_manager">Cache</a>';
                            }
                        }
                        ?>
						<?php } else { ?>
						<a class="button" href="admin/">Author</a>
						<?php } ?>
						<a class="logout" href="#" onclick="javascript:logout()">LogOut</a>
					<?php } ?>
				</div>
			</div>
			<div id="list_dialog" style="display:none;"><table></table></div>
			<div id="copy_dialog" style="display:none;"></div>
			<div id="preview_map_dialog" style="display:none;"></div>
			<div id="options_dialog" style="display:none;">
				<form id="user_options">
				<input type="checkbox" name="save_to_tmp_map" <?php if(isset($_SESSION['save_to_tmp_map']) && $_SESSION['save_to_tmp_map']) echo 'checked="checked"'; ?> value="1"> <?php echo GCAuthor::t('save_to_temp') ?><br />
				<input type="checkbox" name="auto_refresh_mapfiles" <?php if(isset($_SESSION['auto_refresh_mapfiles']) && $_SESSION['auto_refresh_mapfiles']) echo 'checked="checked"'; ?> value="1"> <?php echo GCAuthor::t('auto_refresh_mapfiles') ?><br />
				<button name="save"><?php echo GCAuthor::t('save'); ?></button>
				<div class="logs" style="color:red;"></div>
				</form>
			</div>
			<div id="ogc_services_getcapabilities" style="display:none;" data-title="<?php echo GCAuthor::t('ogc_services'); ?>">
				<table border="1" cellpadding="3" class="stiletabella">
					<tr class="ui-widget ui-state-default"><th>Mapset</th><th>WMS</th><th>WFS</th></tr>
					<?php
					if(isset($mapsets)) {
						foreach($mapsets as $mapset) {
							echo '<tr>
								<td>'.$mapset['mapset_title'].' ('.$mapset['mapset_name'].')</td>
								<td><a href="../services/ows.php?project='.$mapset['project_name'].'&map='.$mapset['mapset_name'].'&request=getcapabilities&service=WMS&version=1.1.1" data-action="getcapabilities" target="_blank">WMS GetCapabilities</a></td>
								<td><a href="../services/ows.php?project='.$mapset['project_name'].'&map='.$mapset['mapset_name'].'&request=getcapabilities&service=WFS" data-action="getcapabilities" target="_blank">WFS GetCapabilities</a></td>
							</tr>';
						}
					}
					?>
				</table>
				<?php if(defined('TINYOWS_PATH')) { ?>
				<br><br>
				<table border="1" cellpadding="3" class="stiletabella">
				<tr class="ui-widget ui-state-default"><th><?php echo GCAuthor::t('theme'); ?></th><th><?php echo GCAuthor::t('layergroup'); ?></th><th><?php echo GCAuthor::t('layer'); ?></th><th>FeatureType</th><th>WFS-T</th></tr>
				<?php
					if(isset($towsFeatures)) {
						foreach($towsFeatures as $towsf) {
							echo '<tr>
								<td>'.$towsf['theme_title'].'</td>
								<td>'.$towsf['layergroup_title'].'</td>
								<td>'.$towsf['layer_title'].'</td>
								<td>'.$towsf['feature_type'].'</td>
								<td><a href="'.TINYOWS_ONLINE_RESOURCE.$towsf['project_name'].'/'.$towsf['feature_type'].'/?service=wfs&request=getcapabilities" data-action="getcapabilities" target="_blank">WFS-T</A></td>
							</tr>';
						}
					}
				?>
				</table>
				<?php } ?>
			</div>
            <div id="cache_manager" style="display:none;">
                <?php if(!empty($p->parametri['project']) && defined('MAPPROXY') && MAPPROXY) { ?>
				<table border="1" cellpadding="3" class="stiletabella">
				<tr role="header" class="ui-widget ui-state-default">
					<th>Cache</th>
					<th><?php echo GCauthor::t('empty') ?>:</th>
				</tr>
                </table>
                <?php } ?>
            </div>

			<div id="mapfiles_manager" style="display:none;" data-title="<?php echo GCAuthor::t('online_maps') ?>">
                <?php if(!empty($p->parametri['project'])) { ?>
                <!--<a href="#" data-action="refresh" data-projectmap="1" data-target="tmp" data-project="<?= $p->parametri['project'] ?>"><?= GCAuthor::t('update') ?></a>TMP | <a href="#" data-action="refresh" data-projectmap="1" data-target="public" data-project="<?= $p->parametri['project'] ?>"><?= GCAuthor::t('update') ?></a>PUBLIC<br>-->
                
				<table border="1" cellpadding="3" class="stiletabella">
				<tr class="ui-widget ui-state-default">
					<th>Mapset</th>
					<th><?php echo GCauthor::t('update') ?>:</th>
					<th><?php echo GCAuthor::t('temporary') ?></th>
					<th><?php echo GCAuthor::t('public') ?></th>
				</tr>

				<?php if(defined('PROJECT_MAPFILE') && PROJECT_MAPFILE){?>
				<tr><td><b><?php echo GCAuthor::t('project') ?></b></td><td></td><td style="text-align:center;"><a href="#" data-action="refresh" data-projectmap="1" data-target="tmp" data-project="<?= $p->parametri['project'] ?>"><?php echo GCAuthor::t('update') ?></a></td><td style="text-align:center;"><a href="#" data-action="refresh" data-projectmap="1" data-target="public" data-project="<?= $p->parametri['project'] ?>"><?php echo GCAuthor::t('update'); ?></a></td></tr>
                <?php 
				if(isset($mapsets)) {
					foreach($mapsets as $mapset) {
						echo '<tr>
							<td>'.$mapset['mapset_title'].' ('.$mapset['mapset_name'].')</td>
							<td></td>
							<td style="text-align:center;"><a data-action="view_map" href="'.$mapset['url'].'&tmp=1" target="_blank">Map</a></td>
							<td style="text-align:center;"><a data-action="view_map" href="'.$mapset['url'].'" target="_blank">Map</a></td>
						</tr>';
					}
				}
                }else{ 
				if(isset($mapsets)) {
					foreach($mapsets as $mapset) {
						echo '<tr>
							<td>'.$mapset['mapset_title'].' ('.$mapset['mapset_name'].')</td>
							<td></td>
							<td style="text-align:center;"><a data-action="view_map" href="'.$mapset['url'].'&tmp=1" target="_blank">Map</a><a href="#" data-action="refresh" data-target="tmp" data-mapset="'.$mapset['mapset_name'].'">'.GCAuthor::t('update').'</a></td>
							<td style="text-align:center;"><a data-action="view_map" href="'.$mapset['url'].'" target="_blank">Map</a><a href="#" data-action="refresh" data-target="public" data-mapset="'.$mapset['mapset_name'].'">'.GCAuthor::t('update').'</a></td>
						</tr>';
					}
				}
				?>
				<tr></tr>
				<tr><td><b><?php echo GCAuthor::t('all') ?></b></td><td></td><td style="text-align:center;"><a href="#" data-action="refresh" data-target="tmp" data-mapset=""><?php echo GCAuthor::t('update') ?></a></td><td style="text-align:center;"><a href="#" data-action="refresh" data-target="public" data-mapset=""><?php echo GCAuthor::t('update'); ?></a></td></tr>
				</table>
                
                <?php }} ?>
			</div>

			<div id="import_dialog" style="display:none;">
				<div id="import_dialog_tabs">
					<ul>
						<li><a href="#import_dialog_shp">SHP</a></li>
						<li><a href="#import_dialog_raster">Raster</a></li>
						<li><a href="#import_dialog_postgis">PostgreSQL</a></li>
						<li><a href="#import_dialog_xls">XLS</a></li>
						<li><a href="#import_dialog_csv">CSV</a></li>
					</ul>
					<div id="import_dialog_shp">
						<input id="shp_file_upload" name="file_upload" type="file" />
						<div data-role="file_list">
						</div>
						<hr>
						File: <input type="text" name="shp_file_name" disabled="disabled"><br />
						Charset: <select name="shp_file_charset"><option value="UTF-8">UTF-8</option><option value="LATIN1">LATIN1</option></select><br />
						Method: <input type="radio" name="shp_insert_method" value="create" checked>Create <input type="radio" name="shp_insert_method" value="append">Append <input type="radio" name="shp_insert_method" value="replace">Replace <br />
						Tablename: <select name="shp_table_name_select" style="display:none;"></select><input type="text" name="shp_table_name"><br />
						SRID: <input type="text" name="shp_srid"><br />
						<button name="import" style="display;none">Import</button>
					</div>
					<div id="import_dialog_raster">
						Directory: <input type="text" name="dir_name"><br />
						<input id="raster_file_upload" name="file_upload" type="file" />
						<div data-role="file_list">
						</div>
						<hr>
						Directory: <input type="text" name="raster_file_name" disabled="disabled"><br />
						SRID: <input type="text" name="raster_srid"><br />
						Tablename: <input type="text" name="raster_table_name"><br />
						<button name="tileindex" style="display;none">Tileindex</button>
					</div>
					<div id="import_dialog_postgis">
						<div data-role="table_list">
						</div>
						<hr>
						Tablename: <input type="text" name="postgis_table_name"> <br />
						SRID: <input type="text" name="postgis_table_srid"><br />
						Geometry type: <select name="postgis_geometry_type">
									<option value="POINT">POINT</option>
									<option value="MULTIPOINT">MULTIPOINT</option>
									<option value="LINESTRING">LINESTRING</option>
									<option value="MULTILINESTRING">MULTILINESTRING</option>
									<option value="POLYGON">POLYGON</option>
									<option value="MULTIPOLYGON">MULTIPOLYGON</option>
							</select><br />
						Coordinate dimension: <select name="coordinate_dimension"><option value="2">2</option><option value="3">3</option><option value="4">4</option></select><br />
						<table data-role="columns">
						<caption>Fields <a href="#" data-action="add_column">+</a></caption>
						<tr><th>Field name</th><th>Field type</th></tr>
						</table>
						<input type="hidden" name="num_columns" value="0">
						<button name="create_table">Create</button>
					</div>
					<div id="import_dialog_xls">
						<input id="xls_file_upload" name="file_upload" type="file" />
						<div data-role="file_list">
						</div>
						<hr>
						File: <input type="text" name="xls_file_name" disabled="disabled"><br />
						Method: <input type="radio" name="xls_insert_method" value="create" checked>Create <input type="radio" name="xls_insert_method" value="append">Append <input type="radio" name="xls_insert_method" value="replace">Replace <br />
						Tablename: <select name="xls_table_name_select" style="display:none;"></select><input type="text" name="xls_table_name"><br />
						<button name="import" style="display;none">Import</button>
					</div>
					<div id="import_dialog_csv">
						<input id="csv_file_upload" name="file_upload" type="file" />
						<div data-role="file_list">
						</div>
						<hr>
						File: <input type="text" name="csv_file_name" disabled="disabled"><br />
						Method: <input type="radio" name="csv_insert_method" value="create" checked>Create <input type="radio" name="csv_insert_method" value="append">Append <input type="radio" name="csv_insert_method" value="replace">Replace <br />
						Tablename: <select name="csv_table_name_select" style="display:none;"></select><input type="text" name="csv_table_name"><br />
						<button name="import" style="display;none">Import</button>
					</div>
				</div>
				<div class="logs" style="color:red;" tabindex="100">
				</div>
				<div class="loading" style="display:none;">
					<img src="../images/ajax_loading.gif">
				</div>
			</div>
		<!-- ### STANDARD  PAGE  HEADER  FINE ##################################################### -->
