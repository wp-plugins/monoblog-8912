<?php

add_action('admin_init', 'admin_register_scripts');

function admin_register_scripts() {
    wp_enqueue_script("jquery");
    wp_register_script("image_change", plugins_url('image_change.js', __FILE__));
    wp_enqueue_script('image_change');
    wp_register_script("mediaelement-and-player", plugins_url('mediaelement/build/mediaelement-and-player.js', __FILE__));
    wp_enqueue_script('mediaelement-and-player');
    wp_register_style('mediaelement-style', plugins_url('mediaelement/build/mediaelementplayer.css', __FILE__));
    wp_enqueue_script('media-upload');
    wp_enqueue_style('mediaelement-style');
}

/*
 * 
 * Menu Creation
 * 
 */

add_action('admin_menu', 'monoblog_create_menu');

function monoblog_create_menu() {
    //create new top-level menu
    add_media_page('Monoblog', 'Monoblog', 'administrator', __FILE__, 'monoblog_admin');
    add_option("monoblog_podcast_bool", '0');
    add_option("monoblog_podcast_explicit", 'no');
    add_option("monoblog_podcast_image", '');
    add_option("monoblog_podcast_category", '');
}


/*
 * Meat and potatoes of the Admin interface
 */

function monoblog_admin() {
    global $wpdb;

    if (count($_POST) > 0) {
        if(array_key_exists('podcast_options', $_POST)){
            if(@$_POST['podcast'] == 'on'){
                update_option('monoblog_podcast_bool', '1');
            }else{
                update_option('monoblog_podcast_bool', '0');
            }
            update_option('monoblog_podcast_explicit', $_POST['explicit']);
            update_option('monoblog_podcast_category', $_POST['category']);
            update_option('monoblog_podcast_image', $_POST['itunes_image_select']);
        }else{
            foreach ($_POST as $id) {
                $wpdb->query("DELETE FROM " . $wpdb->prefix . "monoblog WHERE id=$id");
            }
        }
    }
    $podcast_gen = get_option('monoblog_podcast_bool');
    if($podcast_gen == 0){
        $checked1 = '';
    }else{
        $checked1 = 'checked';
    }
    $explicit = get_option('monoblog_podcast_explicit');
    if($explicit == 'yes'){
        $yes_selected = 'selected';
        $no_selected = '';
    }else{
        $yes_selected = '';
        $no_selected = 'selected';
    }
    
    $images = $wpdb->get_results("SELECT guid, post_name FROM $wpdb->posts WHERE guid LIKE '%jpg' OR guid LIKE '%png' OR guid LIKE '%gif' ORDER BY ID DESC", OBJECT);
    $itunes_image = get_option('monoblog_podcast_image');
    
    $itunes_category = get_option('monoblog_podcast_category');
    
    $categories = array('','Arts','Business','Comedy','Education','Games &amp; Hobbies','Government &amp; Organizations','Health','Kids &amp; Family','Music','News &amp; Politics','Religion &amp; Spirituality','Science &amp; Medicine','Sports &amp; Recreation','Technology','TV &amp; Film');
    
    $query = "SELECT ep_file, ep_time, ep_title, id, post_id FROM " . $wpdb->prefix . "monoblog ORDER BY id DESC";
    $monoblogs = $wpdb->get_results($query, OBJECT);
    echo "<div class='wrap'>
        <h2>Monoblog Management</h2>";
   
    echo "<h3>Monoblog Files</h3>
        <table class='widefat'>
        <thead>
            <tr>
                <th>Post Title</th>
                <th></th>
                <th>Submitted</th>  
                <th>Remove</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>Post Title</th>
                <th></th>
                <th>Submitted</th>       
                <th>Remove</th>
            </tr>
        </tfoot>
        <tbody>";
    foreach ($monoblogs as $monoblog) {
        echo "<tr>
            <td><a href='" . get_bloginfo('url') . "?p=$monoblog->post_id' >$monoblog->ep_title </a></td>
            <td>
                <audio id='$monoblog->id' controls  style='width:300px;' preload='none' >
                    <source src='" . plugins_url("monoblogs/$monoblog->ep_file", __FILE__) . "'>
                </audio>
            </td>
            <td>" . date('r', $monoblog->ep_time) . "</td>
            <td><form method='POST' action=''>
                <input type='hidden' name='$monoblog->id' value='$monoblog->id'>
                <input type='submit' value='Remove' class='button-secondary'></form></td>
        </tr>";
    }
    echo "</tbody>
        </table>
        <script>jQuery('audio').mediaelementplayer({success: function(player, node) {}});</script>
        </div>";
     echo "<h3>Podcast Options</h3>
    <form method='POST' action=''>
        <input type='hidden' name='podcast_options' value=''>
        <ul>
            <li><label for='podcast'>Generate Podcast?</label><input type='checkbox' name='podcast' $checked1 ></li>
            <li><label for='explicit'>Explicit Content?</label>
                <select name='explicit'>
                    <option value='yes' $yes_selected>Yes</option>
                    <option value='no' $no_selected>No</option>
                </select>
            </li>
            <li>
                <label for='category'>iTunes Category</label>
                <select name='category'>";
                    foreach($categories as $category){
                        echo "<option value='$category'";
                        if(htmlentities($itunes_category) == $category){
                            echo "selected";
                        }
                        echo ">$category</option>";
                    }
                echo "</select>
            </li>
            <li>
                <label for='itunes_image_select'>iTunes Podcast Image</label>
                <select id='itunes_image_select' name='itunes_image_select' onchange='ChangeImage();'>
                <option value=''></option>";
                    foreach($images as $image){
                        echo "<option value='$image->guid' ";
                        if($image->guid == $itunes_image){
                            echo "selected";
                        }
                        echo ">$image->post_name</option>";
                    }
                echo "</select><br>
                    <div id='itunes_image'>";
                    if(isset($itunes_image)){
                        echo "<img src='$itunes_image' width='200'>";
                    }
                echo "</div>
            </li>
        </ul>
        <input class='button-primary' type='submit' value='Submit'>
    </form>";
    if($podcast_gen == 1){
        echo "<p>Your Monoblogs can now be accessed in podcast form here <a href='".plugins_url("monoblog-rss.php", __FILE__)."'>".plugins_url("monoblog-rss.php", __FILE__)."</a></p>
            <p>If you wish to submit this podcast to the iTunes store, this is the RSS url you have to submit. <a href='http://support.apple.com/kb/HT1819' target='_blank'>Click here</a> for information on how to do that.</p>";
    }
}

?>
