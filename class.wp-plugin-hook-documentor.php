<?php
/**
 * File: class.wp-plugin-hook-documentor.php
 * @package wp-plugin-hook-documentor
 */

/**
 * WordPress Plugin Hook Documentor
 *
 * @package wp-plugin-hook-documentor
 * @author	Juliette Reinders Folmer, {@link http://www.adviesenzo.nl/ Advies en zo} -
 *  <wp-plugin-hook-documentor@adviesenzo.nl>
 *
 * @version	0.2
 * @since	2013-07-03 // Last changed: by Juliette Reinders Folmer
 * @copyright	Advies en zo, Meedenken en -doen �2013
 * @license http://www.opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @license	http://opensource.org/licenses/academic Academic Free License Version 1.2
 * @example	example/example.php
 *
 */
 
/**
 * Roadmap / Todo:
 * - Build the bloody thing ;-)
 *
 * Future:
 * - Allow .zip/.gz file inputs as source
 * - Hook into GitHub API to allow git:// urls as inputs for source
 * - Compare two php hook arrays
 * - Allow second source input so two versions can be compared and diffed for new hooks/filters
 *
 */

if ( !class_exists( 'wp_plugin_hook_documentor' ) ) {

	class wp_plugin_hook_documentor {
		
		/**
		 * @const	string	version number of this class
		 */
		const VERSION = '0.2';

		/**
		 * @const	bool	temporary constant for use during initial development
		 */
		const DEV = true;



		/**
		 * @param	string	$source		path to the source directory to walk as received
		 */
		public $source;
		
		/**
		 * @param	string	$source		path to the source directory to walk as received
		 */
//		public $source_rel = '../../debug-bar-constants/debug-bar-constants';
		public $source_rel = '../../MimeTypes-Link-Icons';

		
		/**
		 * @param	string	$source_walkable		path to the source directory to walk
		 */
		public $source_walkable;
	
		/**
		 * @param
		 */
//		public $hierarchical;
	
	
		/**
		 * @param	string	$sort_by	chosen sort method
		 */
		public $sort_by;
		
		/**
		 * To translate the text strings, override the value strings of the property in your extended class
		 * If you want to add sorting methods, make sure you override the sort_hooks() method as well
		 *
		 * @param	array	$styles		available output styles
		 */
		public $sort_options = array(
			'name'				=>	'Hook name',
			'file_line'			=>	'File, then line number',
			'type_name'			=>	'Hook type, then hook name',
			'type_file_line'	=>	'Hook type, then file name and line number',
		);

		/**
		 * @param	string	$style		chosen output style
		 */
		public $style;

		/**
		 * To translate the text strings, override the value strings of the property in your extended class
		 *
		 * @param	array	$styles		available output styles
		 */
		public $styles = array(
			   'text'	=>	'Text',
			   'html'	=>	'XHTML',
			   'xml'	=>	'XML1.0',
			   'php'	=>	'PHP',
		);

		/**
		 * @param	string	$format		chosen output format
		 */
		public $format;

		/**
		 * To translate the text strings, override the value strings of the property in your extended class
		 *
		 * @param	array	$formats	available output formats
		 */
		public $formats = array(
			'return'	=>	'Return the string/xml object/php array',
			'textarea'	=>	'Show the resulting code in a textarea',
			'view'		=>	'Show the result in the chosen style',
			'file'		=>	'Present the result as a file',
		);
		
		
		/**
		 * @param	array	$extensions	Which file extensions to look for
		 */
		private $extensions = array( 'php' );
		
		/**
		 * @param	array	$hook_names		Array of wordpress function names which call hooks and their type
		 */
		private $hook_names = array(
			'apply_filters'				=>	'filter',
			'apply_filters_ref_array'	=>	'filter',
			'do_action'					=>	'action',
			'do_action_ref_array'		=>	'action',
		);
		
		private $token_type = 'T_STRING';

	
		/**
		 * @param	string	$plugin_name	Store for the retrieved plugin name
		 */
		public $plugin_name = '';

		/**
		 * @param	array	$hooks		Store for the retrieved hook information
		 *								Format:
		 *								$a['hook_name'] = array(
		 *									'file'		=>	$file_name,
		 *									'line'		=>	$line_number,
		 *									'type'		=>	'action' / 'filter',
		 *									'params'	=>	$hook_params,
		 *									'signature'	=>	$signature,
		 *									'docs'		=>	$documentation,
		 *								)
		 */
		public $hooks = array();
	
	
		function __construct( $source = '', /*$hierarchical = false,*/ $sort_by = 'name', $style = 'html', $format = 'textarea' ) {

			if( version_compare( PHP_VERSION, '5', '<' ) === true ) {
				trigger_error( 'The WP plugin hook documentor requires PHP5+', E_USER_NOTICE );
				exit;
			}

			if( self::DEV === true ) {
				include_once( 'include/jrfdebug.inc.php' );
			}

			$this->validate_params( $source, 'source' );
//			$this->validate_params( $hierarchical, 'hierarchical' );
			$this->validate_params( $sort_by, 'sort_by' );
			$this->validate_params( $style, 'style' );
			$this->validate_params( $format, 'format' );
		}


		function validate_params( $value, $param ) {
			switch( $param ) {

				// @todo: add validation for whether directory can be walked
				case 'source':
					if( is_string( $value ) ) {
						$this->source = $value;
					}
					break;

				// @todo: maybe add a way to allow 0/1 as int and/or string
/*				case 'hierarchical':
					if( is_bool( $value ) ) {
						$this->hierarchical = $value;
					}
					break;
*/

				case 'sort_by':
					if( array_key_exists( $value, $this->sort_options ) ) {
						$this->sort_by = $value;
					}
					break;

				case 'style':
					if( array_key_exists( $value, $this->styles ) ) {
						$this->style = $value;
					}
					break;
	
				case 'format':
					if( array_key_exists( $value, $this->formats ) ) {
						$this->format = $value;
					}
					break;
			}
		}
	

		
		
		function display_output() {
			
			$this->get_hooks();

			if( is_array( $this->hooks ) && count( $this->hooks ) > 0 ) {

				$this->sort_hooks();
				
				$output = $this->generate_output();


                return $this->format_output( $output );
			}
            else {
                return false;
            }
		}

		
		
		function get_hooks() {
			// Efficiency - in case someone wants the same output in several ways, no need to get the hooks again
			if( isset( $this->hooks ) && ( is_array( $this->hooks ) && count( $this->hooks ) > 0 ) ) {
				return $this->hooks;
			}

			include_once( 'include/class.directorywalker.php' );

			$filelist = wpphd_directory_walker::get_file_list( $this->source, true, $this->extensions );

//			pr_var( $filelist, 'retrieved file list', true );


			require_once 'include/class.wp_plugin_tokenizer.php';

  			$slash = ( strrchr( $this->source, DIRECTORY_SEPARATOR ) === DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR );
  			
  			$hooks = array();


			foreach( $filelist as $filename ) {
				$ts = new wpphd_wp_plugin_tokenizer( $this->source . $slash . $filename );
				
				$this->plugin_name = $ts->get_plugin_name( $this->plugin_name );
				
//				pr_var( $ts, 'PHP_Token_Stream class for: ' . $this->source . $slash . $filename, true );


				$filtered = $ts->filter_on_value_and_type( array_keys( $this->hook_names ), $this->token_type );

//				pr_var( $filtered, 'filtered list', true );


				foreach( $filtered as $k => $token ) {
					
					$signature = $ts->get_signature( $token, $k );
	  		 		$parsed_signature = $this->parse_hook_signature( $signature );

	  		 		$hooks[$parsed_signature['hook_name']] = array(
						'file'				=>	$filename,
						'line'				=>	$token->getLine(),
						'type'				=>	$this->hook_names[$token->__toString()],
						'called_by'			=>	$token->__toString(),
						'signature'			=>	$signature,
						'params'			=>	$parsed_signature['params'],
						'comment'			=>	$ts->get_nearest_comment( $token, $k ),
						'parsed_comment'	=>	$ts->parse_nearest_comment( $token, $k ),
//						'apidocblock'	=> $ts->get_nearest_comment( $token, $k, 'api' ),
	  		 		);
				}

			}

			$this->hooks = $hooks;
pr_var( $this->hooks, 'The hooks with gathered info', true );
		}
		
		
		function parse_hook_signature( $sig ) {
			$hook_names = implode( '|', array_keys( $this->hook_names ) );
			//apply_filters( 'mtli_filesize', '(' . $filesize . ')' )
			$found = preg_match( '`^(?:' . $hook_names . ')\s*\(\s*([\'"])([\w-]+)\1\s*,(.+)\)$`', $sig, $matches );
			if( $found > 0 ) {
				$sig = array(
					'hook_name'	=> 	$matches[2],
					'params'	=>	explode( ',', $matches[3] ),
				);
				$sig['params'] = array_map( 'trim', $sig['params'] );
			}
			return $sig;
		}

	
		
		/**
		 * Sorts the hooks array by the hook name using natural case sorting
		 * Alters the value of $this->hooks
		 *
		 * Make sure to override this method in your own class extension if you want
		 * to use a different sorting mechanism or add more sorting methods
		 *
		 * @since 0.2
		 * @author Juliette Reinders Folmer
		 *
		 * @return void
		 */
		function sort_hooks() {
			
			switch( $this->sort_by ) {
				case 'name':
					$this->sort_hooks_by_name();
					break;
				case 'file_line':
					$this->sort_hooks_by_file_and_line_nr();
					break;
				case 'type_name':
					$this->sort_hooks_by_type_and_name();
					break;
				case 'type_file_line':
					$this->sort_hooks_by_type_file_and_line_nr();
					break;
			}
			return;
		}
		
		/**
		 * Sort the hooks array by hook name
		 *
		 * @since 0.2
		 * @author Juliette Reinders Folmer
		 *
		 * @return void
		 */
		function sort_hooks_by_name() {
			uksort( $this->hooks, 'strnatcasecmp' );
		}

		/**
		 * Sort the hooks array by file name and line number
		 *
		 * @since 0.2
		 * @author Juliette Reinders Folmer
		 *
		 * @return void
		 */
		function sort_hooks_by_file_and_line_nr() {
			foreach( $this->hooks as $key => $array ) {
			    $file[$key]	= $array['file'];
			    $line[$key]	= $array['line'];
			}
			array_multisort( $file, SORT_ASC, $line, SORT_ASC, $this->hooks );
		}


		/**
		 * Sort the hooks array by hook type and hook name
		 *
		 * @since 0.2
		 * @author Juliette Reinders Folmer
		 *
		 * @return void
		 */
		function sort_hooks_by_type_and_name() {
			foreach( $this->hooks as $key => $array ) {
			    $type[$key]	= $array['type'];
			    $name[$key]	= $key;
			}
			array_multisort( $type, SORT_ASC, $name, SORT_ASC, $this->hooks );
		}


		/**
		 * Sort the hooks array by hook type, file name and line number
		 *
		 * @since 0.2
		 * @author Juliette Reinders Folmer
		 *
		 * @return void
		 */
		function sort_hooks_by_type_file_and_line_nr() {
			foreach( $this->hooks as $key => $array ) {
			    $type[$key]	= $array['type'];
			    $file[$key]	= $array['file'];
			    $line[$key]	= $array['line'];
			}
			array_multisort( $type, SORT_ASC, $file, SORT_ASC, $line, SORT_ASC, $this->hooks );
		}





		function generate_output() {

			$output = null;

			switch( $this->style ) {
				case 'html':
					$output = $this->generate_html_output(); // html string
					break;

				case 'xml':
					$output = $this->generate_xml_output(); // simpleXML xml object
					break;

				case 'text':
					$output = $this->generate_text_output(); // text string
					break;

				case 'php':
					$output = $this->generate_php_output(); // php array
					break;
			}
			return $output;
		}


		/**
		 *
		 *
		 * Want different text output ? Just override this method in your own extended class.
		 *
		 * @since 0.1
		 * @author Juliette Reinders Folmer
		 *
		 * @return string
		 */
		function generate_text_output() {
			$string = 'Actions and Filters for Plugin: ' . $this->plugin_name . "\r\n" . str_repeat( '=', 80 ) . "\r\n\r\n";

			foreach( $this->hooks as $key => $hook ) {
				$string .= 'Hook: ' . "\t\t\t\t" . $key . "\n\r" .
					'File Name: ' . "\t\t\t" . $hook['file'] . "\n\r" .
					'Line Number: ' . "\t\t\t" . $hook['line'] . "\n\r" .
					'Hook Type: ' . "\t\t\t" . $hook['type'] . "\n\r" .
					'Called by: ' . "\t\t\t" . $hook['called_by'] . "\n\r" .
					'Signature: ' . "\t\t\t" . $hook['signature'] . "\n\r" .
					str_pad ( 'Parameters:', 80, "-", STR_PAD_RIGHT ) . "\n\r";

				if( is_array( $hook['params'] ) && count( $hook['params'] ) > 0 ) {
					foreach( $hook['params'] as $param ) {
						$string .= $param . "\n\r";
					}
					unset( $param );
				}
				else {
					$string .= 'None' . "\n\r";
				}

				$string .= str_pad ( 'Available documentation:', 80, "-", STR_PAD_RIGHT ) . "\n\r";
				if( is_array( $hook['parsed_comment'] ) && count( $hook['parsed_comment'] )> 0 ) {
					foreach( $hook['parsed_comment'] as $tag => $comments_array ) {

						foreach( $comments_array as $key => $item ) {
							// Set the tag column
							if( $key === 0 ) {
//								$string .= str_pad( $tag . ' :', 20 );
								$col1 = str_pad( $tag . ' :', 18 );
							}
							else {
//								$string .= $prefix = str_repeat( ' ', 20 );
								$col1 = str_repeat( ' ', 18 );
                            }
                            
                            // Set the content column
							if( is_string( $item ) ) {
								$string .= $col1 . str_repeat( ' ', 14 ) . $item . "\n\r";
							}
							else if( is_array( $item ) && count( $item ) > 0 ) {
								// @todo -> add the repeated string for the array items after 1
								foreach( $item as $k => $v ) {
									$string .= $col1 . str_pad( $k . ':', 14 ) . $v . "\n\r";
								}
								unset( $k, $v );
							}
							else {
								$string .= '---' . "\n\r";
							}
						}
						unset( $key, $item );
					}
					unset( $tag, $comments_array );
				}
				else {
					$string .= 'None available' . "\n\r";
				}
				$string .= str_repeat( '*', 80 ) . "\r\n\r\n";
			}
			$string .= "\n\r\n\r";

			unset( $key, $hook );

            return $string;
		}
	
/*
        [mtli_classnames (string)] => Array:
        (
                [file (string)] => string[25] : �mime_type_link_images.php�
                [line (string)] => int : 1168
                [type (string)] => string[6] : �filter�
                [called_by (string)] => string[13] : �apply_filters�
                [signature (string)] => string[51] : �apply_filters( 'mtli_classnames', $new_classnames )�
                [params (string)] => Array:
                (
                        [0 (int)] => string[17] : � $new_classnames �
                )
                [comment (string)] => string[199] : �/* Add filter hook for classnames   @api string $new_classnames Allows a developer to filter the class names string   before it is returned to the class attribute of the link tag * /�
                [parsed_comment (string)] => Array:
                (
                        [description (string)] => Array:
                        (
                                [0 (int)] => string[30] : �Add filter hook for classnames�
                        )
                        [api (string)] => Array:
                        (
                                [0 (int)] => Array:
                                (
                                        [type (string)] => string[6] : �string�
                                        [var_name (string)] => string[15] : �$new_classnames�
                                        [comment (string)] => string[115] : � Allows a developer to filter the class names string before it is returned to the class attribute of the link tag�
                                )
                        )
                )
        )
*/


		/**
		 *
		 *
		 * Want different html output ? Just override this method in your own extended class.
		 *
		 * @since 0.1
		 * @author Juliette Reinders Folmer
		 *
		 * @return string
		 */
		function generate_html_output() {
			$string = 'Actions and Filters for Plugin: ' . $this->plugin_name . "\r\n" . str_repeat( '=', 80 ) . "\r\n\r\n";

			foreach( $this->hooks as $key => $hook ) {
				$string .= 'Hook: ' . "\t\t\t\t" . $key . "\n\r" .
					'File Name: ' . "\t\t\t" . $hook['file'] . "\n\r" .
					'Line Number: ' . "\t\t\t" . $hook['line'] . "\n\r" .
					'Hook Type: ' . "\t\t\t" . $hook['type'] . "\n\r" .
					'Called by: ' . "\t\t\t" . $hook['called_by'] . "\n\r" .
					'Signature: ' . "\t\t\t" . $hook['signature'] . "\n\r" .
					str_pad ( 'Parameters:', 80, "-", STR_PAD_RIGHT ) . "\n\r";

				if( is_array( $hook['params'] ) && count( $hook['params'] ) > 0 ) {
					foreach( $hook['params'] as $param ) {
						$string .= $param . "\n\r";
					}
					unset( $param );
				}
				else {
					$string .= 'None' . "\n\r";
				}

				$string .= str_pad ( 'Available documentation:', 80, "-", STR_PAD_RIGHT ) . "\n\r";
				if( is_array( $hook['parsed_comment'] ) && count( $hook['parsed_comment'] )> 0 ) {
					foreach( $hook['parsed_comment'] as $tag => $comments_array ) {

						foreach( $comments_array as $key => $item ) {
							// Set the tag column
							if( $key === 0 ) {
//								$string .= str_pad( $tag . ' :', 20 );
								$col1 = str_pad( $tag . ' :', 18 );
							}
							else {
//								$string .= $prefix = str_repeat( ' ', 20 );
								$col1 = str_repeat( ' ', 18 );
                            }
                            
                            // Set the content column
							if( is_string( $item ) ) {
								$string .= $col1 . str_repeat( ' ', 14 ) . $item . "\n\r";
							}
							else if( is_array( $item ) && count( $item ) > 0 ) {
								// @todo -> add the repeated string for the array items after 1
								foreach( $item as $k => $v ) {
									$string .= $col1 . str_pad( $k . ':', 14 ) . $v . "\n\r";
								}
								unset( $k, $v );
							}
							else {
								$string .= '---' . "\n\r";
							}
						}
						unset( $key, $item );
					}
					unset( $tag, $comments_array );
				}
				else {
					$string .= 'None available' . "\n\r";
				}
				$string .= str_repeat( '*', 80 ) . "\r\n\r\n";
			}
			$string .= "\n\r\n\r";

			unset( $key, $hook );

            return $string;
		}
	


/*
        [mtli_classnames (string)] => Array:
        (
                [file (string)] => string[25] : �mime_type_link_images.php�
                [line (string)] => int : 1168
                [type (string)] => string[6] : �filter�
                [called_by (string)] => string[13] : �apply_filters�
                [signature (string)] => string[51] : �apply_filters( 'mtli_classnames', $new_classnames )�
                [params (string)] => Array:
                (
                        [0 (int)] => string[17] : � $new_classnames �
                )
                [comment (string)] => string[199] : �/* Add filter hook for classnames   @api string $new_classnames Allows a developer to filter the class names string   before it is returned to the class attribute of the link tag * /�
                [parsed_comment (string)] => Array:
                (
                        [description (string)] => Array:
                        (
                                [0 (int)] => string[30] : �Add filter hook for classnames�
                        )
                        [api (string)] => Array:
                        (
                                [0 (int)] => Array:
                                (
                                        [type (string)] => string[6] : �string�
                                        [var_name (string)] => string[15] : �$new_classnames�
                                        [comment (string)] => string[115] : � Allows a developer to filter the class names string before it is returned to the class attribute of the link tag�
                                )
                        )
                )
        )
*/

		/**
		 *
		 *
		 * Want different xml output ? Just override this method in your own extended class.
		 *
		 * @since 0.1
		 * @author Juliette Reinders Folmer
		 *
		 * @return object
		 */
		function generate_xml_output() {

			$xml = new SimpleXMLElement('<plugin/>');
			$xml->addAttribute( 'name', $this->plugin_name );
			$xml->addAttribute( 'application', 'WordPress' );
			$hooks_node = $xml->addChild( 'hooks' );

			foreach( $this->hooks as $key => $hook ) {
				$node = $hooks_node->addChild( 'hook' );
				$node->addAttribute( 'name', $key );
				$node->addAttribute( 'type', $hook['type'] );
				$node->addAttribute( 'file', $hook['file'] );
				$node->addAttribute( 'line_number', $hook['line'] );
				$node->addAttribute( 'called_by', $hook['called_by'] );
				$node->addChild( 'signature', $hook['signature'] );
				
				if( is_array( $hook['params'] ) && count( $hook['params'] ) > 0 ) {
					$param_node = $node->addChild( 'parameters' );
					foreach( $hook['params'] as $param ) {
						$param_node->addChild( 'param', $param );
					}
					unset( $param, $param_node );
				}

				if( is_array( $hook['parsed_comment'] ) && count( $hook['parsed_comment'] )> 0 ) {

					$doc_node = $node->addChild( 'documentation' );
					$tags_node = $doc_node->addChild( 'tags' );
					foreach( $hook['parsed_comment'] as $tag => $comments_array ) {

						foreach( $comments_array as $key => $item ) {

							if( is_string( $item ) ) {
								$tag_node = $tags_node->addChild( 'tag', preg_replace( '`[\r\n]`', '', $item ) );
								$tag_node->addAttribute( 'name', $tag );
							}
							else if( is_array( $item ) && count( $item ) > 0 ) {
								$tag_node = $tags_node->addChild( 'tag' );
								$tag_node->addAttribute( 'name', $tag );

								foreach( $item as $k => $v ) {
									$tag_node->addChild( $k, preg_replace( '`[\r\n]`', '', $v ) );
								}
								unset( $tag_node, $k, $v );
							}
						}
						unset( $tag_node_plural, $key, $item );
					}
					unset( $doc_node, $tag, $comments_array );
				}
			}
			unset( $hooks_node, $key, $hook, $node );
            return $xml;
		}

		


		
		/**
		 *
		 *
		 * Want different php output ? Just override this method in your own extended class.
		 *
		 * @since 0.2
		 * @author Juliette Reinders Folmer
		 *
		 * @return array
		 */
		function generate_php_output() {
			$output = array( $this->plugin_name => $this->hooks );
            return $output;
		}
		
	
		/**
		 *
		 */
		function format_output( $output ) {

			switch( $this->format ) {
				case 'textarea':
					$output = $this->format_textarea_output( $output );
					break;
				case 'view':
					$output = $this->format_view_output( $output );
					break;
				case 'file':
					$output = $this->format_file_output( $output );
					break;
			}
			
			// in the case of 'return', just return the output unchanged
			return $output;
		}



		function format_textarea_output( $output ) {
			$string = '<form id="wpphd-output"><textarea>';

			switch( $this->style ) {
				case 'html':
					$string .= htmlspecialchars ( $output, ENT_QUOTES, 'UTF-8', true );
					break;
	
				case 'xml':
				
					$string .= htmlspecialchars ( $this->get_pretty_xml( $output->asXML() ), ENT_QUOTES, 'UTF-8', true );
					break;
	
				case 'text':
					$string .= htmlspecialchars ( $output, ENT_QUOTES, 'UTF-8', true );
					break;
					
				case 'php':
					$output = '<?php' . "\n\r\n\r" . print_r( $output, true ) . "\n\r\n\r" . '?>';
					$string .= htmlspecialchars ( $output, ENT_QUOTES, 'UTF-8', true );
					break;
			}
			$string .= '</textarea></form>';
			return $string;
		}
		
		function get_pretty_xml( $output ) {
			$dom = new DOMDocument();
			$dom->loadXML( $output );
			$dom->formatOutput = true;
			return $dom->saveXML();
		}

		function format_view_output( $output ) {
			
			// xml - header('Content-type: text/xml');
		}
		

		function format_file_output( $output ) {
			//send file header
			
			// xml $output->asXML( 'filename' )
		}

	} /* End of class */
	

} /* End of class-exists wrapper */


?>