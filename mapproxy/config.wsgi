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

    return dict([(k.upper(), _getfirst(v)) for k,v in parse_qs(qstring).items()])

def _get_query_string(query, **kw):
    if isinstance(query, basestring):
        query = _parse_query_string(query)
    query.update(kw)
    return unquote(urlencode(query))

class WFSProxy(object):

    def __init__(self, app_name, msconf):

        self.bin = msconf['binary']
        self.working_directory = msconf['working_dir']
        self.map = os.path.join(msconf['working_dir'], app_name) + '.map'

    def __call__(self, environ, start_response):
        """ The WFS proxy service """
        query = _parse_query_string(environ['QUERY_STRING'])

        requested_layers = self._get_requested_layer_list(query)
        authorized_layers, _ = self.authorized_layers('feature', [], environ, None)

        #start_response('200 OK', [('Content-Type', "text/ascii")])
        #return [jsdumps(authorized_layers)]
        filtered_layers, key = self.filter_requested_layers(query, authorized_layers)
        if not key is None:
            kw = {key: filtered_layers}
            query_string = _get_query_string(query, map = self.map, **kw)
        else:
            query_string = _get_query_string(query, map = self.map)

        url = "http://localhost?" + query_string
        client = CGIClient(script=self.bin, working_directory=self.working_directory)
        res = client.open(url=url)
        content_type = res.headers.get('Content-type') or "text/ascii"
        start_response('200 OK', [('Content-Type', content_type)])
        return [res.getvalue()]

    def _get_requested_layer_list(self, query):
        if "VERSION" in query and query["VERSION"] > "1.1.0":
            layer_key = "TYPENAMES"
        else:
            layer_key = "TYPENAME"

        if layer_key in query:
            res = query[layer_key]
            if isinstance(res, (list, tuple, )):
                return res, layer_key
            else:
                return [res], layer_key
        else:
            return [], None

    def filter_requested_layers(self, query, authorized_layers):
        requested_layers, key = self._get_requested_layer_list(query)
        if authorized_layers is PERMIT_ALL_LAYERS:
            return [l for l in requested_layers], key
        else:
            return [l for l in requested_layers if l in authorized_layers], key

    def authorized_layers(self, feature, layers, env, query_extent):
        """ Courtesy of mapproxy.service.WMSServer.authorized_layers """
        if 'mapproxy.authorize' in env:
            result = env['mapproxy.authorize']('wfs.' + feature, layers[:],
                environ=env, query_extent=query_extent)
            if result['authorized'] == 'unauthenticated':
                raise RequestError('unauthorized', status=401)
            if result['authorized'] == 'full':
                return PERMIT_ALL_LAYERS, None
            layers = {}
            if result['authorized'] == 'partial':
                for layer_name, permissions in result['layers'].iteritems():
                    if permissions.get(feature, False) == True:
                        layers[layer_name] = permissions.get('limited_to')
            limited_to = result.get('limited_to')
            if limited_to:
                coverage = load_limited_to(limited_to)
            else:
                coverage = None
            return layers, coverage
        else:
            return PERMIT_ALL_LAYERS, None

class AuthProxy(object):

    wfs_auth = {}
    wms_auth = {}
    cname = 'wms_layer_auth'

    def __init__(self, app):
        self.app = app
        self.conf = {}

    def __call__(self, environ, start_response):
        self._load_configuation(environ)
        headers = {'Content-Type': 'text/ascii'}
        cookies = self.load_auth(environ)
        query = _parse_query_string(environ['QUERY_STRING'])
        # TODO: capire perché :-)
        environ['wfs'] = WFSProxy(self.app_name, self.conf['sources']['mapserver_source']['mapserver'])

        # TEST ###
        if 'TEST' in query:

            if  query['TEST'].lower()=='url':
                out = self._get_auth_info_from_gcmap(environ)
            if query['TEST'].lower()=='gcmap':
                headers['Content-Type'] = 'text/json'
                out = jsdumps(self._test_gcmap(environ))
            if query['TEST'].lower()=='env':
                out = str(dict(environ))

            start_response('200 OK', headers.items()+cookies)
            return [out]
        # ###

        another_start_response = lambda s,h: start_response(s, h+cookies)

        environ['mapproxy.authorize'] = self.authorize # authorize callback
        service = query['SERVICE'].upper() if 'SERVICE' in query else None
        if service=='WFS':
            return environ['wfs'](environ, another_start_response)
        else:
            return self.app(environ, another_start_response)

    def _load_configuation(self, environ):
        """ Loads app configuration from file """
        self.app_name = environ['REQUEST_URI'].split('?')[0].split('/')[2]
        self.conf = load_yaml_file(self.app.loader.app_conf(self.app_name)['mapproxy_conf'])

    def _test_gcmap(self, environ):
        """ WARNING: for TEST only """
        res = self._get_auth_info_from_gcmap(environ, cookie=None)

        if res.status_code==200:
            return res.json()
        else:
            return res.text

    def _get_auth_info_from_gcmap(self, environ, cookie=None):
        """ Query the gcmap service and returns the loaded json result """
        url = '%(wsgi.url_scheme)s://%(SERVER_NAME)s/gisclient/services/gcmap.php' % environ
        data = dict(mapset=self.app_name)
        if not cookie is None and 'gisclient3' in cookie:
            data['gisclient3'] = cookie['gisclient3'].value
            self.cname = cookie['gisclient3'].value
        result = requests.post(url, data=data)
        return result

    def load_auth(self, environ):
        """ TODO
        Load the authorization informations into wms_auth and wfs_auth parameters.
        Returns cookie string.
        """
        # ### TODO ###: se non esiste scrivere la informazioni di autorizzazione
        # dei layer in un cookie e restituire il valore del cookie se esiste invece
        # di fare la chiamata tutte le volte
        cookie = Cookie.SimpleCookie()
        if 'HTTP_COOKIE' in environ:
            cookie.load(environ['HTTP_COOKIE'])

        if not 'gisclient3' in cookie or cookie['gisclient3'].value in cookie:
            if not self.cname in cookie:
                self.load_auth_from_gcmap(environ, cookie)
                cookie[self.cname] = self.get_wms_auth_string()
            else:
                self.load_auth_from_cookie(cookie)
        else:
            self.load_auth_from_gcmap(environ, cookie)
            cookie[self.cname] = self.get_wms_auth_string()

        return [tuple(str(cookie[self.cname]).split(': '))]

    def load_auth_from_cookie(self, cookie):
        """ Load the authorization informations into wms_auth (TODO: and wfs_auth)
        parameter from cookie
        """
        auths = cookie[self.cname].value
        for n, layer in enumerate(self.get_ordered_layers()):
            bin_auth = '{0:03b}'.format(int(auths[n])) # dec(1) -> bin(3)
            bool_auth = (bool(int(i)) for i in bin_auth)
            if any(bool_auth):
                self.wms_auth[layer] = dict((k,v) for k,v in zip(('legendgraphic', 'featureinfo', 'map', ), bool_auth) if v)

    def get_wms_auth_string(self):
        """ """
        # TODO: codificare i diversi permessi considerando le combinazioni di
        # 3 cifre binarie come interi da 0 (-> 000) a 7 (-> 111)
        # per ora considero solo gli estremi 0 (tutto negato) e 7 (tutto permesso).
        value = ''
        for layer in self.get_ordered_layers():
            if layer in self.wms_auth:
                value += '7'
            else:
                value += '0'
        return value

    def load_auth_from_gcmap(self, environ, cookie):
        """ Load the authorization informations into wms_auth and wfs_auth
        parameters from gcmap service
        """

        def _append_wms(name, parameters=None, options=None,
            map=True, featureinfo=True, legendgraphic=True, **kw):
            """ """
            permits = {
                'map': map,
                'featureinfo': featureinfo,
                'legendgraphic': legendgraphic
            }
            main_key = name
            singleTile = False
            if not parameters is None and 'layers' in parameters:
                singleTile = (not options is None and options.get('singleTile')==True)
                for layer in parameters['layers']:
                    if singleTile:
                        key = layer
                    else:
                        key = '.'.join((name, layer, ))
                    self.wms_auth[key] = permits
            if singleTile:
                main_key += '_tiles'
            self.wms_auth[main_key] = permits

        res = self._get_auth_info_from_gcmap(environ, cookie)
        if res.status_code==200:
            result = res.json()
            # WMS
            for authorized_layer in result['layers']:
                # TODO: distinguere i diversi permessi possibili:
                #       map, featureinfo, legendgraphic
                # Per ora abilito o tutto o niente
                # WARNING: l'autorizzazione nel caso WMS ha forma standard!!
                _append_wms(**authorized_layer)
            # WFS
            for authorized_layer in result['featureTypes']:
                # TODO: distinguere i diversi permessi possibili:
                #       quali??
                # Per ora abilito o tutto o niente
                self.wfs_auth[authorized_layer['typeName']] = {
                    'feature': True,
                }

    def get_ordered_layers(self):
        """ Returns layer iterator """

        for layer in self.conf["layers"]:
            if not 'layers' in layer:
                yield layer['name']
            else:
                for sub_layer in layer['layers']:
                    yield sub_layer['name']

    def authorize(self, service, layers=[], environ=None, **kw):
        """ """

        # TODO: considerare l'opzione utente non-autenticato se utile
        # ### {'authorized': 'unauthenticated'} ###

        # ### TEST ###
        if environ['REQUEST_URI'].split('?')[0].split('/')[3] == 'demo':
            return {'authorized': 'full'}
        # ###

        allowed = denied = False
        auth_layers = {}

        for layer in (layers or self.get_layer_infos()):
            if service.startswith('wms.'):
                if layer in self.wms_auth:
                    auth_layers[layer] = self.wms_auth[layer]
                    allowed = True
                else:
                    auth_layers[layer] = {}
                    denied = True

        if allowed and not denied:
            return {'authorized': 'full'}
        if denied and not allowed:
            return {'authorized': 'none'}
        return {'authorized': 'partial', 'layers': auth_layers}

class SimpleRequestFilter(object):
    """ WARNING: Not used """

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


activate_this = '/home/plone/Plone/Python-2.7/bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))
from mapproxy.multiapp import make_wsgi_app
application = make_wsgi_app('/apps/GisClient-3.3/mapproxy/', allow_listing=True)
application = AuthProxy(application)
