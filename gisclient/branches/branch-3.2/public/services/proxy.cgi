	#!/usr/bin/env python
2 	
3 	
4 	"""This is a blind proxy that we use to get around browser
5 	restrictions that prevent the Javascript from loading pages not on the
6 	same server as the Javascript.  This has several problems: it's less
7 	efficient, it might break some sites, and it's a security risk because
8 	people can use this proxy to browse the web and possibly do bad stuff
9 	with it.  It only loads pages via http and https, but it can load any
10 	content type. It supports GET and POST requests."""
11 	
12 	import urllib2
13 	import cgi
14 	import sys, os
15 	
16 	# Designed to prevent Open Proxy type stuff.
17 	
18 	allowedHosts = ['www.openlayers.org', 'openlayers.org',
19 	                'labs.metacarta.com', 'world.freemap.in',
20 	                'prototype.openmnnd.org', 'geo.openplans.org',
21 	                'sigma.openplans.org', 'demo.opengeo.org',
22 	                'www.openstreetmap.org', 'sample.azavea.com',
23 	                'v-swe.uni-muenster.de:8080']
24 	
25 	method = os.environ["REQUEST_METHOD"]
26 	
27 	if method == "POST":
28 	    qs = os.environ["QUERY_STRING"]
29 	    d = cgi.parse_qs(qs)
30 	    if d.has_key("url"):
31 	        url = d["url"][0]
32 	    else:
33 	        url = "http://www.openlayers.org"
34 	else:
35 	    fs = cgi.FieldStorage()
36 	    url = fs.getvalue('url', "http://www.openlayers.org")
37 	
38 	try:
39 	    host = url.split("/")[2]
40 	    if allowedHosts and not host in allowedHosts:
41 	        print "Status: 502 Bad Gateway"
42 	        print "Content-Type: text/plain"
43 	        print
44 	        print "This proxy does not allow you to access that location (%s)." % (host,)
45 	        print
46 	        print os.environ
47 	 
48 	    elif url.startswith("http://") or url.startswith("https://"):
49 	   
50 	        if method == "POST":
51 	            length = int(os.environ["CONTENT_LENGTH"])
52 	            headers = {"Content-Type": os.environ["CONTENT_TYPE"]}
53 	            body = sys.stdin.read(length)
54 	            r = urllib2.Request(url, body, headers)
55 	            y = urllib2.urlopen(r)
56 	        else:
57 	            y = urllib2.urlopen(url)
58 	       
59 	        # print content type header
60 	        i = y.info()
61 	        if i.has_key("Content-Type"):
62 	            print "Content-Type: %s" % (i["Content-Type"])
63 	        else:
64 	            print "Content-Type: text/plain"
65 	        print
66 	       
67 	        print y.read()
68 	       
69 	        y.close()
70 	    else:
71 	        print "Content-Type: text/plain"
72 	        print
73 	        print "Illegal request."
74 	
75 	except Exception, E:
76 	    print "Status: 500 Unexpected Error"
77 	    print "Content-Type: text/plain"
78 	    print
79 	    print "Some unexpected error occurred. Error text was:", E