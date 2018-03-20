<?php
/**
 * Customised calendar function
 *
 *
 * @param bool $initial Optional, default is true. Use initial calendar names.
 * @param bool $echo Optional, default is true. Set to false for return.
 * @param int $cat Optional, category ids given as array.
 * @param bool $caption Optional, default is false.
 * @param int $nav Optional, default is 0. 1 -> display both. 2 -> display only future. 
 * @param bool $daybefore Optional, default is false.
 * @return string|null String when retrieving, null when displaying.
 */
function get_calendar_cus($initial = true, $echo = true, $cats = array(), $caption = false, $nav = 0, $daybefore = false) {
    global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;
    $m = 0;
    $monthnum = 0; // monbth!!
    $year = 0; // year!
    $cache = array();
    $key = md5( $m . $monthnum . $year );
    if ( $cache = wp_cache_get( 'get_calendar', 'calendar' ) ) {
        if ( is_array($cache) && isset( $cache[ $key ] ) ) {
            if ( $echo ) {
                echo apply_filters( 'get_calendar',  $cache[$key] );
                return;
            } else {
                return apply_filters( 'get_calendar',  $cache[$key] );
            }
        }
    }

    if ( !is_array($cache) )
        $cache = array();

    // cat info
    $cat_id = '';
    $count = 0;
    foreach ($cats as $key => $value) {
        $cat_id .= $value;
        $count++;
        if($count != count($cats)) $cat_id .= ',';
    }

    // Quick check. If we have no posts at all, abort!
    if ( !$posts ) {
        if(!empty($cats)){
            $sql = "SELECT 1 as test 
                FROM $wpdb->posts 
                  INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) 
                  INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) 
                WHERE post_type = 'post' 
                AND post_status = 'publish' 
                AND $wpdb->term_taxonomy.taxonomy = 'category' 
                AND ( $wpdb->term_taxonomy.term_id IN (" . $cat_id . ")
                    OR $wpdb->term_taxonomy.parent IN (" . $cat_id . "))
               LIMIT 1";
           }else{
            $sql = "SELECT 1 as test 
                FROM $wpdb->posts 
                WHERE post_type = 'post' 
                AND post_status = 'publish' 
               LIMIT 1";
           }
        $gotsome = $wpdb->get_var($sql);
        if ( !$gotsome ) {
            $cache[ $key ] = '';
            wp_cache_set( 'get_calendar', $cache, 'calendar' );
            return;
        }
    }

    if ( isset($_GET['w']) )
        $w = ''.intval($_GET['w']);

    // week_begins = 0 stands for Sunday
    $week_begins = intval(get_option('start_of_week'));

    // Let's figure out when we are
    if ( !empty($monthnum) && !empty($year) ) {
        $thismonth = ''.zeroise(intval($monthnum), 2);
        $thisyear = ''.intval($year);
    } elseif ( !empty($w) ) {
        // We need to get the month from MySQL
        $thisyear = ''.intval(substr($m, 0, 4));
        $d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
        $thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')");
    } elseif ( !empty($m) ) {
        $thisyear = ''.intval(substr($m, 0, 4));
        if ( strlen($m) < 6 )
                $thismonth = '01';
        else
                $thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2);
    } else {
        $thisyear = gmdate('Y', current_time('timestamp'));
        $thismonth = gmdate('m', current_time('timestamp'));
    }

    /* Add 130718 */
    if( isset($_GET['cy']) && isset($_GET['cm']) ){
        $thisyear = ($_GET['cy'] != '')? $_GET['cy'] : $thisyear;
        $thismonth = ($_GET['cm'] != '')? $_GET['cm'] : $thismonth;
    }


    $unixmonth = mktime(0, 0 , 0, $thismonth, 1, $thisyear);
    $last_day = date('t', $unixmonth);

    // Get the next and previous month and year with at least one post
    if($nav != 0){
        if(!empty($cats)){
            if($nav == 2){
                $today = getdate();
                $curenntYear = $today['year'];
                $curenntMonth = $today['mon'];
                if($thismonth > $curenntMonth){
                    $previous = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
                        FROM $wpdb->posts
                          INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) 
                          INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) 
                        WHERE post_date < '$thisyear-$thismonth-01'
                        AND post_type = 'post' AND post_status = 'publish'
                        AND $wpdb->term_taxonomy.taxonomy = 'category' 
                        AND ( $wpdb->term_taxonomy.term_id IN (" . $cat_id . ")
                            OR $wpdb->term_taxonomy.parent IN (" . $cat_id . "))
                            ORDER BY post_date DESC
                            LIMIT 1");
                }else{

                }
            }
            $next = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
                FROM $wpdb->posts
                  INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) 
                  INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) 
                WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59'
                AND post_type = 'post' AND post_status = 'publish'
                AND $wpdb->term_taxonomy.taxonomy = 'category' 
                AND ( $wpdb->term_taxonomy.term_id IN (" . $cat_id . ")
                    OR $wpdb->term_taxonomy.parent IN (" . $cat_id . "))
                    ORDER BY post_date ASC
                    LIMIT 1");

        }else{
            $today = getdate();
            $curenntYear = $today['year'];
            $curenntMonth = $today['mon'];
            if($thismonth > $curenntMonth){
                $previous = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
                    FROM $wpdb->posts
                    WHERE post_date < '$thisyear-$thismonth-01'
                    AND post_type = 'post' AND post_status = 'publish'
                        ORDER BY post_date DESC
                        LIMIT 1");
            }else{

            }

            $next = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
                FROM $wpdb->posts
                WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59'
                AND post_type = 'post' AND post_status = 'publish'
                    ORDER BY post_date ASC
                    LIMIT 1");
        }
    }

    $calendar_output = '';

    if($nav != 0){

        $calendar_output = '<div id="calHead">';

        if ( isset($previous) ) {
            //$calendar_output .= "\n\t\t".'<td colspan="3" id="prev"><a href="' . get_month_link($previous->year, $previous->month) . '" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($previous->month), date('Y', mktime(0, 0 , 0, $previous->month, 1, $previous->year)))) . '">&laquo; ' . $wp_locale->get_month_abbrev($wp_locale->get_month($previous->month)) . '</a></td>';
            $uri = $_SERVER['REQUEST_URI'];

            if(strstr($uri, '?cy')){
                $uri = preg_replace('/\?cy=+\d{1,4}/', '', $uri);
                $uri = preg_replace('/&cm=+\d{1,2}/', '', $uri);
            }else{
                $uri = preg_replace('/&cy=+\d{1,4}/', '', $uri);
                $uri = preg_replace('/&cm=+\d{1,2}/', '', $uri);
            }
            $uri = $uri . "?cy=" . $previous->year . "&cm=" . $previous->month;
            //echo $uri;
        

            $calendar_output .= "\n\t\t".'<div class="past"><a href="' . $uri . '" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($previous->month), date('Y', mktime(0, 0 , 0, $previous->month, 1, $previous->year)))) . '">&lt;&lt;</a></div>';
//            $calendar_output .= "\n\t\t".'<td colspan="3" id="prev"><a href="' . $uri . '" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($previous->month), date('Y', mktime(0, 0 , 0, $previous->month, 1, $previous->year)))) . '">&laquo; ' . $wp_locale->get_month_abbrev($wp_locale->get_month($previous->month)) . '</a></td>';

        } else {
            $calendar_output .= "\n\t\t".'<div class="past">&nbsp;</div>';
        }

        $calendar_output .= '<div class="month">2013年7月</div>';

        if ( $next ) {
            $uri = $_SERVER['REQUEST_URI'];
            if(strstr($uri, '?cy')){
                $uri = preg_replace('/\?cy=+\d{1,4}/', '', $uri);
                $uri = preg_replace('/&cm=+\d{1,2}/', '', $uri);
            }else{
                $uri = preg_replace('/&cy=+\d{1,4}/', '', $uri);
                $uri = preg_replace('/&cm=+\d{1,2}/', '', $uri);
            }
            $uri = $uri . "?cy=" . $next->year . "&cm=" . $next->month;

//            $calendar_output .= "\n\t\t".'<td colspan="3" id="next"><a href="' . get_month_link($next->year, $next->month) . '" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($next->month), date('Y', mktime(0, 0 , 0, $next->month, 1, $next->year))) ) . '">' . $wp_locale->get_month_abbrev($wp_locale->get_month($next->month)) . ' &raquo;</a></td>';
            $calendar_output .= "\n\t\t".'<div class="future"><a href="' . $uri . '" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($next->month), date('Y', mktime(0, 0 , 0, $next->month, 1, $next->year))) ) . '">&gt;&gt;</a></div>';
        } else {
            $calendar_output .= "\n\t\t".'<div class="future">&nbsp;</div>';
        }

        $calendar_output .= '</div>';
    
    }else{

    }



    // table head
    /* translators: Calendar caption: 1: month name, 2: 4-digit year */
    $calendar_caption = _x('%1$s %2$s', 'calendar caption');

    if($caption){
        $calendar_output .= '<table id="wp-calendar">
        <caption>' . sprintf($calendar_caption, $wp_locale->get_month($thismonth), date('Y', $unixmonth)) . '</caption>
        <thead>
        <tr>';
    }else{
        $calendar_output .= '<table id="wp-calendar">
        <thead>
        <tr>';
    }


    $myweek = array();

    for ( $wdcount=0; $wdcount<=6; $wdcount++ ) {
        $myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);
    }

    foreach ( $myweek as $wd ) {
        $day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
        $wd = esc_attr($wd);
        $calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
    }

    $calendar_output .= '
    </tr>
    </thead>

    <tbody>
    <tr>';



    // Get days with posts
    if(!empty($cats)){
        $sql = "SELECT DISTINCT DAYOFMONTH(post_date)
            FROM $wpdb->posts 
              INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) 
              INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) 
            WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00'
            AND post_type = 'post' AND post_status = 'publish'
            AND $wpdb->term_taxonomy.taxonomy = 'category' 
            AND ( $wpdb->term_taxonomy.term_id IN (" . $cat_id . ")
                OR $wpdb->term_taxonomy.parent IN (" . $cat_id . "))
            AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59'";
    }else{
        $sql = "SELECT DISTINCT DAYOFMONTH(post_date)
            FROM $wpdb->posts 
            WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00'
            AND post_type = 'post' AND post_status = 'publish'
            AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59'";
    }

    $dayswithposts = $wpdb->get_results($sql, ARRAY_N);

    if ( $dayswithposts ) {
        foreach ( (array) $dayswithposts as $daywith ) {
            $daywithpost[] = $daywith[0];
        }
    } else {
        $daywithpost = array();
    }

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'camino') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false)
        $ak_title_separator = "\n";
    else
        $ak_title_separator = ', ';

    $ak_titles_for_day = array();
    $ak_post_titles = $wpdb->get_results("SELECT ID, post_title, DAYOFMONTH(post_date) as dom "
        ."FROM $wpdb->posts "
        ."WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00' "
        ."AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59' "
        ."AND post_type = 'post' AND post_status = 'publish'"
    );
    if ( $ak_post_titles ) {
        foreach ( (array) $ak_post_titles as $ak_post_title ) {

                $post_title = esc_attr( apply_filters( 'the_title', $ak_post_title->post_title, $ak_post_title->ID ) );

                if ( empty($ak_titles_for_day['day_'.$ak_post_title->dom]) )
                    $ak_titles_for_day['day_'.$ak_post_title->dom] = '';
                if ( empty($ak_titles_for_day["$ak_post_title->dom"]) ) // first one
                    $ak_titles_for_day["$ak_post_title->dom"] = $post_title;
                else
                    $ak_titles_for_day["$ak_post_title->dom"] .= $ak_title_separator . $post_title;
        }
    }

    // See how much we should pad in the beginning
    $pad = calendar_week_mod(date('w', $unixmonth)-$week_begins);
    if ( 0 != $pad )
        $calendar_output .= "\n\t\t".'<td colspan="'. esc_attr($pad) .'" class="pad">&nbsp;</td>';

    $daysinmonth = intval(date('t', $unixmonth));
    for ( $day = 1; $day <= $daysinmonth; ++$day ) {
        if ( isset($newrow) && $newrow )
            $calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
        $newrow = false;

        if ( in_array($day, $daywithpost) ){
        // Today
            if ( $day == gmdate('j', current_time('timestamp')) && $thismonth == gmdate('m', current_time('timestamp')) && $thisyear == gmdate('Y', current_time('timestamp')) ):
                $calendar_output .= '<td id="today">';
                $link = home_url() . "/event/" . $thisyear . "/" . $thismonth . "/" . $day;//get_day_link( $thisyear, $thismonth, $day ) 

                $calendar_output .= '<a href="' . $link . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
            else:
                if ( $day < gmdate('j', current_time('timestamp')) && $thismonth == gmdate('m', current_time('timestamp')) && $thisyear == gmdate('Y', current_time('timestamp')) ):
                    if(!$daybefore){
                        // No link
                        $calendar_output .= '<td>';
                        $calendar_output .= $day;
                    }else{
                        $calendar_output .= '<td class="link">';
                        $link = home_url() . "/event/" . $thisyear . "/" . $thismonth . "/" . $day;//get_day_link( $thisyear, $thismonth, $day ) 

                        $calendar_output .= '<a href="' . $link . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
                    }

                else:
                // Day after today
                    $calendar_output .= '<td class="link">';
                    $link = home_url() . "/event/" . $thisyear . "/" . $thismonth . "/" . $day;//get_day_link( $thisyear, $thismonth, $day ) 

                    $calendar_output .= '<a href="' . $link . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";

                endif;
            endif;
        }else{
            if ( $day == gmdate('j', current_time('timestamp')) && $thismonth == gmdate('m', current_time('timestamp')) && $thisyear == gmdate('Y', current_time('timestamp')) ):
                $calendar_output .= '<td id="today">';
                $calendar_output .= $day;
            else:
                $calendar_output .= '<td>';
                $calendar_output .= $day;
            endif;
        } 

   
        $calendar_output .= '</td>';

        if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) )
            $newrow = true;
    }

    $pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins);
    if ( $pad != 0 && $pad != 7 )
        $calendar_output .= "\n\t\t".'<td class="pad" colspan="'. esc_attr($pad) .'">&nbsp;</td>';

    $calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";

    $cache[ $key ] = $calendar_output;
    wp_cache_set( 'get_calendar', $cache, 'calendar' );

    if ( $echo )
        echo apply_filters( 'get_calendar',  $calendar_output );
    else
        return apply_filters( 'get_calendar',  $calendar_output );

}
