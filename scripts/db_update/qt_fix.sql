delete from gisclient_3.e_level where id=19;
update gisclient_3.e_level set parent_id=5, depth=2,struct_parent_id=5 where id=18;
update gisclient_3.e_level set depth=3 where id in(53,54);
alter table gisclient_3.qt ADD CONSTRAINT qt_theme_id_fkey FOREIGN KEY (theme_id)
      REFERENCES gisclient_3.theme (theme_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE;
