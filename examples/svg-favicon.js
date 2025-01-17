let path, origin, toCache;

addEventListener("fetch", event => {
	event.respondWith(handleRequest(event, event.request));
	//if(toCache)event.waitUntil(cacheRequest(event, event.request));
});


/**
 * 
 * @param {Event} event - Event
 * @returns {Promise} Promise
 */
async function cacheRequest(event){
	await KV.put(path, await toCache.arrayBuffer());
}

/**
 * 
 * @param {Event} event - Event
 * @param {Request} request - Request
 * @returns {Promise<Response>} Promised Response
 */
async function handleRequest(event, request){
	_url = new URL(request.url);
	path = _url.pathname;
	origin = _url.origin;
	if(path.startsWith('/svg/')){
		return new Response(`<svg width="128" height="128"><text x="20" y="35">${path.slice(5)}</text></svg>`, {headers: {'Content-Type': 'image/svg+xml', 'X-Type': 'generated'}});
	} else {
		const {value: cachedValue, metadata} = await KV.getWithMetadata(path, {type: 'arrayBuffer'});
		if(cachedValue !== null)return new Response(cachedValue, {headers: {'Content-Type': 'image/png', 'X-Type': 'cached'}});

		let a = await fetch(`https://images.weserv.nl/?output=png&url=${origin}/svg/${path.slice(1)}`);
		let obj={};for(let pair of a.headers.entries()){obj[pair[0]]=pair[1]}
		toCache = a.clone();
		event.waitUntil(cacheRequest(event));
		//await KV.put(path, await a.clone().arrayBuffer()/*, {metadata: obj}*/);
		//a.headers.set('X-Type', 'fetched');
		//return a;
		return new Response(a.body, {headers: {...a.headers, 'X-Type': 'fetched'}});
	}
}