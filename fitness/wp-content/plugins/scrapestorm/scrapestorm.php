<?php
/*
  Plugin Name: ScrapeStorm Publisher
  Plugin URI: http://www.scrapestorm.com
  Description: ScrapeStorm,publish data from scrapeStorm to wordpress。
  Version: 4.2.4
  Author: ScrapeStorm
  Author URI: http://www.scrapestorm.com
  License: GPL
 */
if(function_exists('date_default_timezone_set')){
    date_default_timezone_set('PRC');
}else{
    ini_set('date.timezone','Asia/Shanghai');
}

define('TA_PATH', WP_PLUGIN_DIR . '/scrapestorm');

include TA_PATH . '/ta-functions.php';

global $table_prefix, $ta_tbl_setting;

$ta_tbl_setting = $table_prefix . 'ta_publish_setting';

if (is_admin()) {
    /*  利用 admin_menu 钩子，添加菜单 */
    add_action('admin_menu', 'ta_menu');
}

function ta_menu() {
    if (function_exists('add_menu_page')) {
        add_menu_page('ScrapeStorm', 'ScrapeStorm', 'administrator', 'scrapestorm/ta-article-setting.php', '', 'dashicons-share-alt');
    }
}

add_action('init', 'ta_auto_post');


function ta_install() {
}
register_activation_hook(__FILE__, 'ta_install');

add_filter('the_content', 'image_referrer');

//defined function outside to avoid php<5.3
function image_replace($matches){
    global $home_url;
    $src = $matches['2'];
    if(strpos($src,"http") !== 0 || strpos($src, $home_url) ===0){
        //如果本地文件 则不做操作
        return $matches[0];
    }else if(
        strpos($src,"//static.shenjianshou.cn/image") !== false || strpos($src,"//static.shenjian.io/image") !== false ||
        strpos($src,"//static.shenjianshou.cn/images") !== false || strpos($src,"//static.shenjian.io/images") !== false
    ){
        //如果是托管在神箭手的 也不做操作
        return $matches[0];
    }
    return "<img src='".$home_url . "/?__ta=refer&url=" . urlencode($matches['2'])."' ".$matches['1']." ".$matches['3']." />";
}

function image_background($matches){
    global $home_url;
    $src = $matches['1'];
    if(strpos($src,"http") !== 0 || strpos($src, $home_url) ===0){
        //如果本地文件 则不做操作
        return $matches[0];
    }else if(strpos($src,"//static.shenjianshou.cn/images") !== false || strpos($src,"//static.shenjian.io/images") !== false){
        //如果是托管在神箭手的 也不做操作
        return $matches[0];
    }
    return " url(".$home_url."/?__ta=refer&url=".urlencode($src).") ";
}

function image_referrer( $content ) {
    $image_refer = get_option('ta_image_refer', false);
    global $home_url;
    $home_url = get_home_url();
    if($image_refer == true){
        //将非托管到神箭手上的图片和非内置图片替换成内置访问的模式
        //解决主要的防盗链问题
        $content = preg_replace_callback("/<img\s+(.*)src=[\"']([^\"']*)[\"']([^>]*)\/?>/i", "image_replace", $content);

        $content = preg_replace_callback("/\burl\s*\(\s*[\"']?([^\(\)\"']*)[\"']?\s*\)/i", "image_background", $content);
    }
    return $content;
}

function ta_auto_post() {
    global $wpdb;

	if (isset($_GET["__ta"])){
		//默认打开ERROR错误提示 方便调试
		ini_set("display_errors", "On");
        error_reporting(E_ERROR | E_PARSE);
		$request_data = mergeRequest();
		$ta_password = get_option('ta_password', "shenjian.io");
		if ($_GET["__ta"] == "post") {

			if (empty($request_data['__sign']) || $request_data['__sign'] != $ta_password) {
				ta_fail(TA_ERROR_INVALID_PWD, "password is wrong", "Wrong Password");
			}
			$title = $request_data["article_title"];
			$content = $request_data["article_content"];
			$excerpt = $request_data["article_excerpt"];
            //删除 script
            $title = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $title);
            $title = strip_tags($title);

			if (empty($content) && empty($title)) {
				ta_fail(TA_ERROR_MISSING_FIELD, "article_content and article_title are both empty", "title and content can't be empty!");
			}

			$postStatus = 'publish';
			if (isset($request_data["postStatus"]) && in_array($request_data["postStatus"], array('publish', 'draft'))) {
				$postStatus = $request_data["postStatus"];
			}

			$postPassword = '';
			if (isset($request_data["accessword"]) && $request_data["accessword"]) {
				$postPassword = $request_data["accessword"];
			}

			$my_post = array(
				'post_password' => $postPassword,
				'post_status' => $postStatus,
				'post_author' => 1
			);
			if (!empty($title)) {
				$my_post['post_title'] = htmlspecialchars_decode($title);
			}
			if (!empty($content)) {
				$my_post['post_content'] = $content;
			}
			if(!empty($excerpt)){
				$my_post['post_excerpt'] = htmlspecialchars_decode($excerpt);
			}

            $ta_unique = get_option('ta_unique', false);
			if($ta_unique){
                $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_title='%s'",$my_post['post_title']));
                if(!empty($post)){
                    ta_success(array("url" => get_home_url() . "/?p={$post->ID}"));
                }
            }

			$publish_time = intval($request_data["article_publish_time"]);
			if (!empty($publish_time)) {
				$my_post['post_date'] = date("Y-m-d H:i:s", $publish_time);
			} else {
				$my_post['post_date'] = date("Y-m-d H:i:s", time());
			}

			$author = htmlspecialchars_decode($request_data["article_author"]);

			if (!empty($author)) {
				if($author == "author_existing_users"){
                    $user_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->users order by rand() limit %d",1));
				}else{
					$md5author = substr(md5($author), 8, 16);
					$user_id = username_exists($md5author);
				}
				if (!$user_id) {
					$random_password = wp_generate_password();
					$userdata = array(
						'user_login' => $md5author,
						'user_pass' => $random_password,
						'display_name' => $author,
					);

					$user_id = wp_insert_user($userdata);
					if (is_wp_error($user_id)) {
						$user_id = 0;
					}
				}
				if ($user_id) {
					$my_post['post_author'] = $user_id;
				}
			}
			$article_categories = $request_data["article_categories"];
			if (!empty($article_categories)) {
				$rawCates = stripslashes($article_categories);
				$cates = json_decode($rawCates);
				if (is_array($cates)) {
					$post_cates = array();
					foreach ($cates as $cate) {
						$term = term_exists($cate, "category");

						if ($term === 0 || $term === null) {
							$term = wp_insert_term($cate, "category");
						}
						if ($term !== 0 && $term !== null && !is_wp_error($term)) {
							array_push($post_cates, intval($term["term_id"]));
						}
					}
					if (count($post_cates) > 0) {
						$my_post['post_category'] = $post_cates;
					}
				}
			}

			$article_topics = $request_data["article_topics"];
			if (!empty($article_topics)) {
				$rawTags = stripslashes($article_topics);
				$tags = json_decode($rawTags);

				if (is_array($tags)) {
					$post_tags = array();
					$term = null;
					foreach ($tags as $tag) {
						$term = term_exists($tag, "post_tag");

						if ($term === 0 || $term === null) {
							$term = wp_insert_term($tag, "post_tag");
						}
						if ($term !== 0 && $term !== null && !is_wp_error($term)) {
							array_push($post_tags, intval($term["term_id"]));
						}
					}
					if (count($post_tags) > 0) {
						$my_post['tags_input'] = $post_tags;
					}
				}
			}

			// Insert the post into the database
			kses_remove_filters();
			$post_id = wp_insert_post($my_post); //wp_insert_user wp_insert_comment wp_insert_category
			kses_init_filters();

			if (empty($post_id) || is_wp_error($post_id)) {
				ta_fail(TA_ERROR_ERROR, "Empty Post ID", "Empty Post Id");
			}

			if (!empty($request_data["article_thumbnail"])) {
				$image_url = ta_redirect_url($request_data["article_thumbnail"]); // Define the image URL here
				//创建thumbnail
				// Add Featured Image to Post
				if ($image_url !== false && !empty($post_id)) {
					$upload_dir = wp_upload_dir(); // Set upload folder
					$image_data = file_get_contents($image_url['realurl']); // Get image data
					$suffix = "jpg";
					$filename = md5($image_url['realurl']) . "." . $suffix; // Create image file name
					// Check folder permission and define file location
					if (wp_mkdir_p($upload_dir['path'])) {
						$file = $upload_dir['path'] . '/' . $filename;
					} else {
						$file = $upload_dir['basedir'] . '/' . $filename;
					}

					// Create the image  file on the server
					file_put_contents($file, $image_data);
					if (file_exists($file)) {
						//文件存在 在做插入
						// Check image file type
						$wp_filetype = wp_check_filetype($filename, null);

						// Set attachment data
						$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title' => sanitize_file_name($filename),
							'post_content' => '',
							'post_status' => 'inherit'
						);

						// Create the attachment
						$attach_id = wp_insert_attachment($attachment, $file, $post_id);

						// Include image.php
						require_once(ABSPATH . 'wp-admin/includes/image.php');

						// Define attachment metadata
						$attach_data = wp_generate_attachment_metadata($attach_id, $file);

						// Assign metadata to attachment
						wp_update_attachment_metadata($attach_id, $attach_data);

						// And finally assign featured image to post
						set_post_thumbnail($post_id, $attach_id);
					}
				}
			}

			//插入评论
			$comment_json = preg_replace("/[\r\n\t]/", '', $request_data['article_comment']);
			//ta_fail(TA_ERROR_ERROR, "DEBUG", "".$comment_json);
			$article_comment = json_decode(stripslashes($comment_json), true);

			if ($article_comment != null && is_array($article_comment)) {
				foreach ($article_comment as $comment) {
					$content = $comment["article_comment_content"];
					if (!empty($content)) {
						//内容不是空

						$pub_time = $comment["article_comment_publish_time"];
						if (empty($pub_time)) {
							$pub_time = time();
						}

						$cdata = array(
							'comment_post_ID' => $post_id,
							'comment_content' => $content,
							'comment_type' => '',
							'comment_parent' => 0,
							'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
							'comment_date' => date("Y-m-d H:i:s", $pub_time),
							'comment_approved' => 1,
						);

						$cauthor = $comment["article_comment_author"];

						if (!empty($cauthor)) {
							$cmd5author = substr(md5($cauthor), 8, 16);
							$cuser_id = username_exists($cmd5author);
							if (!$cuser_id) {
								$random_password = wp_generate_password();
								$cuserdata = array(
									'user_login' => $cmd5author,
									'user_pass' => $random_password,
									'display_name' => $cauthor,
								);

								$cuser_id = wp_insert_user($cuserdata);
								if (is_wp_error($cuser_id)) {
									$cuser_id = 0;
								}
							}
							if ($cuser_id != 0) {
								$cdata["user_id"] = $cuser_id;
							}
							$cdata["comment_author"] = $cauthor;
							$cdata['comment_author_IP'] = ta_random_ip();
						}

						wp_insert_comment($cdata);
					}
				}
			}

			//绑定的附件列表
            if($request_data['attach_id_list']){
                $upload_dir = wp_upload_dir(); // Set upload folder
                $attach_id_list = json_decode($request_data['attach_id_list'],true);
                foreach ($attach_id_list as $attach_id){
                    $metadata = wp_get_attachment_metadata($attach_id);
                    // $filename should be the path to a file in the upload directory.
                    $filename = $upload_dir['basedir']. '/'. $metadata['file'];
                    $filetype = wp_check_filetype( basename( $filename ), null );
                    $attachment = array(
                        'ID' => $attach_id,
                        'post_parent' => $post_id,
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    wp_insert_attachment($attachment, $filename);
                }
            }

			ta_success(array("url" => get_home_url() . "/?p=" . $post_id));
		} else if ($_GET["__ta"] == "details") {
			if (empty($request_data['__sign']) || $request_data['__sign'] != $ta_password) {
				ta_fail(TA_ERROR_INVALID_PWD, "password is wrong", "password is wrong");
			}
			$ret = array();

			if ($request_data["type"] === "cate") {
				$cates = get_terms('category', 'orderby=count&hide_empty=0');

				foreach ($cates as $cate) {
					array_push($ret, array("value" => urlencode($cate->name), "text" => urlencode($cate->name)));
				}
			}

			ta_success($ret);
		} else if ($_GET["__ta"] == "version") {
			global $wp_version;
			if (empty($request_data['__sign']) || $request_data['__sign'] != $ta_password) {
				ta_fail(TA_ERROR_INVALID_PWD, "password is wrong", "password is wrong");
			}
			$versions = array(
				'php' => PHP_VERSION,
				'plugin' => '4.2.4',
				'wp' => $wp_version,
			);
			ta_success($versions);
		} else if ($_GET["__ta"] == "refer") {
			//图片转发逻辑
			$url = urldecode($_GET["url"]);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			//处理302重定向的问题
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$img=curl_exec($ch);
			curl_close($ch);
			header("Content-type: image/jpeg");
			die($img);
		} else if ($_GET["__ta"] == "upload") {
            $upload_field = $request_data['upload_field'];
            if($upload_field == 'article_content'){
                $file_uri = json_decode(stripslashes($request_data['file_uri']), true);

                // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );

                // The ID of the post this attachment is for.
                $parent_post_id = 0;
                $index = 0 ;
                foreach ($_FILES as $file_id=>$file){
                    $attach_id = media_handle_upload( $file_id, $parent_post_id );
                    if (! is_wp_error( $attach_id ) ) {
                        $file_uri[$upload_field][$index]['attach_id'] = $attach_id;
                        $file_uri[$upload_field][$index]['remote'] = wp_get_attachment_url($attach_id);
                        $index ++;
                        // The image was uploaded successfully!
                    } else {
                        // There was an error uploading the image.
                    }
                }
                ta_success(array("file_uri"=>$file_uri));
            }
        }
	}
}
