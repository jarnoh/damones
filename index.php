<?php
	$debug = true;
	$framegrabber = false;
	$frame = 0;
	$fps = 60;

	$length_in_sec = 68;
	$lastframe = $fps * $length_in_sec;
	$frame_width = 1920;
	$frame_height = 1080;
	
	if (isset($_GET)) {
		if (isset($_GET['framegrabber']) && $_GET['framegrabber']==='true') {
			$framegrabber = true;
			$frame = 0;
			$fps = 60;
		}
	}
?><!DOCTYPE html>
<html id="html">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<script>
			<?php echo file_get_contents('lib/jquery-1.8.2.min.js'); ?>
			<?php echo file_get_contents('lib/three.min.js'); ?>
			<?php echo file_get_contents('lib/OBJLoader.js'); ?>
			
			var debug = <?php echo (($debug == true)?'true':'false')?>;
<?php if ($framegrabber) { ?> 
			var framegrabber = <?php echo ($framegrabber?'true':'false')?>;
			var frame = parseInt(<?php echo $frame ?>);
			var frame_width = parseInt(<?php echo $frame_width ?>);
			var frame_height = parseInt(<?php echo $frame_height ?>);
			var fps = parseFloat(<?php echo $fps ?>);
			var lastframe = parseInt(<?php echo $lastframe ?>);
<?php } ?>
			var tmppartarray = [];
		</script>
		<script>
			function dataUrlToObjectUrl(d, mimestr) {
				var tmparr = window.atob(d);
				var uint8arr = new Uint8Array(tmparr.length);
				
				for (var i=0; i<uint8arr.length; i++) {
					uint8arr[i] = tmparr.charCodeAt(i);
				}
				
				if (mimestr != 'raw') {
					var tmpblob = new Blob([uint8arr], {type: mimestr});
				
					return window.URL.createObjectURL(tmpblob);
				} else {
					return uint8arr;
				}
			}
			
			function launchFullScreen(element) {
				if(element.requestFullScreen) {
					element.requestFullScreen();
				} else if(element.mozRequestFullScreen) {
					element.mozRequestFullScreen();
				} else if(element.webkitRequestFullScreen) {
					element.webkitRequestFullScreen();
				}
			}
			
			function log(obj) {
				if (debug) {
					console.log(obj);
				}
			}
		</script>

<?php
	$stylefiles = glob('css/*.[cC][sS][sS]');

	for ( $sfptr = 0; $sfptr < count($stylefiles); $sfptr++ ) {
		echo "    <style>\n";

		ob_start();
		include($stylefiles[$sfptr]);
		$style = ob_get_clean();

		echo $style;

		echo "    </style>\n\n";
	}
	
	$fonts = glob('fonts/*.[jJ][sS]');

	if (count($fonts) > 0) {
		echo "    <script>\n";
		for ($i=0; $i<count($fonts); $i++) {
			echo "//  ".$fonts[$i]."\n";
			ob_start();
			include($fonts[$i]);
			$fontcontent = ob_get_clean();
			
			echo $fontcontent;
			echo "\n\n";
		}
		
		echo "    </script>\n";
	}
	
	$jsfiles = glob('js/*.[jJ][sS]');

	for ($jsfptr=0; $jsfptr<count($jsfiles); $jsfptr++) {
		echo "    <script>\n";
		
		ob_start();
		include($jsfiles[$jsfptr]);
		$jscontent = ob_get_clean();
		
		echo $jscontent;
		
		echo "    </script>\n\n";
	}
	
	$shaders = array();
	$vertex = glob('shaders/*.vertex');
	$fragment = glob('shaders/*.fragment');

	$files = array_merge($vertex, $fragment);
	
	for ($i=0; $i<count($files); $i++) {
		$filename = preg_replace("#.*/#", "", $files[$i]); // remove path
		$extension = preg_replace("#.*\.#", "", $filename); // get extension
		$filename = preg_replace("/\\.[^.\\s]{6,8}$/", "", $filename); // remove extension
		
		$data = file_get_contents($files[$i]);
		
		$mimetype = '';
		
		switch ($extension) {
			case ('vertex'):
				$mimetype = "x-shader/x-vertex";
				break;
			
			case ('fragment'):
				$mimetype = "x-shader/x-fragment";
				break;
				
			default:
				break;
		
		}
		
		echo "<script id=\"".$extension."_".$filename."\" type=\"$mimetype\">\n";
		echo $data;
		echo "\n</script>\n\n";
	}
	
	$files = array();
	
	$sounds = glob('audio/*.{mp3,ogg}', GLOB_BRACE);
	$images = glob('img/*.{png,jpg}', GLOB_BRACE);
	$objects = glob('objects/*.{obj}', GLOB_BRACE);
	$rawdata = glob('rawdata/*.{raw}', GLOB_BRACE);
	
	$files = array_merge($sounds, $images, $objects, $rawdata);

	echo "    <script>\n";
	echo "    var datatmp = '';\n";
	echo "    var assetname = '';\n";
	echo "    var data_array = [];\n";

	for ($i=0; $i<count($files); $i++) {

		$filename = preg_replace("#.*/#", "", $files[$i]); // remove path
		$extension = preg_replace("#.*\.#", "", $filename); // get extension
		$filename = preg_replace("/\\.[^.\\s]{3,4}$/", "", $filename); // remove extension
		
		$data = base64_encode(file_get_contents($files[$i]));
		
		echo "    data_array[$i] = '$data';";
		echo "    url = '$files[$i]';\n";

		switch ($extension) {
			case ('obj'):
				echo "    var objecturl_$filename = dataUrlToObjectUrl(data_array[$i], 'text/plain');\n";
				echo "    var tmploader_$filename = new THREE.OBJLoader();\n";
				echo "    var object_$filename;\n";
				echo "    tmploader_$filename.load(objecturl_$filename, function(obj) { console.log('Loaded 3D .obj - $filename'); object_$filename = obj; });\n";
				echo "    assetname = 'object_$filename';\n";
				break;
			case ('ogg'):
				echo "    var ogg_audio_$filename = new Audio();\n";
				echo "    ogg_audio_$filename.src = dataUrlToObjectUrl(data_array[$i], 'audio/ogg');\n";
				echo "    assetname = 'ogg_audio_$filename';\n";
				break;
			case ('mp3'):
				echo "    var mp3_audio_$filename = new Audio();\n";
				echo "    mp3_audio_$filename.src = dataUrlToObjectUrl(data_array[$i], 'audio/mp3');\n";
				echo "    assetname = 'mp3_audio_$filename';\n";
				break;
			case ('jpg'):
				echo "    var image_$filename = new Image();\n";
				echo "    image_$filename.src = dataUrlToObjectUrl(data_array[$i], 'image/jpg');\n";
				echo "    assetname = 'image_$filename';\n";
				break;
			case ('png'):
				echo "    var image_$filename = new Image();\n";
				echo "    image_$filename.src = dataUrlToObjectUrl(data_array[$i], 'image/png');\n";
				echo "    assetname = 'image_$filename';\n";
				break;
			case ('raw'):
				echo "    var raw_$filename = dataUrlToObjectUrl(data_array[$i], 'raw');\n";
				echo "    assetname = 'raw_$filename';\n";
				break;
			default:
				break;
		}
		
		echo "    log('Preloaded ".$files[$i]." as '+assetname);\n";
	}
	
	echo "    log(navigator.userAgent.toLowerCase());\n";
	echo "    if (!navigator.userAgent.match(/Trident.*[ :]*11\./)) {\n";
	echo "        log('Not using IE');\n";
	echo "        data_array[$i] = assetname = undefined;\n";
	echo "    } else {\n";
	echo "        log('Using IE, not releasing assets...');\n";
	echo "    }\n";

	echo "    </script>\n";
	
?>
	</head>

	<body>
		<script>
			var debug = true;
			var global_engine = null;
			var global_audioplayer = null;
			var global_tick = 0;

<?php if (!$framegrabber) { ?>
			function update() {
				window.requestAnimationFrame(update);
				global_engine.draw(global_engine.getTick());
			}
<?php } else { ?>
			function update(frame) {
				var framenumber = parseInt(((frame)?frame:0));
				var frame_to_ms = Math.floor(framenumber / fps * 1000);

				global_engine.draw(frame_to_ms);
				
				var canvas = $('#demo canvas')[0];
				var framedata = canvas.toDataURL();
				
				$('#framecounter').text('Saving frame: ' + framenumber +' ts: ' + frame_to_ms);
				
				$.ajax({
					url: 'framesaver.php',
					type: 'POST',
					data: { action: 'saveframe', framenumber: framenumber, framedata: framedata },
					success: function(res) {
						if (res && res.status && res.status==='ok') {
							if (framenumber++ < lastframe) {
								update(framenumber);
							} else {
								alert('dumping done!');
							}
						} else {
							$('#framecounter').text('status: ' + res.status + ' error: ' + res.error);
						}
					},
					error: function(a,b,c) {

					}
				})
			}
<?php } ?>
			
			function overshoot_smoothstep(min, max, t) {
				var tmp = (t-min) / (max-min);
				return tmp * tmp * tmp * (5.0 - 4.0 * tmp);
			}
			
			function smoothstep(min, max, t) {
				var tmp = (t-min) / (max-min);
				return tmp * tmp * (3.0 - 2.0 * tmp);
			}
			
			function smootherstep(min, max, t) {
				var tmp = (t-min) / (max-min);
				return tmp * tmp * tmp * (tmp * (tmp * 6.0 - 15.0) + 10.0);
			}
			
			function init() {
<?php if ($framegrabber) { ?>
				global_engine = new DemoEngine('#demo', frame_width, frame_height);
<?php } else { ?>
				var width = window.innerWidth;
				var height = window.innerHeight;
				
				var w=0;
				var h=0;
				
				if (width/height < 16/9) {
					w = width;
					h = Math.floor(width / (16/9));
				} else {
					w = Math.floor(height * (16/9));
					h = height;
				}
				
				if (h < height) {
					$("#demo").css({ 'margin-top': Math.floor((height-h)/2) + 'px' });
				}
				
				log("Creating Damones demo engine");
				global_engine = new DemoEngine('#demo', w, h);
<?php } ?>
				log("Adding render targets");
				global_engine.addRenderTarget('secondary', global_engine.getWidth(), global_engine.getHeight());
				global_engine.addRenderTarget('tertiary', global_engine.getWidth(), global_engine.getHeight());
				
				log("Adding audio");
				if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1)
				{
					window.setTimeout(global_engine.setAudio(ogg_audio_FutureKitchen), 10000);
				} else {
					global_engine.setAudio(mp3_audio_FutureKitchen);
				}
				
				global_engine.setAudioLooping(false);
				
				log("Adding demo parts:");
<?php				
	$partdir = "parts/";
	$partorder = array(
		'boozembly-start.js', 
		'part-01-jope.js'
	);
	
	for ($i=0; $i<count($partorder); $i++) {
		$partfilename = $partdir.$partorder[$i];
		$partdata = file_get_contents($partfilename);
		echo "    log(\"$partfilename\")\n";
		echo "    global_engine.addPart($partdata)\n";
	}
?>			
			}

			$(document).ready(function() {
				$('#btn_fullscreen_no').off('click').on('click', function() {
					$('#setup').remove();

					init();

<?php if (!$framegrabber) { ?>
					log("playing");

					global_engine.play();
					global_engine.showControls(false);
<?php } else { ?>
					$('#demo').append('<div id="framecounter" class="framecounter"></div>');
<?php } ?>

					update();
				});
				
				$('#btn_fullscreen_yes').off('click').on('click', function() {
					$('#setup').remove();

					launchFullScreen(document.getElementById('html'));
					
					window.setTimeout(
						function(){
							init();

<?php if (!$framegrabber) { ?>
							global_engine.play();
							global_engine.showControls(false);
<?php } else { ?>
							$('#demo').append('<div id="framecounter" class="framecounter"></div>');
<?php } ?>

							update();
						}, 
						1000
					);
				});
				
				$('#btn_fullscreen_maybe').off('click').on('click', function() {
					if (Math.random() > 0.5) {
						$('#btn_fullscreen_yes').click();
					} else {
						$('#btn_fullscreen_no').click();
					}
				});
				
				$('#setup').show();
			});
		</script>
		<div id="main" class="main">
			<div id="demo" class="demo"></div>
		</div>
		<div id="setup" class="setup" style="display: none;">
			<label>Run fullscreen?</label>
			<button id="btn_fullscreen_yes" class="yes">Yes</button>
			<button id="btn_fullscreen_no" class="no">No</button>
			<button id="btn_fullscreen_maybe" class="maybe">Maybe</button>
		</div>
	</body>
</html>