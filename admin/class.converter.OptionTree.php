<?php

if( !class_exists( 'OptionTree2Redux' ) ) {
	class OptionTree2Redux {

		protected $converter;
		public $version;
		public $database;
		public $data;
		public $config;
		public $sections;
		public $framework = "OptionTree";

		public function __construct( $converter ) {
			
			$this->converter = $converter;

			add_action('init', array($this, 'addPanel'), 100);

			add_action( 'admin_footer', array($this,'ajax_javascript') );
			add_action( 'wp_ajax_'.$this->framework.'_2_Redux', array($this,'ajax_callback') );
		}



		function ajax_javascript() {
			?>
			<script type="text/javascript" >
			jQuery(document).ready(function($) {
				$('.redux-converter-action').click(function() {
					var parent = $(this).parents('.redux-group-tab:first');
					var data = {
						action: '<?php echo $this->framework; ?>_2_Redux',
						nonce: parent.find('.convertToReduxNonce').val(),
						opt_name: parent.find('#redux_opt_name-text').val(),
						migrate_data: parent.find('#redux_convert_data').val(),
						global_variable: parent.find('#redux_global_variable-text').val(),
						delete_data: parent.find('#<?php echo $this->framework; ?>2Redux_Panel_redux_delete_old_data_1_0').is(":checked"),
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

			if ( !wp_verify_nonce( $_REQUEST['nonce'], 'convertToRedux' . $this->framework ) ) {
				//die();
			}
			if (isset($_REQUEST['download'])) {
				header("Content-Type: application/octet-stream");
				header("Content-Transfer-Encoding: Binary");
				header("Pragma: no-cache");
				header("Expires: 0");
				header("Content-disposition: attachment; filename=\"ReduxFramework.config.php\""); 
			} else {
				header("Content-Type: text/plain");	
			}

			$_REQUEST['uuid'] = uniqid($_REQUEST['nonce']);
			unset($_REQUEST['migrate_data']);

		    $_REQUEST['sections'] = $this->getSections();

			if ( !empty( $_REQUEST['sections'] ) ) {

				$_REQUEST['sections'] =  $this->converter->objectToHTML( $_REQUEST['sections'] );

				echo $this->converter->getConfigFile($_REQUEST);

			}

			die(); // this is required to return a proper result
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
		
			return $sections;

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


		public function addPanel() {

			$this->version = OT_VERSION;		

			$sections = $this->getSections();

			$this->data = get_option( 'option_tree' );

			if (!empty($sections) && class_exists('ReduxFramework')) {

				$args = array(
					'opt_name'=>$this->framework.'2Redux_Panel', 
					'save_defaults'=>false,
					'menu_title' => $this->framework.' 2 Redux',
					//'database'	=> 'transient',
					'output' => false,
					'show_import_export' => false,
					'page_slug' => $this->framework.'Redux_Converter',
					'enqueue' => false,
					'intro_text' => '<p>This is your panel converted. Saving will be saved to a transient value which gets reset every hour. <a href="./admin.php?page='.$this->framework.'_2_redux">Proceed here</a> to get the export code you would need to migrate from '.$this->framework.' to Redux.</p>'
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
							'subtitle' => __('Reset this panel to match what is inside '.$this->framework.'.', 'redux-framework-demo'),
							'options'=>array(1=>'Reset this panel to match what is stored in '.$this->framework.'.'),
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
							'desc' => 'By default the global variable is the same as the opt_name. If you want it to be something else (or your old variable), choose a name here. All standard variable rules apply (no spaces, dashes, or other odd symbols). '.$this->framework.'\'s typical variables (if you wanted to keep your code as is) are $data or $smof_data.',
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
							'content' => '<center><input type="hidden" class="convertToReduxNonce" value="'.wp_create_nonce( 'convertToRedux'.$this->framework ).'"><a href="#" target="_blank" class="button button-primary redux-converter-action">View Redux Config File</a> <a href="#" data-action="download" class="button button-primary redux-converter-action">Download Redux Config File</a></center>',
							
						),			    		
			    	)
			    );

				$ReduxFramework = new ReduxFramework($sections, $args);	

				//print_r(get_option( 'option_tree' ));

				//print_r(ot_get_option('my_category_checkbox'));
				//echo ot_get_option('my_category_checkbox');

				$convertData = false;
				if ( empty( $ReduxFramework->options ) || ( isset( $ReduxFramework->options['redux_convert_refresh_data'] ) && $ReduxFramework->options['redux_convert_refresh_data'] == 1 ) ) {
					$convertData = true;
					$ReduxFramework->options = get_option( 'option_tree' );
				}
				foreach($sections as $section) {
					if (isset($section['fields'])) {
						foreach($section['fields'] as $field) {
							if ($convertData && isset($ReduxFramework->options[$field['id']]) && !empty($ReduxFramework->options[$field['id']])) {
								$ReduxFramework->options[$field['id']] = $this->convertValue($ReduxFramework->options[$field['id']], $field); // Not sure why this happens. Huh.
							}
							if( isset( $field['required'] ) ) {
					            $ReduxFramework->get_fold($field);
						    }		
						}						
					}
					
				}
			}			
		}

		function convertValue($value, $field) {
		    switch ($field['type']) {
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