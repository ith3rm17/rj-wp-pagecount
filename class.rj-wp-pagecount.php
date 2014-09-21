<?php
/*
 * @author Reynald Jay Cueto
 * @version 1.0
 * @desc when you call get($post_ID): you should get
 * 
 */

class RJ_PAGECOUNT {
	
	protected $metatype='rjpcount';
	protected $table;
	protected $tablemeta;
	public $maintable;

	public function __construct(){
		global $wpdb;

		$this->table='wp_' . $this->metatype;
		$this->tablemeta='wp_' . $this->metatype . '_meta';
		$wpdb->{$this->metatype}=$this->table;
		$metaname=$this->metatype.'_meta';
		$wpdb->$metaname=$this->tablemeta;
	}

	public function uninstall(){
		global $wpdb;
//		$wpdb->query("drop table {$this->tablemeta}");
//		$wpdb->query("drop table {$this->table}");
	}
	
	public function install(){
		global $wpdb;

		if(!empty($wpdb->charset)) $charset=' DEFAULT CHARSET = ' .$wpdb->charset;
		if(!empty($wpdb->collate)) $charset.=' COLLATE ' . $wpdb->collate;
			
		$sql0="CREATE TABLE IF NOT EXISTS `{$this->table}`(
				`ID` int(11) unsigned not null auto_increment,
				`date` date NOT NULL DEFAULT '0000-00-00',
				`post_id` varchar(255) not null,
				`count` int(11) unsigned not null default 1,
				`uri` varchar(255) default '',  
				primary key(`ID`)
			) {$charset}";
			
		$sql1="CREATE TABLE IF NOT EXISTS `{$this->tablemeta}`(
				`meta_id` bigint(20) unsigned not null auto_increment,
				`{$this->metatype}_id` bigint(20) unsigned not null,
				`meta_key` varchar(255) default null,
				`meta_value` longtext default null, 
				primary key(`meta_id`),
				KEY `{$this->metatype}_id` (`{$this->metatype}_id`),
  				KEY `meta_key` (`meta_key`)
			){$charset}";
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		dbDelta($sql0);
		dbDelta($sql1);
		
		$this->import();
	}
	
	public static function get_date($maybeint=''){
		if(empty($maybeint)){
			return strftime('%Y-%m-%d',time());
		}elseif($maybeint && absint($maybeint) && strlen($maybeint) == 10){
			return strftime('%Y-%m-%d',$maybeint);
		}elseif(strlen($maybeint) == 18 ){
			return strftime('%Y-%m-%d',strtotime($date_string));	
		}
	}
	
	
	public function get_by_id($rjp_id=null){
		if(empty($rjp_id)) return false;
		global $wpdb;

		return $wpdb->get_row("SELECT * FROM `{$this->table}` WHERE `ID`={$rjp_id}");
	}
	
	public function add($post_id=null,$date=''){
		global $wpdb;
		
		if(empty($post_id)) return false;
		
		$date=$this->get_date();
		
		$r=$wpdb->insert($this->table,array(
			'post_id'=>$post_id,
			'date'=>$date,
			)
		);
		
		$id=$wpdb->insert_id;
		
		if($r)
			return $this->get_by_id($id);

			return false;
	}
	
	public function increment($post_id){
		global $wpdb;

		if(empty($post_id)) return false;
		$p=$this->get($post_id);
		
		if(!$p) return false;
		
		$count=$p->count;
		$count++;
		return $wpdb->update(
			$this->table,
			array(
				'count'=>$count
			),
			array(
				'ID'=>$p->ID,
			)
		);
	}
	
	public function get($post_id=null,$date=''){
		global $wpdb;
		if(empty($post_id)) return false;
		
		$date=$this->get_date($date);
		$row=$wpdb->get_row("SELECT * FROM {$this->table} WHERE `post_id` = {$post_id} AND `date` ='{$date}' ORDER by `ID` DESC");

		$this->_combine_objectmeta($row);
		return $row;
	}

	public static function rjlog($msg){
		$fh=fopen('/tmp/dump','a');
		fwrite($fh,$msg . "\n");
		fclose($fh);
	}
	
	public static function update($post_id=null){
		//count only on a single post
		if(!is_single()) return;
		global $wpdb,$post,$current_user;

		if(!$post_id && $post){
			$post_id=$post->ID;
		}else return false;

		$date=RJ_PAGECOUNT::get_date();
		
		if($current_user && isset( $current_user->roles ) && in_array('administrator',$current_user->roles) ){
			return;
		}
		
		$results = $wpdb->query( $wpdb->prepare( "UPDATE ". $wpdb->prefix . "rjpcount SET count = count+1 WHERE post_id = '%s' AND `date`='%s' ORDER BY `ID` DESC LIMIT 1", $post_id,$date ) );

		if ($results == 0) {
			$wpdb->query( $wpdb->prepare ( "INSERT INTO ". $wpdb->prefix . "rjpcount (`date`, `post_id`, `count`) VALUES ('%s', '%s', 1)", $date, $post_id ) );
		} 
	}

	/**
	 * @desc get the total count for a single post, default date is time()
	 * @param $post_id
	 * @return unknown_type
	 */
	public function get_single_total($post_id){
		global $wpdb;

		$rows=$wpdb->get_results("SELECT ID,post_id,count FROM {$this->table} WHERE `post_id`={$post_id}");
		$total=0;
		
		if($rows){
			foreach($rows as $row){
				$total+=$row->count;
			}
			return $total;
		}
		return false;
	}
	
	public static function get_total(){
		global $wpdb;
		$row=$wpdb->get_row("SELECT ID,SUM(count) FROM " . $wpdb->prefix . "rjpcount");
		if($row)
			return $row->{'SUM(count)'};
		else return 0;
	}
	
	public function get_total_by_date($date=''){
		global $wpdb;
		$today=RJ_PAGECOUNT::get_date($date);
		$row=$wpdb->get_row("SELECT ID,SUM(count),date FROM " . $wpdb->prefix . "rjpcount WHERE `date` = '{$today}'");
		if($row)
			return $row->{'SUM(count)'};
		else return 0;
	}
	
	public function get_top($limit=10){
		global $wpdb;
		$date=$this->get_date();
		$results=$wpdb->get_results("SELECT * FROM `{$this->table}` WHERE `date`='{$date}' ORDER BY `count` DESC LIMIT {$limit}");
		return $results;
	}
	
	public function get_fields(){
		global $wpdb;

		try{
			if(!is_array($this->maintable)):
				$results=$wpdb->get_results("SHOW COLUMNS FROM `{$this->table}`");
				foreach($results as $i){
					$this->maintable[]=$i->Field;
				}
			endif;
		}catch(Exception $e){}

		if( !is_array($this->maintable) && count($this->maintable) < 1 ){
			$this->maintable=array(
				"ID",
				"post_id",
				"date",
				"count",
			);
		}
		return $this->maintable;
	}
	
	public function import(){
		global $wpdb;
		
		try{
			$pvc=$wpdb->get_results("SHOW COLUMNS FROM `wp_pvc_total`");
		}catch(Exception $e){};
		
		if(count($pvc) < 2 ){
			return false;
		}
		
		$pvc_post_id='postnum';
		$pvc_post_count='postcount';
		
		$results=$wpdb->get_results("SELECT * FROM wp_pvc_total");
		
		foreach($results as $i){
			
			$count=$i->{$pvc_post_count};
			$post_id=$i->{$pvc_post_id};
			
			$row=$this->get($post_id);
			
			if($row){
				if($count < $row->count){
					$count=$row->count;
				}
			}else{
				$row=$this->add($post_id);
			}
			
			$wpdb->update(
				$this->table,
				array('count'=>$count),
				array('ID'=>$row->ID)
			);
		}
	}
	
	private function _combine_objectmeta(&$object){
		global $wpdb;

		if(!$object) return false;
		$objectmeta=get_metadata($this->metatype,$object->ID);
		if(!$objectmeta) return false;

		$ref=new ReflectionObject($object);
		foreach($objectmeta as $k=>$v){
			if(!$ref->hasProperty($k)){
				if(is_array($v[0])){
					$object->$k=$v;
				}else $object->$k=$v[0];
			}
		}
		return true; 
	}
}