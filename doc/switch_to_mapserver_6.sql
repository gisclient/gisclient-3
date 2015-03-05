-- MAPSERVER 6 --
SET search_path = gisclient_32, pg_catalog;

-- ESEGUIRE L'INSERT DEI PATTERN E DEI SIMBOLI MS6

ALTER TABLE style DROP CONSTRAINT pattern_id_fkey;

TRUNCATE e_symbolcategory CASCADE ;
TRUNCATE symbol CASCADE ;
TRUNCATE font CASCADE ;
TRUNCATE e_pattern CASCADE;

-- POPOLA LA CATEGORIA DI SIMBOLI
INSERT INTO e_symbolcategory VALUES (1, 'MapServer', NULL);
INSERT INTO e_symbolcategory VALUES (2, 'Line', NULL);
INSERT INTO e_symbolcategory VALUES (3, 'Campiture', NULL);
INSERT INTO e_symbolcategory VALUES (4, 'Marker', NULL);
INSERT INTO e_symbolcategory VALUES (5, 'CatastoCML', NULL);
INSERT INTO e_symbolcategory VALUES (7, 'Numeri e lettere', NULL);
--- SW
INSERT INTO e_symbolcategory VALUES (6, 'TechNET', NULL);
INSERT INTO e_symbolcategory VALUES (8, 'COSAP', NULL);
INSERT INTO e_symbolcategory VALUES (9, 'SkiGIS', NULL);
INSERT INTO e_symbolcategory VALUES (10, 'SIGNS', NULL);
--INSERT INTO e_symbolcategory VALUES (11, 'VEHICLES', NULL);
INSERT INTO e_symbolcategory VALUES (12, 'R3-Ambiente', NULL);
-- CLIENTI
INSERT INTO e_symbolcategory VALUES (50, 'Sentieri CMSO', NULL);
INSERT INTO e_symbolcategory VALUES (60, 'P.E.I. CMVerbano', NULL);
INSERT INTO e_symbolcategory VALUES (70, 'PRG TSI Mori', NULL);

-- POPOLA I PATTERN E AGGIORNA GLI STILI
INSERT INTO e_pattern VALUES(0,'NO PATTERN','#PATTERN END',0);
INSERT INTO e_pattern VALUES(1,'1-3','PATTERN 1 3 END',1);
INSERT INTO e_pattern VALUES(2,'2-3','PATTERN 2 3 END',2);
INSERT INTO e_pattern VALUES(3,'3-3','PATTERN 3 3 END',3);
INSERT INTO e_pattern VALUES(4,'5-5','PATTERN 5 5 END',4);
INSERT INTO e_pattern VALUES(5,'10-10','PATTERN 10 10 END',5);
INSERT INTO e_pattern VALUES(6,'10-3','PATTERN 10 3 END',6);
INSERT INTO e_pattern VALUES(7,'3-10','PATTERN 3 10 END',7);
INSERT INTO e_pattern VALUES(8,'5-3-1-3','PATTERN 5 3 1 3 END',8);
INSERT INTO e_pattern VALUES(9,'5-3-1-3-1-3','PATTERN 5 3 1 3 1 3 END',9);
INSERT INTO e_pattern VALUES(10,'5-3-5-3-1-3','PATTERN 5 3 5 3 1 3 END',10);
INSERT INTO e_pattern VALUES(11,'1-2-1-6','PATTERN 1 2 1 6 END',11);


-- POPOLA I FONT

INSERT INTO font VALUES ('dejavu-sans', 'dejavu-sans.ttf');
INSERT INTO font VALUES ('dejavu-sans-bold', 'dejavu-sans-bold.ttf');
INSERT INTO font VALUES ('dejavu-sans-bold-italic', 'dejavu-sans-bold-italic.ttf');
INSERT INTO font VALUES ('dejavu-serif', 'dejavu-serif.ttf');
INSERT INTO font VALUES ('dejavu-serif-bold', 'dejavu-serif-bold');
INSERT INTO font VALUES ('dejavu-serif-bold-italic', 'dejavu-serif-bold-italic.ttf');
INSERT INTO font VALUES ('dejavu-serif-italic', 'dejavu-serif-italic.ttf');
INSERT INTO font VALUES ('dejavu-sans-italic', 'dejavu-sans-italic.ttf');
--INSERT INTO font VALUES ('tpa', 'tpa.ttf');
--INSERT INTO font VALUES ('tpe', 'tpe.ttf');
--INSERT INTO font VALUES ('tpg', 'tpg.ttf');
--INSERT INTO font VALUES ('tpo', 'tpo.ttf');
--INSERT INTO font VALUES ('tps', 'tps.ttf');
--INSERT INTO font VALUES ('tptc', 'tptc.ttf');
--INSERT INTO font VALUES ('tpts', 'tpts.ttf');
--INSERT INTO font VALUES ('tptlr', 'tptlr.ttf');
--INSERT INTO font VALUES ('cosap', 'cosap.ttf');



TRUNCATE symbol_ttf;




INSERT INTO symbol VALUES ('AIR', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#033;"');
INSERT INTO symbol VALUES ('VIGNETO', 3, 0, NULL, 'Type VECTOR
  Filled TRUE
  Points
		.8 .6
		.4 .6
		.6 0
		.4 0
		.2 .6
		.4 .8
		.6 .8
		.8 .6
  END
		');
INSERT INTO symbol VALUES ('TENT', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
.5 0
1 1
.75 1
.5 .5
.25 1
0 1
END');
INSERT INTO symbol VALUES ('STAR', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 .375
.35 .375
.5 0
.65 .375
1 .375
.75 .625
.875 1
.5 .75
.125 1
.25 .625
END');
INSERT INTO symbol VALUES ('TRIANGLE', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
.5 0
1 1
0 1
END');
INSERT INTO symbol VALUES ('SQUARE', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
0 0
1 0
1 1
0 1
END');
INSERT INTO symbol VALUES ('PLUS', 1, 0, NULL, 'TYPE VECTOR
POINTS
.5 0
.5 1
-99 -99
0 .5
1 .5
END');
INSERT INTO symbol VALUES ('CROSS', 1, 0, NULL, 'TYPE VECTOR
POINTS
0 0
1 1
-99 -99
0 1
1 0
END');
INSERT INTO symbol VALUES ('VIVAIO', 3, 0, NULL, 'TYPE Vector
  POINTS
		.3 1
		.7 1
		.9 .1
		.1 .1
		.3 1
		-99 -99
		.2 .2
		.2 .1
		-99 -99
		.5 .2
		.5 .1
		-99 -99
		.7 .2
		.7 .1
  END
	');
INSERT INTO symbol VALUES ('CIRCLE', 1, 0, NULL, 'TYPE ELLIPSE
FILLED TRUE
POINTS
1 1
END');
INSERT INTO symbol VALUES ('WATER', 3, 0, NULL, 'Type VECTOR
  Filled FALSE  
   Points
		0 .6
		.1 .4
		.2 .4
		.3 .6
		.4 .6
		.5 .4
		.6 .4
		.7 .6
		.8 .6
		.9 .4
		1 .4
		1.1 .6
  END');
INSERT INTO symbol VALUES ('CIRCLE_EMPTY', 3, 0, NULL, 'TYPE Vector
  POINTS
    0 .5
		.1 .7
		.3 .9
		.5 1
		.7 .9
		.9 .7
		1 .5
		.9 .3
		.7 .1
		.5 0
		.3 .1
		.1 .3
		0 .5
  END
	');
    INSERT INTO symbol VALUES ('CIRCLE_HALF', 3, 0, NULL, 'TYPE Vector
  POINTS
    0 .5
		.1 .7
		.3 .9
		.5 1
		.7 .9
		.9 .7
		1 .5
		0 .5
  END

	');
INSERT INTO symbol VALUES ('HATCH', 3, 0, NULL, 'TYPE HATCH');
INSERT INTO symbol VALUES ('10-3', 2, 0, NULL, ' Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		10 3
	END');
INSERT INTO symbol VALUES ('1-3', 2, 0, NULL, 'Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		1 3
	END');
INSERT INTO symbol VALUES ('2-3', 2, 0, NULL, 'Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		2 3
	END');
INSERT INTO symbol VALUES ('3-10', 2, 0, NULL, '  Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		3 10
	END');
INSERT INTO symbol VALUES ('3-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    3 3
  END ');
  INSERT INTO symbol VALUES ('5-5', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 5
  END ');
  INSERT INTO symbol VALUES ('10-10', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    10 10
  END ');
INSERT INTO symbol VALUES ('5-3-1-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 1 3
  END ');
INSERT INTO symbol VALUES ('5-3-1-3-1-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 1 3 1 3
  END ');
INSERT INTO symbol VALUES ('5-3-5-3-1-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 5 3 1 3
  END ');
INSERT INTO symbol VALUES ('BOSCO', 3, 0, NULL, 'TYPE Vector
  POINTS
    .5 1
    .5 0
		-99 -99
		.5 0
		.3 .1 
		-99 -99
		.5 .0
		.7 .1
		-99 -99
		.5 .3
		.2 .4
		-99 -99
		.5 .3
		.8 .4
		-99 -99
		.5 .6
		.1 .8
		-99 -99
		.5 .6
		.9 .8
  END
	');
INSERT INTO symbol VALUES ('CIMITERO', 3, 0, NULL, 'TYPE VECTOR
POINTS
.5 0
.5 1
-99 -99
.2 .3
.8 .3
END
');
INSERT INTO symbol VALUES ('FAUNA', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#035;"');
INSERT INTO symbol VALUES ('FLORA', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#036;"');
INSERT INTO symbol VALUES ('FRUTTETO', 3, 0, NULL, 'Type VECTOR
  Filled TRUE
  Points
		.2 1
		.2 .8
		.4 .8 
		.4 .4
		0 0
		.2 0
		.4 .2
		.4 0
		.6 0
		.6 .2
		.8 0
		1 0
		.6 .4
		.6 .8
		.8 .8
		.8 1
		.2 1
  END
		');
INSERT INTO symbol VALUES ('INCOLTO', 3, 0, NULL, 'Type VECTOR
  Filled TRUE
  Points
	0 1
	.2 .6
	.35 .85
	.5 .6
	.65 .85
	.8 .6 
	1 1
	.9 1
	.8 .8
	.7 1
	.6 1
	.5 .8
	.4 1
	.3 1
	.2 .8
	.1 1
	0 1
  END
		');
INSERT INTO symbol VALUES ('NOISE', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#037;"');
INSERT INTO symbol VALUES ('NOISE_INSP', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#038;"');
INSERT INTO symbol VALUES ('PASCOLO', 3, 0, NULL, '  Type VECTOR
  Filled TRUE
  Points
    0 .4
		.2 1
		.4 1
		.2 .4
		0 .4
		-99 -99
		.4 0
		.6 0 
		.6 1
		.4 1
		.4 0 
	   -99 -99
		 .8 .4
		 1 .4
		 .8 1
		 .6 1
		 .8 .4	
  END
		');
INSERT INTO symbol VALUES ('RANDOM', 3, 0, NULL, '  Type VECTOR
  Filled TRUE
  Points
    .1 .1
		.3 .3
  -99 -99
		.5 .2
		.7 0
  -99 -99
		.9 .2
  -99 -99
		.7 .3
  -99 -99
		.1 .5		
  -99 -99
		.6 .5
		.4 .7
  -99 -99
		.3 .8
  -99 -99
		.8 .7
  -99 -99
		.1 .9
  -99 -99
		.6 .8
		.6 1
  END
		');
INSERT INTO symbol VALUES ('RISAIA', 3, 0, NULL, 'Type VECTOR
  Filled TRUE
  Points
		0 1
		0 .4
		.2 .4
		.2 1
		0 1
		-99 -99
		.4 1
		.4 0
		.6 0
		.6 1
		.4 1
		-99 -99 
		.8 1
		.8 .4
		1 .4
		1 1
		.8 1 
  END
		');
INSERT INTO symbol VALUES ('RUPESTRE', 3, 0, NULL, '  Type VECTOR
  Filled TRUE
  Points
    .2 .8
    .35 .6
    .65 .6
    .8 .8
   -99 -99
    0 .6
    .15 .45
    .35 .45
    .5 .6
    .65 .45
    .85 .45
    1 .6		
  END
		');
INSERT INTO symbol VALUES ('ARROW', 2, 0, NULL, 'TYPE Vector
	FILLED True
	POINTS
	  0 0
		.5 .5
		0 1
		0 0
	END');
INSERT INTO symbol VALUES ('ARROWBACK', 2, 0, NULL, '	TYPE Vector
	FILLED True
	POINTS
	  1 1
		.5 .5
		1 0
		1 1
	END');
INSERT INTO symbol VALUES ('CIRCLE_FILL', 3, 0, NULL, 'TYPE ELLIPSE
FILLED TRUE
POINTS
1 1
END
	');
INSERT INTO symbol VALUES ('WARNING', 1, 4, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#033;"');
INSERT INTO symbol VALUES ('SQUARE_EMPTY', 3, 0, NULL, 'Type VECTOR
  Points
	.1 .1
	.1 .9
	.9 .9
	.9 .1
	.1 .1
  END');
INSERT INTO symbol VALUES ('1-2-1-6', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    1 2 1 6
  END ');
INSERT INTO symbol VALUES ('TRIANGLE_EMPTY', 3, 0, NULL, 'Type VECTOR
  Points
	.1 .1
	.9 .1
	.9 .1
	.5 .9
	.1 .1
  END');
INSERT INTO symbol VALUES ('PLUS_FILL', 3, 0, NULL, 'TYPE VECTOR
POINTS
    .1 .3
    .5 .3
    -99 -99
    .3 .1
    .3 .5
    -99 -99
    .5 .7
    .9 .7
    -99 -99
    .7 .5
    .7 .9
END');
INSERT INTO symbol VALUES ('SNOW', 3, 0, NULL,  'Type VECTOR
  Points
	0 .5
	1 .5
	-99 -99
	.2 0
	.8 1
	-99 -99
	.8 0
	.2 1
  END
		');
INSERT INTO symbol VALUES ('HEXAGON_EMPTY', 3, 0, NULL, 'Type VECTOR
  Points
	.3 .1
	.8 .1
	1 .5
	.8 .9
	.3 .9
	.1 .5
	.3 .1
  END');
INSERT INTO symbol VALUES ('HEXAGON_BEE', 3, 0, NULL, 'Type VECTOR
  Points
	.1 0
	.2 .2
	.1 .4
	0 .4
	-99 -99
	.2 .2
	.4 .2
	-99 -99
	.5 0
	.4 .2
	.5 .4
	.6 .4
  END
');
INSERT INTO symbol VALUES ('ICE', 3, 0, NULL, 'Type VECTOR
  Points
	0 .5
    .5 1
	-99 -99
	0 0
    1 .5
	-99 -99
	.5 0
    0 1
    -99 -99
    .5 0
    .5 1
    -99 -99
    0 0
    0 .5
  END
');
INSERT INTO symbol VALUES ('HALF_SQUARE', 3, 0, NULL, 'Type VECTOR
  Points
	.2 1.8
	1.8 1.8
	1.8 .2
  END');
INSERT INTO symbol VALUES ('DASH_DASH', 3, 0, NULL, 'Type VECTOR
  Points
	0 .9 
	.3 .9
	-99 -99
	.7 .9
	1 .9
	-99 -99
	.2 .4 
	.8 .4
  END
');
INSERT INTO symbol VALUES ('DASH_DASH_VERTICAL', 3, 0, NULL, 'Type VECTOR
  Points
	.9 0 
	.9 .3 
	-99 -99
	.9 .7 
	.9 1 
	-99 -99
	.4 .2 
	.4 .8 
  END
');
INSERT INTO symbol VALUES ('DASH_LINE', 3, 0, NULL, 'Type VECTOR
  Points
	0 .9 
	1 .9
	-99 -99
	.2 .4 
	.8 .4
  END
');
INSERT INTO symbol VALUES ('STREAMERS', 3, 0, NULL, 'Type VECTOR
  Points
	.1 .1
    .4 .1
	-99 -99
	.9 .1
    .6 .4
	-99 -99
	.1 .6 
    .1 .9 
    -99 -99
	.4 .6
    .7 .9
  END
');
INSERT INTO symbol VALUES ('POINT_LINE_VERTICAL', 3, 0, NULL, 'Type VECTOR
  Points
	.9 0  
	.9 1 
	-99 -99
	 .4 .4
	 .4 .6
  END
');
INSERT INTO symbol VALUES ('DOUBLE_LINE_VERTICAL', 3, 0, NULL, 'Type VECTOR
  Points
    .0 0  
	.0 1 
	-99 -99
	.3 0  
	.3 1 
	-99 -99
	1 0  
	1 1 
  END
');
INSERT INTO symbol VALUES ('ARROW2', 2, 0, NULL, 'TYPE Vector
	POINTS
	  0 0
		.5 .5
		0 1
	END');
INSERT INTO symbol VALUES ('ARROWBACK2', 2, 0, NULL, '	TYPE Vector
	POINTS
	  1 1
		.5 .5
		1 0
	END');
 INSERT INTO symbol VALUES ('ARROW3', 2, 0, NULL, 'TYPE VECTOR
 POINTS
0 .5
1 .5
-99 -99
.8 .7
1 .5
.8 .3
END');
INSERT INTO symbol VALUES ('HOURGLASS', 2, 0, NULL, 'TYPE Vector
FILLED TRUE
	POINTS
	 0 0
	 0 1
	 1 0
	 1 1
	 0 0
	END
	ANTIALIAS true
	');
INSERT INTO symbol VALUES ('SQUARE_FILL', 3, 0, NULL, 'Type VECTOR
FILLED TRUE
  Points
	.1 .1
	.1 .9
	.9 .9
	.9 .1
	.1 .1
  END
		');
INSERT INTO symbol VALUES ('RUBINETTO', 3, 0, NULL, 'TYPE TRUETYPE
FONT "symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"');
INSERT INTO symbol VALUES ('RIPARIE-CANNETO', 3, 0, NULL, 'TYPE VECTOR
POINTS
.3 0
.3 1
.7 1
END
 ');
 INSERT INTO symbol VALUES ('VERTEX', 3, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
	1 8
	3 8
	3 9
	1 9
	1 8
-99 -99
	7 8
	9 8
	9 9
	7 9
	7 8
-99 -99
	4 1
	6 1
	6 2
	4 2
	4 1
END');
 INSERT INTO symbol VALUES ('T', 3, 0, NULL, 'TYPE VECTOR
POINTS
.5 .5
.5 1	
-99 -99
0 .5
1 .5
END');
 INSERT INTO symbol VALUES ('DOUBLE_T', 3, 0, NULL, 'TYPE VECTOR
POINTS
.3 .5
.3 1	
-99 -99
.7 .5
.7 1	
-99 -99
0 .5
1 .5
END');
 INSERT INTO symbol VALUES ('D', 3, 0, NULL, 'TYPE VECTOR
 FILLED TRUE
POINTS
.5 0
.5 1
.3 .9
.1 .7
0 .5
.1 .3
.3 .1
.5 0
END');
 INSERT INTO symbol VALUES ('MONUMENTO', 3, 0, NULL, 'TYPE VECTOR
POINTS
.5 1
.2 .3
.2 .2
.4 0
.6 0
.6 .2
.6 .3
.5 1
END');
 INSERT INTO symbol VALUES ('VERTICAL', 4, 0, NULL, 'TYPE VECTOR
POINTS
.5 0
.5 1
END');
 INSERT INTO symbol VALUES ('HORIZONTAL', 4, 0, NULL, 'TYPE VECTOR
POINTS
0 .5
1 .5
END');
 
INSERT INTO symbol VALUES ('SQUARE_HALF', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 0
0 1
1 0
0 0
END');
INSERT INTO symbol VALUES ('IDRANTE', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
1 1
-99 -99
.2 1
.2 .4
.8 .4
.8 1
.2 1
-99 -99
.2 .8
0 .8
0 .6
.2 .6
-99 -99
.8 .8
1 .8
1 .6
.8 .6
-99 -99
0 .4
1 .4
.9 .2
.7 0
.3 0
.1 .2
0 .4
END');
INSERT INTO symbol VALUES ('PHOTO', 4, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/photo.png"');


-- Catasto
INSERT INTO symbol VALUES ('01 - P. ORIENTAMENTO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"');
INSERT INTO symbol VALUES ('02 - TERMINE PARTICELLARE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"');
INSERT INTO symbol VALUES ('03 - PARAMETRO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"');
INSERT INTO symbol VALUES ('04 - OSSO DI MORTO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"');
INSERT INTO symbol VALUES ('05 - FLUSSO GRANDE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"');
INSERT INTO symbol VALUES ('06 - FLUSSO MEDIO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"');
INSERT INTO symbol VALUES ('07 - FLUSSO PICCOLO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"');
INSERT INTO symbol VALUES ('08 - P. FID. TRIGONOMETRICO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#072;"');
INSERT INTO symbol VALUES ('09 - GRAFFA GRANDE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#073;"');
INSERT INTO symbol VALUES ('10 - ANCORA', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"');
INSERT INTO symbol VALUES ('11 - TERMINE PROVINCIALE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#075;"');
INSERT INTO symbol VALUES ('13 - CROCE SU ROCCIA', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#077;"');
INSERT INTO symbol VALUES ('14 - GRAFFA PICCOLA', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#078;"');
INSERT INTO symbol VALUES ('15 - BAFF. PICCOLA', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#079;"');
INSERT INTO symbol VALUES ('16 - BAFF. GRANDE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#079;"');
INSERT INTO symbol VALUES ('20 - P. FID. SEMPLICE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#084;"');



--- Numeri
INSERT INTO symbol VALUES ('#0', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#048;"');
INSERT INTO symbol VALUES ('#1', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#049;"');
INSERT INTO symbol VALUES ('#2', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#050;"');
INSERT INTO symbol VALUES ('#3', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#051;"');
INSERT INTO symbol VALUES ('#4', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#052;"');
INSERT INTO symbol VALUES ('#5', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#053;"');
INSERT INTO symbol VALUES ('#6', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#054;"');
INSERT INTO symbol VALUES ('#7', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#055;"');
INSERT INTO symbol VALUES ('#8', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#056;"');
INSERT INTO symbol VALUES ('#9', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#057;"');
INSERT INTO symbol VALUES ('#A', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"');
INSERT INTO symbol VALUES ('#B', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"');
INSERT INTO symbol VALUES ('#C', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"');
INSERT INTO symbol VALUES ('#D', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"');
INSERT INTO symbol VALUES ('#E', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"');
INSERT INTO symbol VALUES ('#F', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"');
INSERT INTO symbol VALUES ('#G', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"');
INSERT INTO symbol VALUES ('#H', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#072;"');
INSERT INTO symbol VALUES ('#I', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#073;"');
INSERT INTO symbol VALUES ('#J', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"');
INSERT INTO symbol VALUES ('#K', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#075;"');
INSERT INTO symbol VALUES ('#L', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#076;"');
INSERT INTO symbol VALUES ('#M', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#077;"');
INSERT INTO symbol VALUES ('#N', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#078;"');
INSERT INTO symbol VALUES ('#O', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#079;"');
INSERT INTO symbol VALUES ('#P', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#080;"');
INSERT INTO symbol VALUES ('#Q', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#081;"');
INSERT INTO symbol VALUES ('#R', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#082;"');
INSERT INTO symbol VALUES ('#S', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#083;"');
INSERT INTO symbol VALUES ('#T', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#084;"');
INSERT INTO symbol VALUES ('#U', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#085;"');
INSERT INTO symbol VALUES ('#V', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#086;"');
INSERT INTO symbol VALUES ('#W', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#087;"');
INSERT INTO symbol VALUES ('#X', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#088;"');
INSERT INTO symbol VALUES ('#Y', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#089;"');
INSERT INTO symbol VALUES ('#Z', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#090;"');

--- R3 MAP SYMBOLS
INSERT INTO symbol VALUES ('PIN1', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"');
INSERT INTO symbol VALUES ('PIN2', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"');
INSERT INTO symbol VALUES ('PIN3', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"');
INSERT INTO symbol VALUES ('PIN4', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"');
INSERT INTO symbol VALUES ('CAMERA', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"');
INSERT INTO symbol VALUES ('ORIENTED-PIN', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"');
INSERT INTO symbol VALUES ('LIKE', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"');

----- R3-Ambiebte
INSERT INTO symbol VALUES ('ARIA', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"');
INSERT INTO symbol VALUES ('IDRICO', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"');
INSERT INTO symbol VALUES ('IDRICO SUP.', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"');
INSERT INTO symbol VALUES ('IDRICO SOTT.', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"');
INSERT INTO symbol VALUES ('SUOLO E SOTT.', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"');
INSERT INTO symbol VALUES ('ECOSIST. FLORA', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"');
INSERT INTO symbol VALUES ('ECOSIST. FAUNA', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"');
INSERT INTO symbol VALUES ('ECOSIST. UCCELLI', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#072;"');
INSERT INTO symbol VALUES ('RUMORE E VIBRAZIONI', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#073;"');
INSERT INTO symbol VALUES ('PAESAGGIO', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"');
INSERT INTO symbol VALUES ('TERRE E ROCCE', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#075;"');
INSERT INTO symbol VALUES ('RADIAZIONI', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#076;"');

-- RIPORTA GLI STILI CON PATTERN NEL MODO CORRETTO
update style set pattern_id = e_pattern.pattern_id 
from e_pattern where symbol_name=pattern_name;
update style set symbol_name=NULL where pattern_id is not null;

ALTER TABLE style
  ADD CONSTRAINT pattern_id_fkey FOREIGN KEY (pattern_id)
      REFERENCES e_pattern (pattern_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE NO ACTION;

-- SISTEMA GLI STILI IN MODO DA RIALLINEARSI CON LA VESTIZIONE CORRETTA

update style set pattern_id = 0 where pattern_id is null;

update style 
  set symbol_name = NULL 
  where 
    SYMBOL_name = 'CIRCLE' and
    pattern_id = 0  and
    class_id in
      (Select class_id from class where layer_id IN
        (SELECT layer_id from layer where layertype_id = 2));

update style
set style_def = 'GAP '||(size::INT*2)::TEXT
where --style_def is null
--and 
symbol_name in (
select symbol_name from symbol where symbolcategory_id = 3);

update style
set style_def = 'GAP -'||(size::INT*4)::TEXT
where --style_def is null
--and 
symbol_name in (
select symbol_name from symbol where 
symbol_name like 'ARRO%' or symbol_name like '%_SPACE');

--pulisce le label dal BACKGROUNDCOLOR
update class
set label_def = label_def||' STYLE 
GEOMTRANSFORM ''labelpoly'' 
COLOR '||label_bgcolor||' 
END'
where label_bgcolor is not null;
UPDATE CLASS set label_bgcolor = NULL;

-- sistema i testi fissi

update class set
class_text = ''''||class_text||'''' where class_text is not null;

delete from symbol where symbolcategory_id = 2;
delete from e_symbolcategory where symbolcategory_id = 2;

update style set symbol_name = NULL where symbol_name='CIRCLE'
and pattern_id = 0 and class_id in
(select class_id from class where layer_id in
(select layer_id from layer where layertype_id = 2));


INSERT INTO version (version_name,version_key, version_date) values ('6', 'mapserver', CURRENT_DATE);
