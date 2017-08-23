const path = require('path');
const fs = require('graceful-fs');
const denodeify = require('denodeify');
const UglifyJS = require('uglify-js');

const writeFile = denodeify(fs.writeFile);
const readFile = denodeify(fs.readFile);

require('polyfill-service').listAllPolyfills().then(function(polyfills){
	polyfills.map(function(fill) {
		var metaDataFile = __dirname + '/__dist/' + fill + '/meta.json';
		readFile(metaDataFile).then(function(data) {
			var data = JSON.parse(data);
			if (data.detectSource) {
				var minified = UglifyJS.minify('_(' + data.detectSource + ')', {
					fromString: true,
					compress: { screw_ie8: false },
					mangle: { screw_ie8: false },
					output: { screw_ie8: false, beautify: false }
				}).code;
				data.detectSourceMinified = minified.substring(2, minified.length - 2);
			} else{
				data.detectSourceMinified = '';
			}
			return writeFile(metaDataFile, JSON.stringify(data));
		}).catch(function(e){
			console.error(e);
		});
	})
});