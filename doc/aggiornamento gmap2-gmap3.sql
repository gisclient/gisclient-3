update gisclient_31.layergroup set owstype_id=7 where owstype_id=2;
update gisclient_31.layergroup set layers='satellite' where layers='G_SATELLITE_MAP';
update gisclient_31.layergroup set layers='roadmap' where layers='G_NORMAL_MAP';
update gisclient_31.layergroup set layers='hybrid' where layers='G_HYBRID_MAP';
update gisclient_31.layergroup set layers='terrain' where layers='G_PHYSICAL_MAP';

