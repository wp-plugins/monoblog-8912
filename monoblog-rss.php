<?php
include('../../../wp-config.php');
$dbh = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
$selected = mysql_select_db(DB_NAME,$dbh);

$site_info = mysql_query("SELECT option_name, option_value 
    FROM ".$table_prefix."options 
    WHERE 
    option_name='siteurl' 
    OR option_name='blogname' 
    OR option_name='admin_email' 
    OR option_name='blogdescription'
    OR option_name='monoblog_podcast_explicit'
    OR option_name='monoblog_podcast_category'
    OR option_name='monoblog_podcast_image'
    ");
$site_information = array();
while($option = mysql_fetch_assoc($site_info)){
    $site_information[$option['option_name']] = $option['option_value'];
}

$urlpath = dirname("http://" . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI']).'/monoblogs';

$podcasts = mysql_query("SELECT * FROM ".$table_prefix."monoblog ORDER BY id DESC") or die(mysql_error());


    echo "
    <rss xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\" xml:lang=\"en\" version=\"2.0\" encoding='UTF-8'>
        <channel>
            <title>".$site_information['blogname']." Audio Blogs</title>
            <link>".$site_information['blogurl']."</link>
            <description>
                ".$site_information['blogdescription']."
            </description>
            <generator>
                The Happy ".$site_information['blogname']." Podcast Generating Robot
            </generator>
            <lastBuildDate>".date('r')."</lastBuildDate>
            <language>en</language>
            <copyright>(c) ".$site_information['blogname']."</copyright>
            <itunes:image href=\"".$site_information['monoblog_podcast_image']."\"/>
            <image>
                <url>
                    ".$site_information['monoblog_podcast_image']."
                </url>
                <title>".$site_information['blogname']." Audio Blogs</title>
                <link>".$site_information['monoblog_podcast_image']."</link>
            </image>
            <itunes:summary>
                ".$site_information['blogdescription']."
            </itunes:summary>
            <itunes:subtitle>".$site_information['blogdescription']."</itunes:subtitle>
            <itunes:author>".$site_information['blogname']."</itunes:author>
            <itunes:owner>
                <itunes:name>".$site_information['blogname']."</itunes:name>
                <itunes:email>".$site_information['admin_email']."</itunes:email>
            </itunes:owner>
            <itunes:explicit>".$site_information['monoblog_podcast_explicit']."</itunes:explicit>
            <itunes:category text=\"".htmlentities($site_information['monoblog_podcast_category'])."\"></itunes:category>

    ";

    while($episode = mysql_fetch_assoc($podcasts)){
        $search = array(chr(145), chr(146), chr(147), chr(148), chr(151)); 
        $replace = array("'", "'", '"', '"', '-'); 
        
        $description = utf8_encode(htmlentities(iconv('UTF-8', 'ASCII//TRANSLIT', $episode['ep_desc'])));
        echo "<item>
                <title>".utf8_encode(htmlentities(iconv('UTF-8', 'ASCII//TRANSLIT', $episode['ep_title'])))."</title>
                <itunes:subtitle>".$description."</itunes:subtitle>
                <itunes:summary>
                    <![CDATA[
                    ".$description."
                        ]]>
                </itunes:summary>
                <description>
                    ".$description."
                </description>
                <link>
                $urlpath/".$episode['ep_file']."
                </link>
                <enclosure url=\"$urlpath/".$episode['ep_file']."\" length=\"".$episode['ep_size']."\"  
                 type='".$episode['ep_mime']."' />
                <guid>
                    $urlpath/".$episode['ep_file']."
                </guid>
                <itunes:duration>".$episode['ep_duration']."</itunes:duration>
                <author>".$site_information['blogname']."</author>
                <itunes:author>".$site_information['blogname']."</itunes:author>
                <itunes:explicit>".$site_information['monoblog_podcast_explicit']."</itunes:explicit>
                <pubDate>".date('r', $episode['ep_time'])."</pubDate>
            </item>
            ";
    }

    echo "</channel>
    </rss>";

?>
