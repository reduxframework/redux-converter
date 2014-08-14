<?php

    if ( ! class_exists( 'SMOF2Redux_Data' ) ) {
        class SMOF2Redux_Data {

            protected $converter;
            public $version;
            public $database;
            public $data;
            public $converted_data;
            public $sections = array();
            public $framework = "SMOF";

            public function __construct() {

                //add_action('init', array( $this, 'init' ), 0 );

            }

            public function init() {
                // Find the version
                if ( defined( 'SMOF_VERSION' ) ) {
                    $this->version = SMOF_VERSION;
                } else {
                    $this->version = '1.3';
                }


                $this->field_types = scandir( ReduxFramework::$_dir . '/inc/fields' );


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

                $this->getSections();

                if ( ! empty( $this->sections ) ) {
                    foreach ( $this->sections as $section ) {
                        if ( isset( $section['fields'] ) && ! empty( $section['fields'] ) ) {
                            foreach ( $section['fields'] as $field ) {
                                if ( isset( $this->data[ $field['id'] ] ) ) {
                                    $this->converted_data[ $field['id'] ] = $this->convertValue( $this->data[ $field['id'] ], $field['type'] );
                                }
                            }
                        }
                    }
                }

            }

            public function getSections( $withWarnings = true ) {
                global $of_options;

                $sections = array();
                $section  = array();
                $fields   = array();

                foreach ( $of_options as $key => $value ) {
                    foreach ( $value as $k => $v ) {
                        if ( empty( $v ) ) {
                            unset( $value[ $k ] );
                        }
                    }

                    if ( isset( $value['name'] ) ) {
                        $value['title'] = $value['name'];
                        unset( $value['name'] );
                    }

                    if ( isset( $value['std'] ) ) {
                        $value['default'] = $value['std'];
                        unset( $value['std'] );
                    }

                    if ( isset( $value['fold'] ) ) {
                        $value['required'] = array( $value['fold'], '=', 1 );
                        unset( $value['fold'] );
                    }
                    if ( isset( $value['folds'] ) ) {
                        unset( $value['folds'] );
                    }
                    if ( ! isset( $value['type'] ) ) {
                        continue;
                    }


                    switch ( $value['type'] ) {
                        case 'heading':
                            if ( isset( $value['icon'] ) && ! empty( $value['icon'] ) ) {
                                //$value['icon_type'] = "image";
                            }
                            if ( ! empty( $fields ) ) {
                                $section['fields'] = $fields;
                                $fields            = array();
                            }
                            if ( ! empty( $section ) ) {
                                $section['icon'] = "el-icon-cog";
                                $sections[]      = $section;
                                $section         = array();
                            }
                            unset( $value['type'] );
                            $section = $value;
                            unset( $value );
                            break;
                        case "text":
                            if ( isset( $value['mod'] ) ) {
                                unset( $value['mod'] );
                            }
                            break;
                        case "select":
                            if ( isset( $value['mod'] ) ) {
                                unset( $value['mod'] );
                            }
                            break;
                        case "textarea":
                            if ( isset( $value['cols'] ) ) {
                                unset( $value['cols'] );
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
                            if ( isset( $value['preview'] ) ) {
                                unset( $value['preview'] );
                            }
                            if ( isset( $value['options'] ) ) {
                                $value['fonts'] = $value['options'];

                                unset( $value['options'] );
                            }
                            if ( isset( $value['default'] ) ) {
                                unset( $value['default'] );
                            }
                            $value['type'] = "typography";
                            break;
                        case "typography":
                            if ( isset( $value['preview'] ) ) {
                                unset( $value['preview'] );
                            }
                            if ( isset( $value['options'] ) ) {
                                $value['fonts'] = $value['options'];
                                unset( $value['options'] );
                            }
                            break;
                        case "border":
                            break;
                        case "info":
                            if ( isset( $value['title'] ) ) {
                                unset( $value['title'] );
                            }
                            if ( isset( $value['default'] ) ) {
                                $value['raw'] = $value['default'];
                                unset( $value['default'] );
                            }
                            break;
                        case "switch":
                            break;
                        case "images":
                            $value['type'] = "image_select";
                            if ( strpos( strtolower( $value['title'] ), 'pattern' ) !== false ) {
                                $value['tiles'] = true;
                            }
                            break;
                        case "image":
                            $value['type']     = "info";
                            $value['raw_html'] = true;
                            break;
                        case "slider":
                            $value['type'] = "slides";
                            break;
                        case "sorter":
                            if ( isset( $value['default'] ) ) {
                                $value['options'] = $value['default'];
                                unset( $value['default'] );
                            }
                            break;
                        case "tiles":
                            $value['type']  = "image_select";
                            $value['tiles'] = true;
                            break;
                        case "backup":
                        case "transfer":
                            unset( $value );
                            if ( $of_options[ ( $key - 1 ) ]['type'] == "heading" ) {
                                if ( strpos( strtolower( $of_options[ ( $key - 1 ) ]['name'] ), 'backup' ) !== false ) {
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
                            if ( isset( $value['mod'] ) && $value['mod'] == "min" ) {
                                unset( $value['mod'] );
                            } else {
                                $value['url'] = true;
                            }
                            break;

                        default:
                            if ( $withWarnings && ! in_array( $value['type'], $this->field_types ) ) {

                                $content = "<h3 style='color: red;'>Found a field with an unknown type!</h3> <p>Perhaps this was a custom field and will need to be remade for use within Redux. This was the field's configuration:</p>";
                                $content .= "<pre style='overflow:auto;border: 2px dashed #eee;padding: 2px 5px; width: 100%;'>";
                                ob_start();
                                var_dump( $value );
                                $content .= ob_get_clean();
                                $content .= "</pre>";
                                $value['desc']     = $content;
                                $value['type']     = "info";
                                $value['raw_html'] = true;
                            }

                            //unset($value); // Can't do custom types. Must be fixed manually.
                            # code...
                            break;
                    }
                    if ( isset( $value['default'] ) && ! empty( $value['default'] ) ) {
                        $value['default'] = $this->convertValue( $value['default'], $value['type'] );
                    }

                    if ( ! empty( $value ) ) {
                        $fields[] = $value;
                    }

                }
                if ( ! empty( $fields ) ) {
                    $section['fields'] = $fields;
                    $fields            = array();
                }
                if ( ! empty( $section ) ) {

                    $sections[] = $section;
                    $section    = array();
                }
                $this->sections = $sections;

            }

            function get_attachment_id_by_url( $url ) {
                // Split the $url into two parts with the wp-content directory as the separator.
                $parse_url = explode( parse_url( WP_CONTENT_URL, PHP_URL_PATH ), $url );

                // Get the host of the current site and the host of the $url, ignoring www.
                $this_host = str_ireplace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );
                $file_host = str_ireplace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );

                // Return nothing if there aren't any $url parts or if the current host and $url host do not match.
                if ( ! isset( $parse_url[1] ) || empty( $parse_url[1] ) || ( $this_host != $file_host ) ) {
                    return;
                }

                // Now we're going to quickly search the DB for any attachment GUID with a partial path match.
                // Example: /uploads/2013/05/test-image.jpg
                global $wpdb;

                $prefix     = $wpdb->prefix;
                $attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM " . $prefix . "posts WHERE guid RLIKE %s;", $parse_url[1] ) );

                // Returns null if no attachment is found.
                return $attachment[0];
            }

            function convertValue( $value, $type ) {
                switch ( $type ) {
                    case "text":
                        if ( ! is_array( $value ) ) {
                            $value = stripcslashes( $value ); // Not sure why this happens. Huh.
                        }
                        break;
                    case "typography":
                        $default = array();
                        if ( isset( $value['size'] ) ) {
                            $default['font-size'] = $value['size'];
                            $px                   = filter_var( $default['font-size'], FILTER_SANITIZE_NUMBER_INT );
                            $default['units']     = str_replace( $px, "", $default['font-size'] );
                        }
                        if ( isset( $value['color'] ) ) {
                            $default['color'] = $value['color'];
                        }
                        if ( isset( $value['face'] ) ) {
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
                            foreach ( $fonts as $font ) {
                                if ( strpos( strtolower( $font ), strtolower( $value['face'] ) ) !== false ) {
                                    $default['font-family'] = $font;
                                }
                            }
                        }
                        if ( isset( $value['style'] ) ) {
                            if ( strpos( strtolower( $value['style'] ), 'bold' ) !== false ) {
                                $default['font-weight'] = "bold";
                            }
                            if ( strpos( strtolower( $value['style'] ), 'italic' ) !== false ) {
                                $default['font-style'] = "italic";
                            }
                        }
                        $value = $default;
                        break;
                    case "border":
                        if ( isset( $value['width'] ) ) {
                            $value['border-width'] = $value['width'] . "px";
                            $value['units']        = "px";
                            unset( $value['width'] );
                        }
                        if ( isset( $value['color'] ) ) {
                            $value['border-color'] = $value['color'];
                            unset( $value['color'] );
                        }
                        if ( isset( $value['style'] ) ) {
                            $value['border-style'] = $value['style'];
                            unset( $value['style'] );
                        }
                        break;
                    case "upload":
                    case "image":
                    case "media":
                        if ( isset( $value ) && ! empty( $value ) ) {
                            $value = array( 'url' => $value );
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