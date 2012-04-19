OpenLayers.Protocol.HTTP.prototype.create = function(features, options) {
        options = OpenLayers.Util.applyDefaults(options, this.options);

        var resp = new OpenLayers.Protocol.Response({
            reqFeatures: features,
            requestType: "create"
        });

		var params = options.params;
		params.features = this.format.write(features)

        resp.priv = OpenLayers.Request.POST({
            url: options.url,
            callback: this.createCallback(this.handleCreate, resp, options),	
            headers: options.headers,
			data:OpenLayers.Util.getParameterString(options.params),
			headers: {"Content-Type": "application/x-www-form-urlencoded"}
        });
        return resp;
    };
	
 OpenLayers.Strategy.Save.prototype.save = function(features) {
        if(!features) {
            features = this.layer.features;
        }
        this.events.triggerEvent("start", {features:features});
		
		
		//Possiamo evitare la riproiezione
		
		/*
        var remote = this.layer.projection;
        var local = this.layer.map.getProjectionObject();
        if(!local.equals(remote)) {
            var len = features.length;
            var clones = new Array(len);
            var orig, clone;
            for(var i=0; i<len; ++i) {
                orig = features[i];
                clone = orig.clone();
                clone.fid = orig.fid;
                clone.state = orig.state;
                if(orig.url) {
                    clone.url = orig.url;
                }
                clone._original = orig;
                clone.geometry.transform(local, remote);
                clones[i] = clone;
            }
            features = clones;
        }
		*/	

		var commitOptions = {callback: this.onCommit,scope: this};
		//TODO VEDERE I TIPI .....
		if(this.create) commitOptions["create"] = this.create;
		if(this.update) commitOptions["update"] = this.update;
		if(this["delete"]) commitOptions["delete"] = this["delete"];
        this.layer.protocol.commit(features,commitOptions);
    };
	
	
