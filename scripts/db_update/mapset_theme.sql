insert into gisclient_3.e_level(id,name,parent_name,"order",parent_id,depth,leaf,export,struct_parent_id,"table",admintype_id) values (23,'mapset_theme','mapset_theme',18,8,2,1,1,8,'mapset_theme',2);
insert into gisclient_3.e_form(id,name,config_file,tab_type,level_destination,save_data,parent_level) values (88,'map_theme','mapset_theme',5,23,'mapset_theme',8);
insert into gisclient_3.e_form(id,name,config_file,tab_type,level_destination,save_data,parent_level) values (87,'map_theme','mapset_theme',4,23,'mapset_theme',8);
insert into gisclient_3.e_form(id,name,config_file,tab_type,level_destination,save_data,parent_level) values (89,'map_theme','mapset_theme',0,23,'mapset_theme',8);
insert into gisclient_3.form_level (id,level,mode,form,order_fld,visible) values (81,23,1,88,1,1);
insert into gisclient_3.form_level (id,level,mode,form,order_fld,visible) values (82,23,-1,89,2,1);
CREATE TABLE gisclient_3.mapset_theme
(
  mapset_name character varying NOT NULL,
  theme_id integer NOT NULL,
  rootpath character varying,
  mapset_theme_order integer,
  CONSTRAINT mapset_theme_pkey PRIMARY KEY (mapset_name, theme_id),
  CONSTRAINT mapset_theme_mapset_name_fkey FOREIGN KEY (mapset_name)
      REFERENCES gisclient_3.mapset (mapset_name) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT mapset_theme_theme_id_fkey FOREIGN KEY (theme_id)
      REFERENCES gisclient_3.theme (theme_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
);
