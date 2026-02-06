<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$ahsc_tables=array();
$ahsc_tables['wp_postmeta']=array(
	'meta_id_optimized'=>array(
		'type'=>'UNIQUE KEY',
		'param'=>array('meta_id')
	),
	'post_id_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('post_id', 'meta_key', 'meta_id')
	),
	'meta_key_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('post_id', 'meta_key')
	)
);

$ahsc_tables['wp_usermeta']=array(
	'umeta_id_optimized'=>array(
		'type'=>'UNIQUE KEY',
		'param'=>array('umeta_id')
	),
	'user_id_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('user_id', 'meta_key','umeta_id')
	),
	'meta_key_optimized'=>array(
		'type'=>'KEY',
		'param'=>array( 'meta_key','user_id')
	)
);

$ahsc_tables['wp_termmeta']=array(
 'meta_id_optimized'=>array(
	 'type'=>'UNIQUE KEY',
	 'param'=>array('meta_id')
 ),
 'term_id_optimized'=>array(
	 'type'=>'KEY',
	 'param'=>array('term_id', 'meta_key', 'meta_id')
 ),
 'meta_key_optimized'=>array(
	 'type'=>'KEY',
	 'param'=>array('meta_key','term_id')
 ),

);

$ahsc_tables['wp_options']=array(
	'option_id_optimized'=>array(
	  'type'=>'UNIQUE KEY',
	  'param'=>array('option_id')
	),
	'autolod_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('autoload','option_id')
	)
);

$ahsc_tables['wp_posts']=array(
	'type_status_date_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('post_type','post_status','post_date','post_author','ID')
	),
	'post_author_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('post_author','post_type','post_status','post_date','ID')
	)
);
$ahsc_tables['wp_comments']=array(
	'comment_post_parent_approved_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('comment_post_ID','comment_parent','comment_approved','comment_ID')
	)
);

/*CONTROLLO PER ESISTENZA UNIQUE KEY

SELECT EXISTS (SELECT constraint_name
                 FROM INFORMATION_SCHEMA.table_constraints
                WHERE table_name = 'my_table' AND constraint_type='UNIQUE');
*/
/*CONTROLLO PER ESISTENZA KEU
SELECT DISTINCT
INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
WHERE INDEX_NAME = 'KEY_NAME'
and TABLE_NAME='TABLE_NAME'
*/

function AHSC_DBOPT_Check(){
	global $ahsc_tables,$wpdb;
	$query_result=array();
	$check=true;
	foreach($ahsc_tables as $table_name=>$index_settings){
		foreach($index_settings as $index_name=>$index_param){
			$pfx=$wpdb->prefix.substr($table_name,'3',strlen($table_name));
			$query_result[$pfx][$index_name]=AHSC_check_key_exists($index_name,$table_name);
		}
	}

	foreach($query_result as $table=>$index){
		foreach($index as $index_name=>$index_exist){
			if($index_exist===0){
				$check=false;
				break;
			}else{
				continue;
			}
		}
	}

	/*echo "<pre> <p>===================================SQLCONTROLLO=====================================================</p>".
	     "<p>". var_export($query_result,true)."</p>".
	     "<p>CHECK RESULT: ".var_export($check,true)."</p>".
	     "<p>================================================================================================</p></pre>";*/
	return $check;
}

//AHSC_DBOPT_Check();
// phpcs:disable
function AHSC_check_key_exists($index_name,$table_name){
	global $wpdb;
	$pfx=$wpdb->prefix.substr($table_name,'3',strlen($table_name));
	$sql="SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE INDEX_NAME = '{$index_name}' and TABLE_NAME='{$pfx}'";
	//$result=$wpdb->query( $wpdb->prepare("SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE INDEX_NAME=%s and TABLE_NAME=%s", array( $index_name, $pfx)) ); //@phpcs:ignore
	$result=$wpdb->query($sql);
	/*echo "<pre> <p>===================================SQLCONTROLLOESISTENZA=====================================================</p>".
	     "<p>index : $index_name table: $pfx </p>".
	     "<p>sql : $sql</p>".
	     "<p> SQL RESULT :".var_export($result,true)."<p>".
	     "<p> CHECK SINGLE RESULT :".var_export(($result!==0)?1:0,true)."</p>".
	     "<p>================================================================================================</p></pre>";*/
	return ($result!==0)?1:0;
}
// phpcs:disable
function AHSC_DBOPT_manage($status){
	$result=array("status"=>$status);
	if($status!=="false"){
		$result['action']="ottimizza";
		$result+=AHSC_DBOPT_Optimize();
	}else{
		$result['action']="elimina";
		$result+=AHSC_DBOPT_Drop_chenges();
	}
	return $result;
}

/* AGGIUNTA KEY

ALTER TABLE `ps_cart_rule` ADD KEY `id_customer` (`id_customer`,`active`,`date_to`);

*/

/*AGGIUNTA UNQIUE

ALTER TABLE table_name ADD CONSTRAINT unique_name UNIQUE (field1, field2, ...);

*/

function AHSC_DBOPT_Optimize(){
	global $ahsc_tables,$wpdb;
	$query_result=array();
	foreach($ahsc_tables as $table_name=>$index_settings){
		$pfx=$wpdb->prefix.substr($table_name,'3',strlen($table_name));
		//$sql="ALTER TABLE {$pfx} ROW_FORMAT=DYNAMIC;";
		$wpdb->query( $wpdb->prepare("ALTER TABLE %i ROW_FORMAT=DYNAMIC;",array( $pfx) ));//@phpcs:ignore
		foreach($index_settings as $index_name=>$index_param){

			//$str_param=implode(",",$index_param['param']);

			//$query_result[$pfx][$index_name]=array();
			$param_count=count($index_param['param'])-1;
			//$query_result[$pfx][$index_name]['param:count']=$param_count;
			$str_param="";
			$prepare_arr=array();

            array_push($prepare_arr,$pfx);
			array_push($prepare_arr,$index_name);
			foreach($index_param['param'] as $pos=>$val){
				array_push($prepare_arr,$val);
			}

			$param_prepare_str="";
			for($i=0;$i<=$param_count;$i++){
				$param_prepare_str.="%i,";

			}
			$param_prepare_str=substr($param_prepare_str,0,strlen($param_prepare_str)-1);
			//$query_result[$pfx][$index_name]['param:str']=$param_prepare_str;
			//$query_result[$pfx][$index_name]['param:str:val']=$str_param;
			$k_exs=AHSC_check_key_exists($index_name,$table_name);

			/*switch ($index_param['type']) {
				case "UNIQUE KEY":
					$query_result[$pfx][$index_name]['sql']=$wpdb->prepare("ALTER TABLE %i ADD CONSTRAINT %i UNIQUE ($param_prepare_str)",$prepare_arr);
				case "KEY":
					$query_result[$pfx][$index_name]['sql']=$wpdb->prepare("ALTER TABLE %i ADD KEY %i ($param_prepare_str)",$prepare_arr);

			}*/

			if(!$k_exs){

				switch ($index_param['type']){
					case "UNIQUE KEY":
						//$sql="ALTER TABLE {$pfx} ADD CONSTRAINT {$index_name} UNIQUE ({$str_param}) ";

						$wpdb->query( $wpdb->prepare("ALTER TABLE %i ADD CONSTRAINT %i UNIQUE ($param_prepare_str)",$prepare_arr));//@phpcs:ignore
						break;
					case "KEY":
						//$sql="ALTER TABLE {$pfx} ADD KEY {$index_name} ({$str_param})";

						$wpdb->query( $wpdb->prepare("ALTER TABLE %i ADD KEY %i ($param_prepare_str)",$prepare_arr));//@phpcs:ignore
						break;
				}


			}
		}
	}
	/*echo "<pre><p>===================================AGGIUNTA=====================================================</p>".
	     var_export($query_result,true).
	     "<p>================================================================================================</p></pre>";*/
	return $query_result;
}
//AHSC_DBOPT_Optimize();

/*CANCELLAZIONE
 *
 * ALTER TABLE `my_table` DROP KEY `name_of_my_key`
 * ALTER TABLE table_name DROP INDEX unique_name,
 **/
function AHSC_DBOPT_Drop_chenges(){
	global $ahsc_tables,$wpdb;
	$query_result=array();
	foreach($ahsc_tables as $table_name=>$index_settings){
		foreach($index_settings as $index_name=>$index_param){

			//$str_param=implode(",",$index_param['param']);
			$pfx=$wpdb->prefix.substr($table_name,'3',strlen($table_name));
			switch ($index_param['type']){
				case "UNIQUE KEY":
					//$sql="ALTER TABLE {$pfx} DROP INDEX {$index_name}";

					$wpdb->query( $wpdb->prepare("DROP INDEX %i ON %i;",array( $index_name,$pfx)));//@phpcs:ignore

					break;
				case "KEY":
					//$sql="ALTER TABLE {$pfx} DROP KEY {$index_name}";
					$wpdb->query( $wpdb->prepare("ALTER TABLE %i DROP KEY %i;",array( $pfx,$index_name)));//@phpcs:ignore
					break;
			}
			//$query_result[$pfx][$index_name]=array();
			//$query_result[$pfx][$index_name]['sql'] =  $sql; //$wpdb->query( $sql );
			//$query_result[$pfx][$index_name]['result'] = $wpdb->query( $sql );//@phpcs:ignore

		}
	}
	//var_dump($query_result);
	return $query_result;
}
//AHSC_DBOPT_Drop_chenges();