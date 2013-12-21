<?php

if( !class_exists( 'OptionTree2Redux' ) ) {
	class OptionTree2Redux {

		protected $converter;

		public function __construct( $converter ) {

			$this->converter = $converter;

			$this->converter->frameworks['OptionTree']['version'] = OT_VERSION;

			//add_action('init', array($this, 'addPanel'), 100);

			//add_action( 'admin_menu', array($this, 'addExportMenu') );

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

		public function getSections() {
			global $of_options;
			$options = $of_options;
			$sections = array();
			$section = array();
			$fields = array();
			
			foreach($options as $key=>$value) {
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

			    switch ($value['type']) {
			    	case 'heading':
						if (isset($value['icon']) && !empty($value['icon']) ) {
							$value['icon_type'] = "image";
						}
			    		if (!empty($fields)) {
			    			$section['fields'] = $fields;
			    			$fields = array();
			    		}
			    		if (!empty($section)) {
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
			    		break;
			    	case "switch":
			    		break;
			    	case "images":
			    		$value['type'] = "image_select";
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
					unset($value); // Can't do custom types. Must be fixed manually.
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
					'menu_title' => 'SMOF to Redux',
					'database'	=> 'transient',
					'output' => false,
					'page_slug' => 'SMOF2Redux_Panel',
					'enqueue' => false,
					'default_icon_class' => 'icon-large',
					'intro_text' => '<p>This is your panel converted. Saving will not function. <a href="./admin.php?page=smof_2_redux">Proceed here</a> to get the export code you would need to migrate from SMOF to Redux.</p>'
				);
				$theme = wp_get_theme();

				$args['display_name'] = $theme->get('Name');
				$args['display_version'] = $theme->get('Version');
			    $args['google_api_key'] = 'AIzaSyAX_2L_UzCDPEnAHTG7zhESRVpMPS4ssII';

			    $sections[] = array('type'=>'divide');
			    $sections[] = array( 
			    	'title'=> 'Conversion Classes',
			    	'header' => 'This is a test',
			    	'icon' => 'el-icon-home',
			    );

				$ReduxFramework = new ReduxFramework($sections, $args);	
				global $smof_data; // Always get from SMOF
				global $data;
				$smof = array();
				if (!empty($smof_data)) {
					$ReduxFramework->options = $smof_data;	
				} else if (!empty($data)) {
					$ReduxFramework->options = $data;	
				}
				foreach($sections as $section) {
					if (isset($section['fields'])) {
						foreach($section['fields'] as $field) {
							if (isset($ReduxFramework->options[$field['id']]) && !empty($ReduxFramework->options[$field['id']])) {
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
		    $sections = $this->getSections();
			if ( !empty( $sections ) ) {
				echo '<textarea rows="20" cols="50" class="large-text code">if (class_exists("ReduxFramework")) {
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
}</textarea>';
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
	$SMOF2Redux = new SMOFtoRedux();
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
				$value = array('url'=>$value);
	    		break;    	
	    	default:
	    		break;
	    }
		return $value;
	}	
}