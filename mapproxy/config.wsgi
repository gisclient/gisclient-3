# WSGI module for use with Apache mod_wsgi or gunicorn

# # uncomment the following lines for logging
# # create a log.ini with `mapproxy-util create -t log-ini`
# from logging.config import fileConfig
# import os.path
# fileConfig(r'/opt/mapproxy/gwmapproxy/log.ini', {'here': os.path.dirname(__file__)})

#from mapproxy.wsgiapp import make_wsgi_app
#application = make_wsgi_app(r'/opt/mapproxy/gwmapproxy/mapproxy.yaml')


class SimpleAuthFilter(object):
    """
    Simple MapProxy authorization middleware.

    It authorizes WMS requests for layers where the name does
    not start with `prefix`.
    """
    def __init__(self, app, prefix='secure'):
        self.app = app
        self.prefix = prefix

    def __call__(self, environ, start_reponse):
        # put authorize callback function into environment
        environ['mapproxy.authorize'] = self.authorize
        return self.app(environ, start_reponse)

    def authorize(self, service, layers=[], environ=None, **kw):
        allowed = denied = False
        if service.startswith('wms.'):
            auth_layers = {}
            for layer in layers:
                if layer.startswith(self.prefix):
                    auth_layers[layer] = {}
                    denied = True
                else:
                    auth_layers[layer] = {
                        'map': True,
                        'featureinfo': True,
                        'legendgraphic': True,
                    }
                    allowed = True
        else: # other services are allowed
          return {'authorized': 'true'}

        if allowed and not denied:
            return {'authorized': 'full'}
        if denied and not allowed:
            return {'authorized': 'none'}
        return {'authorized': 'partial', 'layers': auth_layers}

activate_this = '/home/plone/Plone/Python-2.7/bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))
from mapproxy.multiapp import make_wsgi_app
application = make_wsgi_app('/apps/GisClient-3.3/mapproxy/', allow_listing=True)
#application = SimpleAuthFilter(application, prefix='bc_')


