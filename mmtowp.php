<?php
/*
Plugin Name: Mindmap to WP
Description: Convert mindmap to WordPress content
Author: <a href="https://www.skribix.com" target="_blank">Skribix.com</a>
Text Domain: mmtowp
Version: 1.1
*/

function mmtowp_menu() {
	add_menu_page('Mindmap to WP', 'Mindmap to WP', 8, 'mmtowp_panel','mmtowp_panel', 'dashicons-admin-site-alt3');
}
add_action("admin_menu", "mmtowp_menu");

function mmtowp_panel() {
	wp_register_script('mmtowp-admin-js',plugins_url( '/js/mmtowp_admin.js', __FILE__ ),'jquery','1.0');
	wp_enqueue_script('mmtowp-admin-js');
	
	$mmtowp_firstbgcolor=get_option( 'mmtowp_firstbgcolor' );
	$mmtowp_secondbgcolor=get_option( 'mmtowp_secondbgcolor' );
	$mmtowp_firsttxtcolor=get_option( 'mmtowp_firsttxtcolor' );
	$mmtowp_secondtxtcolor=get_option( 'mmtowp_secondtxtcolor' );
	$mmtowp_obfuscatelinks=get_option( 'mmtowp_obfuscatelinks' );
	
	if (isset($_POST['mmtowp_firstbgcolor']) && isset($_POST['mmtowp_secondbgcolor']) && isset($_POST['mmtowp_firsttxtcolor']) && isset($_POST['mmtowp_secondtxtcolor']) && isset($_POST['mmtowp_obfuscatelinks'])) {
		$mmtowp_firstbgcolor=wp_strip_all_tags($_POST['mmtowp_firstbgcolor']);
		$mmtowp_secondbgcolor=wp_strip_all_tags($_POST['mmtowp_secondbgcolor']);
		$mmtowp_firsttxtcolor=wp_strip_all_tags($_POST['mmtowp_firsttxtcolor']);
		$mmtowp_secondtxtcolor=wp_strip_all_tags($_POST['mmtowp_secondtxtcolor']);
		$mmtowp_obfuscatelinks=wp_strip_all_tags($_POST['mmtowp_obfuscatelinks']);
		
		update_option( 'mmtowp_firstbgcolor', $mmtowp_firstbgcolor );
		update_option( 'mmtowp_secondbgcolor', $mmtowp_secondbgcolor );
		update_option( 'mmtowp_firsttxtcolor', $mmtowp_firsttxtcolor );
		update_option( 'mmtowp_secondtxtcolor', $mmtowp_secondtxtcolor );
		update_option( 'mmtowp_obfuscatelinks', $mmtowp_obfuscatelinks );
	}
	
	echo '
	<style>#mmimport_results{display:none;} .card {width:100% !important;max-width:100% !important;}</style>
	<div class="wrap">
		<h1>Mindmap to WordPress</h1>
		<form id="mmform">
			<div class="card">
				<h2 class="title">Import mindmap file</h2>
				<p>Upload your mindmap file to scan data before import (depth 6 maximum).</p>
				<p><input type="file" accept=".mm" id="mmfile"></p>
				<p id="mmimport_results"></p>
			</div>
		</form>
		<br />
		<form action="" method="POST">
			<div class="card">
				<h2 class="title">Customize menu</h2>
				<p>You can use <b>[mmtowp_menu]</b> shortcode to insert the dynamic menu in a sidebar widget.</p>
				<p>First background color: <input type="text" name="mmtowp_firstbgcolor" value="'.$mmtowp_firstbgcolor.'"></p>
				<p>First text color: <input type="text" name="mmtowp_firsttxtcolor" value="'.$mmtowp_firsttxtcolor.'"></p>
				<p>Second background color: <input type="text" name="mmtowp_secondbgcolor" value="'.$mmtowp_secondbgcolor.'"></p>
				<p>Second text color: <input type="text" name="mmtowp_secondtxtcolor" value="'.$mmtowp_secondtxtcolor.'"></p>
				<p>Obfuscate links: <select name="mmtowp_obfuscatelinks"><option value="No" '.($mmtowp_obfuscatelinks=='No'?'selected':'').'>No</option><option value="Yes" '.($mmtowp_obfuscatelinks=='Yes'?'selected':'').'>Yes</option></select>
				<p><input type="submit" value="Update"></p>
			</div>
		</form>
	</div>
	';
}

add_action("wp_ajax_mmtowp_mm_upload", "mmtowp_mm_upload");
function mmtowp_mm_upload() {
	global $wpdb;
	
	$created_pages=0;
	$updated_pages=0;
	$drafted_pages=0;
	$nodes_ID=array();
	
	$xml=simplexml_load_file($_FILES['theFile']['tmp_name']) or die("Error: Cannot create object");
	
	$lvl_0_kw=(string)$xml->node[0]['TEXT'];
	$lvl_0_ID=(string)$xml->node[0]['ID'];
	
	$hasdata = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key='node_ID' and meta_value='".$lvl_0_ID."'");
	if (count($hasdata) == 0) {
		$ins_post = array(
			'post_title'    => $lvl_0_kw,
			'post_type'  	=> 'page',
			'post_content'  => '',
			'post_status'   => 'pending',
			'post_author'   => get_current_user_id()
		);
		$lvl_0_postId=wp_insert_post($ins_post);
		update_post_meta($lvl_0_postId, "node_ID", ''.$lvl_0_ID.'');
		update_post_meta($lvl_0_postId, "node_level", 0);
		
		$created_pages++;
		$nodes_ID[]=$lvl_0_ID;
	}
	else {
		$loop = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_level', 'value' => '0'))));
		while ( $loop->have_posts() ) : $loop->the_post();
			global $post;
			$postid=$post->ID;
			
			$update_post = array('ID'=> $postid,'post_title'=> $lvl_0_kw,'post_status'=> 'pending');
			wp_update_post($update_post);
			
			update_post_meta($postid, "node_ID", ''.$lvl_0_ID.'');
			
		endwhile; wp_reset_query();
		
		$updated_pages++;
		$nodes_ID[]=$lvl_0_ID;
	}

	foreach ($xml->node[0]->node as $lvl1) {
		$lvl_1_kw=(string)$lvl1['TEXT'];
		$lvl_1_ID=(string)$lvl1['ID'];
	
		$hasdata = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key='node_ID' and meta_value='".$lvl_1_ID."'");
		if (count($hasdata) == 0) {
			$ins_post = array(
				'post_title'    => $lvl_1_kw,
				'post_type'  	=> 'page',
				'post_content'  => '',
				'post_status'   => 'pending',
				'post_author'   => get_current_user_id()
			);
			$lvl_1_postId=wp_insert_post($ins_post);
			update_post_meta($lvl_1_postId, "node_ID", ''.$lvl_1_ID.'');
			update_post_meta($lvl_1_postId, "node_level", 1);
			update_post_meta($lvl_1_postId, "node_parent_ID", ''.$lvl_0_ID.'');
			
			$created_pages++;
			$nodes_ID[]=$lvl_1_ID;
		}
		else {
			$loop = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $lvl_1_ID))));
			while ( $loop->have_posts() ) : $loop->the_post();
				global $post;
				$postid=$post->ID;
				
				$update_post = array('ID'=> $postid,'post_title'=> $lvl_1_kw,'post_status'=> 'pending');
				wp_update_post($update_post);
				
				update_post_meta($postid, "node_parent_ID", ''.$lvl_0_ID.'');
				
			endwhile; wp_reset_query();
			
			$updated_pages++;
			$nodes_ID[]=$lvl_1_ID;
		}
		
		foreach ($lvl1->node as $lvl2) {
			$lvl_2_kw=(string)$lvl2['TEXT'];
			$lvl_2_ID=(string)$lvl2['ID'];
			
			$hasdata = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key='node_ID' and meta_value='".$lvl_2_ID."'");
			if (count($hasdata) == 0) {
				$ins_post = array(
					'post_title'    => $lvl_2_kw,
					'post_type'  	=> 'page',
					'post_content'  => '',
					'post_status'   => 'pending',
					'post_author'   => get_current_user_id()
				);
				$lvl_2_postId=wp_insert_post($ins_post);
				update_post_meta($lvl_2_postId, "node_ID", ''.$lvl_2_ID.'');
				update_post_meta($lvl_2_postId, "node_level", 2);
				update_post_meta($lvl_2_postId, "node_parent_ID", ''.$lvl_1_ID.'');
				
				$created_pages++;
				$nodes_ID[]=$lvl_2_ID;
			}
			else {
				$loop = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $lvl_2_ID))));
				while ( $loop->have_posts() ) : $loop->the_post();
					global $post;
					$postid=$post->ID;
					
					$update_post = array('ID'=> $postid,'post_title'=> $lvl_2_kw,'post_status'=> 'pending');
					wp_update_post($update_post);
					
					update_post_meta($postid, "node_parent_ID", ''.$lvl_1_ID.'');
					
				endwhile; wp_reset_query();
				
				$updated_pages++;
				$nodes_ID[]=$lvl_2_ID;
			}
			
			foreach ($lvl2->node as $lvl3) {				
				$lvl_3_kw=(string)$lvl3['TEXT'];
				$lvl_3_ID=(string)$lvl3['ID'];
				
				$hasdata = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key='node_ID' and meta_value='".$lvl_3_ID."'");
				if (count($hasdata) == 0) {
					$ins_post = array(
						'post_title'    => $lvl_3_kw,
						'post_type'  	=> 'page',
						'post_content'  => '',
						'post_status'   => 'pending',
						'post_author'   => get_current_user_id()
					);
					$lvl_3_postId=wp_insert_post($ins_post);
					update_post_meta($lvl_3_postId, "node_ID", ''.$lvl_3_ID.'');
					update_post_meta($lvl_3_postId, "node_level", 3);
					update_post_meta($lvl_3_postId, "node_parent_ID", ''.$lvl_2_ID.'');
					
					$created_pages++;
					$nodes_ID[]=$lvl_3_ID;
				}
				else {
					$loop = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $lvl_3_ID))));
					while ( $loop->have_posts() ) : $loop->the_post();
						global $post;
						$postid=$post->ID;
						
						$update_post = array('ID'=> $postid,'post_title'=> $lvl_3_kw,'post_status'=> 'pending');
						wp_update_post($update_post);
						
						update_post_meta($postid, "node_parent_ID", ''.$lvl_2_ID.'');
						
					endwhile; wp_reset_query();
					
					$updated_pages++;
					$nodes_ID[]=$lvl_3_ID;
				}

				foreach ($lvl3->node as $lvl4) {					
					$lvl_4_kw=(string)$lvl4['TEXT'];
					$lvl_4_ID=(string)$lvl4['ID'];
					
					$hasdata = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key='node_ID' and meta_value='".$lvl_4_ID."'");
					if (count($hasdata) == 0) {
						$ins_post = array(
							'post_title'    => $lvl_4_kw,
							'post_type'  	=> 'page',
							'post_content'  => '',
							'post_status'   => 'pending',
							'post_author'   => get_current_user_id()
						);
						$lvl_4_postId=wp_insert_post($ins_post);
						update_post_meta($lvl_4_postId, "node_ID", ''.$lvl_4_ID.'');
						update_post_meta($lvl_4_postId, "node_level", 4);
						update_post_meta($lvl_4_postId, "node_parent_ID", ''.$lvl_3_ID.'');
						
						$created_pages++;
						$nodes_ID[]=$lvl_4_ID;
					}
					else {
						$loop = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $lvl_4_ID))));
						while ( $loop->have_posts() ) : $loop->the_post();
							global $post;
							$postid=$post->ID;
							
							$update_post = array('ID'=> $postid,'post_title'=> $lvl_4_kw,'post_status'=> 'pending');
							wp_update_post($update_post);
							
							update_post_meta($postid, "node_parent_ID", ''.$lvl_3_ID.'');
							
						endwhile; wp_reset_query();
						
						$updated_pages++;
						$nodes_ID[]=$lvl_4_ID;
					}
					
					foreach ($lvl4->node as $lvl5) {						
						$lvl_5_kw=(string)$lvl5['TEXT'];
						$lvl_5_ID=(string)$lvl5['ID'];
						
						$hasdata = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key='node_ID' and meta_value='".$lvl_5_ID."'");
						if (count($hasdata) == 0) {
							$ins_post = array(
								'post_title'    => $lvl_5_kw,
								'post_type'  	=> 'page',
								'post_content'  => '',
								'post_status'   => 'pending',
								'post_author'   => get_current_user_id()
							);
							$lvl_5_postId=wp_insert_post($ins_post);
							update_post_meta($lvl_5_postId, "node_ID", ''.$lvl_5_ID.'');
							update_post_meta($lvl_5_postId, "node_level", 5);
							update_post_meta($lvl_5_postId, "node_parent_ID", ''.$lvl_4_ID.'');
							
							$created_pages++;
							$nodes_ID[]=$lvl_5_ID;
						}
						else {
							$loop = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $lvl_5_ID))));
							while ( $loop->have_posts() ) : $loop->the_post();
								global $post;
								$postid=$post->ID;
								
								$update_post = array('ID'=> $postid,'post_title'=> $lvl_5_kw,'post_status'=> 'pending');
								wp_update_post($update_post);
								
								update_post_meta($postid, "node_parent_ID", ''.$lvl_4_ID.'');
								
							endwhile; wp_reset_query();
							
							$updated_pages++;
							$nodes_ID[]=$lvl_5_ID;
						}
						
						foreach ($lvl5->node as $lvl6) {							
							$lvl_6_kw=(string)$lvl6['TEXT'];
							$lvl_6_ID=(string)$lvl6['ID'];
							
							$hasdata = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key='node_ID' and meta_value='".$lvl_6_ID."'");
							if (count($hasdata) == 0) {
								$ins_post = array(
									'post_title'    => $lvl_6_kw,
									'post_type'  	=> 'page',
									'post_content'  => '',
									'post_status'   => 'pending',
									'post_author'   => get_current_user_id()
								);
								$lvl_6_postId=wp_insert_post($ins_post);
								update_post_meta($lvl_6_postId, "node_ID", ''.$lvl_6_ID.'');
								update_post_meta($lvl_6_postId, "node_level", 6);
								update_post_meta($lvl_6_postId, "node_parent_ID", ''.$lvl_5_ID.'');
								
								$created_pages++;
								$nodes_ID[]=$lvl_6_ID;
							}
							else {
								$loop = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $lvl_6_ID))));
								while ( $loop->have_posts() ) : $loop->the_post();
									global $post;
									$postid=$post->ID;
									
									$update_post = array('ID'=> $postid,'post_title'=> $lvl_6_kw,'post_status'=> 'pending');
									wp_update_post($update_post);
									
									update_post_meta($postid, "node_parent_ID", ''.$lvl_5_ID.'');
									
								endwhile; wp_reset_query();
								
								$updated_pages++;
								$nodes_ID[]=$lvl_6_ID;
							}
						}
					}
				}
			}
		}
	}
	
	$loop = new WP_Query(array('post_type' => 'page','posts_per_page' => -1));
	while ( $loop->have_posts() ) : $loop->the_post();
		global $post;
		$postid=$post->ID;
		
		$node_ID=get_post_meta($postid, "node_ID", true);
		if (strlen($node_ID)>1 && !in_array($node_ID, $nodes_ID)) {
			$update_post = array('ID'=> $postid,'post_status'=> 'draft');
			wp_update_post($update_post);
			$drafted_pages++;
		}
		
	endwhile; wp_reset_query();
	
	$output='<b>Import results:</b>';
	if ($created_pages>0) {
		$output.='<br>- '.$created_pages.' new page'.($created_pages>1?'s':'').' created';
	}
	if ($updated_pages>0) {
		$output.='<br>- '.$updated_pages.' existing page'.($updated_pages>1?'s':'').' updated';
	}
	if ($drafted_pages>0) {
		$output.='<br>- '.$drafted_pages.' old page'.($drafted_pages>1?'s':'').' updated in draft status';
	}
	
	echo $output;
	exit();
}


add_shortcode('mmtowp_menu','mmtowp_menu_fonction');
function mmtowp_menu_fonction(){
	wp_register_script('mmtowp-js',plugins_url( '/js/mmtowp.js', __FILE__ ),'jquery','1.0');
	wp_enqueue_script('mmtowp-js');
	wp_enqueue_style( 'mmtowp-css', plugins_url( '/css/mmtowp.css', __FILE__ ) );
	
	$mmtowp_firstbgcolor=get_option( 'mmtowp_firstbgcolor' );
	$mmtowp_secondbgcolor=get_option( 'mmtowp_secondbgcolor' );
	$mmtowp_firsttxtcolor=get_option( 'mmtowp_firsttxtcolor' );
	$mmtowp_secondtxtcolor=get_option( 'mmtowp_secondtxtcolor' );
	$mmtowp_obfuscatelinks=get_option( 'mmtowp_obfuscatelinks' );
	
	$return='';
	if (strlen($mmtowp_firstbgcolor)>1 || strlen($mmtowp_secondbgcolor)>1) {
		$return.='<style>';
		$return.=(strlen($mmtowp_firstbgcolor)>1?'.mmtowop_lvl { background-color:'.$mmtowp_firstbgcolor.' !important; } .mmtowop_lvl a,.mmtowop_lvl .mmtowp_link,.mmtowp_expand { color:'.$mmtowp_firsttxtcolor.' !important; }':'');
		$return.=(strlen($mmtowp_secondbgcolor)>1?'.mmtowop_lvl_2,.mmtowop_lvl_3,.mmtowop_lvl_4,.mmtowop_lvl_5,.mmtowop_lvl_6 { background-color:'.$mmtowp_secondbgcolor.' !important; } .mmtowop_lvl_2 a,.mmtowop_lvl_3 a,.mmtowop_lvl_4 a,.mmtowop_lvl_5 a,.mmtowop_lvl_6 a,.mmtowop_lvl_2 .mmtowp_link,.mmtowop_lvl_3 .mmtowp_link,.mmtowop_lvl_4 .mmtowp_link,.mmtowop_lvl_5 .mmtowp_link,.mmtowop_lvl_6 .mmtowp_link,.mmtowp_expand { color:'.$mmtowp_secondtxtcolor.' !important; } .mmtowop_lvl_3 a,.mmtowop_lvl_3 .mmtowp_link { border-left:1px dotted '.$mmtowp_secondtxtcolor.' !important; }':'');
		$return.='</style>';
	}
	
	$queried_object = get_queried_object();
	if ($queried_object) {
		$output=array();
		
		$post_id = $queried_object->ID;
		$node_level=get_post_meta($post_id, "node_level", true);
		$base_node_level=$node_level;
		$node_ID=get_post_meta($post_id, "node_ID", true);
		$base_node_ID=$node_ID;
		$node_parent_ID=get_post_meta($post_id, "node_parent_ID", true);
		
		if (strlen($node_ID)==0) { return ''; }
		
		
		if ($node_level==0) {
			$loop1 = new WP_Query(
				array(
					'post_type' => 'page',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
						  'key'   => 'node_level', 
						  'value' => 1
						)
					)
				)
			);
			while ( $loop1->have_posts() ) : $loop1->the_post();
				global $post;
				$postid=$post->ID;
				$post_title=$post->post_title;
				$return.='<div class="mmtowop_lvl mmtowop_lvl_1" data-level="1">'.($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>');
				$node_ID=get_post_meta($postid, "node_ID", true);
				
				$loop2 = new WP_Query(
					array(
						'post_type' => 'page',
						'posts_per_page' => -1,
						'meta_query' => array(
							array(
							  'key'   => 'node_level', 
							  'value' => 2
							),
							array(
							  'key'   => 'node_parent_ID', 
							  'value' => $node_ID
							)
						)
					)
				);
				while ( $loop2->have_posts() ) : $loop2->the_post();
					global $post;
					$postid=$post->ID;
					$post_title=$post->post_title;
					$return.='<div class="mmtowop_lvl mmtowop_lvl_2" data-level="2">'.($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>');
					$node_ID=get_post_meta($postid, "node_ID", true);
					
					$loop3 = new WP_Query(
						array(
							'post_type' => 'page',
							'posts_per_page' => -1,
							'meta_query' => array(
								array(
								  'key'   => 'node_level', 
								  'value' => 3
								),
								array(
								  'key'   => 'node_parent_ID', 
								  'value' => $node_ID
								)
							)
						)
					);
					while ( $loop3->have_posts() ) : $loop3->the_post();
						global $post;
						$postid=$post->ID;
						$post_title=$post->post_title;
						$return.='<div class="mmtowop_lvl mmtowop_lvl_3" data-level="3">'.($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>');
						$node_ID=get_post_meta($postid, "node_ID", true);
						
						$loop4 = new WP_Query(
							array(
								'post_type' => 'page',
								'posts_per_page' => -1,
								'meta_query' => array(
									array(
									  'key'   => 'node_level', 
									  'value' => 4
									),
									array(
									  'key'   => 'node_parent_ID', 
									  'value' => $node_ID
									)
								)
							)
						);
						while ( $loop4->have_posts() ) : $loop4->the_post();
							global $post;
							$postid=$post->ID;
							$post_title=$post->post_title;
							$return.='<div class="mmtowop_lvl mmtowop_lvl_4" data-level="4">'.($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>');
							$node_ID=get_post_meta($postid, "node_ID", true);
							
							$loop5 = new WP_Query(
								array(
									'post_type' => 'page',
									'posts_per_page' => -1,
									'meta_query' => array(
										array(
										  'key'   => 'node_level', 
										  'value' => 5
										),
										array(
										  'key'   => 'node_parent_ID', 
										  'value' => $node_ID
										)
									)
								)
							);
							while ( $loop5->have_posts() ) : $loop5->the_post();
								global $post;
								$postid=$post->ID;
								$post_title=$post->post_title;
								$return.='<div class="mmtowop_lvl mmtowop_lvl_5" data-level="5">'.($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>');
								$node_ID=get_post_meta($postid, "node_ID", true);
								
								$loop6 = new WP_Query(
									array(
										'post_type' => 'page',
										'posts_per_page' => -1,
										'meta_query' => array(
											array(
											  'key'   => 'node_level', 
											  'value' => 6
											),
											array(
											  'key'   => 'node_parent_ID', 
											  'value' => $node_ID
											)
										)
									)
								);
								while ( $loop6->have_posts() ) : $loop6->the_post();
									global $post;
									$postid=$post->ID;
									$post_title=$post->post_title;
									$return.='<div class="mmtowop_lvl mmtowop_lvl_6" data-level="6">'.($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>').'</div>';
									$node_ID=get_post_meta($postid, "node_ID", true);
								endwhile; wp_reset_query();
								$return.='</div>';
							endwhile; wp_reset_query();
							$return.='</div>';
						endwhile; wp_reset_query();
						$return.='</div>';
					endwhile; wp_reset_query();
					$return.='</div>';
				endwhile; wp_reset_query();
				$return.='</div>';
				
			endwhile; wp_reset_query();
			
			return $return;
		}
		else {
			$nodes=array();

			$loop1 = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $node_parent_ID))));
			while ( $loop1->have_posts() ) : $loop1->the_post();
				global $post;
				$postid=$post->ID;
				$post_title=$post->post_title;
				$morenode_ID=get_post_meta($postid, "node_ID", true);
				$morenode_parent_ID=get_post_meta($postid, "node_parent_ID", true);
				$morenode_level=get_post_meta($postid, "node_level", true);
				
				if ($post->ID > 0) {
					$nodes['lvl_'.$morenode_level][]=array(
						'link' => ($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>'),
						'node_ID' => $morenode_ID,
						'node_parent_ID' => $morenode_parent_ID,
						'node_level' => $morenode_level,
					);
				}
				
				$loop2 = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $morenode_parent_ID))));
				while ( $loop2->have_posts() ) : $loop2->the_post();
					global $post;
					$postid=$post->ID;
					$post_title=$post->post_title;
					$morenode_ID=get_post_meta($postid, "node_ID", true);
					$morenode_parent_ID=get_post_meta($postid, "node_parent_ID", true);
					$morenode_level=get_post_meta($postid, "node_level", true);
					
					if ($post->ID > 0) {
						$nodes['lvl_'.$morenode_level][]=array(
							'link' => ($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>'),
							'node_ID' => $morenode_ID,
							'node_parent_ID' => $morenode_parent_ID,
							'node_level' => $morenode_level,
						);
					}
					
					$loop3 = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $morenode_parent_ID))));
					while ( $loop3->have_posts() ) : $loop3->the_post();
						global $post;
						$postid=$post->ID;
						$post_title=$post->post_title;
						$morenode_ID=get_post_meta($postid, "node_ID", true);
						$morenode_parent_ID=get_post_meta($postid, "node_parent_ID", true);
						$morenode_level=get_post_meta($postid, "node_level", true);
						
						if ($post->ID > 0) {
							$nodes['lvl_'.$morenode_level][]=array(
								'link' => ($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>'),
								'node_ID' => $morenode_ID,
								'node_parent_ID' => $morenode_parent_ID,
								'node_level' => $morenode_level,
							);
						}
						
						$loop4 = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $morenode_parent_ID))));
						while ( $loop4->have_posts() ) : $loop4->the_post();
							global $post;
							$postid=$post->ID;
							$post_title=$post->post_title;
							$morenode_ID=get_post_meta($postid, "node_ID", true);
							$morenode_parent_ID=get_post_meta($postid, "node_parent_ID", true);
							$morenode_level=get_post_meta($postid, "node_level", true);
							
							if ($post->ID > 0) {
								$nodes['lvl_'.$morenode_level][]=array(
									'link' => ($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>'),
									'node_ID' => $morenode_ID,
									'node_parent_ID' => $morenode_parent_ID,
									'node_level' => $morenode_level,
								);
							}
							
							$loop5 = new WP_Query(array('post_type' => 'page','posts_per_page' => 1,'meta_query' => array(array('key' => 'node_ID', 'value' => $morenode_parent_ID))));
							while ( $loop5->have_posts() ) : $loop5->the_post();
								global $post;
								$postid=$post->ID;
								$post_title=$post->post_title;
								$morenode_ID=get_post_meta($postid, "node_ID", true);
								$morenode_parent_ID=get_post_meta($postid, "node_parent_ID", true);
								$morenode_level=get_post_meta($postid, "node_level", true);
								
								if ($post->ID > 0) {
									$nodes['lvl_'.$morenode_level][]=array(
										'link' => ($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>'),
										'node_ID' => $morenode_ID,
										'node_parent_ID' => $morenode_parent_ID,
										'node_level' => $morenode_level,
									);
								}
							endwhile; wp_reset_query();
						endwhile; wp_reset_query();
					endwhile; wp_reset_query();
				endwhile; wp_reset_query();
			endwhile; wp_reset_query();
			
			$loop1 = new WP_Query(array('post_type' => 'page','posts_per_page' => -1,'meta_query' => array(array('key' => 'node_parent_ID', 'value' => $node_parent_ID))));
			while ( $loop1->have_posts() ) : $loop1->the_post();
				global $post;
				$postid=$post->ID;
				$post_title=$post->post_title;
				$morenode_ID=get_post_meta($postid, "node_ID", true);
				$morenode_parent_ID=get_post_meta($postid, "node_parent_ID", true);
				$morenode_level=get_post_meta($postid, "node_level", true);
				
				if ($post->ID > 0) {
					$nodes['lvl_'.$morenode_level][]=array(
						'link' => ($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>'),
						'node_ID' => $morenode_ID,
						'node_parent_ID' => $morenode_parent_ID,
						'node_level' => $morenode_level,
					);
				}
			endwhile; wp_reset_query();
			
			$loop1 = new WP_Query(array('post_type' => 'page','posts_per_page' => -1,'meta_query' => array(array('key' => 'node_parent_ID', 'value' => $node_ID))));
			while ( $loop1->have_posts() ) : $loop1->the_post();
				global $post;
				$postid=$post->ID;
				$post_title=$post->post_title;
				$morenode_ID=get_post_meta($postid, "node_ID", true);
				$morenode_parent_ID=get_post_meta($postid, "node_parent_ID", true);
				$morenode_level=get_post_meta($postid, "node_level", true);
				
				if ($post->ID > 0) {
					$nodes['lvl_'.$morenode_level][]=array(
						'link' => ($mmtowp_obfuscatelinks=='Yes'?'<span class="mmtowp_link" data-mmtowplink="'.base64_encode(urlencode(get_permalink($postid))).'">'.ucfirst($post_title).'</span>':'<a href="'.get_permalink($postid).'">'.ucfirst($post_title).'</a>'),
						'node_ID' => $morenode_ID,
						'node_parent_ID' => $morenode_parent_ID,
						'node_level' => $morenode_level,
					);
				}
			endwhile; wp_reset_query();
			
			if ($node_level>0) {
				$return.='<input type="hidden" id="mmtowp_allexpand" value="1">';
			}
			
			$return.='<div class="mmtowop_lvl mmtowop_lvl_0" data-level="0">'.$nodes['lvl_0'][0]['link'].'</div>';
			
			if ($base_node_level==1) {
				foreach($nodes['lvl_1'] as $node) {
					$return.='<div class="mmtowop_lvl mmtowop_lvl_1 '.($node['node_ID']==$base_node_ID?'mmtowp_active':'').'" data-level="1">'.$node['link'].'';
					if ($node['node_ID']==$base_node_ID) {
						foreach($nodes['lvl_2'] as $nodedaughter) {
							$return.='<div class="mmtowop_lvl mmtowop_lvl_2" data-level="2">'.$nodedaughter['link'].'</div>';
						}
					}
					$return.='</div>';
				}
			}
			else if ($base_node_level==2) {
				$return.='<div class="mmtowop_lvl mmtowop_lvl_1" data-level="1">'.$nodes['lvl_1'][0]['link'];
				foreach($nodes['lvl_2'] as $node) {
					$return.='<div class="mmtowop_lvl mmtowop_lvl_2 '.($node['node_ID']==$base_node_ID?'mmtowp_active':'').'" data-level="2">'.$node['link'].'';
					if ($node['node_ID']==$base_node_ID) {
						foreach($nodes['lvl_3'] as $nodedaughter) {
							$return.='<div class="mmtowop_lvl mmtowop_lvl_3" data-level="3">'.$nodedaughter['link'].'</div>';
						}
					}
					$return.='</div>';
				}
				$return.='</div>';
			}
			else if ($base_node_level==3) {
				$return.='<div class="mmtowop_lvl mmtowop_lvl_1" data-level="1">'.$nodes['lvl_1'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_2" data-level="2">'.$nodes['lvl_2'][0]['link'];
				foreach($nodes['lvl_3'] as $node) {
					$return.='<div class="mmtowop_lvl mmtowop_lvl_3 '.($node['node_ID']==$base_node_ID?'mmtowp_active':'').'" data-level="3">'.$node['link'].'';
					if ($node['node_ID']==$base_node_ID) {
						foreach($nodes['lvl_4'] as $nodedaughter) {
							$return.='<div class="mmtowop_lvl mmtowop_lvl_4" data-level="4">'.$nodedaughter['link'].'</div>';
						}
					}
					$return.='</div>';
				}
				$return.='</div>';
				$return.='</div>';
			}
			else if ($base_node_level==4) {
				$return.='<div class="mmtowop_lvl mmtowop_lvl_1" data-level="1">'.$nodes['lvl_1'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_2" data-level="2">'.$nodes['lvl_2'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_3" data-level="3">'.$nodes['lvl_3'][0]['link'];
				foreach($nodes['lvl_4'] as $node) {
					$return.='<div class="mmtowop_lvl mmtowop_lvl_4 '.($node['node_ID']==$base_node_ID?'mmtowp_active':'').'" data-level="4">'.$node['link'].'';
					if ($node['node_ID']==$base_node_ID) {
						foreach($nodes['lvl_5'] as $nodedaughter) {
							$return.='<div class="mmtowop_lvl mmtowop_lvl_5" data-level="5">'.$nodedaughter['link'].'</div>';
						}
					}
					$return.='</div>';
				}
				$return.='</div>';
				$return.='</div>';
				$return.='</div>';
			}
			else if ($base_node_level==5) {
				$return.='<div class="mmtowop_lvl mmtowop_lvl_1" data-level="1">'.$nodes['lvl_1'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_2" data-level="2">'.$nodes['lvl_2'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_3" data-level="3">'.$nodes['lvl_3'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_4" data-level="4">'.$nodes['lvl_4'][0]['link'];
				foreach($nodes['lvl_5'] as $node) {
					$return.='<div class="mmtowop_lvl mmtowop_lvl_5 '.($node['node_ID']==$base_node_ID?'mmtowp_active':'').'" data-level="5">'.$node['link'].'';
					if ($node['node_ID']==$base_node_ID) {
						foreach($nodes['lvl_6'] as $nodedaughter) {
							$return.='<div class="mmtowop_lvl mmtowop_lvl_6" data-level="6">'.$nodedaughter['link'].'</div>';
						}
					}
					$return.='</div>';
				}
				$return.='</div>';
				$return.='</div>';
				$return.='</div>';
				$return.='</div>';
			}
			else if ($base_node_level==6) {
				$return.='<div class="mmtowop_lvl mmtowop_lvl_1" data-level="1">'.$nodes['lvl_1'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_2" data-level="2">'.$nodes['lvl_2'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_3" data-level="3">'.$nodes['lvl_3'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_4" data-level="4">'.$nodes['lvl_4'][0]['link'];
				$return.='<div class="mmtowop_lvl mmtowop_lvl_5" data-level="5">'.$nodes['lvl_5'][0]['link'];
				foreach($nodes['lvl_6'] as $node) {
					$return.='<div class="mmtowop_lvl mmtowop_lvl_6 '.($node['node_ID']==$base_node_ID?'mmtowp_active':'').'" data-level="6">'.$node['link'].'</div>';
				}
				$return.='</div>';
				$return.='</div>';
				$return.='</div>';
				$return.='</div>';
				$return.='</div>';
			}
		}
		
		return $return;
	}
}

function mmtowop_meta_box_markup(){
	?>
	<p>Need content for your pages? Order your optimized content in just a few clicks.</p>
	<p><a class="button button-primary button-large" href="https://skribix.com" target="_blank">Order content</a></p>
	<?php
}

function mmtowop_custom_meta_box()
{
    add_meta_box("demo-meta-box", "Skribix", "mmtowop_meta_box_markup", "page", "side", "high", null);
}

add_action("add_meta_boxes", "mmtowop_custom_meta_box");
?>