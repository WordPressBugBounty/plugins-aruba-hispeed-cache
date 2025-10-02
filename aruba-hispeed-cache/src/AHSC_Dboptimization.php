<?php
$tables=array();
$tables['wp_postmeta']=array(
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

$tables['wp_usermeta']=array(
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

$tables['wp_termmeta']=array(
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

$tables['wp_options']=array(
	'option_id_optimized'=>array(
	  'type'=>'UNIQUE KEY',
	  'param'=>array('option_id')
	),
	'autolod_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('autoload','option_id')
	)
);

$tables['wp_posts']=array(
	'type_status_date_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('post_type','post_status','post_date','post_author','ID')
	),
	'post_author_optimized'=>array(
		'type'=>'KEY',
		'param'=>array('post_author','post_type','post_status','post_date','ID')
	)
);
$tables['wp_comments']=array(
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
	global $tables,$wpdb;
	$query_result=array();
	$check=true;
	foreach($tables as $table_name=>$index_settings){
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

/*	echo "<pre> <p>===================================SQLCONTROLLO=====================================================</p>".
	     "<p>". var_export($query_result,true)."</p>".
	     "<p>CHECK RESULT: ".var_export($check,true)."</p>".
	     "<p>================================================================================================</p></pre>";*/
	return $check;
}

//AHSC_DBOPT_Check();

function AHSC_check_key_exists($index_name,$table_name){
	global $wpdb;
	$pfx=$wpdb->prefix.substr($table_name,'3',strlen($table_name));
	$sql="SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE INDEX_NAME = '{$index_name}' and TABLE_NAME='{$pfx}'";
	$result=$wpdb->query( $sql );
	/*echo "<pre> <p>===================================SQLCONTROLLOESISTENZA=====================================================</p>".
	     "<p> SQL :".var_export($sql,true)."</p>".
	     "<p> SQL RESULT :".var_export($result,true)."<p>".
	     "<p> CHECK SINGLE RESULT :".var_export(($result!==0)?1:0,true)."</p>".
	     "<p>================================================================================================</p></pre>";*/
	return ($result!==0)?1:0;
}

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
	global $tables,$wpdb;
	$query_result=array();
	foreach($tables as $table_name=>$index_settings){
		$pfx=$wpdb->prefix.substr($table_name,'3',strlen($table_name));
		$sql="ALTER TABLE {$pfx} ROW_FORMAT=DYNAMIC;";
		$query_result[$pfx]['fix']=$sql;
		$wpdb->query( $sql );
		foreach($index_settings as $index_name=>$index_param){

			$str_param=implode(",",$index_param['param']);
			$k_exs=AHSC_check_key_exists($index_name,$table_name);
			if(!$k_exs){
				switch ($index_param['type']){
					case "UNIQUE KEY":
						$sql="ALTER TABLE {$pfx} ADD CONSTRAINT {$index_name} UNIQUE ({$str_param}) ";
						break;
					case "KEY":
						$sql="ALTER TABLE {$pfx} ADD KEY {$index_name} ({$str_param})";
						break;
				}
				$query_result[$pfx][$index_name]=array();
				$query_result[$pfx][$index_name]['sql'] = $sql;
				$query_result[$pfx][$index_name]['result'] =$wpdb->query( $sql );
			}
		}
	}
/*	echo "<pre><p>===================================AGGIUNTA=====================================================</p>".
	     var_export($query_result,true).
	     "<p>================================================================================================</p></pre>";*/
	//var_dump($query_result);
	return $query_result;
}
//AHSC_DBOPT_Optimize();

/*CANCELLAZIONE
 *
 * ALTER TABLE `my_table` DROP KEY `name_of_my_key`
 * ALTER TABLE table_name DROP INDEX unique_name,
 * */
function AHSC_DBOPT_Drop_chenges(){
	global $tables,$wpdb;
	$query_result=array();
	foreach($tables as $table_name=>$index_settings){
		foreach($index_settings as $index_name=>$index_param){

			//$str_param=implode(",",$index_param['param']);
			$pfx=$wpdb->prefix.substr($table_name,'3',strlen($table_name));
			switch ($index_param['type']){
				case "UNIQUE KEY":
					$sql="ALTER TABLE {$pfx} DROP INDEX {$index_name}";
					break;
				case "KEY":
					$sql="ALTER TABLE {$pfx} DROP KEY {$index_name}";
					break;
			}
			$query_result[$pfx][$index_name]=array();
			$query_result[$pfx][$index_name]['sql'] =  $sql; //$wpdb->query( $sql );
			$query_result[$pfx][$index_name]['result'] = $wpdb->query( $sql );

		}
	}
	/*echo "<pre> <p>===================================ELIMINAZIONE=====================================================</p>".
	     var_export($query_result,true).
	     "<p>================================================================================================</p></pre>";*/
	//var_dump($query_result);
	return $query_result;
}
//AHSC_DBOPT_Drop_chenges();