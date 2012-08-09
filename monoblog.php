<?php

/*
  Plugin Name: Monoblog
  Plugin URI:  http://ownthenarrative.com/post/sots--monoblog
  Description: This widget plugin is so that you can record yourself or have someone else read your blog posts aloud (with something like Audacity) and upload it to your site so it can be played in the relevant blog post.
  Version: 8.10.12
  Author: Justin Kendall
  Author URI: http://OwnTheNarrative.com
 */
//Admin Functions separate for organizational purposes
include("admin_functions.php");

register_activation_hook(__FILE__, 'monoblog_activate');

function monoblog_activate() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    /*
     * Setup the database. Add table "wp_monoblog"
     */
    $table_name = $wpdb->prefix . "monoblog";
    $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "monoblog` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ep_file` varchar(255) NOT NULL,
    `ep_date` date NOT NULL,
    `ep_time` int(12) NOT NULL DEFAULT '0',
    `ep_desc` varchar(200) NOT NULL,
    `ep_duration` varchar(8) NOT NULL,
    `ep_mime` varchar(20) NOT NULL,
    `ep_title` varchar(60) NOT NULL,
    `ep_size` int(12) NOT NULL,
    `post_id` int(11) NOT NULL,
    UNIQUE KEY id (`id`)
    );";
    dbDelta($sql);
}

/*
 * Inits for both public and admin pages with enqueued scripts and styles.
 */

add_action('init', 'monoblog_main');

function monoblog_main() {
    wp_enqueue_script("jquery");
    wp_register_script("mediaelement-and-player", plugins_url('mediaelement/build/mediaelement-and-player.js', __FILE__));
    wp_enqueue_script('mediaelement-and-player');
    wp_register_style('mediaelement-style', plugins_url('mediaelement/build/mediaelementplayer.css', __FILE__));
    wp_enqueue_style('mediaelement-style');
    wp_enqueue_script('plupload-all');
    $post = url_to_postid("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    //return $post;
    //return has_monoblog($post);
}

add_action('widgets_init', create_function('', 'return register_widget("monoblog");'));

class monoblog extends WP_Widget {

    function monoblog() {
        parent::WP_Widget(false, $name = 'Monoblog');
    }

    function widget($args, $instance) {
        extract($args);
        $title = 'Monoblog';
        $message = $instance['message'];
        $url = url_to_postid("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

        if ($url != 0) {
                echo $before_widget;
                if ($title)
                    echo $before_title . $title . $after_title .
                    monoblog::has_monoblog($url) .
                    $after_widget;
        }
    }

    function has_monoblog($id) {
        global $wpdb;
        $mono = @current(@mysql_fetch_row(mysql_query('SELECT COUNT(*) AS the_count FROM ' . $wpdb->prefix . 'monoblog WHERE post_id=' . $id . ' LIMIT 1')));

        if ($mono < 1) {
            /*
             * If a user has at least author-level rights and there is no monoblog of record...
             * show the plupload form
             */
            if (current_user_can('author') || current_user_can('editor') || current_user_can('administrator')) {

                $rs = "
            Upload a Monoblog for this post 
                <div id=\"uploader\">
                    <a id=\"pickfiles\" href=\"javascript:;\" style='float:left;'>Select Monoblog File&nbsp;&nbsp;&nbsp;</a>
                    <div id=\"filelist\" style='float:left;'> </div>
                    <a id=\"uploadfiles\" href=\"javascript:;\"  style='float:left;'></a>
                </div>";
                $rs .= monoblog::plupload_init($id);
                return $rs;
            } else {
                return;
            }
        }
        /*
         * If there is a monoblog, show the MediaElement Player
         */ else {
            $mono = @current(@mysql_fetch_row(mysql_query('SELECT ep_file FROM ' . $wpdb->prefix . 'monoblog WHERE post_id=' . $id . ' LIMIT 1')));
            $rs = "<p style='width:100%; height:80px;'>This post has a Monoblog. <br>Click play on the player to hear.</p>";
            $rs .= "<audio id='mono_audio' controls='control' style='width:100%; height:0px; position:relative;' preload='none' src='" . plugins_url("monoblogs/$mono", __FILE__) . "' type='audio/mp3'></audio>
            <script>jQuery('audio').mediaelementplayer({success: function(player, node) {}});</script>";
            /*
             * Added bonus: if the current user is at least an author, the monoblog file can be changed.
             * As such, the plupload form is shown below the player.
             */
            if (current_user_can('author') || current_user_can('editor') || current_user_can('administrator')) {
                $rs .= "
            
            <div id=\"uploader\">
                <a id=\"pickfiles\" href=\"javascript:;\" style='float:left;'>Select A Different Monoblog File&nbsp;&nbsp;&nbsp;</a>
                <div id=\"filelist\" style='float:left;'> </div>
                <a id=\"uploadfiles\" href=\"javascript:;\"  style='float:left;'></a>
            </div>";
                $rs .= monoblog::plupload_init($id);
            }
            return $rs;
        }
    }

    function plupload_init($id) {
        $rs = "
            <script type=\"text/javascript\">
                //<![CDATA[
                var uploader = new plupload.Uploader({
                    runtimes: 'html5',
                    flash_swf_url: 'js/plupload.flash.swf',
                    browse_button: 'pickfiles',
                    chunk_size : '1mb',
                    multi_selection:false,
                    url: '" . plugins_url('upload.php?mb=' . $id, __FILE__) . "',
                    preinit: attachCallbacks,
                    filters : [
                        {title : \"MP3 files\", extensions : \"mp3\"}
                    ]

                });

                uploader.init();

                uploader.bind('FilesAdded', function(up, files) {
                    for (var i in files) {
                        document.getElementById('filelist').innerHTML += '<div id=\"' + files[i].id + '\">' + files[i].name + ' (' + plupload.formatSize(files[i].size) + ') <b></b></div>';
                    }
                    document.getElementById('uploadfiles').innerHTML = '&nbsp;&nbsp;&nbsp;Upload';
                });

                uploader.bind('UploadProgress', function(up, file) {
                    document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + \"%</span>\";
                });

                uploader.bind('Error', function(up, args) { alert(args.code + ': ' + args.message); });

                document.getElementById('uploadfiles').onclick = function() {
                    uploader.start(); document.getElementById('uploadfiles').innerHTML = ''; document.getElementById('pickfiles').innerHTML = '';
                };
                //]]>
                function attachCallbacks(Uploader) {
                    Uploader.bind('FileUploaded', function(Up, File, Response) {
                        if( (Uploader.total.uploaded + 1) == Uploader.files.length)
                        {window.location.reload();}
                    });
                }
            </script>
            <div class='clear'></div>
            
            ";
        return $rs;
    }

}

?>
