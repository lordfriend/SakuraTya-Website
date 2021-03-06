<?php
define('FAV_NAME', $table_prefix . "favorites");
add_action('profile_personal_options','add_sign_option');
add_action('profile_update', 'update_sign');
add_action('publish_post','recalc_score');
add_action('deleted_post','delete_score');
add_action('edit_post','delete_score');
add_action('wp_ajax_edit_comment','sakuratya_handle_edit_comment');
add_action('wp_ajax_nopriv_update_comments','sakuratya_handle_update_comments');
add_action('wp_ajax_update_comments','sakuratya_handle_update_comments');
add_action('wp_ajax_insert_comment','sakuratya_handle_insert_comment');

function sakuratya_handle_insert_comment(){
	header('Content-Type:text/plain;charset=utf-8');
	$time=current_time('mysql');
	$time_gmt=current_time('mysql',true);
	$data=array(
			'comment_post_ID'=>$_REQUEST['pid'],
			'comment_author'=>wp_get_current_user()->data->display_name,
			'comment_author_email'=>wp_get_current_user()->data->user_email,
			'comment_author_url'=>wp_get_current_user()->data->user_url,
			'comment_content'=>$_REQUEST['content'],
			'comment_type'=>'',
			'comment_parent'=>isset($_REQUEST['parent'])?$_REQUEST['parent']:0,
			'user_id'=>wp_get_current_user()->data->ID,
			'comment_author_IP'=>$_SERVER['REMOTE_ADDR'],
			'comment_agent'=>$_SERVER['HTTP_USER_AGENT'],
			'comment_date'=>$time,
			'comment_date_gmt'=>$time_gmt,
			'comment_approved' => 1
			);
	die(json_encode(array('status'=>wp_insert_comment($data))));
}

function sakuratya_handle_update_comments(){
	header('Content-Type:text/plain;charset=utf-8');
	if(!isset($_REQUEST['id'])){
		die();
	}
	$comments=get_comments(array(
			"post_id"=>$_REQUEST['id'],
			'status' => 'approve'
			));
	wp_list_comments(array('callback'=>'sakuratya_list_comments','max_depth'=>3),$comments);
	die();
}

function sakuratya_handle_edit_comment(){
	header('Content-Type:text/plain;charset=utf-8');
	if(isset($_REQUEST['content'])&&isset($_REQUEST['cid'])){}else{
		die(json_encode(array('error'=>'Invalid argument.')));
	}
	$commentarr=get_comment($_REQUEST['cid'],ARRAY_A);
	date_default_timezone_set('Asia/Shanghai');
	$commentarr['comment_date']=strftime('%Y-%m-%d %H:%M:%S');
	date_default_timezone_set('Europe/London');
	$commentarr['comment_date_gmt']=strftime('%Y-%m-%d %H:%M:%S');
	date_default_timezone_set('Asia/Shanghai');
	$commentarr['comment_agent']=$_SERVER['HTTP_USER_AGENT'];
	$commentarr['comment_author_IP']=$_SERVER['REMOTE_ADDR'];
	$commentarr['comment_author_url']=get_author_posts_url(get_user_by('email',$commentarr['comment_author_email'])->ID);
	$commentarr["comment_content"]=$_REQUEST['content'];
	die(json_encode(array('status'=>wp_update_comment($commentarr))));
}

function sakuratya_list_comments($comment, $args, $depth){
	$GLOBALS['comment'] = $comment;
	?> 
	<li <?php comment_class()?> id="li-comment-<?php comment_ID() ?>">
	<div class="comment" id="comment_<?php comment_ID() ?>">
		<?php if($comment->comment_parent==0){?>
			<div class="comment_meta">
				<div class="comment_author">
					<a class="comment_author_avatar" href="<?php echo get_author_posts_url(get_user_by('email',$comment->comment_author_email)->ID);?>"><img src="<?php echo get_custom_avatar($comment->user_id)?>" /></a>
					<a class="comment_author_name" href="<?php echo get_author_posts_url(get_user_by('email',$comment->comment_author_email)->ID);?>"><?php echo $comment->comment_author;?></a>
				</div>
				<div class="comment_timestamp">
					<span>发表于</span>
					<a href="#comment_1">
						<time datetime="<?php echo $comment->comment_date;?>"><?php echo $comment->comment_date;?></time>
					</a>
				</div>
			</div>
		<?php }else{?>
			<?php $parent=get_comment($comment->comment_parent);?>
			<div class="comment_meta">
				<div class="comment_author">
					<a class="comment_author_avatar" href="<?php echo get_author_posts_url(get_user_by('email',$comment->comment_author_email)->ID);?>"><img src="<?php echo get_custom_avatar($comment->user_id)?>" /></a>
					<a class="comment_author_name" href="<?php echo get_author_posts_url(get_user_by('email',$comment->comment_author_email)->ID);?>"><?php echo $comment->comment_author;?></a>
				</div>
				<div class="comment_conversation">
					<span>对</span>
					<a href="<?php echo get_author_posts_url(get_user_by('email',$parent->comment_author_email)->ID);?>"><?php echo $parent->comment_author;?></a>
					<span>的回复</span>
				</div>
				<div class="comment_timestamp">
					<span>发表于</span>
					<a href="#comment_1">
						<time datetime="<?php echo $comment->comment_date;?>"><?php echo $comment->comment_date;?></time>
					</a>
				</div>
			</div>
		<?php }?>
	<div class="comment_content">
		<p><?php echo $comment->comment_content;?></p>
	</div>
	<div class="reply"><div class="reply_icon"></div><span>回复</span></div>
	<?php if(current_user_can('edit_comment')&&current_user_can('moderate_comments')){?>
	<div class="edit_link" cid="<?php comment_ID()?>"><div class="edit_icon"></div><span>编辑</span></div>
	<?php }?>
	<div style="display:block;clear:both;"></div>
</div>
	<?php 
}

function recalc_score($id){
	
}

function delete_score($id){
	
}

function update_sign($uid){
	if(!isset($_POST['sign'])){
		return;
	}
	$sign=$_POST['sign'];
	$sign=strip_tags($sign);
	update_user_meta($uid, 'sign', $sign);
}

function get_sign($uid){
	return get_user_meta($uid,'sign',true);
}

function add_sign_option($profileuser){
	?>
	<table class="form-table">
		<tr>
			<th scope="row">签名</th>
			<td>
				<input type="text" name="sign" id="sign" class="regular-text" value="<?php echo get_user_meta($profileuser->ID,'sign',true);?>" />
				<span class="description">将会被显示在您发布的文章和您的用户主页中。</span>
			</td>
		</tr>
	</table>
	<?php 
}
function rank_score_calc($post, $dc, $vc, $g){
	date_default_timezone_set('Asia/Shanghai');
	$cc = (int)$post->comment_count;
	$dtm = new DateTime($post->post_modified);
	$dtn = new DateTime();
	$span = $dtn->diff($dtm)->days;
	$score = (0.5*$dc + 0.3*$cc + 0.2*$vc)/(pow($span+2,$g));
	return $score;
}
function author_info($id){
	global $dir;
	?>
	<div id="author_info">
	<!-- The href property of #author_avatar_wrapper and #author_name is both the author profile page's url -->
	<!-- <?php echo $id;?> -->
	<a id="author_avatar_wrapper" href="<?php echo get_author_posts_url($id);?>"><img src="<?php echo get_custom_avatar($id);?>" /></a>
	<a id="author_name" href="<?php echo get_author_posts_url($id);?>"><?php echo get_author_name($id);?></a>
	<div id="author_sign"><?php echo get_sign($id);?></div>
	<div id="author_function_wrapper"></div>
	<div class="info_section_label">
	<div id="info_label_author"></div>
	<div class="info_section_divider"></div>
	</div>
	</div>
	<?php	
}
function nav_menu(){
	?>
	<div id="header_bar">
		<div id="header_wrapper">
			<div id="logo"></div>
			<div id="user_container_wrapper"></div>
		</div>
	</div>
	<div id="nav_menu_wrapper">
		<ul id="nav_menu">
				<li><a href="/">首页</a></li>
				<li><a href="/themes">系统主题</a></li>
				<li><a href="/skins">软件皮肤</a></li>
				<li><a href="/icons-and-cursors">图标&amp;光标</a></li>
				<li><a href="/paintings">绘画作品</a></li>
		</ul>
		<div style="clear:both;display:block"></div>
	</div>
			<?php
}
function set_post_views($postID) {
	$count_key = 'post_views_count';
	$count = get_post_meta($postID, $count_key, true);
	if($count==''){
		$count = 0;
		delete_post_meta($postID, $count_key);
		add_post_meta($postID, $count_key, '0');
	}else{
		$count++;
		update_post_meta($postID, $count_key, $count);
	}
}
function get_post_views($postID){
	$count_key = 'post_views_count';
	$count = get_post_meta($postID, $count_key, true);
	if($count==''){
		delete_post_meta($postID, $count_key);
		add_post_meta($postID, $count_key, '0');
		return "0";
	}
	return $count;
}
function is_favorite($post_id, $user_id){
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(*) FROM `".FAV_NAME."` WHERE `user_id` = ".$user_id." AND `post_id` = ".$post_id)!=0;
}
function get_favorites($post_id){
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(*) FROM `".FAV_NAME."` WHERE `post_id` = ".$post_id);
}
function get_downloads($post_id){
	$r = get_post_meta($post_id, "downloads", true);
	if ($r ==""){return 0;}else{return $r;}
}
function work_block($post){
	global $wpdb;
	$id = $post->ID;
	$tags = get_the_tags($id);
	$dc=get_downloads($id);
	$fc=get_favorites($id);
	//@todo 排行榜每天0点更新，需在配置时写入crontab
	if (function_exists('get_field')){
		$aid = get_field('preview',$id);
		if (!$aid == ""){
			$a = wp_get_attachment_image_src($aid, array(240,180));
			$b = wp_get_attachment_image_src($aid,'full');
			$preview= $a[0];
			$preview_big= $b[0];
			unset($a);unset($b);
		}
	}
	?>
<div id="work_id_<?php echo $id;?>" class="works_panel">
	<div class="work_content_wrapper">
		<div class="preview_img_wrapper">
			<a href="<?php echo $post->guid;?>">
				<img src="<?php echo $preview;?>" />
			</a>
				<div class="img_lightbox_controls">
					<a href="<?php echo $preview_big;?>" class="zoom_in_tool"></a>
				</div>
		</div>
		<div class="work_title">
			<a href="<?php echo $post->guid;?>" title="<?php echo $post->post_title;?>"><?php echo $post->post_title."(".rank_score_calc($post, $dc, get_post_views($id), 1).")";?></a>
		</div>
        <div class="work_author">
			<span>by</span>
			<a href="<?php echo get_author_posts_url($post->post_author);?>"><?php echo get_the_author_meta('display_name',$post->post_author);?></a>
		</div>
		<div class="work_tags">
			<!-- data attribute in li element is tagID, the text is tagName -->
			<ul>
				<?php if($tags){?>
					<?php foreach ($tags as $tag){?>
					<li data="<?php echo $tag->term_id;?>"><?php echo $tag->name;?></li>
					<?php }?>
				<?php }?>
				<div style="clear:both;display:block;"></div>
			</ul>
		</div>
		<div class="work_panel_divider"></div>
		<div class="statistics_show_wrapper">
			<div class="statistics_downloads_wrapper" title="<?php echo $dc;?>次下载">
				<div class="downloads_icon"></div>
					<span><?php echo $dc;?></span>
			</div>
		<div class="statistics_comments_wrapper" title="<?php echo $post->comment_count;?>条评论">
			<div class="comments_icon"></div>
			<span><?php echo $post->comment_count;?></span>
		</div>
		<div class="statistics_favorites_wrapper" title="<?php echo $fc;?>次收藏">
			<div class="favorites_icon"></div>
			<span><?php echo $fc;?></span>
		</div>
		</div>
	</div>
	<div class="work_special_indicator"></div>
</div>
                <?php
}