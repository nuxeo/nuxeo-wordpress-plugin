(function() {
	tinymce.PluginManager.add('nuxeo', function( editor, url ) {
		var sh_tag = 'nuxeo';

		//helper functions 
		function getAttr(s, n) {
			n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
			return n ?  window.decodeURIComponent(n[1]) : '';
		};

		function html( cls, data ,con) {
			//var placeholder = url + '/img/' + getAttr(data,'type') + '.jpg';
			var placeholder = url + '/img/nuxeo.png';
			data = window.encodeURIComponent( data );
			content = window.encodeURIComponent( con );

			return '<img src="' + placeholder + '" class="mceItem ' + cls + '" ' + 'data-sh-attr="' + data + '" data-sh-content="'+ con+'" data-mce-resize="false" data-mce-placeholder="1" />';
		}

		function replaceShortcodes( content ) {
			return content.replace( /\[nuxeo([^\]]*)\]/g, function( all,attr,con) {
				return html( 'wp-nuxeo', attr , con);
			});
		}

		function restoreShortcodes( content ) {
			return content.replace( /(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function( match, image ) {
				var data = getAttr( image, 'data-sh-attr' );
				var con = getAttr( image, 'data-sh-content' );

				if ( data ) {
					return '<p>[' + sh_tag + data + ']</p>';
				} else {
					return '<p>[' + sh_tag + ']</p>';
				}
				
				return match;
			});
		}

		//add popup
		editor.addCommand('nuxeo_popup', function(ui, v) {
			//setup defaults
			var path = '';
			if (v.path)
				path = v.path;
			var type = '';
			if (v.type)
				type = v.type;
			var nxquery = '';
			if (v.nxquery)
				nxquery = v.nxquery;

			editor.windowManager.open( {
				title: 'Nuxeo Shortcode',
				body: [
					{
						type: 'textbox',
						name: 'path',
						label: 'Path',
						value: path,
						tooltip: 'Leave blank for none'
					},
					{
						type: 'textbox',
						name: 'type',
						label: 'Type',
						value: type,
						tooltip: 'Leave blank for none'
					},
					{
						type: 'textbox',
						name: 'nxquery',
						label: 'NXQL Query',
						value: nxquery,
						tooltip: 'Leave blank for none'
					}
				],
				onsubmit: function( e ) {
					var shortcode_str = '[' + sh_tag;
					
					// Check for type
					if (typeof e.data.type != 'undefined' && e.data.type.length)
						shortcode_str += ' type="'+e.data.type+'"';
					
					// Check for path
					if (typeof e.data.path != 'undefined' && e.data.path.length)
						shortcode_str += ' path="' + e.data.path + '"';
					
					// Check for nxquery
					if (typeof e.data.nxquery != 'undefined' && e.data.nxquery.length)
						shortcode_str += ' nxquery="' + e.data.nxquery + '"';

					//add panel content
					shortcode_str += ']';
					
					//insert shortcode to tinymce
					editor.insertContent( shortcode_str);
				}
			});
	      	});

		//add button
		editor.addButton('nuxeo', {
			icon: 'nuxeo',
			tooltip: 'Nuxeo',
			onclick: function() {
				editor.execCommand('nuxeo_popup','',{
					type : '',
					path : '',
					nxquery  : ''
				});
			}
		});

		//replace from shortcode to an image placeholder
		editor.on('BeforeSetcontent', function(event){ 
			event.content = replaceShortcodes( event.content );
		});

		//replace from image placeholder to shortcode
		editor.on('GetContent', function(event){
			event.content = restoreShortcodes(event.content);
		});

		//open popup on placeholder double click
		editor.on('DblClick',function(e) {
			var cls  = e.target.className.indexOf('wp-nuxeo');
			if ( e.target.nodeName == 'IMG' && e.target.className.indexOf('wp-nuxeo') > -1 ) {
				var title = e.target.attributes['data-sh-attr'].value;
				title = window.decodeURIComponent(title);
				console.log(title);
				var content = e.target.attributes['data-sh-content'].value;
				editor.execCommand('nuxeo_popup','',{
					type : getAttr(title,'type'),
					path : getAttr(title,'path'),
					nxquery : getAttr(title,'nxquery')
				});
			}
		});
	});
})();