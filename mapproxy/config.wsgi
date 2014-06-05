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


def _parse_query_string(qstring):

    def _getfirst(value):
        if isinstance(value, (list, tuple, )):
            if len(value)==1:
                return value[0]
        return value

    return dict([(k, _getfirst(v)) for k,v in parse_qs(qstring).items()])

def _get_query_string(query, **kw):
    if isinstance(query, basestring):
        query = _parse_query_string(query)
    query.update(kw)
    return unquote(urlencode(query))

class WFSProxy(object):
    """ """

    def __init__(self, environ, app):
        self.app = app
        self.app_name = environ['REQUEST_URI'].split('?')[0].split('/')[-1]
        conf = load_yaml_file(app.loader.app_conf(self.app_name)['mapproxy_conf'])
        mapserver_conf = conf['sources']['mapserver_source']['mapserver']
        self.script = mapserver_conf['binary']
        self.working_directory = mapserver_conf['working_dir']
        self.map = '%s/%s.map' % (self.working_directory, self.app_name)

    def __call__(self, query, start_response):
        query_string = _get_query_string(query, map = self.map)
        url = "http://localhost?" + query_string
        client = CGIClient(script=self.script, working_directory=self.working_directory)
        res = client.open(url=url)
        content_type = res.headers.get('Content-type') or "text/ascii"
        start_response('200 OK', [('Content-Type', content_type)])
        return [res.getvalue()]


class SimpleRequestFilter(object):
    """ """

    def __init__(self, app):
        self.app = app

    def __call__(self, environ, start_response):
        # Add the callback to the WSGI environment

        query = _parse_query_string(environ['QUERY_STRING'])
        environ['wfs'] = WFSProxy

        asked_for_wfs = 'SERVICE' in query and query['SERVICE'].upper()=='WFS'

        if not asked_for_wfs:
            return self.app(environ, start_response)
        else:
            proxy = WFSProxy(environ, self.app)
            return proxy(query, start_response)

activate_this = '/opt/wsgi-env/bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))
from mapproxy.multiapp import make_wsgi_app
application = make_wsgi_app('/apps/GisClient-3.3/mapproxy/', allow_listing=True)
#application = SimpleAuthFilter(application, prefix='bc_')
application = SimpleRequestFilter(application)
