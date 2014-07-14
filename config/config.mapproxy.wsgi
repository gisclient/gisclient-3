# -*- coding: utf-8 -*-

# WSGI module for use with Apache mod_wsgi or gunicorn

# # uncomment the following lines for logging
# # create a log.ini with `mapproxy-util create -t log-ini`
# from logging.config import fileConfig
# import os.path
# fileConfig(r'/opt/mapproxy/gwmapproxy/log.ini', {'here': os.path.dirname(__file__)})

#from mapproxy.wsgiapp import make_wsgi_app
#application = make_wsgi_app(r'/opt/mapproxy/gwmapproxy/mapproxy.yaml')

#activate_this = '/opt/wsgi-env/bin/activate_this.py'
activate_this = '/home/plone/Plone/Python-2.7/bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))

from mapproxy.multiapp import make_wsgi_app
from beaker.middleware import SessionMiddleware
from mapproxy.util.yaml import load_yaml_file
import mapproxy.config.config
from mapproxy.multiapp import make_wsgi_app
from mapproxy.client.cgi import CGIClient
from mapproxy.service.wms import PERMIT_ALL_LAYERS
from mapproxy.exception import RequestError
from urlparse import parse_qs
from urllib import urlencode, unquote
from json import dumps as jsdumps
from json import loads as jsloads
import Cookie
import requests
import os.path

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

class BaseProxy(object):
    """ """

    mapset = None

    def setup(self, environ):
        """ """
        uri = [p for p in environ['REQUEST_URI'].split('?')[0].split('/') if p]
        if len(uri)>1:
            # TODO: rename self.mapset in self.project
            self.mapset = uri[1]
        self.service = None if len(uri)<=2 else uri[2]
        return uri

class SessionProxy(BaseProxy):
    """ """

    _session = None # Session dict dedicated to the requested mapset
    __session = None # The whole session
    first_call = True
    service = None
    conf = None

    def setup(self, environ):
        """ """
        uri = BaseProxy.setup(self, environ)
        self.session(environ=environ)
        return uri

    def session(self, *args, **kw):
        """ Session management for setting/extract values.
        Returns single value or generator.
        """
        environ = None if not 'environ' in kw else kw.pop('environ')

        # Session setup
        if self._session is None:
            session = environ['beaker.session']
            if not self.mapset is None and not self.mapset in session:
                session[self.mapset] = dict()
            elif not self.mapset is None:
                self._session = session[self.mapset]
            self.__session = session

        # Setting values in session
        for key, value in kw.items():
            self._session[key] = value
        if len(kw)>0: self.__session.save()

        if len(args)==0:
            return self._session

        out = (self._session.get(k) for k in args)
        if len(args)==1:
            return out.next()
        else:
            return out

class LoginProxy(SessionProxy):
    """ """
    auth = dict(wms={}, wfs={})

    def __call__(self, environ, start_response=None, reset=True):
        self.setup(environ)
        self.load_auth(environ, reset=reset)
        if not start_response is None:
            start_response('200 OK', [('Content-Type', 'text/ascii')])
        return ['Authorization info updated!']

    def _update_wms_auth(self, name, parameters=None, nodes=None, options=None, map=True, featureinfo=True, legendgraphic=True, **kw):
        """ DEPRECATED """

        permits = {
            'map': map,
            'featureinfo': featureinfo,
            'legendgraphic': legendgraphic
        }
        main_key = name
        singleTile = (not options is None and options.get('singleTile')==True)
        if not nodes is None:
            for layer_info in nodes:
                if singleTile:
                    key = layer_info['layer']
                else:
                    key = '.'.join((name, layer_info['layer'], ))
                self.auth['wms'][key] = permits

        if singleTile:
            main_key += '_tiles'
        self.auth['wms'][main_key] = permits

    def load_auth(self, environ, reset=False):
        """ """
        if reset or not 'auth' in self._session:
            self._init_auth(environ)
        else:
            self.auth = self._session['auth']
            self.first_call = False

    #def _call_gcmap(self, environ):
        #""" DEPRECATED: Query the gcmap service. Returns dictionary """
        #url = '%(wsgi.url_scheme)s://%(SERVER_NAME)s/gisclient/services/gcmap.php' % environ
        #data = dict(parse_qs(environ['QUERY_STRING']), mapset=self.mapset)
        #result = requests.post(url, data=data)
        #assert result.status_code==200, result.text
        #return result.json()

    def _call_gcauth(self, environ):
        """ Query the gcauthorized service. Returns dictionary """
        url = '%(wsgi.url_scheme)s://%(SERVER_NAME)s/gisclient/services/gcauthorized.php' % environ
        data = dict(parse_qs(environ['QUERY_STRING']), mapset=self.mapset)
        result = requests.post(url, data=data)
        assert result.status_code==200, result.text
        return result.json()

    def _init_auth(self, environ):
        """ Load the authorization information from gcauthorized service """
        self.auth = LoginProxy.auth # reset the auth value
        self.auth = self._call_gcauth(environ)
        # Update values in session
        self.session(environ=environ, auth=self.auth)
        return True

    def _init_auth_old(self, environ):
        """ DEPRECATED: Load the authorization information from gcmap service """
        self.auth = LoginProxy.auth # reset the auth value
        result = self._call_gcmap(environ)
        # WMS
        for authorized_layer in result['layers']:
            # TODO: distinguere i diversi permessi possibili:
            #       map, featureinfo, legendgraphic
            # Per ora abilito o tutto o niente
            # WARNING: l'autorizzazione nel caso WMS ha forma standard!!
            self._update_wms_auth(**authorized_layer)
        # WFS
        for authorized_layer in result['featureTypes']:
            # TODO: distinguere i diversi permessi possibili:
            #       quali??
            # Per ora abilito o tutto o niente
            self.auth['wfs'][authorized_layer['typeName']] = {
                'feature': True,
            }
        # Update values in session
        self.session(auth=self.auth)
        return True

class WFSProxy(SessionProxy):
    """ """

    def load_configuation(self, environ):
        """ Loads app configuration """
        # ### TODO ###
        # spostare alla sola classe WFS per il caricamento delle
        # configurazioni di mapserver
        # ###
        #if not self._session is None and 'conf' in self._session:
            #self.conf = self.session('conf')
        #else:
            #app_conf = self.app.loader.app_conf(self.mapset)
            #assert not app_conf is None, "Warning! No configuration found related to requested application: %s" % self.mapset
            #assert os.path.isfile(app_conf['mapproxy_conf']), "Warning! No file found: %s" % app_conf['mapproxy_conf']
            #conf = load_yaml_file(app_conf['mapproxy_conf'])
            #assert conf, "Warning! Configuration file cannot be loaded (%s)" % app_conf['mapproxy_conf']
            #if self._session is None:
                #self.session(environ=environ)
            #self.session(conf=conf)
            #self.conf = self._session['conf']
        self.bin = "/usr/lib/cgi-bin/mapserv"
        self.working_directory = "/apps/GisClient-3.3/map/geoweb_genova"

    def setup(self, environ):
        SessionProxy.setup(self, environ)
        self.load_configuation(environ)

    def call_mapserver(self, query):
        filtered_layers = self.get_filtered_layers(query)
        query_string = _get_query_string(query, map = '%s.map' %self.mapset, **filtered_layers)

        url = "http://localhost?" + query_string
        client = CGIClient(script=self.bin, working_directory=self.working_directory)
        return client.open(url=url)

    def __call__(self, environ, start_response):
        """ The WFS proxy service """

        self.setup(environ)
        query = _parse_query_string(environ['QUERY_STRING'])

        res = self.call_mapserver(query)
        content_type = res.headers.get('Content-type') or "text/ascii"
        start_response('200 OK', [('Content-Type', content_type)])
        return [res.getvalue()]

    def get_filtered_layers(self, query):
        """ TODO """
        return self.get_requested_layers(query)

    def get_requested_layers(self, query):
        """ Returns dict. """
        if "VERSION" in query and query["VERSION"] > "1.1.0":
            layer_key = "TYPENAMES"
        else:
            layer_key = "TYPENAME"

        if layer_key in query:
            res = query[layer_key]
            if isinstance(res, (list, tuple, )):
                return {layer_key: res}
            else:
                return {layer_key: [res]}
        else:
            return {}

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

class AuthProxy(LoginProxy):
    """ """

    def __init__(self, app):
        self.app = app

    def _TEST_(self, value, environ, start_response, **kw):
        """ """
        ct = 'json'
        headers = lambda ct: {'Content-Type': 'text/%s' % ct}
        if value == 'auth':
            if len(self.auth['wms'])==0:
                self.load_auth(environ, reset=True)
            out = jsdumps(dict(first_call=self.first_call, auth=self.auth))
        else:
            out = jsdumps(getattr(self, value))
        start_response('200 OK', headers(ct).items())
        return [out]

    def setup(self, environ):
        self.login = LoginProxy()
        return LoginProxy.setup(self, environ)

    def __call__(self, environ, start_response):
        """ """
        url_parts = self.setup(environ)
        query = _parse_query_string(environ['QUERY_STRING'])

        # ### TEST
        if 'TEST' in query:
            return self._TEST_(query['TEST'].lower(), environ, start_response)
        # ###

        # 1. if no specific app name given use the basic app
        if self.service=='demo' or (self.service is None and not query):
            return self.app(environ, start_response)

        # 2.
        if self.service=='login': return self.login(environ, start_response)
        # 3.
        if self.service in ('gcmap', 'gcauth', 'gcauthorized'):
            start_response('200 OK', [
                ('Content-Type', 'application/json'),
                ('Charset', 'UTF-8')
            ])
            return [jsdumps(self._call_gcauth(environ))]

        # DEPRECATED
        #self.load_configuation(environ)

        # TODO: per ora evito questioni di autorizzazione.
        self.load_auth(environ)

        # TODO
        environ['wfs'] = WFSProxy()
        service = query['SERVICE'].upper() if 'SERVICE' in query else None
        # 4.
        if service=='WFS':
            return environ['wfs'](environ, start_response)
        else:
            return self.app(environ, start_response)

    def load_auth(self, environ, reset=False):
        LoginProxy.load_auth(self, environ, reset)
        environ['mapproxy.authorize'] = self.proxy_authorize # authorize callback

    def proxy_authorize(self, service, *args, **kw):
        """ """

        # ### TEST ###
        if self.service=='demo': return {'authorized': 'full'}
        # ###

        if service.startswith('wms.'):
            return self.auth['wms']
        else:
            return {'authorized': 'full'}

    def authorize(self, service, layers=[], environ=None, **kw):
        """ DEPRECATED The auth callback """

        # ### TEST ###
        #return {'authorized': 'full'}
        # ###

        # TODO: considerare l'opzione utente non-autenticato se utile
        # ### {'authorized': 'unauthenticated'} ###

        # ### TEST ###
        if self.service=='demo': return {'authorized': 'full'}
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

config_dir = '/apps/GisClient-3.3/mapproxy/'
application = make_wsgi_app(config_dir, allow_listing=True)
application = AuthProxy(application)
# Configure the SessionMiddleware
file_session_opts = {
    'session.data_dir': '/tmp',
    'session.type': 'file',
    'session.cookie_expires': True,
}
cm_session_opts = {
    'session.type': 'ext:memcached',
    'session.url': '127.0.0.1:11211',
    'session.lock_dir': '/tmp',
    'session.cookie_expires': True,
}
application = SessionMiddleware(application, file_session_opts)