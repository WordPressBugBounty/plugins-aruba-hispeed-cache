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
/*
 * The three rules switched off across this file cannot be satisfied by an index management
 * feature: WordPress exposes no API for DDL or for reading index metadata, altering the
 * schema is the whole point, and a DDL statement has nothing to cache. They are disabled by
 * name, with the reason recorded, rather than through a blanket suppression — every other
 * rule still applies to this file.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- index management on core tables: no WordPress API exists and nothing here is cacheable.

function AHSC_check_key_exists($index_name,$table_name){
	global $wpdb;
	$pfx=$wpdb->prefix.substr($table_name,'3',strlen($table_name));

	/*
	 * Three things were wrong here.
	 *
	 * 1. The index and table names were interpolated straight into the query text. They
	 *    come from the $ahsc_tables constant map rather than from user input, so it was
	 *    not exploitable, but there is no reason not to prepare it: INDEX_NAME and
	 *    TABLE_NAME are compared as values, so %s is the correct placeholder.
	 *
	 * 2. The result came from $wpdb->query(), which returns false when the query fails,
	 *    and the old "return ( $result !== 0 ) ? 1 : 0" read that false as 1, i.e. "the
	 *    index is already there". A failing lookup therefore reported the table as
	 *    optimized: AHSC_DBOPT_Optimize() skipped creating the index and
	 *    AHSC_DBOPT_Check() declared everything fine. get_var() returns null on failure,
	 *    which is distinguishable from a legitimate count of zero.
	 *
	 * 3. INFORMATION_SCHEMA spans every schema on the server, and the query had no
	 *    TABLE_SCHEMA filter. On shared MySQL with several installations using the same
	 *    table prefix, an index belonging to another database counted as ours.
	 *    DATABASE() pins the lookup to the current connection.
	 */
	$found = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(DISTINCT INDEX_NAME) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = %s',
			array( $pfx, $index_name )
		)
	);

	if ( null === $found ) {
		AHSC_log(
			sprintf( 'Index lookup failed for %1$s.%2$s: %3$s', $pfx, $index_name, $wpdb->last_error ),
			'db-optimization',
			'warning'
		);

		/*
		 * Report the index as missing rather than present. The optimization is then
		 * attempted and any problem surfaces — at worst MySQL answers "Duplicate key
		 * name", which is harmless and gets logged — whereas claiming it exists hides
		 * the failure behind a green status.
		 */
		return 0;
	}

	$result = (int) $found;
	/*echo "<pre> <p>===================================SQLCONTROLLOESISTENZA=====================================================</p>".
	     "<p>index : $index_name table: $pfx </p>".
	     "<p>sql : $sql</p>".
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
	global $ahsc_tables,$wpdb;
	$query_result=array();
	foreach($ahsc_tables as $table_name=>$index_settings){
		$pfx=$wpdb->prefix.substr($table_name,'3',strlen($table_name));
		//$sql="ALTER TABLE {$pfx} ROW_FORMAT=DYNAMIC;";
		$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ROW_FORMAT=DYNAMIC;", array( $pfx ) ) );
		foreach($index_settings as $index_name=>$index_param){

			//$str_param=implode(",",$index_param['param']);

			//$query_result[$pfx][$index_name]=array();
			/*
			 * The placeholder list used to be assembled at runtime ("%i,%i,%i") and
			 * interpolated into the query text. That works — $wpdb->prepare() accepts the
			 * arguments as a single array — but it makes the statement impossible to
			 * verify statically, because neither a reader nor PHPCS can match the
			 * placeholders against the arguments. Index definitions carry 1 to 5 columns
			 * (wp_posts uses 5), so one literal query per arity keeps every placeholder
			 * checkable, with a logged fallback if a wider index is ever added.
			 */
			$columns = array_values( $index_param["param"] );
			$k_exs=AHSC_check_key_exists($index_name,$table_name);

			/*switch ($index_param['type']) {
				case "UNIQUE KEY":
					$query_result[$pfx][$index_name]['sql']=$wpdb->prepare("ALTER TABLE %i ADD CONSTRAINT %i UNIQUE ($param_prepare_str)",$prepare_arr);
				case "KEY":
					$query_result[$pfx][$index_name]['sql']=$wpdb->prepare("ALTER TABLE %i ADD KEY %i ($param_prepare_str)",$prepare_arr);

			}*/

			if(!$k_exs){

				$is_unique = ( 'UNIQUE KEY' === $index_param['type'] );

				// Cleared so the statements below can be checked as a group afterwards.
				$wpdb->last_error = '';

				switch ( count( $columns ) ) {
					case 1:
						if ( $is_unique ) {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD CONSTRAINT %i UNIQUE (%i)', $pfx, $index_name, $columns[0] ) );
						} else {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD KEY %i (%i)', $pfx, $index_name, $columns[0] ) );
						}
						break;
					case 2:
						if ( $is_unique ) {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD CONSTRAINT %i UNIQUE (%i,%i)', $pfx, $index_name, $columns[0], $columns[1] ) );
						} else {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD KEY %i (%i,%i)', $pfx, $index_name, $columns[0], $columns[1] ) );
						}
						break;
					case 3:
						if ( $is_unique ) {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD CONSTRAINT %i UNIQUE (%i,%i,%i)', $pfx, $index_name, $columns[0], $columns[1], $columns[2] ) );
						} else {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD KEY %i (%i,%i,%i)', $pfx, $index_name, $columns[0], $columns[1], $columns[2] ) );
						}
						break;
					case 4:
						if ( $is_unique ) {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD CONSTRAINT %i UNIQUE (%i,%i,%i,%i)', $pfx, $index_name, $columns[0], $columns[1], $columns[2], $columns[3] ) );
						} else {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD KEY %i (%i,%i,%i,%i)', $pfx, $index_name, $columns[0], $columns[1], $columns[2], $columns[3] ) );
						}
						break;
					case 5:
						if ( $is_unique ) {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD CONSTRAINT %i UNIQUE (%i,%i,%i,%i,%i)', $pfx, $index_name, $columns[0], $columns[1], $columns[2], $columns[3], $columns[4] ) );
						} else {
							$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD KEY %i (%i,%i,%i,%i,%i)', $pfx, $index_name, $columns[0], $columns[1], $columns[2], $columns[3], $columns[4] ) );
						}
						break;
					default:
						// Never silently skip an index: adding a wider one to $ahsc_tables must be noticed.
						AHSC_log(
							sprintf( 'Unsupported index width (%1$d columns) for %2$s.%3$s: index not created.', count( $columns ), $pfx, $index_name ),
							'db-optimization',
							'warning'
						);
						break;
				}

				// A failed ALTER used to pass unnoticed: the return value was discarded.
				if ( '' !== $wpdb->last_error ) {
					AHSC_log(
						sprintf( 'Could not create index %1$s on %2$s: %3$s', $index_name, $pfx, $wpdb->last_error ),
						'db-optimization',
						'warning'
					);
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

					$wpdb->query( $wpdb->prepare( "DROP INDEX %i ON %i;", array( $index_name, $pfx ) ) );

					break;
				case "KEY":
					//$sql="ALTER TABLE {$pfx} DROP KEY {$index_name}";
					$wpdb->query( $wpdb->prepare( "ALTER TABLE %i DROP KEY %i;", array( $pfx, $index_name ) ) );
					break;
			}
			//$query_result[$pfx][$index_name]=array();
			//$query_result[$pfx][$index_name]['sql'] =  $sql; //$wpdb->query( $sql );
			//$query_result[$pfx][$index_name]['result'] = $wpdb->query( $sql );

		}
	}
	//var_dump($query_result);
	return $query_result;
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

//AHSC_DBOPT_Drop_chenges();