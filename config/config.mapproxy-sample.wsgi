#path of your virtual env
activate_this = '/opt/mapproxy/bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))
from mapproxy.multiapp import make_wsgi_app
#path of mapproxy folder
application = make_wsgi_app('.......GisClient-3.3/mapproxy/', allow_listing=True)

