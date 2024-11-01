<?php
/*
Plugin Name: Widgets2Editor 
Plugin URI: http://www.vuleticd.com/products/wordpress-plugins/widgets-to-editor/
Description: Enables inserting of almost all widgets output into the visual editor, as static HTML blocks, witch can be changed once inserted into editor. Templates are defined in new widgets sidebar, and applied in editor with standard TinyMCE template plugin.
Version: 0.2
Author: Dragan Vuletic
Author URI: http://www.vuleticd.com
Usage: View readme.txt

Copyright (C) <2008>  <Dragan Vuletic>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.opensource.org/licenses/gpl-3.0.html>


*/

define('TWOBUY_TF_PLUGIN_DIR',dirname(__FILE__));         //Abs path to the plugin directory

if (!class_exists('TwobuyTinyFixes')) {
	
	class TwobuyTinyFixes {
		public $abs_site_root;
		public $site_http_root;
		public $ids='';
		public $names='';
 		
        function TwobuyTinyFixes() { 
         	$this->site_http_root = get_option('siteurl'); 
         	//Root dir of the current site 
         	$this->abs_site_root= ABSPATH;         	
        }
        
        
        function TwobuyTinyFixes_Create_Rewrite_Rules($rewrite) {
			$newrules = array();
			$newrules['tinys/(.*)$'] = 'index.php?tiny=$matches[1]';
			return $newrules + $rewrite;
        }
        
        function TwobuyTinyFixes_Add_Query_Var($vars) {
        	array_push($vars,'tiny');
			return($vars);
        }
        
        function TwobuyTinyFixes_Parse_Query() {
        	if (TwobuyTinyFixes::TwobuyTinyFixes_Is_Tiny_Template()) {
				global $wp_query;
				$wp_query->is_single = false;
				$wp_query->is_page = false;
				$wp_query->is_archive = false;
				$wp_query->is_search = false;
				$wp_query->is_home = false;
				$wp_query->is_404 = false;
						
				add_action('template_redirect', array('TwobuyTinyFixes', 'TwobuyTinyFixes_Template_Redirect'));
			}
        }
        
        function TwobuyTinyFixes_Is_Tiny_Template() {
    		global $wp_version;
    		$keyword = ( isset($wp_version) && ($wp_version >= 2.0) ) ? 
                get_query_var('tiny') : 
                $GLOBALS['tiny'];
                
			if (!is_null($keyword) && ($keyword != ''))
				return true;
			else
				return false;
		}
		
		function TwobuyTinyFixes_Flush_Rules() {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
			
		}
        /**
         * Definings the sidebar pannel for template widgets
         *
         */
        function TwobuyTinyFixes_Add_WPanel() {
        	
        	register_sidebar(
			array(
				'id' => 'tiny_templates',
				'name' => 'Tiny Templates',
				'before_widget' => '<div class="mceTmpl">',
				'after_widget' => '</div>' . "\n",
				'before_title' => '<span style="display:none;">',
				'after_title' => '</span>' . "\n",
				)
			);
			add_filter('sidebars_widgets', array('TwobuyTinyFixes', 'TwobuyTinyFixes_WPanels'));
        }
        
        function TwobuyTinyFixes_WPanels($sidebars_widgets){
        	if ( !empty($sidebars_widgets['tiny_templates']) ) {
				return $sidebars_widgets;
        	}
        	
        	global $wp_widget_factory;
			global $wp_registered_sidebars;
		
			$default_widgets = array(
				'tiny_templates' => array(),
			);
			
			$registered_sidebars = array_keys($wp_registered_sidebars);
			$registered_sidebars = array_diff($registered_sidebars, array('wp_inactive_widgets'));
			foreach ( $registered_sidebars as $sidebar ) {
				$sidebars_widgets[$sidebar] = (array) $sidebars_widgets[$sidebar];
			}
			$sidebars_widgets['wp_inactive_widgets'] = (array) $sidebars_widgets['wp_inactive_widgets'];
			
			foreach ( $default_widgets as $panel => $widgets ) {
				if ( empty($sidebars_widgets[$panel]) ) {
					$sidebars_widgets[$panel] = (array) $sidebars_widgets[$panel];
				} else {
					continue;
				}
				
				foreach ( $widgets as $widget ) {
					if ( !is_a($wp_widget_factory->widgets[$widget], 'WP_Widget') ) {
						continue;
					}
					
					$widget_ids = array_keys((array) $wp_widget_factory->widgets[$widget]->get_settings());
					$widget_id_base = $wp_widget_factory->widgets[$widget]->id_base;
					$new_widget_number = $widget_ids ? max($widget_ids) + 1 : 2;
					foreach ( $widget_ids as $key => $widget_id ) {
						$widget_ids[$key] = $widget_id_base . '-' . $widget_id;
					}
					
					# check if active already
					foreach ( $widget_ids as $widget_id ) {
						if ( in_array($widget_id, $sidebars_widgets[$panel]) ) {
							continue 2;
						}
					}
					
					# use an inactive widget if available
					foreach ( $widget_ids as $widget_id ) {
						foreach ( array_keys($sidebars_widgets) as $sidebar ) {
							$key = array_search($widget_id, $sidebars_widgets[$sidebar]);
						
							if ( $key === false ) {
								continue;
							} elseif ( in_array($sidebar, $registered_sidebars) ) {
								continue 2;
							}
						
							unset($sidebars_widgets[$sidebar][$key]);
							$sidebars_widgets[$panel][] = $widget_id;
							continue 3;
						}
					
						$sidebars_widgets[$panel][] = $widget_id;
						continue 2;
					}
					
					# create a widget on the fly
					$new_settings = $wp_widget_factory->widgets[$widget]->get_settings();
				
					$new_settings[$new_widget_number] = array();
					$wp_widget_factory->widgets[$widget]->_set($new_widget_number);
					$wp_widget_factory->widgets[$widget]->_register_one($new_widget_number);
				
					$widget_id = "$widget_id_base-$new_widget_number";
					$sidebars_widgets[$panel][] = $widget_id;
				
					$wp_widget_factory->widgets[$widget]->save_settings($new_settings);
				}
			}
			
			if ( isset($sidebars_widgets['array_version']) && $sidebars_widgets['array_version'] == 3 ) {
				$sidebars_widgets['wp_inactive_widgets'] = array_merge($sidebars_widgets['wp_inactive_widgets']);
			} else {
				unset($sidebars_widgets['wp_inactive_widgets']);
			}
		
			return $sidebars_widgets;
        }
	
		// redirect  the template to blank page
        function TwobuyTinyFixes_Template_Redirect() {
        	global $wp_the_query;

        	$args = array(
        			'template'=>$wp_the_query->query_vars["tiny"]
        			);

        	include TWOBUY_TF_PLUGIN_DIR . '/tiny-template.php';
			die;
        }
        
        function TwobuyTinyFixes_Print_Template($args) {
        	global $wp_registered_widgets, $wp_registered_sidebars, $_wp_sidebars_widgets;
        	$template = trim($args['template']);
        	
        	$sidebarT = $_wp_sidebars_widgets["tiny_templates"];
        	//var_dump($template);
        	foreach ($sidebarT as $id) {
        		if ($template == $id) {
        	
        			$params = array_merge(
                        array( array_merge( $wp_registered_sidebars["tiny_templates"], array('widget_id' => $id, 'widget_name' => $wp_registered_widgets[$id]['name']) ) ),
                        (array) $wp_registered_widgets[$id]['params']
                      );

	        		// Substitute HTML id and class attributes into before_widget
	        		$classname_ = '';
	        		foreach ( (array) $wp_registered_widgets[$id]['classname'] as $cn ) {
	           			if ( is_string($cn) )
	              			$classname_ .= '_' . $cn;
               			elseif ( is_object($cn) )
                  			$classname_ .= '_' . get_class($cn);
	        		}
            		$classname_ = ltrim($classname_, '_');
            		$params[0]['before_widget'] = sprintf($params[0]['before_widget'], $id, $classname_);

	        		$params = apply_filters( 'dynamic_sidebar_params', $params );

        			$callback = $wp_registered_widgets[$id]['callback'];
        			if (is_callable($callback)) {
        				call_user_func_array($callback,$params);
        			}
        	
        		}
        	}
        }
        
        /**
         * We need to set class internal params with template widgets ids and names, so we can use it when we initiate ext_list JS 
         *
         */
        function TwobuyTinyFixes_Add_Internals() {
        	global $wp_registered_widgets;
        	// it's admin side, so options are better place
        	$sidebars_widgets = get_option('sidebars_widgets', array());
        	$sidebarT = $sidebars_widgets["tiny_templates"];
        	if (count($sidebarT) > 0) {
        		$this->ids = implode(',',$sidebarT); 
        	}
        	// I have to simulate the widget excecution to get the names
        	// The widget is tweaked a little for this to work, since I don't need the title in templates body
        	foreach ((array) $sidebarT as $wid) {
        		$widget = $wp_registered_widgets[$wid];
        		$args = array(
					'before_widget' => '',
					'after_widget' => '',
					'before_title' => '%BEG_OF_TITLE%',  // This is important
					'after_title' => '%END_OF_TITLE%'
				);
				$params = array($args, (array) $widget['params'][0]);
				// Execute the widget and collect the output
				ob_start();
				call_user_func_array($widget['callback'], $params);
				$label = ob_get_clean();
				// Clean up a little
				if ( preg_match("/%BEG_OF_TITLE%(.*?)%END_OF_TITLE%/", "$label", $label) ) {
					$label = end($label);
					$label = strip_tags($label);
					$label = @html_entity_decode($label, ENT_COMPAT, get_option('blog_charset'));
					$label = $label;
				} else {
					$label = $widget['name'];
				}
				// fill in array
				$names[] = $label;       		
        	}
        	// Generate the names string
        	if (count($names) > 0) {
        		$this->names = implode(',',$names);
        	}
        }
        /**
         * Function that will add the 2 extra members into the Tiny init array
         *
         */
        function TwobuyTinyFixes_AddTo_Init($initArray)  {
        	// add widget ids and names in JS call, from class params loaded on admin_head 
        	$ids = $this->ids;
        	$names = $this->names;
        	
			$extlistpath = trailingslashit($this->site_http_root).'wp-content/plugins/widgets2editor/editor/template/ext_list.php?ids='.$ids.'&names='.$names;
			// Add the new plugin to the old array
			$initArray['template_external_list_url'] = $extlistpath;
			$initArray['extended_valid_elements'] = 'div[*],*[*];';
			// And return the changed array to Tiny
			return $initArray;
        }
               
        /**
         * Function that will add the plugin for TinyMCE, that we need to add the select button in it
         *
         */
        function TwobuyTinyFixes_Tiny_Plugin($plugins)  {
			$pluginpath = trailingslashit($this->site_http_root).'wp-content/plugins/widgets2editor/editor/template/editor_plugin.js';
			$plugins['template'] = $pluginpath;

			return $plugins;
        }
        
        /**
         * Function that will load the language for template plugin
         *
         */
        function TwobuyTinyFixes_Load_Lang($langs)  {
			$langs["template"] = TWOBUY_TF_PLUGIN_DIR  . '/editor/template/langs/langs.php';			
			return $langs;
        }
        
         /**
         * Function that will add the button for TinyMCE Visual view
         *
         */
        function TwobuyTinyFixes_Tiny_Button($mcebuttons) {
			if ( !empty($mcebuttons) )
			{
				$mcebuttons[] = '|';
			}		
			$mcebuttons[] = 'template';		
			return $mcebuttons;
		}
    
    }
}


if (class_exists("TwobuyTinyFixes")) {
	$twobuy_tf_object = new TwobuyTinyFixes();
}
//Actions and Filters	
if (isset($twobuy_tf_object)) {
	
	include_once(TWOBUY_TF_PLUGIN_DIR.'/tiny-template-widget.php');
	
	add_action('init', array(&$twobuy_tf_object, 'TwobuyTinyFixes_Add_WPanel'), -200);
	add_filter('rewrite_rules_array', array(&$twobuy_tf_object,'TwobuyTinyFixes_Create_Rewrite_Rules'));
	add_filter('query_vars', array(&$twobuy_tf_object, 'TwobuyTinyFixes_Add_Query_Var'));
	add_filter('init', array(&$twobuy_tf_object, 'TwobuyTinyFixes_Flush_Rules'));
	add_action('parse_query', array(&$twobuy_tf_object, 'TwobuyTinyFixes_Parse_Query'));
	
	if ( is_admin() ) {
		add_action('admin_head', array(&$twobuy_tf_object, 'TwobuyTinyFixes_Add_Internals'));
		add_filter('tiny_mce_before_init', array(&$twobuy_tf_object, 'TwobuyTinyFixes_AddTo_Init'));
		add_filter('mce_external_plugins', array(&$twobuy_tf_object, 'TwobuyTinyFixes_Tiny_Plugin'));
		add_filter( 'mce_external_languages', array(&$twobuy_tf_object, 'TwobuyTinyFixes_Load_Lang') );
		add_filter('mce_buttons_2', array(&$twobuy_tf_object, 'TwobuyTinyFixes_Tiny_Button'), 0);
	}
}
?>