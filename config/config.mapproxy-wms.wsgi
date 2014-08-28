activate_this = '/home/plone/Plone/Python-2.7/bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))
from mapproxy.multiapp import make_wsgi_app
application = make_wsgi_app('/home/robystar/GisClient-3.3/mapproxy/', allow_listing=True)

