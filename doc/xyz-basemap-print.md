# XYZ raster basemaps (experimental)

The print/image compositor supports standard 256px Web Mercator XYZ PNG/JPEG
tiles over HTTP(S). It uses GDAL's TMS driver with `YOrigin=top`, not a WMS
request to the tile host. PMTiles, vector tiles, signed cookies and custom tile
grids are outside this initial implementation.

## Configure a test

1. Apply `doc/migrations/xyz_basemap_type.sql` if XYZ is absent from `e_owstype`.
2. Use a dedicated test layergroup with `owstype_id=9`, `isbaselayer=1`, and
   `url=https://your-host/ortho/${z}/${x}/${y}.png`. Associate it with the test mapset.
   Both `${z}` and `{z}` syntax are normalized for OpenLayers and GDAL.
3. Set `GC_XYZ_MAX_ZOOM` on the backend to the actual highest published zoom
   (default 19, accepted integer range 0–22). This initial setting is shared by
   XYZ sources and used for both the browser server-resolution grid and printing.
4. Ensure the backend can reach the tile host and write `var/cache/xyz-print`.
5. Compare identical areas with WMS and XYZ in A4/A3 at 300 DPI, first cold,
   then warm. Check alignment, opacity, missing tiles, and output sharpness.

The backend reads the URL from the configured layergroup and verifies its mapset
and project membership. The print payload uses:

```json
{"service":"XYZ","parameters":{"PROJECT":"test","MAP":"test_map","LAYERGROUP_ID":123},"opacity":100}
```

This payload can also be supplied to static-image consumers using `MapImage`.
External clients need to send it explicitly; updating Author does not update them.
Tile responses 204/404 become transparent, other HTTP failures remain errors.
In the local GDAL 3.8 smoke test, missing tiles produced transparent pixels but
also emitted cache-read errors. Verify missing-tile behavior on the deployed
GDAL/MapServer versions before using sparse coverage in production.
GDAL's persistent tile cache lives under `var/cache/xyz-print/tiles`; manage its
disk usage and clear it after replacing tiles at unchanged URLs.

## Coordinate systems

The XYZ source is always EPSG:3857. The print layer declares that projection;
MapServer reprojects it to the requested output CRS. Test a known point in each
customer CRS; its transformation and any required grids must be installed.

The legacy browser client does not reproject XYZ rasters. Direct XYZ display is
intended for mapsets using EPSG:3857 (or its 900913 alias) and matching zooms.
Other mapset CRSs are explicitly rejected for direct browser XYZ configuration.
The browser uses the global Web Mercator extent and the full server-resolution
list; it does not assume that the map's zoom index equals the source zoom index.
The layer retains the mapset's configured display resolutions, independently of
the source resolutions, so selecting XYZ does not replace the map's scale ladder.
For local-CRS mapsets, retain a WMS facade such as MapProxy. Do not switch an
existing customer mapset to XYZ solely to test printing.

Existing WMS/WMTS printing is retained. Deploying the code does not convert any
existing basemap, remove services, or change customer SRIDs.

## Validation

With Composer dependencies and MapScript installed:

```sh
vendor/bin/phpunit -c phpunit.xml.dist --filter 'XyzPrint'
node tests/print_xyz_payload_test.js
```

The tests cover URL normalization, the source's global grid, zoom validation,
configured project/mapset membership, XYZ-only legends and project identification,
static-image payloads, and mixed WMS/XYZ order, visibility and opacity.
PHP tests that exercise `MapImage` need the MapScript constants and otherwise skip.
They do not replace an end-to-end PDF test on a running Author deployment.
