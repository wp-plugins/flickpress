(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('flickpress');

	tinymce.create('tinymce.plugins.flickpress', {
		init : function(ed, url) {
			ed.addButton('flickpress', {
				title : 'Browse and Insert Flickr Photos',
				image : url + '/../../images/flickpress.png',
				onclick : function() {
					edflickpress();
				}
			});
		},
		createControl : function(n, cm) {
			return null;
		},

		getInfo : function() {
			return {
				longname : 'Browse and Insert Flickr Photos',
				author : 'Isaac Wedin',
				authorurl : 'http://familypress.net',
				infourl : 'http://familypress.net',
				version : "0.6"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('flickpress', tinymce.plugins.flickpress);
})();
