<?php

add_action('admin_init', 'admin_register_scripts');

function admin_register_scripts() {
    wp_enqueue_script("jquery");
    wp_register_script("mediaelement-and-player", plugins_url('mediaelement/build/mediaelement-and-player.js', __FILE__));
    wp_enqueue_script('mediaelement-and-player');
    wp_register_style('mediaelement-style', plugins_url('mediaelement/build/mediaelementplayer.css', __FILE__));
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
}


/*
 * Meat and potatoes of the Admin interface
 */

function monoblog_admin() {
    global $wpdb;

    if (count($_POST) > 0) {
        foreach ($_POST as $id) {
            $wpdb->query("DELETE FROM " . $wpdb->prefix . "monoblog WHERE id=$id");
        }
    }
    $query = "SELECT ep_file, ep_time, ep_title, id, post_id FROM " . $wpdb->prefix . "monoblog ORDER BY id DESC";
    $monoblogs = $wpdb->get_results($query, OBJECT);
    echo "<div class='wrap'>
        <h2>Monoblog Management</h2>";

    echo "<table class='widefat'>
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
}

?>
