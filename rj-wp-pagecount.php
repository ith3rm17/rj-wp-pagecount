<?php
/**
 * Plugin Name: RJ Page Count
 * Author: Reynald Jay Cueto
 * Author URI: http://www.myminipin.com
 * Version: 1.0.1
 * Description: my plugin for page count
 */

require_once( ABSPATH . 'wp-admin/includes/template.php');
require_once( ABSPATH . 'wp-admin/includes/screen.php');
require_once( ABSPATH . 'wp-admin/includes/dashboard.php');

require_once(dirname(__FILE__) . '/class.rj-wp-pagecount.php');
$rj_page_count=new RJ_PAGECOUNT;

function rjp_count_widget_setup(){
	wp_add_dashboard_widget('rjp_count_widget',__('RJ Post Count','rjp_count_widget'),'rjp_count_widget');
}

function rjp_count_widget(){
	global $rj_page_count;
	require_once(dirname(__FILE__) . '/admin/views/dashboard.php');
}

function rjp_count_inc($content){
	global $post,$rj_page_count;
	if(gettype($post)!=='object') return false;
	
	if($post->ID){
		$p=$rj_page_count->get($post->ID);
		
		if($p){
			$rj_page_count->increment($post->ID);
		}else{
			$rj_page_count->add($post->ID);	
		}
	}
	return $content;
}

function rjp_update($content){
	RJ_PAGECOUNT::update();
	return $content;
}

//add_action('the_content','rjp_count_inc');
add_action('the_content','rjp_update');

wp_enqueue_script('jquery');
add_action('wp_dashboard_setup','rjp_count_widget_setup');
register_activation_hook(__FILE__,array(&$rj_page_count,'install'));
