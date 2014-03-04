<?php

if( !class_exists( 'OptionTree2Redux_Data' ) ) {
	class OptionTree2Redux_Data {

		protected $converter;
		public $version;
		public $database;
		public $data;
		public $converted_data;
		public $sections = array();
		public $framework = "OptionTree";

		public function __construct() {

			//add_action('init', array( $this, 'init' ), 0 );

		}

		public function init() {
			
			$this->version = OT_VERSION;		

			$this->getSections();

			$this->data = get_option( 'option_tree' );
			
			if (!empty($this->sections)) {
				foreach($this->sections as $section) {
					if (isset($section['fields']) && !empty($section['fields'])) {
						foreach($section['fields'] as $field) {
							if (isset($this->data[$field['id']])) {
								$this->converted_data[$field['id']] = $this->convertValue($this->data[$field['id']], $field['type']);
							}
						}
					}
				}
			}

		}

		public function getSections($withWarnings = true) {
			
			$this->args = get_option( 'option_tree_settings', array() );
			if ( empty( $this->args ) ) {
				return;
			}
			$sections = array();
			$section = array();
			$fields = array();
//			$options = get_option( 'option_tree' );
//			print_r($this->args);
			foreach($this->args['sections'] as $key=>$value) {
				if (isset($value['content'])) {
					$value['desc'] = $value['content'];
					unset($value['content']);	
				}
				$value['icon'] = "el-icon-cog";
				$value['fields'] = array();
				$sections[$value['id']] = $value;
			}

			foreach($this->args['settings'] as $key=>$value) {
				$section = $value['section'];
				unset($value['section']);
				$sections[$section]['fields'][] = $this->cleanSetting($value, $withWarnings);
			}
			$this->sections = $sections;
		}


		public function cleanSetting($value, $withWarnings = true) {
			$value = array_filter($value);
			

			if (isset($value['label'])) {
				$value['title'] = $value['label'];
				unset($value['label']);	
			}
			if (isset($value['std'])) {
				$value['default'] = $value['std'];
				unset($value['std']);	
			}				

			if (isset($value['choices'])) {
				$value['options'] = array();

				foreach ($value['choices'] as $ckey=>$cval) {
					$cval = array_filter($cval);
					if (isset($cval['src'])) {
						$value['options'][$cval['value']] = array( 'alt' => $cval['label'], 'img' => $cval['src'] );
					} else {
						$value['options'][$cval['value']] = $cval['label'];	
					}
				}
				unset($value['choices']);	
			}

			switch ($value['type']) {
				case "background":

				break;
				case "category-checkbox":
					$value['type'] = "checkbox";
					$value['data'] = "category";
					$value['args'] = array( 'hide_empty' => false );				
				break;
				case "category-select":
					$value['type'] = "select";
					$value['data'] = "category";
					$value['args'] = array( 'hide_empty' => false );
					$value['multi'] = false;
				break;
				case "checkbox":
					$value['type'] = "checkbox";
					if (isset($value['options'])) {

					}
				break;
				case "colorpicker":
					$value['type'] = "color";
				break;
				case "css":
					$value['type'] = "ace_editor";
					if (isset($value['rows'])) {
						unset($value['rows']);
					}
					$value['mode'] = 'css';
					$value['theme'] = 'monokai';
				break;
				case "custom-post-type-select":
					$value['multi'] = false;
					$value['type'] = "select";
			 		/* setup the post types */
			        $value['post_type'] = isset( $value['post_type'] ) ? explode( ',', $value['post_type'] ) : array( 'post' );
			        /* query posts array */
			        $value['args'] = apply_filters( 'ot_type_custom_post_type_checkbox_query', array( 'post_type' => $value['post_type'], 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'any' ), $value['id'] );
			        unset($value['post_type']);	
			        $value['data'] = "posts";				
				break;
				case "custom-post-type-checkbox":
					$value['type'] = "checkbox";
			 		/* setup the post types */
			        $value['post_type'] = isset( $value['post_type'] ) ? explode( ',', $value['post_type'] ) : array( 'post' );
			        /* query posts array */
			        $value['args'] = apply_filters( 'ot_type_custom_post_type_checkbox_query', array( 'post_type' => $value['post_type'], 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'any' ), $value['id'] );
			        unset($value['post_type']);	
			        $value['data'] = "posts";				
				break;
				case "list-item":
//print_r($value);
					$value['type'] = "group";
					$value['groupname'] = $value['title'];
					if (isset($value['settings'])) {
						$value['fields'] = array();
						foreach($value['settings'] as $setting)	{
							$value['fields'][] = $this->cleanSetting($setting);
						}
					}
					//print_r($value['fields']);

					//exit();
				break;
				case "slider":
					$value['type'] = "slides";
				break;					
				case "measurement":
					$value['type'] = "spacing";
					$value['all'] = true;
				break;	
				case "numeric_slider":
					$value['type'] = "slider";
					if (isset($value['min_max_step'])) {
						$min_max_step = explode(',', $value['min_max_step']);
						$value['min'] = $min_max_step[0];
						$value['max'] = $min_max_step[1];
						$value['step'] = $min_max_step[2];
					} else {
						$value['min'] = 1;
						$value['max'] = 100;
						$value['step'] = 1;
					}
					//print_r($value);
				break;	
				case "page-select":
					$value['type'] = "select";
					$value['data'] = "page";
					$value['multi'] = false;
				break;						
				case "page-checkbox":
					$value['type'] = "checkbox";
					$value['data'] = "page";
				break;						
				case "post-select":
					$value['type'] = "select";
					$value['data'] = "post";
					$value['multi'] = false;
				break;						
				case "post-checkbox":
					$value['type'] = "checkbox";
					$value['data'] = "post";
				break;						
				case "radio":
				break;						
				case "radio-image":
					$value['type'] = "image_select";
					if (!isset($value['options'])) {
						$value['options'] = array(
							
							'left-sidebar' => array('alt' => 'Left Sidebar', 'img' => ReduxFramework::$_url.'assets/img/2cl.png'),
							'right-sidebar' => array('alt' => 'Right Sidebar', 'img' => ReduxFramework::$_url.'assets/img/2cr.png'),
							'full-width' => array('alt' => 'full-width', 'img' => ReduxFramework::$_url.'assets/img/1col.png'),
							'dual-sidebar' => array('alt' => 'Dual Sidebar', 'img' => ReduxFramework::$_url.'assets/img/3cm.png'),
							'left-dual-sidebar' => array('alt' => 'Left Dual Sidebar', 'img' => ReduxFramework::$_url.'assets/img/3cl.png'),
							'right-dual-sidebar' => array('alt' => 'Right Dual Sidebar', 'img' => ReduxFramework::$_url.'assets/img/3cr.png')
						);
					}
				break;						
				case "select":
				
				break;						
				case "sidebar-select":
					$value['type'] = "select";
					$value['data'] = "sidebar";
					$value['multi'] = false;
				break;	
				case "sidebar-checkbox":
					$value['type'] = "checkbox";
					$value['data'] = "sidebar";

				break;					
				case "tag-checkbox":
					$value['type'] = "checkbox";
					$value['title'] = $value['title'];
					$value['data'] = "tags";
				break;						
				case "tag-select":
					$value['type'] = "select";
					$value['title'] = $value['title'];
					$value['data'] = "tags";
					$value['multi'] = false;
				break;						
				case "taxonomy-select":
			        $taxonomy = isset( $value['taxonomy'] ) ? explode( ',', $value['taxonomy'] ) : array( 'category' );
			        unset( $value['taxonomy'] );
        			$value['args'] = array( 'hide_empty' => false, 'taxonomy' => $taxonomy );
					$value['type'] = "select";
					$value['data'] = "category";
					$value['multi'] = false;
				break;						
				case "taxonomy-checkbox":
			        $taxonomy = isset( $value['taxonomy'] ) ? explode( ',', $value['taxonomy'] ) : array( 'category' );
			        unset( $value['taxonomy'] );
        			$value['args'] = array( 'hide_empty' => false, 'taxonomy' => $taxonomy );				
					$value['type'] = "checkbox";
					$value['data'] = "category";
				break;						
				case "text":
				case "input":
				
				break;						
				case "textarea":
					$value['type'] = "editor";
				break;						
				case "textarea-simple":
					$value['type'] = "textarea";
				break;						
				case "textblock":
					$value['type'] = "raw";
					$value['content'] = $value['desc'];
					unset($value['desc'], $value['title']);
				break;						
				case "textblock-titled":
					$value['type'] = "info";
				break;						
				case "typography":
				
				break;						
				case "upload":
					$value['type'] = "media";
				break;
				case "gallery":
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
			//	$value['default'] = $this->convertValue($value['default'], $value['type']);
			}			
			return $value;
		}		

		function get_attachment_id_by_url( $url ) {
			// Split the $url into two parts with the wp-content directory as the separator.
			$parse_url  = explode( parse_url( WP_CONTENT_URL, PHP_URL_PATH ), $url );
		 	
			// Get the host of the current site and the host of the $url, ignoring www.
			$this_host = str_ireplace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );
			$file_host = str_ireplace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );
		 
			// Return nothing if there aren't any $url parts or if the current host and $url host do not match.
			if ( ! isset( $parse_url[1] ) || empty( $parse_url[1] ) || ( $this_host != $file_host ) )
				return;
		 
			// Now we're going to quickly search the DB for any attachment GUID with a partial path match.
			// Example: /uploads/2013/05/test-image.jpg
			global $wpdb;
		 
			$prefix     = $wpdb->prefix;
			$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM " . $prefix . "posts WHERE guid RLIKE %s;", $parse_url[1] ) );
		 
			// Returns null if no attachment is found.
			return $attachment[0];
		}		

		function convertValue($value, $type) {
			switch ($type) {
				case "text":
		    		break;  
				case "media":
						$value = array('url' => $value );
		    		break; 		    		
				case "checkbox":
				case "taxonomy-checkbox":
				case "tag-checkbox":
				case "sidebar-checkbox":
				case "post-checkbox":
				case "page-checkbox":
				case "custom-post-type-checkbox":
				case "category-checkbox":		
					foreach ($value as $key => $val) {
						//if (count($value) == 1) {
							//$value[$key] = 1;
						//} else {
							$value[$key] = 1;	
						//}
						
					}

		    		break;  		    			
		    	default:
		    		break;
		    }
			return $value;			
		}	
	}

}

/*
1.5
	SMOF_VERSION
	define( 'OPTIONS', $theme_name.'_options' );
	$data = of_get_options();
	$smof_data = of_get_options();


1.4.3
	define( 'OPTIONS', $theme_name.'_options' );

        if( is_child_theme() ) {
                $temp_obj = wp_get_theme();
                $theme_obj = wp_get_theme( $temp_obj->get('Template') );
        } else {
                $theme_obj = wp_get_theme();    
        }

        define( 'OPTIONS', $theme_name.'_options' );

        SMOF_VERSION -> Version

1.4
	SMOF_VERSION -> Version
	DEFINE: OPTIONS
	$data => values
	$data = get_option(OPTIONS);	

1.3
	DEFINE: OPTIONS
	$of_options => Options
	$data => values
	$data = get_option(OPTIONS);

v1.2


v1.1 13/11/11
	DEFINE: OPTIONS
	$of_options => Options
	$data => values
	$data = get_option(OPTIONS);
 */