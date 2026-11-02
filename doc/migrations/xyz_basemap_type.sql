-- Optional, idempotent registration for testing XYZ basemaps.
INSERT INTO gisclient_34.e_owstype (owstype_id, owstype_name, owstype_order)
VALUES (9, 'XYZ', 9)
ON CONFLICT (owstype_id) DO NOTHING;
