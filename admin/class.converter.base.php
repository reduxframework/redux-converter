<?php

if( !class_exists( 'Convert2Redux' ) ) {
	class Convert2Redux {

		protected $converter;
		public $version;
		public $database;
		public $data;
		public $config;
		public $sections;
		public $framework = "";
		public $convertDataClass;

		public function __construct( $converter, $framework ) {
			
			$this->converter = $converter;
			$this->framework = $framework;
			add_action('init', array($this, 'addPanel'), 100);
			add_action( 'admin_footer', array($this,'ajax_javascript') );
			add_action( 'wp_ajax_'.$this->framework.'_2_Redux', array($this,'ajax_callback') );

		}

		function ajax_javascript() {
			?>
			<script type="text/javascript">
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

			include(dirname(__FILE__)."/class.converter.{$this->framework}.data.php");
			$class = $this->framework.'2Redux_Data';
			
			$this->convertDataClass = new $class();
			
			$this->convertDataClass->init();

			$_REQUEST['framework'] = $this->framework;

		    $_REQUEST['sections'] = $this->convertDataClass->sections;

			if ( !empty( $_REQUEST['sections'] ) ) {
				
				$_REQUEST['sections'] =  $this->converter->objectToHTML( $_REQUEST['sections'] );

				global $wp_filesystem;
				$_REQUEST['migrate_data_class'] = str_replace('<?php', '', $wp_filesystem->get_contents(dirname(__FILE__).'/class.converter.'.$this->framework.'.data.php') );

				echo $this->converter->getConfigFile($_REQUEST);

			}

			die(); // this is required to return a proper result
		}

	

		public function addPanel() {

			include(dirname(__FILE__).'/class.converter.'.$this->framework.'.data.php');
			$class = $this->framework.'2Redux_Data';
			$this->convertDataClass = new $class();
			$this->convertDataClass->init();

			if (!empty($this->convertDataClass->sections) && class_exists('ReduxFramework')) {

				$args = array(
					'opt_name'=>$this->framework.'2Redux_Panel', 
					'save_defaults'=>false,
					'menu_title' => $this->framework.' 2 Redux',
					//'database'	=> 'transient',
					'output' => false,
					'show_import_export' => false,
					'page_slug' => $this->framework.'Redux_Converter',
					'enqueue' => false,
					'intro_text' => '<p>This is your panel converted. Saving will be saved to a transient value which gets reset every hour. <a href="javascript:jQuery(\'.redux-group-tab-link-a:last\').click();">Proceed here</a> to get the export code you would need to migrate from '.$this->framework.' to Redux.</p>'
				);
				$theme = wp_get_theme();

				$args['display_name'] = $theme->get('Name');
				$args['display_version'] = $theme->get('Version');
			    $args['google_api_key'] = 'AIzaSyAX_2L_UzCDPEnAHTG7zhESRVpMPS4ssII';

			    $this->convertDataClass->sections[] = array('type'=>'divide');
			    $this->convertDataClass->sections[] = array( 
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

				$ReduxFramework = new ReduxFramework($this->convertDataClass->sections, $args);	

				
				if ( empty( $ReduxFramework->options ) || ( isset( $ReduxFramework->options['redux_convert_refresh_data'] ) && is_array($ReduxFramework->options['redux_convert_refresh_data']) && $ReduxFramework->options['redux_convert_refresh_data'][1] == 1 ) ) {
					$ReduxFramework->options = $this->convertDataClass->converted_data;
				}
				foreach($this->convertDataClass->sections as $section) {
					if (isset($section['fields'])) {
						foreach($section['fields'] as $field) {
							if( isset( $field['required'] ) ) {
					            $ReduxFramework->get_fold($field);
						    }		
						}						
					}
					
				}
			}			
		}

	}
}

