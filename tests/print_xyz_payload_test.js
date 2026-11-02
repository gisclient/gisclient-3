// Run with: node tests/print_xyz_payload_test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function Control() {}
Control.ModifyFeature = function () {};
Control.Button = function () {};
const context = {
    OpenLayers: {
        Control,
        Class: (_base, methods) => methods,
    },
    $: () => ({ val: () => 'no' }),
    console,
};
context.$.each = (items, callback) => items.forEach((item, index) => callback(index, item));
vm.createContext(context);
vm.runInContext(fs.readFileSync('public/services/print/PrintMap.js', 'utf8'), context);

const xyz = {
    CLASS_NAME: 'OpenLayers.Layer.XYZ',
    options: { xyzLayergroupId: 42 },
    opacity: 0,
    getVisibility: () => true,
};
const wms = {
    CLASS_NAME: 'OpenLayers.Layer.WMS',
    url: 'https://wms.example/service',
    params: { LAYERS: 'trees' },
    options: {},
    opacity: 1,
    getVisibility: () => true,
};
const controller = {
    formId: 'print',
    map: {
        config: { projectName: 'customer', mapsetName: 'customer_map' },
        layers: [xyz, wms, { ...xyz, getVisibility: () => false }],
    },
};
const tiles = JSON.parse(JSON.stringify(context.OpenLayers.Control.PrintMap.getTiles.call(controller)));
assert.equal(tiles.length, 2);
assert.equal(tiles[0].type, 'WMS');
assert.deepEqual(tiles[0].parameters.LAYERS, ['trees']);
assert.equal(tiles[1].service, 'XYZ');
assert.equal(tiles[1].opacity, 0);
assert.deepEqual(tiles[1].parameters, {
    PROJECT: 'customer', MAP: 'customer_map', LAYERGROUP_ID: 42,
});
assert.equal(tiles[1].url, undefined); // The backend must resolve the configured URL.
console.log('Mixed WMS/XYZ print payload: order, visibility, opacity and configured source ID passed');
