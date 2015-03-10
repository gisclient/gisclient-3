#path of your virtual env
activate_this = '/opt/mapproxy/bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))
from mapproxy.multiapp import make_wsgi_app
#mapproxy folder path
application = make_wsgi_app('.......gisclient-3/mapproxy/', allow_listing=True)

