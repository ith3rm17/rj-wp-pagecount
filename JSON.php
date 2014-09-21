<?php
/**
 * Author: Reynald Jay Cueto
 * Author URI: http://www.myminipin.com
 * Version: 1.0
 * Description: hehe
 * 
 */

//load wordpress
require_once(dirname(__FILE__) . '/../../../wp-load.php');

status_header(200);
header('Content-Type:application/json');

$idata=json_decode(file_get_contents('php://input'));

//default
$odata=array('response'=>'false');

if(!empty($idata) && is_array($_idata)):
	switch($idata['action']){
		case 'getcount':
			global $rj_page_count;
			$post_id=$idata['post_id'];
				if(empty($post_id)) break;
				$object=$rj_page_count->get($post_id);
				if($object){
					$total=$object->get_single_total($post_id);
					
					if(is_int($total)){
						$idata['response']='true';
						$idata['data']=array(
							'count'=>$total
						);
					}
				}
			break;
			
		case 'total':
			global $rj_page_count;
			$total=$rj_page_count->get_total();
			
			if(is_int($total)){
				$idata['response']='true';
				$idata['data']=array(
					'count'=>$total
				);
			}
			break;
		case 'default':
	}
endif;
echo json_encode($odata);