<?php

if( !class_exists( 'SMOF2Redux' ) ) {
	class SMOF2Redux {

		protected $converter;
		public $version;
		public $database;
		public $data;
		public $config;
		public $sections;

		public function __construct( $converter ) {
			
			$this->converter = $converter;

			// Find the version
			if (defined('SMOF_VERSION')) {
				$this->version = SMOF_VERSION;
			} else {
				$this->version = '1.3';
			}

			// Get the saved data
			if ( $this->version <= "1.5" ) {
				// Get the old data values
				global $data;
				$this->data = $data;

				if ( defined( 'OPTIONS' ) ) {
					$this->database = OPTIONS;	
				}
			} else {
				global $smof_data;
				$this->data = $smof_data;
			}

			add_action('init', array($this, 'addPanel'), 100);

			add_action( 'admin_footer', array($this,'ajax_javascript') );
			add_action( 'wp_ajax_SMOF_2_Redux', array($this,'ajax_callback') );
		}



		function ajax_javascript() {
			?>
			<script type="text/javascript" >
			jQuery(document).ready(function($) {
				$('.redux-converter-action').click(function() {
					var parent = $(this).parents('.redux-group-tab:first');
					var data = {
						action: 'SMOF_2_Redux',
						nonce: parent.find('.convertToReduxNonce').val(),
						opt_name: parent.find('#redux_opt_name-text').val(),
						migrate_data: parent.find('#redux_convert_data').val(),
						global_variable: parent.find('#redux_global_variable-text').val(),
						delete_data: parent.find('#SMOF2Redux_Panel_redux_delete_old_data_1_0').is(":checked"),
					};
					if ($(this).data('action') == "download") {
						data.download = true;
					}
					
					var url = ajaxurl+'?'+$.param( data );
					$(this).attr('href', url)

				});
				
			});
			</script>
			<?php
		}		

		function ajax_callback() {

			$_REQUEST = array_filter($_REQUEST);
			//print_r($_REQUEST);

			if ( !wp_verify_nonce( $_REQUEST['nonce'], 'convertToReduxSMOF' ) ) {
				//die();
			}
			if (isset($_REQUEST['download'])) {
				header("Content-Type: application/octet-stream");
				header("Content-Transfer-Encoding: Binary");
				header("Content-disposition: attachment; filename=\"ReduxFramework.config.php\""); 
			} else {
				header("Content-Type: text/plain");	
			}

			$_REQUEST['uuid'] = uniqid($_REQUEST['nonce']);
			unset($_REQUEST['migrate_data']);


		    $_REQUEST['sections'] = $this->getSections();

			if ( !empty( $_REQUEST['sections'] ) ) {
				$_REQUEST['sections'] =  $this->objectToHTML( $_REQUEST['sections'] );
				if (!class_exists('Mustache_Autoloader')) {
					require_once(dirname(__FILE__).'/includes/Mustache/Autoloader.php');	
				}
				Mustache_Autoloader::register();
				$m = new Mustache_Engine;
				echo htmlspecialchars_decode(htmlspecialchars_decode( $m->render(file_get_contents(dirname(__FILE__).'/includes/outputClass.php'), $_REQUEST ) ) );
				die();
				//echo uniqid($_REQUEST['nonce']);
				echo '<?
if (class_exists("ReduxFramework")) {
	$sections = 
	' . $this->objectToHTML( $sections ) . ';

	// Change your opt_name to match where you want the data saved.
	$args = array(
		"opt_name"=>"'.($_REQUEST["opt_name"] ? $_REQUEST["opt_name"] : 'redux_panel').'", // Where your data is stored. Use a different name or use the same name as your current theme. Must match the $database_newName variable in the converter code.
		"menu_title" => "Redux Converted", // Title for your menu item
		"page_slug" => "'.($_REQUEST["opt_name"] ? $_REQUEST["opt_name"] : 'redux_panel').'", // Make this the same as your opt_name unless you care otherwise
		';
if (empty($_REQUEST['global_variable'])) {
	echo '//';
}
echo '"global_variable" => "'.(!empty( $_REQUEST["global_variable"] ) ? $_REQUEST["global_variable"] : $_REQUEST["opt_name"]).'", // By default Redux sets your global variable to be the opt_name you set above. This is what the newest SMOF uses as it\'s variable name. You can change, but you may need to update your files.
		//"intro_text" => "<p>This theme is now using Redux</p>" // Extra header info
		"google_api_key" => "", // You must acquire a Google Web Fonts API key if you want Google Fonts to work
	);
	// Use this section if this is for a theme. Replace with plugin specific data if it is for a plugin.
	$theme = wp_get_theme();
	$args["display_name"] = $theme->get("Name");
	$args["display_version"] = $theme->get("Version");

	$ReduxFramework = new ReduxFramework($sections, $args);
}

';


if ($_REQUEST['migrate_date'] == true) {
echo '
function migrateSMOFDataToRedux($oldname, $oldtheme=false) {
	$database_newName = "'.($_REQUEST["opt_name"] ? $_REQUEST["opt_name"] : 'redux_panel').'"; // Where your data will now be saved. Must match your opt_name in the ReduxFramework $args array.
	';

	if (SMOF_VERSION == "1.5.1" || SMOF_VERSION == "1.5.1") {
		echo '$data = get_theme_mods(); // SMOF 1.5.1+';
	} else {
		if (defined('OPTIONS')) {
			echo '$data = get_option('.OPTIONS.');';
		} else {
			echo '$data = get_option(""); // Please provide the option name where your SMOF values are stored.';
		}
	}
	echo '
	
	$sections = '.str_replace(array("\r\n", "\n", "\r", "  ", "	"), '',var_export($sections, true)).';
	if (isset($data) && !empty($data)) {
		foreach($sections as $section) {
			foreach($section["fields"] as $field) {
				if (isset($data[$field["id"]]) && !empty($data[$field["id"]])) {
					$data[$field["id"]] = SMOF2ReduxConvertValue($data[$field["id"]], $field["type"]); // Not sure why this happens. Huh.
				}
				if( isset( $field["required"] ) ) {
		            $ReduxFramework->get_fold($field);
			    }		
			}
		}
		update_option($database_newName, $data); // Update the database
	}
		
}
add_action("after_switch_theme", "updatedatabaseoptions", 10 ,  2);

if (!function_exists("SMOF2ReduxConvertValue")) {
	function SMOF2ReduxConvertValue($value, $type) {
	    switch ($type) {
			case "text":
				if (!is_array($value)) {
					$value = stripcslashes($value); // Not sure why this happens. Huh.
				}
	    		break;
	    	case "typography":
				$default = array();
				if (isset($value["size"])) {
					$default["font-size"] = $value["size"];
					$px = filter_var($default["font-size"], FILTER_SANITIZE_NUMBER_INT);
					$default["units"] = str_replace($px, "", $default["font-size"]);
				}
				if (isset($value["color"])) {
					$default["color"] = $value["color"];
				}
				if (isset($value["face"])) {
					$fonts = array(
						"Arial, Helvetica, sans-serif",
						"\'Arial Black\', Gadget, sans-serif",
						"\'Bookman Old Style\', serif",
						"\'Comic Sans MS\', cursive",
						"Courier, monospace",
						"Garamond, serif",
						"Georgia, serif",
						"Impact, Charcoal, sans-serif",
						"\'Lucida Console\', Monaco, monospace",
						"\'Lucida Sans Unicode\', \'Lucida Grande\', sans-serif",
						"\'MS Sans Serif\', Geneva, sans-serif",
						"\'MS Serif\', \'New York\', sans-serif",
						"\'Palatino Linotype\', \'Book Antiqua\', Palatino, serif",
						"Tahoma, Geneva, sans-serif",
						"\'Times New Roman\', Times, serif",
						"\'Trebuchet MS\', Helvetica, sans-serif",
						"Verdana, Geneva, sans-serif",
	                );
	                foreach($fonts as $font) {
	                	if (strpos(strtolower($font),strtolower($value["face"])) !== false) {
							$default["font-family"] = $font;
						}
	                }
				}
				if (isset($value["style"])) {
					if (strpos(strtolower($value["style"]),"bold") !== false) {
						$default["font-weight"] = "bold";
					}
					if (strpos(strtolower($value["style"]),"italic") !== false) {
						$default["font-style"] = "italic";
					}
				} 			
				$value = $default;
	    		break;
	    	case "border":
	    		if (isset($value["width"])) {
	    			$value["border-width"] = $value["width"]."px";
	    			$value["units"] = "px";
	    			unset($value["width"]);
	    		}
				if (isset($value["color"])) {
	    			$value["border-color"] = $value["color"];
	    			unset($value["color"]);
	    		}
				if (isset($value["style"])) {
	    			$value["border-style"] = $value["style"];
	    			unset($value["style"]);
	    		}
	    		break;			    			    			    			    		
	    	case "upload":
	    	case "image":
			case "media":
				$value = array("url"=>$value);
	    		break;    	
	    	default:
	    		break;
	    }
		return $value;
	}	
}

';
}

			}
			//header("Content-type: application/x-msdownload");
	        //header("Content-Disposition: attachment; filename=data.csv");
	        //header("Pragma: no-cache");
	        //header("Expires: 0");

			exit();
		        

			die(); // this is required to return a proper result
		}

		function objectToHTML($object) {
	        // create html
	        $html = var_export($object, true);
	        
	        // change double spaces to tabs
	        $html = str_replace("  ", "\t", $html);
	        
	        // correctly formats "=> array("
	        $html = preg_replace('/([\t\r\n]+?)array/', 'array', $html);
	        
	        // Remove number keys from array
	        $html = preg_replace('/[0-9]+ => array/', 'array', $html);
	        
	        // add extra tab at start of each line
	        return str_replace("\n", "\n\t", $html);
		}		

		public function getSections($withWarnings = true) {
			global $of_options;

			$sections = array();
			$section = array();
			$fields = array();	
			
			foreach($of_options as $key=>$value) {
				foreach ($value as $k=>$v) {
					if (empty($v)) {
						unset($value[$k]);
					}
				}
				
			    if (isset($value['name'])) {
			        $value['title'] = $value['name'];
			        unset($value['name']); 
			    }

			    if (isset($value['std'])) {
			        $value['default'] = $value['std'];
			        unset($value['std']);
			    }

			    if (isset($value['fold'])) {
			    	$value['required'] = array($value['fold'], '=' , 1);
			    	unset($value['fold']);
			    }
			    if (isset($value['folds'])) {
			    	unset($value['folds']);
			    }	    
			    if (!isset($value['type'])) {
			    	continue;
			    }
			    switch ($value['type']) {
			    	case 'heading':
						if (isset($value['icon']) && !empty($value['icon']) ) {
							//$value['icon_type'] = "image";
						}
			    		if (!empty($fields)) {
			    			$section['fields'] = $fields;
			    			$fields = array();
			    		}
			    		if (!empty($section)) {
			    			$section['icon'] = "el-icon-cog";
			    			$sections[] = $section;
			    			$section = array();
			    		} 
			    		unset($value['type']);
			    		$section = $value;
			    		unset($value);
			    		break;
					case "text":
						if(isset($value['mod'])) {
		    				unset($value['mod']);
		    			}
			    		break;
			    	case "select":
		    			if(isset($value['mod'])) {
		    				unset($value['mod']);
		    			}
			    		break;
			    	case "textarea":
			    		if(isset($value['cols'])) {
		    				unset($value['cols']);
		    			}
			    		break;
			    	case "radio":
			    		break;
			    	case "checkbox":
			    		break;
			    	case "multicheck":
			    		$value['type'] = "checkbox";
			    		break;
			    	case "color":
			    		break;
					case "select_google_font":	
						if (isset($value['preview'])) {
			    			unset($value['preview']);
			    		}
			    		if (isset($value['options'])) {
			    			$value['fonts'] = $value['options'];

			    			unset($value['options']);
			    		}
			    		if (isset($value['default'])) {
			    			unset($value['default']);
			    		}
			    		$value['type'] = "typography";
						break;
			    	case "typography":
			    		if (isset($value['preview'])) {
			    			unset($value['preview']);
			    		}
			    		if (isset($value['options'])) {
			    			$value['fonts'] = $value['options'];
			    			unset($value['options']);
			    		}
			    		break;
			    	case "border":    			    		
			    		break;
			    	case "info":
			    		if (isset($value['title'])) {
			    			unset($value['title']);
			    		}
			    		if (isset($value['default'])) {
			    			$value['raw'] = $value['default'];
			    			unset($value['default']);
			    		}
			    		break;
			    	case "switch":
			    		break;
			    	case "images":
			    		$value['type'] = "image_select";
			    		if (strpos(strtolower($value['title']),'pattern') !== false) {
			    			$value['tiles'] = true;
			    		}
			    		break;
			    	case "image":
			    		$value['type'] = "info";
			    		$value['raw_html'] = true;
			    		break;
			    	case "slider":
			    		$value['type'] = "slides";
			    		break;
			    	case "sorter":
			    		if (isset($value['default'])) {
			    			$value['options'] = $value['default'];
			    			unset($value['default']);
			    		}
			    		break;
			    	case "tiles":
			    		$value['type'] = "image_select";
			    		$value['tiles'] = true;
			    		break;
			    	case "backup":
			    	case "transfer":
			    		unset($value);
			    		if ($of_options[($key-1)]['type'] == "heading") {
			    			if (strpos(strtolower($of_options[($key-1)]['name']),'backup') !== false) {
			    				$section = array();	
			    			}
			    		}
			    		break;
			    	case "sliderui":
			    		$value['type'] = "slider";
			    		break;	    			    			    			    			    		
			    	case "upload":
					case "media":
			    		$value['type'] = "media";
			    		if (isset($value['mod']) && $value['mod'] == "min") {
			    			unset($value['mod']);
			    		} else {
			    			$value['url'] = true;
			    		}
			    		break;

			    	default:
			    		if ($withWarnings) {
							$content = "<h3 style='color: red;'>Found a field with an unknown type!</h3> <p>Perhaps this was a custom field and will need to be remade for use within Redux. This was the field's configuration:</p>";
				    		$content .= "<pre style='overflow:auto;border: 2px dashed #eee;padding: 2px 5px; width: 100%;'>";
				    		ob_start();
							var_dump($value);
							$content .= ob_get_clean();
				    		$content .= "</pre>";
				    		$value['desc'] = $content;
				    		$value['type'] = "info";
				    		$value['raw_html'] = true;			    			
			    		}
			    		
					//unset($value); // Can't do custom types. Must be fixed manually.
			    		# code...
			    		break;
			    }
				if (isset($value['default']) && !empty($value['default'])) {
					$value['default'] = SMOF2ReduxConvertValue($value['default'], $value['type']);
				}

			    if (!empty($value)) {
			    	$fields[] = $value;	
			    }
			    
			}
			if (!empty($fields)) {
				$section['fields'] = $fields;
				$fields = array();
			}
			if (!empty($section)) {

				$sections[] = $section;
				$section = array();
			}		
			return $sections;

		}


		public function addPanel() {

			$sections = $this->getSections();

			if (!empty($sections) && class_exists('ReduxFramework')) {

				$args = array(
					'opt_name'=>'SMOF2Redux_Panel', 
					'save_defaults'=>false,
					'menu_title' => 'SMOF 2 Redux',
					//'database'	=> 'transient',
					'output' => false,
					'show_import_export' => false,
					'page_slug' => 'SMOF2Redux_Converter',
					'enqueue' => false,
					'intro_text' => '<p>This is your panel converted. Saving will be saved to a transient value which gets reset every hour. <a href="./admin.php?page=smof_2_redux">Proceed here</a> to get the export code you would need to migrate from SMOF to Redux.</p>'
				);
				$theme = wp_get_theme();

				$args['display_name'] = $theme->get('Name');
				$args['display_version'] = $theme->get('Version');
			    $args['google_api_key'] = 'AIzaSyAX_2L_UzCDPEnAHTG7zhESRVpMPS4ssII';

			    $sections[] = array('type'=>'divide');
			    $sections[] = array( 
			    	'title'=> 'Convert to Redux!',
			    	'icon' => 'el-icon-asl',
			    	'fields' => array(
						array(
							'id'=>'redux_conversion_welcome',
							'type' => 'info',
							'title' => __("Rest assured, you're making the right choice.", 'redux-framework-demo'), 
							'desc' => "Converting to a new framework is not always easy, in fact it's downright <strong>painful</strong>. We don't think it has to be that way. That's why we created this converter plugin.<br /><br />Fill out the items below and download a fully function Redux Framework class. <a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3WQGEY4NSYE38' target='_blank'>Be sure to donate</a>. Every bit helps."
						),		
						array(
							'id'=>'redux_convert_refresh_data',
							'type' => 'checkbox',
							'title' => __('Reset Panel to Old Data', 'redux-framework-demo'), 
							'subtitle' => __('Reset this panel to match what is inside SMOF.', 'redux-framework-demo'),
							'options'=>array(1=>'Reset this panel to match what is stored in SMOF.'),
						),									    		
						array(
							'id'=>'redux_opt_name',
							'type' => 'text',
							'title' => __('Database name, aka Redux opt_name', 'redux-framework-demo'), 
							'subtitle' => __('Choose the place where your data will be stored in the database.', 'redux-framework-demo'),
							'desc' => 'Once this is specified, Redux will take care of the rest. It is suggested to use a different key than you previously had. Data migration code will be provided.',
							'default' => 'redux_converter'
						),	
						array(
							'id'=>'redux_convert_data',
							'type' => 'switch',
							'title' => __('Add Data Migration', 'redux-framework-demo'), 
							'subtitle' => __('Don\'t just implement a new panel, but migrate your old data as well.', 'redux-framework-demo'),
							'default' => 1,
							'desc' => 'A function will be provided which will, on theme activation/upgrade, migrate your data to your new Redux opt_name location denoted above. This makes it super easy to convert even if you have many users! <span style="color: maroon;">Please verify data migrates properly. Redux takes no responsibility if a field does not convert as it should.</span> If you find a bug, <a href="https://github.com/ReduxFramework/redux-converter/issues" target="_blank">please submit it to us</a>!',
						),								    		
						array(
							'id'=>'redux_global_variable',
							'type' => 'text',
							'title' => __('Redux Global Variable', 'redux-framework-demo'), 
							'subtitle' => __('Redux provides a global variable for your access your panel data anywhere within wordpress.', 'redux-converter'),
							'desc' => 'By default the global variable is the same as the opt_name. If you want it to be something else (or your old variable), choose a name here. All standard variable rules apply (no spaces, dashes, or other odd symbols). SMOF\'s typical variables (if you wanted to keep your code as is) are $data or $smof_data.',
						),				    		
						array(
							'id'=>'redux_delete_old_data',
							'type' => 'checkbox',
							'title' => __('Delete Old Panel Data?', 'redux-framework-demo'), 
							'subtitle' => __('We strongly suggest you don\'t do this, but it is your choice.', 'redux-framework-demo'),
							'desc' => '<span style="color: red;">There is no undoing this. If something in the conversion goes bad, your previously set data will be lost. It is suggested to leave this be just in case.</span>',
							'options'=>array(1=>'Remove old data on migration'),
						),				    		
			    		
						array(
							'id'=>'redux_download_file',
							'type' => 'raw',
							'content' => '<center><input type="hidden" class="convertToReduxNonce" value="'.wp_create_nonce( 'convertToReduxSMOF' ).'"><a href="#" target="_blank" class="button button-primary redux-converter-action">View Redux Config File</a> <a href="#" data-action="download" class="button button-primary redux-converter-action">Download Redux Config File</a></center>',
							
						),			    		
			    		
			    			
			    	)
			    );

				$ReduxFramework = new ReduxFramework($sections, $args);	
				global $smof_data; // Always get from SMOF
				global $data;
				$smof = array();
				$convertData = false;
				if ( empty( $ReduxFramework->options ) || ( isset( $ReduxFramework->options['redux_convert_refresh_data'] ) && $ReduxFramework->options['redux_convert_refresh_data'] == 1 ) ) {
					$convertData = true;
					if (!empty($smof_data)) {
						$ReduxFramework->options = $smof_data;	
					} else if (!empty($data)) {
						$ReduxFramework->options = $data;	
					}					
				}
				foreach($sections as $section) {
					if (isset($section['fields'])) {
						foreach($section['fields'] as $field) {
							if ($convertData && isset($ReduxFramework->options[$field['id']]) && !empty($ReduxFramework->options[$field['id']])) {
								$ReduxFramework->options[$field['id']] = SMOF2ReduxConvertValue($ReduxFramework->options[$field['id']], $field['type']); // Not sure why this happens. Huh.
							}
							if( isset( $field['required'] ) ) {
					            $ReduxFramework->get_fold($field);
						    }		
						}						
					}
					
				}
			}			
		}


		

		function addExportMenu() {
		    add_menu_page(
		        'SMOF2Redux Export',     // page title
		        'SMOF2Redux Export',     // menu title
		        'manage_options',   // capability
		        'smof_2_redux',     // menu slug
		        array($this, 'exportPage'), // callback function
		        null,
		        600
		    );
		}

		function exportPage(){
		    global $title;

		    echo '<div class="wrap">';
		    echo "<h1>$title</h1>";
		    echo "<h2 class='description'>The following is the ReduxFramework config code. Use this within your theme. Be sure to change the names and more specifically the opt_name for where the data is stored.</h2><br />";
		    $sections = $this->getSections(false);
			if ( !empty( $sections ) ) {
				if (!class_exists('Mustache_Autoloader')) {
					require_once(dirname(__FILE__).'/../includes/mustache.php/src/Mustache/Autoloader.php');	
				}
				Mustache_Autoloader::register();
				$m = new Mustache_Engine;
				echo $m->render('Hello {{planet}}', array('planet' => 'World!')); // "Hello World!"

				echo 'if (class_exists("ReduxFramework")) {
	$sections = 
	' . $this->objectToHTML( $sections ) . ';

	// Change your opt_name to match where you want the data saved.
	$args = array(
		"opt_name"=>"redux_panel", // Where your data is stored. Use a different name or use the same name as your current theme. Must match the $database_newName variable in the converter code.
		"menu_title" => "Redux Converted", // Title for your menu item
		"page_slug" => "redux_panel", // Make this the same as your opt_name unless you care otherwise
		"default_icon_class" => "icon-large",
		//"global_variable" => "of_options", // By default Redux sets your global variable to be the opt_name you set above. This is what the newest SMOF uses as it\'s variable name. You can change, but you may need to update your files.
		//"intro_text" => "<p>This theme is now using Redux</p>" // Extra header info
		"google_api_key" => "", // You must acquire a Google Web Fonts API key if you want Google Fonts to work
	);
	// Use this section if this is for a theme. Replace with plugin specific data if it is for a plugin.
	$theme = wp_get_theme();
	$args["display_name"] = $theme->get("Name");
	$args["display_version"] = $theme->get("Version");

	$ReduxFramework = new ReduxFramework($sections, $args);
}';
				echo "<h3>To ensure your data is properly migrated, you will need to add the following code to your theme. It will convert your SMOF data to Redux.</h3>";
				echo '<textarea rows="20" cols="50" class="large-text code">';
echo 'function migrateSMOFDataToRedux($oldname, $oldtheme=false) {
	$database_newName = "redux_panel"; // Where your data will now be saved. Must match your opt_name in the ReduxFramework $args array.
	';

	if (SMOF_VERSION == "1.5.1" || SMOF_VERSION == "1.5.1") {
		echo '$data = get_theme_mods(); // SMOF 1.5.1+';
	} else {
		if (defined('OPTIONS')) {
			echo '$data = get_option('.OPTIONS.');';
		} else {
			echo '$data = get_option(""); // Please provide the option name where your SMOF values are stored.';
		}
	}
	echo '
	
	$sections = '.str_replace(array("\r\n", "\n", "\r", "  ", "	"), '',var_export($sections, true)).';
	if (isset($data) && !empty($data)) {
		foreach($sections as $section) {
			foreach($section["fields"] as $field) {
				if (isset($data[$field["id"]]) && !empty($data[$field["id"]])) {
					$data[$field["id"]] = SMOF2ReduxConvertValue($data[$field["id"]], $field["type"]); // Not sure why this happens. Huh.
				}
				if( isset( $field["required"] ) ) {
		            $ReduxFramework->get_fold($field);
			    }		
			}
		}
		update_option($database_newName, $data); // Update the database
	}
		
}
add_action("after_switch_theme", "updatedatabaseoptions", 10 ,  2);

if (!function_exists("SMOF2ReduxConvertValue")) {
	function SMOF2ReduxConvertValue($value, $type) {
	    switch ($type) {
			case "text":
				if (!is_array($value)) {
					$value = stripcslashes($value); // Not sure why this happens. Huh.
				}
	    		break;
	    	case "typography":
				$default = array();
				if (isset($value["size"])) {
					$default["font-size"] = $value["size"];
					$px = filter_var($default["font-size"], FILTER_SANITIZE_NUMBER_INT);
					$default["units"] = str_replace($px, "", $default["font-size"]);
				}
				if (isset($value["color"])) {
					$default["color"] = $value["color"];
				}
				if (isset($value["face"])) {
					$fonts = array(
						"Arial, Helvetica, sans-serif",
						"\'Arial Black\', Gadget, sans-serif",
						"\'Bookman Old Style\', serif",
						"\'Comic Sans MS\', cursive",
						"Courier, monospace",
						"Garamond, serif",
						"Georgia, serif",
						"Impact, Charcoal, sans-serif",
						"\'Lucida Console\', Monaco, monospace",
						"\'Lucida Sans Unicode\', \'Lucida Grande\', sans-serif",
						"\'MS Sans Serif\', Geneva, sans-serif",
						"\'MS Serif\', \'New York\', sans-serif",
						"\'Palatino Linotype\', \'Book Antiqua\', Palatino, serif",
						"Tahoma, Geneva, sans-serif",
						"\'Times New Roman\', Times, serif",
						"\'Trebuchet MS\', Helvetica, sans-serif",
						"Verdana, Geneva, sans-serif",
	                );
	                foreach($fonts as $font) {
	                	if (strpos(strtolower($font),strtolower($value["face"])) !== false) {
							$default["font-family"] = $font;
						}
	                }
				}
				if (isset($value["style"])) {
					if (strpos(strtolower($value["style"]),"bold") !== false) {
						$default["font-weight"] = "bold";
					}
					if (strpos(strtolower($value["style"]),"italic") !== false) {
						$default["font-style"] = "italic";
					}
				} 			
				$value = $default;
	    		break;
	    	case "border":
	    		if (isset($value["width"])) {
	    			$value["border-width"] = $value["width"]."px";
	    			$value["units"] = "px";
	    			unset($value["width"]);
	    		}
				if (isset($value["color"])) {
	    			$value["border-color"] = $value["color"];
	    			unset($value["color"]);
	    		}
				if (isset($value["style"])) {
	    			$value["border-style"] = $value["style"];
	    			unset($value["style"]);
	    		}
	    		break;			    			    			    			    		
	    	case "upload":
	    	case "image":
			case "media":
				$value = array("url"=>$value);
	    		break;    	
	    	default:
	    		break;
	    }
		return $value;
	}	
}

';

				echo "</textarea>";
		    } 
		    echo '</div>';
		}		

	}
	
}



if (!function_exists('SMOF2ReduxConvertValue')) {
	function SMOF2ReduxConvertValue($value, $type) {
	    switch ($type) {
			case "text":
				if (!is_array($value)) {
					$value = stripcslashes($value); // Not sure why this happens. Huh.
				}
	    		break;
	    	case "typography":
				$default = array();
				if (isset($value['size'])) {
					$default['font-size'] = $value['size'];
					$px = filter_var($default['font-size'], FILTER_SANITIZE_NUMBER_INT);
					$default['units'] = str_replace($px, "", $default['font-size']);
				}
				if (isset($value['color'])) {
					$default['color'] = $value['color'];
				}
				if (isset($value['face'])) {
					$fonts = array(
						"Arial, Helvetica, sans-serif",
						"'Arial Black', Gadget, sans-serif",
						"'Bookman Old Style', serif",
						"'Comic Sans MS', cursive",
						"Courier, monospace",
						"Garamond, serif",
						"Georgia, serif",
						"Impact, Charcoal, sans-serif",
						"'Lucida Console', Monaco, monospace",
						"'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
						"'MS Sans Serif', Geneva, sans-serif",
						"'MS Serif', 'New York', sans-serif",
						"'Palatino Linotype', 'Book Antiqua', Palatino, serif",
						"Tahoma, Geneva, sans-serif",
						"'Times New Roman', Times, serif",
						"'Trebuchet MS', Helvetica, sans-serif",
						"Verdana, Geneva, sans-serif",
	                );
	                foreach($fonts as $font) {
	                	if (strpos(strtolower($font),strtolower($value['face'])) !== false) {
							$default['font-family'] = $font;
						}
	                }
				}
				if (isset($value['style'])) {
					if (strpos(strtolower($value['style']),'bold') !== false) {
						$default['font-weight'] = "bold";
					}
					if (strpos(strtolower($value['style']),'italic') !== false) {
						$default['font-style'] = "italic";
					}
				} 			
				$value = $default;
	    		break;
	    	case "border":
	    		if (isset($value['width'])) {
	    			$value['border-width'] = $value['width']."px";
	    			$value['units'] = "px";
	    			unset($value['width']);
	    		}
				if (isset($value['color'])) {
	    			$value['border-color'] = $value['color'];
	    			unset($value['color']);
	    		}
				if (isset($value['style'])) {
	    			$value['border-style'] = $value['style'];
	    			unset($value['style']);
	    		}
	    		break;			    			    			    			    		
	    	case "upload":
	    	case "image":
			case "media":
				if (!empty($value)) {
					$value = array('url'=>$value);	
				}
	    		break;    	
	    	default:
	    		break;
	    }
		return $value;
	}	
}