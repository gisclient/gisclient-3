import mapscript
mapobj=mapscript.mapObj()
outputFormat=mapscript.outputFormatObj("GD/PNG")
outputFormat.name="png24"
outputFormat.setMimetype("image/png")
outputFormat.imagemode=1
outputFormat.setExtension("png")
mapobj.legend.status=1
mapobj.legend.keysizex=100
mapobj.legend.keysizey=30
mapobj.legend.keyspacingy=20
mapobj.legend.width=100
mapobj.setSize(600,1200)
mapobj.setExtent(132771,447652,134477,451064)
mapobj.setOutputFormat(outputFormat)
style=mapscript.styleObj()
style.symbolname="point5"
style.color=mapscript.colorObj(225,0,0)
cls=mapscript.classObj()
cls.insertStyle(style)
cls.name="testclass"
cls.setExpression("(4 + 4)")
mapobj.setSymbolSet('test_symbols.set')








#! /usr/bin/python

# mimic the Mapnik 'hello world' at
# http://trac.mapnik.org/wiki/GettingStarted
# based on example in Web Mapping Illustrated

import mapscript

# create a base mapfile
map = mapscript.mapObj()
map.selectOutputFormat("AGGPNG24")
map.name<http://map.name> = "CustomMap"
map.setSize(1000, 500)
#map.setExtent(-180.0, -90.0, 180.0, 90.0) map.setExtent(-22, -36, 60, 38)
map.imagecolor.setRGB(70,130,180)
map.units = mapscript.MS_DD

# set Web image params
map.web.imagepath = "/var/www/tmp/"
map.web.imageurl = "/tmp"

# create layer object
layer = mapscript.layerObj(map)
layer.name<http://layer.name> = "countries"
#layer.type = mapscript.MS_LAYER_LINE
layer.type = mapscript.MS_LAYER_POLYGON
layer.status = mapscript.MS_DEFAULT
layer.data = "/home/randre/gis_data/unep/unep_coastlines.shp"
layer.template = "template.html"

# create a class
class1 = mapscript.classObj(layer)
class1.name<http://class1.name> = "Countries"

# create a style
style = mapscript.styleObj(class1)
style.outlinecolor.setRGB(125,125,125)
style.width = 1
style.color.setRGB(240,240,240)
#style.antialias = mapscript.MS_TRUE

# write the map object into a map file
#map.save("custom.map")

# write out an image using these params
mapimage = map.draw()
mapimage.save("ms_world.png")
--

