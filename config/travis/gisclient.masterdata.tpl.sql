--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = DB_SCHEMA, pg_catalog;


--
-- Data for Name: authfilter; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY authfilter (filter_id, filter_name, filter_description, filter_priority) FROM stdin;
\.


--
-- Data for Name: e_conntype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_conntype (conntype_id, conntype_name, conntype_order) FROM stdin;
7	WMS	4
3	SDE	7
6	Postgis	2
8	Oracle Spatial	3
1	Local Folder	1
9	WFS	5
4	OGR	5
\.


--
-- Data for Name: e_charset_encodings; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_charset_encodings (charset_encodings_id, charset_encodings_name, charset_encodings_order) FROM stdin;
1	ISO-8859-1	1
2	UTF-8	2
\.


--
-- Data for Name: e_datatype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_datatype (datatype_id, datatype_name, datatype_order) FROM stdin;
1	Stringa di testo	\N
2	Numero	\N
3	Data	\N
10	Immagine	\N
15	File	\N
\.


--
-- Data for Name: e_fieldformat; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_fieldformat (fieldformat_id, fieldformat_name, fieldformat_format, fieldformat_order) FROM stdin;
1	intero	%d	10
2	decimale (1 cifra)	%01.1f	20
3	decimale (2 cifre)	%01.2f	30
\.


--
-- Data for Name: e_fieldtype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) FROM stdin;
1	Standard	\N
2	Collegamento	\N
3	E-mail	\N
107	Varianza	\N
106	Deviazione  St	\N
105	Conteggio	\N
104	Max	\N
103	Min	\N
102	Media	\N
101	Somma	\N
10	File	\N
8	Immagine	\N
\.


--
-- Data for Name: e_filetype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_filetype (filetype_id, filetype_name, filetype_order) FROM stdin;
1	File SQL	1
2	File CSV	2
3	File Shape	3
\.


--
-- Data for Name: e_level; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) FROM stdin;
12	class	class	6	11	4	0	1	11	class	2
17	qtfield	qtfield	11	11	4	0	1	11	qtfield	2
16	qtrelation	qtrelation	10	11	4	1	1	11	qtrelation	2
8	mapset	mapset	15	2	1	0	1	2	mapset	2
100	classgroup	layer	\N	11	4	1	0	11	classgroup	2
1	root	\N	1	\N	\N	0	0	\N	\N	2
2	project	project	2	1	0	0	1	1	project	2
5	theme	theme	3	2	1	0	5	2	theme	2
6	project_srs	project_srs	4	2	1	1	1	2	project_srs	2
7	catalog	catalog	13	2	1	1	2	2	catalog	2
9	link	link	15	2	1	1	4	2	link	2
10	layergroup	layergroup	4	5	2	0	1	5	layergroup	2
11	layer	layer	5	10	3	0	1	10	layer	2
14	style	style	7	12	5	1	1	12	style	2
21	mapset_groups	mapset_groups	16	8	2	1	4	8	mapset_usergroup	2
22	mapset_layergroup	mapset_layergroup	17	8	2	1	1	8	mapset_layergroup	2
23	mapset_qt	mapset_qt	18	8	2	1	2	8	mapset_qt	2
3	groups	groups	7	1	0	0	0	1	groups	1
51	group_authfilter	groups	1	3	1	1	1	3	group_authfilter	2
24	mapset_link	mapset_link	19	8	2	1	3	8	mapset_link	2
27	selgroup	selgroup	\N	2	1	0	8	2	selgroup	2
33	project_admin	project_admin	15	2	1	1	0	2	project_admin	2
32	user_project	project	8	2	1	1	0	2	user_project	2
47	layer_groups	layer_groups	\N	11	4	1	1	11	layer_groups	2
48	project_languages	project_languages	\N	2	1	1	1	2	project_languages	2
4	users	users	6	1	0	0	0	1	users	1
49	authfilter	authfilter	8	1	0	1	0	1	authfilter	2
50	layer_authfilter	layer	15	11	4	1	1	11	layer_authfilter	2
46	user_groups	group_users	\N	3	1	1	0	3	user_group	1
45	group_users	user_groups	\N	4	1	1	0	4	user_group	1
52	qtfield_groups	qtfield	1	17	5	1	0	17	qtfield_groups	2
19	qtlink	layer	12	11	4	1	0	11	qtlink	2
28	selgroup_layer	selgroup	\N	27	2	1	1	27	selgroup_layer	2
\.


--
-- Data for Name: e_form; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) FROM stdin;
5	mapset	mapset	0	8	\N	\N	\N	\N	\N	title
8	temi	theme	0	5	\N	\N	\N	\N	\N	theme_order,theme_title
9	temi	theme	1	5	\N	\N	\N	\N	\N	\N
10	temi	theme	1	5	\N	\N	2	\N	\N	\N
11	temi	theme	2	5	\N	\N	2	\N	\N	\N
12	project_srs	project_srs	0	6	\N	\N	2	\N	\N	\N
13	project_srs	project_srs	1	6	\N	\N	2	\N	\N	\N
14	project_srs	project_srs	2	6	\N	\N	2	\N	\N	\N
23	group	group	50	3	\N	group	2	\N	group	\N
26	mapset	mapset	1	8		\N	2	\N	\N	\N
27	mapset	mapset	1	8	\N	mapset	2	\N	\N	\N
28	mapset	mapset	2	2	\N	mapset	2	\N	\N	\N
34	layer	layer	0	11	\N	\N	10	\N	\N	layer_order,layer_name
35	layer	layer	1	11	\N	layer	10	\N	\N	\N
36	layer	layer	1	11	\N	layer	10	\N	\N	\N
37	layer	layer	2	11	\N	layer	10	\N	\N	\N
38	classi	class	0	12	\N	\N	11	\N	\N	class_order
39	classi	class	1	12	\N	\N	11	\N	\N	\N
40	classi	class	1	12	\N	class	11	\N	\N	\N
41	classi	class	2	12	\N	class	11	\N	\N	\N
42	stili	style	0	14	\N	\N	12	\N	\N	style_order
43	stili	style	1	14	\N	\N	12	\N	\N	\N
44	stili	style	1	14	\N	style	12	\N	\N	\N
45	stili	style	2	14	\N	style	12	\N	\N	\N
50	catalog	catalog	0	7	\N	\N	2	\N	\N	catalog_name
51	catalog	catalog	1	7	\N	\N	2	\N	\N	\N
52	catalog	catalog	1	7	\N	catalog	2	\N	\N	\N
53	catalog	catalog	2	7	\N	catalog	2	\N	\N	\N
70	links	link	0	9		\N	2	\N	\N	link_order,link_name
72	links	link	1	9		\N	2	\N	\N	\N
73	links	link	1	9		\N	2	\N	\N	\N
74	links	link	2	9		\N	2	\N	\N	\N
81	map_group	mapset_group	4	21	\N	mapset_groups	8	\N	\N	\N
82	map_group	mapset_group	5	21	\N	mapset_groups	8	\N	\N	\N
83	map_group	mapset_group	0	21	\N	\N	8	\N	\N	\N
105	selgroup	selgroup	0	27	\N	\N	2	\N	\N	\N
106	selgroup	selgroup	1	27	\N	\N	2	\N	\N	\N
107	selgroup	selgroup	1	27	\N	\N	2	\N	\N	\N
108	qt_selgroup	qt_selgroup	4	28	\N	qt_selgroup	27	\N	\N	\N
109	qt_selgroup	qt_selgroup	5	28	\N	qt_selgroup	27	\N	\N	\N
149	group_users	group_users	4	45	\N	group_users	3	\N	\N	\N
150	group_users	group_users	5	45	\N	group_users	3	\N	\N	\N
151	user_groups	user_groups	4	46	\N	user_groups	4	\N	\N	\N
152	user_groups	user_groups	5	46	\N	user_groups	4	\N	\N	\N
75	qt_relation	qt_relation_addnew	0	16	\N	\N	13	\N	\N	\N
62	qt_fields	qtfield	0	17	\N	\N	11	\N	\N	qtrelationtype_id,qtrelation_name,field_header,qtfield_name
63	qt_fields	qtfield	1	17	\N	\N	11	\N	\N	\N
64	qt_fields	qtfield	1	17	\N	\N	11	\N	\N	\N
65	qt_fields	qtfield	2	17	\N	\N	11	\N	\N	\N
201	classgroup	classgroup	1	100	\N	\N	11	\N	\N	\N
200	classgroup	classgroup	0	100	\N	\N	11	\N	\N	\N
58	qt_relation	qtrelation	0	16	\N	qtrelation	11	\N	\N	\N
59	qt_relation	qtrelation	1	16	\N	qtrelation	11	\N	\N	\N
60	qt_relation	qtrelation	1	16	\N	qtrelation	11	\N	\N	\N
61	qt_relation	qtrelation	2	16	\N	qtrelation	11	\N	\N	\N
170	layer_groups	layer_groups	4	47	\N	layer_groups	11	\N	\N	\N
171	layer_groups	layer_groups	5	47	\N	layer_groups	11	\N	\N	\N
202	project_languages	project_languages	0	48	\N	\N	2	\N	\N	\N
203	project_languages	project_languages	1	48	\N	\N	2	\N	\N	\N
204	authfilter	authfilter	0	49	\N	\N	2	\N	\N	\N
205	authfilter	authfilter	1	49	\N	\N	2	\N	\N	\N
16	user	user	0	4	\N	user	2	\N	user	\N
206	layer_authfilter	layer_authfilter	4	50	\N	layer_authfilter	11	\N	\N	\N
207	layer_authfilter	layer_authfilter	5	50	\N	layer_authfilter	11	\N	\N	\N
209	group_authfilter	group_authfilter	1	51	\N	\N	3	\N	\N	\N
208	group_authfilter	group_authfilter	0	51	\N	\N	3	\N	\N	\N
2	progetto	project	0	2	\N	\N	\N	\N	\N	project_name
3	progetto	project	1	2		\N	\N	\N	\N	\N
6	progetto	project	2	2		\N	\N	\N	\N	\N
7	progetto	project	1	2	\N	\N	\N	\N	\N	\N
30	layergroup	layergroup	0	10	\N	\N	5	\N	\N	layergroup_order,layergroup_title
31	layergroup	layergroup	1	10	\N	\N	5	\N	\N	\N
32	layergroup	layergroup	1	10	\N	\N	5	\N	\N	\N
33	layergroup	layergroup	2	10	\N	\N	5	\N	\N	\N
133	project_admin	admin_project	2	33	\N	\N	2	\N	\N	\N
134	project_admin	admin_project	5	33	\N	\N	6	\N	\N	\N
210	qtfield_groups	qtfield_groups	4	52	\N	qtfield_groups	17	\N	\N	\N
211	qtfield_groups	qtfield_groups	5	52	\N	qtfield_groups	17	\N	\N	\N
212	qtfield_groups	qtfield_groups	0	52	\N	qtfield_groups	17	\N	\N	\N
66	qtlink	qtlink	2	19	\N	qtlink	11	\N	\N	\N
67	qtlink	qtlink	0	19	\N	\N	11	\N	\N	\N
68	qtlink	qtlink	1	19	\N	qtlink	11	\N	\N	\N
69	qtlink	qtlink	110	19	\N	\N	11	\N	\N	\N
84	map_layer	mapset_layergroup	4	22	\N	mapset_layergroup	8	\N	\N	\N
85	map_layer	mapset_layergroup	5	22	\N	mapset_layergroup	8	\N	\N	\N
86	map_layer	mapset_layergroup	0	22	\N	mapset_layergroup	8	\N	\N	\N
20	group	group	0	3	\N	group	2	\N	group	\N
18	user	user	50	4	\N	user	2	\N	user	\N
213	selgroup_layer	selgroup_layer	4	28	\N	selgroup_layer	27	\N	\N	\N
214	selgroup_layer	selgroup_layer	5	28	\N	selgroup_layer	27	\N	\N	\N
\.


--
-- Data for Name: e_language; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_language (language_id, language_name, language_order) FROM stdin;
en	English	1
fr	Francais	2
de	Deutsch	3
es	Espanol	4
it	Italiano	5
\.


--
-- Data for Name: e_layertype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) FROM stdin;
5	annotation          	4	\N
1	point               	0	\N
2	line                	1	\N
3	polygon             	2	\N
4	raster              	3	\N
8	tileindex	7	\N
10	tileraster	100	\N
11	chart	8	\N
\.


--
-- Data for Name: e_lblposition; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_lblposition (lblposition_id, lblposition_name, lblposition_order) FROM stdin;
1	UL	\N
2	UC	\N
3	UR	\N
4	CL	\N
5	CC	\N
6	CR	\N
7	LL	\N
8	LC	\N
9	LR	\N
10	AUTO	\N
\.


--
-- Data for Name: e_legendtype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_legendtype (legendtype_id, legendtype_name, legendtype_order) FROM stdin;
1	auto	1
0	nessuna	2
\.


--
-- Data for Name: e_orderby; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_orderby (orderby_id, orderby_name, orderby_order) FROM stdin;
0	Nessuno	\N
1	Crescente	\N
2	Decresente	\N
\.


--
-- Data for Name: e_outputformat; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) FROM stdin;
2	AGG PNG	AGG/PNG	image/png	PC256	png	\N	\N
4	PNG 8 bit	GD/PNG	image/png	PC256	png	\N	\N
5	PNG 24 bit	GD/PNG	image/png	RGB	png	\N	\N
6	PNG 32 bit Trasp	GD/PNG	image/png	RGBA	png	\N	\N
7	AGG Q	AGG/PNG	image/png; mode=8bit	RGB	png	    FORMATOPTION "QUANTIZE_FORCE=ON"\n    FORMATOPTION "QUANTIZE_DITHER=OFF"\n    FORMATOPTION "QUANTIZE_COLORS=256"	\N
1	AGG PNG 24 bit	AGG/PNG	image/png; mode=24bit	RGB	png	\N	\N
3	AGG JPG	AGG/JPG	image/jpeg	RGB	jpg	\N	\N
9	AGG PNG	AGG/PNG	image/png	RGB	png	    FORMATOPTION "QUANTIZE_FORCE=ON"\n\t\tFORMATOPTION "QUANTIZE_DITHER=OFF"\n\t\tFORMATOPTION "QUANTIZE_COLORS=256"	\N
\.


--
-- Data for Name: e_owstype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_owstype (owstype_id, owstype_name, owstype_order) FROM stdin;
3	VirtualEarth	3
4	Yahoo	4
5	OSM	5
1	OWS	1
7	Google v.3	7
8	Bing tiles	8
2	Google v.2	2
6	TMS	6
9	WMTS	9
\.


--
-- Data for Name: e_papersize; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) FROM stdin;
1	A4 Verticale	A4	P	\N
2	A4 Orizzontale	A4	L	\N
3	A3 Verticale	A3	P	\N
4	A3 Orizzontale	A3	L	\N
5	A2 Verticale	A2	P	\N
6	A2 Orizzontale	A2	L	\N
7	A1 Verticale	A1	P	\N
8	A1 Orizzontale	A1	L	\N
9	A0 Verticale	A0	P	\N
10	A0 Orizzontale	A0	L	\N
\.


--
-- Data for Name: e_pattern; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_pattern (pattern_id, pattern_name, pattern_def, pattern_order) FROM stdin;
0	NO PATTERN	#PATTERN END	0
1	1-3	PATTERN 1 3 END	1
2	2-3	PATTERN 2 3 END	2
3	3-3	PATTERN 3 3 END	3
4	5-5	PATTERN 5 5 END	4
5	10-10	PATTERN 10 10 END	5
6	10-3	PATTERN 10 3 END	6
7	3-10	PATTERN 3 10 END	7
8	5-3-1-3	PATTERN 5 3 1 3 END	8
9	5-3-1-3-1-3	PATTERN 5 3 1 3 1 3 END	9
10	5-3-5-3-1-3	PATTERN 5 3 5 3 1 3 END	10
11	1-2-1-6	PATTERN 1 2 1 6 END	11
\.


--
-- Name: e_pattern_pattern_id_seq; Type: SEQUENCE SET; Schema: gisclient; Owner: -
--

SELECT pg_catalog.setval('e_pattern_pattern_id_seq', 1, false);


--
-- Data for Name: e_qtrelationtype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_qtrelationtype (qtrelationtype_id, qtrelationtype_name, qtrelationtype_order) FROM stdin;
1	Dettaglio (1 a 1)	\N
2	Secondaria (Info 1 a molti)	\N
\.


--
-- Data for Name: e_resultype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_resultype (resultype_id, resultype_name, resultype_order) FROM stdin;
1	Mostra sempre	1
4	Nascondi	2
5	Ignora	3
10	Nascondi in tabella	4
20	Nascondi in tooltip	5
30	Nascondi in scheda	6
\.


--
-- Data for Name: e_searchtype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_searchtype (searchtype_id, searchtype_name, searchtype_order) FROM stdin;
4	Numerico	\N
5	Data	\N
1	Testo	\N
2	Parte di testo	\N
3	Lista di valori	\N
0	Nessuno	\N
6	Lista di valori, non WFS	\N
\.


--
-- Data for Name: e_sizeunits; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) FROM stdin;
2	feet	\N
3	inches	\N
1	pixels	\N
4	kilometers	\N
5	meters	\N
6	miles	\N
7	dd	\N
\.


--
-- Data for Name: e_symbolcategory; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_symbolcategory (symbolcategory_id, symbolcategory_name, symbolcategory_order) FROM stdin;
1	MapServer	\N
2	Line	\N
3	Campiture	\N
4	Marker	\N
5	CatastoCML	\N
6	TechNET	\N
8	COSAP	\N
9	SkiGIS	\N
10	SIGNS	\N
12	R3-Ambiente	\N
50	Sentieri CMSO	\N
60	P.E.I. CMVerbano	\N
70	PRG TSI Mori	\N
7	Numeri e lettere	\N
\.


--
-- Data for Name: e_tiletype; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY e_tiletype (tiletype_id, tiletype_name, tiletype_order) FROM stdin;
0	no Tiles	1
1	WMS Tiles	2
2	Tilecache Tiles	3
\.


--
-- Data for Name: font; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY font (font_name, file_name) FROM stdin;
dejavu-sans	dejavu-sans.ttf
dejavu-sans-bold	dejavu-sans-bold.ttf
dejavu-sans-bold-italic	dejavu-sans-bold-italic.ttf
dejavu-serif	dejavu-serif.ttf
dejavu-serif-bold	dejavu-serif-bold
dejavu-serif-bold-italic	dejavu-serif-bold-italic.ttf
dejavu-serif-italic	dejavu-serif-italic.ttf
dejavu-sans-italic	dejavu-sans-italic.ttf
\.


--
-- Data for Name: form_level; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY form_level (id, level, mode, form, order_fld, visible) FROM stdin;
1	1	3	2	1	1
2	2	0	3	1	1
4	2	3	8	5	1
5	2	3	5	8	1
7	2	1	7	1	1
8	2	2	6	1	1
14	2	3	12	3	1
15	6	1	13	1	1
16	6	2	13	1	1
17	6	0	13	1	1
19	8	0	26	1	1
20	8	1	27	1	1
21	8	2	28	1	1
22	5	0	9	1	1
23	5	1	10	1	1
24	5	2	11	1	1
25	5	3	30	3	1
26	10	0	31	1	1
27	10	1	32	1	1
28	10	2	33	1	1
29	10	3	34	3	1
30	11	0	35	1	1
31	11	1	36	1	1
32	11	2	37	1	1
34	12	0	39	1	1
35	12	1	40	1	1
36	12	2	41	2	1
37	12	3	42	3	1
38	14	0	43	1	1
39	14	1	44	1	1
40	14	2	45	1	1
45	2	3	50	4	1
46	7	0	51	1	1
47	7	1	52	1	1
48	7	2	53	1	1
54	16	0	59	1	1
55	16	1	60	1	1
56	16	2	61	1	1
57	17	0	63	1	1
58	17	1	64	1	1
59	17	2	65	1	1
63	2	3	70	7	1
64	9	0	72	1	1
65	9	1	73	1	1
66	9	2	74	1	1
74	8	3	81	2	1
75	21	1	82	1	1
77	8	3	84	6	1
78	22	1	85	1	1
98	2	3	105	6	1
99	27	1	106	1	1
101	27	0	107	1	1
102	28	1	109	1	1
116	27	3	108	6	1
127	33	1	134	15	1
131	2	3	133	15	1
132	27	2	106	1	1
164	1	3	16	3	1
165	4	0	18	1	1
166	4	1	18	1	1
167	4	2	18	1	1
168	1	3	20	2	1
169	3	0	23	1	1
170	3	1	23	1	1
171	3	2	23	1	1
172	3	3	149	2	1
173	45	1	150	1	1
175	4	3	151	2	1
176	46	1	152	1	1
79	22	-1	86	2	1
69	16	1	75	2	0
76	21	1	83	2	0
100	27	2	105	2	0
163	27	3	151	1	1
33	11	3	38	3	1
500	11	3	200	2	0
501	100	0	201	1	0
502	100	1	201	1	0
503	100	2	201	1	0
51	11	3	58	4	1
52	11	3	62	5	1
200	11	0	170	7	1
201	47	1	171	1	1
202	47	3	171	1	1
203	47	2	171	1	1
504	48	0	203	1	1
505	48	1	203	1	1
506	48	2	203	1	1
507	2	3	202	2	1
508	49	0	205	1	1
509	49	1	205	1	1
510	49	2	205	1	1
513	50	1	207	1	1
512	11	3	206	8	1
511	1	3	204	4	1
514	3	3	208	3	1
515	51	0	209	1	1
516	51	1	209	1	1
517	51	2	209	1	1
518	17	0	210	1	1
519	52	1	211	1	1
53	11	3	66	6	1
60	19	0	67	1	1
61	19	1	68	1	1
62	19	1	69	2	1
520	27	3	213	1	1
521	28	1	214	1	1
\.


--
-- Data for Name: groups; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY groups (groupname, description) FROM stdin;
unipol	Vede e interroga dati Unipol
\.


--
-- Data for Name: group_authfilter; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY group_authfilter (groupname, filter_id, filter_expression) FROM stdin;
\.


--
-- Data for Name: i18n_field; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY i18n_field (i18nf_id, table_name, field_name) FROM stdin;
1	class	class_title
2	class	expression
3	class	label_def
4	class	class_text
5	layer	layer_title
6	layer	data_filter
7	layer	layer_def
8	layer	metadata
9	layer	labelitem
10	layer	classitem
11	layergroup	layergroup_title
12	layergroup	sld
13	qtfield	qtfield_name
14	qtfield	field_header
15	style	style_def
16	theme	theme_title
17	theme	copyright_string
18	mapset	mapset_title
19	mapset	mapset_description
20	qtfield	filter_field_name
21	qtfield	lookup_name
\.


--
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE SET; Schema: gisclient; Owner: -
--

SELECT pg_catalog.setval('i18n_field_i18nf_id_seq', 1, false);


--
-- Data for Name: layer_authfilter; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY layer_authfilter (layer_id, filter_id, required) FROM stdin;
\.


--
-- Data for Name: layer_groups; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY layer_groups (layer_id, groupname, wms, wfs, wfst, layer_name, layer_groups_id) FROM stdin;
\.


--
-- Name: layer_groups_seq; Type: SEQUENCE SET; Schema: gisclient; Owner: -
--

SELECT pg_catalog.setval('layer_groups_seq', 36, true);


--
-- Data for Name: link; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY link (link_id, project_name, link_name, link_def, link_order, winw, winh) FROM stdin;
\.


--
-- Data for Name: localization; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY localization (localization_id, project_name, i18nf_id, pkey_id, language_id, value) FROM stdin;
\.


--
-- Name: localization_localization_id_seq; Type: SEQUENCE SET; Schema: gisclient; Owner: -
--

SELECT pg_catalog.setval('localization_localization_id_seq', 1228, true);


--
-- Data for Name: project_admin; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY project_admin (project_name, username) FROM stdin;
\.


--
-- Data for Name: project_languages; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY project_languages (project_name, language_id) FROM stdin;
\.


--
-- Data for Name: symbol; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def, symbol_type, font_name, ascii_code, filled, points, image) FROM stdin;
AIR	3	0	\N	TYPE TRUETYPE\nFONT "MSYMB"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#033;"	\N	\N	\N	0	\N	\N
VIGNETO	3	0	\N	Type VECTOR\n  Filled TRUE\n  Points\n\t\t.8 .6\n\t\t.4 .6\n\t\t.6 0\n\t\t.4 0\n\t\t.2 .6\n\t\t.4 .8\n\t\t.6 .8\n\t\t.8 .6\n  END\n\t\t	\N	\N	\N	0	\N	\N
TENT	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n.5 0\n1 1\n.75 1\n.5 .5\n.25 1\n0 1\nEND	\N	\N	\N	0	\N	\N
STAR	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 .375\n.35 .375\n.5 0\n.65 .375\n1 .375\n.75 .625\n.875 1\n.5 .75\n.125 1\n.25 .625\nEND	\N	\N	\N	0	\N	\N
TRIANGLE	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n.5 0\n1 1\n0 1\nEND	\N	\N	\N	0	\N	\N
SQUARE	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n0 0\n1 0\n1 1\n0 1\nEND	\N	\N	\N	0	\N	\N
PLUS	1	0	\N	TYPE VECTOR\nPOINTS\n.5 0\n.5 1\n-99 -99\n0 .5\n1 .5\nEND	\N	\N	\N	0	\N	\N
CROSS	1	0	\N	TYPE VECTOR\nPOINTS\n0 0\n1 1\n-99 -99\n0 1\n1 0\nEND	\N	\N	\N	0	\N	\N
VIVAIO	3	0	\N	TYPE Vector\n  POINTS\n\t\t.3 1\n\t\t.7 1\n\t\t.9 .1\n\t\t.1 .1\n\t\t.3 1\n\t\t-99 -99\n\t\t.2 .2\n\t\t.2 .1\n\t\t-99 -99\n\t\t.5 .2\n\t\t.5 .1\n\t\t-99 -99\n\t\t.7 .2\n\t\t.7 .1\n  END\n\t	\N	\N	\N	0	\N	\N
CIRCLE	1	0	\N	TYPE ELLIPSE\nFILLED TRUE\nPOINTS\n1 1\nEND	\N	\N	\N	0	\N	\N
WATER	3	0	\N	Type VECTOR\n  Filled FALSE  \n   Points\n\t\t0 .6\n\t\t.1 .4\n\t\t.2 .4\n\t\t.3 .6\n\t\t.4 .6\n\t\t.5 .4\n\t\t.6 .4\n\t\t.7 .6\n\t\t.8 .6\n\t\t.9 .4\n\t\t1 .4\n\t\t1.1 .6\n  END	\N	\N	\N	0	\N	\N
CIRCLE_EMPTY	3	0	\N	TYPE Vector\n  POINTS\n    0 .5\n\t\t.1 .7\n\t\t.3 .9\n\t\t.5 1\n\t\t.7 .9\n\t\t.9 .7\n\t\t1 .5\n\t\t.9 .3\n\t\t.7 .1\n\t\t.5 0\n\t\t.3 .1\n\t\t.1 .3\n\t\t0 .5\n  END\n\t	\N	\N	\N	0	\N	\N
CIRCLE_HALF	3	0	\N	TYPE Vector\n  POINTS\n    0 .5\n\t\t.1 .7\n\t\t.3 .9\n\t\t.5 1\n\t\t.7 .9\n\t\t.9 .7\n\t\t1 .5\n\t\t0 .5\n  END\n\n\t	\N	\N	\N	0	\N	\N
BOSCO	3	0	\N	TYPE Vector\n  POINTS\n    .5 1\n    .5 0\n\t\t-99 -99\n\t\t.5 0\n\t\t.3 .1 \n\t\t-99 -99\n\t\t.5 .0\n\t\t.7 .1\n\t\t-99 -99\n\t\t.5 .3\n\t\t.2 .4\n\t\t-99 -99\n\t\t.5 .3\n\t\t.8 .4\n\t\t-99 -99\n\t\t.5 .6\n\t\t.1 .8\n\t\t-99 -99\n\t\t.5 .6\n\t\t.9 .8\n  END\n\t	\N	\N	\N	0	\N	\N
CIMITERO	3	0	\N	TYPE VECTOR\nPOINTS\n.5 0\n.5 1\n-99 -99\n.2 .3\n.8 .3\nEND\n	\N	\N	\N	0	\N	\N
FAUNA	3	0	\N	TYPE TRUETYPE\nFONT "MSYMB"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#035;"	\N	\N	\N	0	\N	\N
FLORA	3	0	\N	TYPE TRUETYPE\nFONT "MSYMB"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#036;"	\N	\N	\N	0	\N	\N
FRUTTETO	3	0	\N	Type VECTOR\n  Filled TRUE\n  Points\n\t\t.2 1\n\t\t.2 .8\n\t\t.4 .8 \n\t\t.4 .4\n\t\t0 0\n\t\t.2 0\n\t\t.4 .2\n\t\t.4 0\n\t\t.6 0\n\t\t.6 .2\n\t\t.8 0\n\t\t1 0\n\t\t.6 .4\n\t\t.6 .8\n\t\t.8 .8\n\t\t.8 1\n\t\t.2 1\n  END\n\t\t	\N	\N	\N	0	\N	\N
INCOLTO	3	0	\N	Type VECTOR\n  Filled TRUE\n  Points\n\t0 1\n\t.2 .6\n\t.35 .85\n\t.5 .6\n\t.65 .85\n\t.8 .6 \n\t1 1\n\t.9 1\n\t.8 .8\n\t.7 1\n\t.6 1\n\t.5 .8\n\t.4 1\n\t.3 1\n\t.2 .8\n\t.1 1\n\t0 1\n  END\n\t\t	\N	\N	\N	0	\N	\N
NOISE	3	0	\N	TYPE TRUETYPE\nFONT "MSYMB"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#037;"	\N	\N	\N	0	\N	\N
NOISE_INSP	3	0	\N	TYPE TRUETYPE\nFONT "MSYMB"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#038;"	\N	\N	\N	0	\N	\N
PASCOLO	3	0	\N	  Type VECTOR\n  Filled TRUE\n  Points\n    0 .4\n\t\t.2 1\n\t\t.4 1\n\t\t.2 .4\n\t\t0 .4\n\t\t-99 -99\n\t\t.4 0\n\t\t.6 0 \n\t\t.6 1\n\t\t.4 1\n\t\t.4 0 \n\t   -99 -99\n\t\t .8 .4\n\t\t 1 .4\n\t\t .8 1\n\t\t .6 1\n\t\t .8 .4\t\n  END\n\t\t	\N	\N	\N	0	\N	\N
RANDOM	3	0	\N	  Type VECTOR\n  Filled TRUE\n  Points\n    .1 .1\n\t\t.3 .3\n  -99 -99\n\t\t.5 .2\n\t\t.7 0\n  -99 -99\n\t\t.9 .2\n  -99 -99\n\t\t.7 .3\n  -99 -99\n\t\t.1 .5\t\t\n  -99 -99\n\t\t.6 .5\n\t\t.4 .7\n  -99 -99\n\t\t.3 .8\n  -99 -99\n\t\t.8 .7\n  -99 -99\n\t\t.1 .9\n  -99 -99\n\t\t.6 .8\n\t\t.6 1\n  END\n\t\t	\N	\N	\N	0	\N	\N
RISAIA	3	0	\N	Type VECTOR\n  Filled TRUE\n  Points\n\t\t0 1\n\t\t0 .4\n\t\t.2 .4\n\t\t.2 1\n\t\t0 1\n\t\t-99 -99\n\t\t.4 1\n\t\t.4 0\n\t\t.6 0\n\t\t.6 1\n\t\t.4 1\n\t\t-99 -99 \n\t\t.8 1\n\t\t.8 .4\n\t\t1 .4\n\t\t1 1\n\t\t.8 1 \n  END\n\t\t	\N	\N	\N	0	\N	\N
RUPESTRE	3	0	\N	  Type VECTOR\n  Filled TRUE\n  Points\n    .2 .8\n    .35 .6\n    .65 .6\n    .8 .8\n   -99 -99\n    0 .6\n    .15 .45\n    .35 .45\n    .5 .6\n    .65 .45\n    .85 .45\n    1 .6\t\t\n  END\n\t\t	\N	\N	\N	0	\N	\N
ARROW	2	0	\N	TYPE Vector\n\tFILLED True\n\tPOINTS\n\t  0 0\n\t\t.5 .5\n\t\t0 1\n\t\t0 0\n\tEND	\N	\N	\N	0	\N	\N
ARROWBACK	2	0	\N	\tTYPE Vector\n\tFILLED True\n\tPOINTS\n\t  1 1\n\t\t.5 .5\n\t\t1 0\n\t\t1 1\n\tEND	\N	\N	\N	0	\N	\N
CIRCLE_FILL	3	0	\N	TYPE ELLIPSE\nFILLED TRUE\nPOINTS\n1 1\nEND\n\t	\N	\N	\N	0	\N	\N
SQUARE_EMPTY	3	0	\N	Type VECTOR\n  Points\n\t.1 .1\n\t.1 .9\n\t.9 .9\n\t.9 .1\n\t.1 .1\n  END	\N	\N	\N	0	\N	\N
TRIANGLE_EMPTY	3	0	\N	Type VECTOR\n  Points\n\t.1 .1\n\t.9 .1\n\t.9 .1\n\t.5 .9\n\t.1 .1\n  END	\N	\N	\N	0	\N	\N
PLUS_FILL	3	0	\N	TYPE VECTOR\nPOINTS\n    .1 .3\n    .5 .3\n    -99 -99\n    .3 .1\n    .3 .5\n    -99 -99\n    .5 .7\n    .9 .7\n    -99 -99\n    .7 .5\n    .7 .9\nEND	\N	\N	\N	0	\N	\N
SNOW	3	0	\N	Type VECTOR\n  Points\n\t0 .5\n\t1 .5\n\t-99 -99\n\t.2 0\n\t.8 1\n\t-99 -99\n\t.8 0\n\t.2 1\n  END\n\t\t	\N	\N	\N	0	\N	\N
HEXAGON_EMPTY	3	0	\N	Type VECTOR\n  Points\n\t.3 .1\n\t.8 .1\n\t1 .5\n\t.8 .9\n\t.3 .9\n\t.1 .5\n\t.3 .1\n  END	\N	\N	\N	0	\N	\N
HEXAGON_BEE	3	0	\N	Type VECTOR\n  Points\n\t.1 0\n\t.2 .2\n\t.1 .4\n\t0 .4\n\t-99 -99\n\t.2 .2\n\t.4 .2\n\t-99 -99\n\t.5 0\n\t.4 .2\n\t.5 .4\n\t.6 .4\n  END\n	\N	\N	\N	0	\N	\N
ICE	3	0	\N	Type VECTOR\n  Points\n\t0 .5\n    .5 1\n\t-99 -99\n\t0 0\n    1 .5\n\t-99 -99\n\t.5 0\n    0 1\n    -99 -99\n    .5 0\n    .5 1\n    -99 -99\n    0 0\n    0 .5\n  END\n	\N	\N	\N	0	\N	\N
HALF_SQUARE	3	0	\N	Type VECTOR\n  Points\n\t.2 1.8\n\t1.8 1.8\n\t1.8 .2\n  END	\N	\N	\N	0	\N	\N
DASH_DASH	3	0	\N	Type VECTOR\n  Points\n\t0 .9 \n\t.3 .9\n\t-99 -99\n\t.7 .9\n\t1 .9\n\t-99 -99\n\t.2 .4 \n\t.8 .4\n  END\n	\N	\N	\N	0	\N	\N
DASH_DASH_VERTICAL	3	0	\N	Type VECTOR\n  Points\n\t.9 0 \n\t.9 .3 \n\t-99 -99\n\t.9 .7 \n\t.9 1 \n\t-99 -99\n\t.4 .2 \n\t.4 .8 \n  END\n	\N	\N	\N	0	\N	\N
DASH_LINE	3	0	\N	Type VECTOR\n  Points\n\t0 .9 \n\t1 .9\n\t-99 -99\n\t.2 .4 \n\t.8 .4\n  END\n	\N	\N	\N	0	\N	\N
STREAMERS	3	0	\N	Type VECTOR\n  Points\n\t.1 .1\n    .4 .1\n\t-99 -99\n\t.9 .1\n    .6 .4\n\t-99 -99\n\t.1 .6 \n    .1 .9 \n    -99 -99\n\t.4 .6\n    .7 .9\n  END\n	\N	\N	\N	0	\N	\N
POINT_LINE_VERTICAL	3	0	\N	Type VECTOR\n  Points\n\t.9 0  \n\t.9 1 \n\t-99 -99\n\t .4 .4\n\t .4 .6\n  END\n	\N	\N	\N	0	\N	\N
DOUBLE_LINE_VERTICAL	3	0	\N	Type VECTOR\n  Points\n    .0 0  \n\t.0 1 \n\t-99 -99\n\t.3 0  \n\t.3 1 \n\t-99 -99\n\t1 0  \n\t1 1 \n  END\n	\N	\N	\N	0	\N	\N
ARROW2	2	0	\N	TYPE Vector\n\tPOINTS\n\t  0 0\n\t\t.5 .5\n\t\t0 1\n\tEND	\N	\N	\N	0	\N	\N
ARROWBACK2	2	0	\N	\tTYPE Vector\n\tPOINTS\n\t  1 1\n\t\t.5 .5\n\t\t1 0\n\tEND	\N	\N	\N	0	\N	\N
ARROW3	2	0	\N	TYPE VECTOR\n POINTS\n0 .5\n1 .5\n-99 -99\n.8 .7\n1 .5\n.8 .3\nEND	\N	\N	\N	0	\N	\N
HOURGLASS	2	0	\N	TYPE Vector\nFILLED TRUE\n\tPOINTS\n\t 0 0\n\t 0 1\n\t 1 0\n\t 1 1\n\t 0 0\n\tEND\n\tANTIALIAS true\n\t	\N	\N	\N	0	\N	\N
SQUARE_FILL	3	0	\N	Type VECTOR\nFILLED TRUE\n  Points\n\t.1 .1\n\t.1 .9\n\t.9 .9\n\t.9 .1\n\t.1 .1\n  END\n\t\t	\N	\N	\N	0	\N	\N
RIPARIE-CANNETO	3	0	\N	TYPE VECTOR\nPOINTS\n.3 0\n.3 1\n.7 1\nEND\n 	\N	\N	\N	0	\N	\N
VERTEX	3	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n\t1 8\n\t3 8\n\t3 9\n\t1 9\n\t1 8\n-99 -99\n\t7 8\n\t9 8\n\t9 9\n\t7 9\n\t7 8\n-99 -99\n\t4 1\n\t6 1\n\t6 2\n\t4 2\n\t4 1\nEND	\N	\N	\N	0	\N	\N
T	3	0	\N	TYPE VECTOR\nPOINTS\n.5 .5\n.5 1\t\n-99 -99\n0 .5\n1 .5\nEND	\N	\N	\N	0	\N	\N
DOUBLE_T	3	0	\N	TYPE VECTOR\nPOINTS\n.3 .5\n.3 1\t\n-99 -99\n.7 .5\n.7 1\t\n-99 -99\n0 .5\n1 .5\nEND	\N	\N	\N	0	\N	\N
D	3	0	\N	TYPE VECTOR\n FILLED TRUE\nPOINTS\n.5 0\n.5 1\n.3 .9\n.1 .7\n0 .5\n.1 .3\n.3 .1\n.5 0\nEND	\N	\N	\N	0	\N	\N
MONUMENTO	3	0	\N	TYPE VECTOR\nPOINTS\n.5 1\n.2 .3\n.2 .2\n.4 0\n.6 0\n.6 .2\n.6 .3\n.5 1\nEND	\N	\N	\N	0	\N	\N
VERTICAL	4	0	\N	TYPE VECTOR\nPOINTS\n.5 0\n.5 1\nEND	\N	\N	\N	0	\N	\N
HORIZONTAL	4	0	\N	TYPE VECTOR\nPOINTS\n0 .5\n1 .5\nEND	\N	\N	\N	0	\N	\N
SQUARE_HALF	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 0\n0 1\n1 0\n0 0\nEND	\N	\N	\N	0	\N	\N
IDRANTE	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n1 1\n-99 -99\n.2 1\n.2 .4\n.8 .4\n.8 1\n.2 1\n-99 -99\n.2 .8\n0 .8\n0 .6\n.2 .6\n-99 -99\n.8 .8\n1 .8\n1 .6\n.8 .6\n-99 -99\n0 .4\n1 .4\n.9 .2\n.7 0\n.3 0\n.1 .2\n0 .4\nEND	\N	\N	\N	0	\N	\N
PHOTO	4	0	\N	TYPE PIXMAP\n  IMAGE "../../symbols/photo.png"	\N	\N	\N	0	\N	\N
\.


--
-- Data for Name: symbol_ttf; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY symbol_ttf (symbol_ttf_name, font_name, symbolcategory_id, ascii_code, "position", symbol_ttf_image) FROM stdin;
\.


--
-- Data for Name: tb_import; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY tb_import (tb_import_id, catalog_id, conn_filter, conn_model, file_path) FROM stdin;
\.


--
-- Data for Name: tb_import_table; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY tb_import_table (tb_import_table_id, tb_import_id, table_name) FROM stdin;
\.


--
-- Data for Name: tb_logs; Type: TABLE DATA; Schema: gisclient; Owner: -
--

COPY tb_logs (tb_logs_id, tb_import_id, data, ora, log_info) FROM stdin;
\.


--
-- PostgreSQL database dump complete
--

