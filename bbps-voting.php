<?php
/*
Plugin Name: bbpress Voting
Plugin URI: https://www.wpdesk.pl/
Description: Vote
Author: WP Desk
Version: 1.0
*/

/*
bbps - vote functions
*/

//hook into the forum atributes meta box

add_action('bbp_forum_metabox' , 'bbps_extend_forum_attributes_mb');

/* the support forum checkbox will add resolved / not resolved status to all forums */
/* The premium forum will create a support forum that can only be viewed by that user and admin users */
function bbps_extend_forum_attributes_mb($forum_id){

	//get out the forum meta
	$support_forum = bbps_is_support_forum( $forum_id );
	if( $support_forum )
		$checked1 = "checked";
	else
		$checked1 = "";

	$voting_forum = bbps_is_voting_forum( $forum_id );
	if( $voting_forum )
		$checked2 = "checked";
	else
		$checked2 = "";

	?>
	<hr />

<!--
This is not tested enough for people to start using so for now we will only have support forums
<p>
		<strong> Premium Forum:</strong>
		<input type="checkbox" name="bbps-premium-forum" value="1"  echo $checked; />
		<br />
		<small>Click here for more information about creating a premium forum.</small>
	</p>
-->

	<p>
		<strong><?php _e( 'Support Forum:', 'bbps' ); ?></strong>
		<input type="checkbox" name="bbps-support-forum" value="1" <?php echo $checked1; ?>/>
		<br />
		<!-- <small>Click here To learn more about the support forum setting.</small> -->
	</p>
	<p>
		<strong><?php _e( 'Voting Forum:', 'bbps' ); ?></strong>
		<input type="checkbox" name="bbps-voting-forum" value="1" <?php echo $checked2; ?>/>
		<br />
		<!-- <small>Click here To learn more about the support forum setting.</small> -->
	</p>

<?php
}

//hook into the forum save hook.

add_action( 'bbp_forum_attributes_metabox_save' , 'bbps_forum_attributes_mb_save' );

function bbps_forum_attributes_mb_save($forum_id){

//get out the forum meta
$premium_forum = get_post_meta( $forum_id, '_bbps_is_premium' );
$support_forum = get_post_meta( $forum_id, '_bbps_is_support');
$voting_forum = get_post_meta( $forum_id, '_bbps_is_voting');

	//if we have a value then save it
	if ( !empty( $_POST['bbps-premium-forum'] ) )
		update_post_meta($forum_id, '_bbps_is_premium', $_POST['bbps-premium-forum']);

	//the forum used to be premium now its not
	if ( !empty($premium_forum) && empty( $_POST['bbps-premium-forum'] ) )
		update_post_meta($forum_id, '_bbps_is_premium', 0);

	//support options
	if ( !empty( $_POST['bbps-support-forum'] ) )
		update_post_meta($forum_id, '_bbps_is_support', $_POST['bbps-support-forum']);

	//the forum used to be premium now its not
	if ( !empty($support_forum) && empty( $_POST['bbps-support-forum'] ) )
		update_post_meta($forum_id, '_bbps_is_support', 0);

	//voting options
	if ( !empty( $_POST['bbps-voting-forum'] ) )
		update_post_meta($forum_id, '_bbps_is_voting', $_POST['bbps-voting-forum']);

	//the forum used to be premium now its not
	if ( !empty($voting_forum) && empty( $_POST['bbps-voting-forum'] ) )
		update_post_meta($forum_id, '_bbps_is_voting', 0);


	return $forum_id;

}

function bbps_is_support_forum( $forum_id ){

	$support_forum = get_post_meta( $forum_id, '_bbps_is_support', true );
	if ($support_forum == 1)
		return true;
	else
		return false;
}
function bbps_is_voting_forum( $forum_id ){

	$voting_forum = get_post_meta( $forum_id, '_bbps_is_voting', true );
	if ($voting_forum == 1)
		return true;
	else
		return false;
}

//add_action('bbp_template_before_topics_loop', 'dtbaker_vote_bbp_template_before_topics_loop');
function dtbaker_vote_bbp_template_before_topics_loop(){
    // a tab to display resolved or unresilved voted items within this forum.
    $forum_id = bbp_get_forum_id();
    if(bbps_is_voting_forum($forum_id)){
        ?>
        <a href="<?php echo add_query_arg(array('show_resolved'=>0), bbp_get_forum_permalink($forum_id));?>">Pending Feature Requests</a> |
        <a href="<?php echo add_query_arg(array('show_resolved'=>1), bbp_get_forum_permalink($forum_id));?>">Resolved Requests</a>
        <?php
    }
}
add_filter('bbp_topic_pagination' , 'dtbaker_vote_bbp_topic_pagination' , 10 , 1);
function dtbaker_vote_bbp_topic_pagination($options){
	if (bbps_is_voting_forum(bbp_get_forum_id())){
        if(isset($_REQUEST['show_resolved']) && $_REQUEST['show_resolved']){
            $options['add_args']=array('show_resolved'=>1);
        }
    }
    return $options;
}

function bbps_voting_is_admin(){

	global $current_user;
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;

	$topic_author_id = bbp_get_topic_author_id();
	$can_edit = "";
	//check the users permission this is easy
	if( current_user_can('administrator') || current_user_can('bbp_moderator') ){
		$can_edit = true;
	}
	return $can_edit;
}


add_action('bbp_template_before_single_topic', 'bbps_add_voting_forum_features');
function bbps_add_voting_forum_features(){
	//only display all this stuff if the support forum option has been selected.
	if (bbps_is_voting_forum(bbp_get_forum_id())){
        $topic_id = bbp_get_topic_id();
        $forum_id = bbp_get_forum_id();
        $user_id = get_current_user_id();

        if ( (isset($_GET['action']) && isset($_GET['topic_id']) && $_GET['action'] == 'bbps_vote_for_topic')  )
            bbps_vote_topic();

        if ( (isset($_GET['action']) && isset($_GET['topic_id']) && $_GET['action'] == 'bbps_unvote_for_topic')  )
            bbps_unvote_topic();

        $votes = bbps_get_topic_votes($topic_id);
        ?>
        <div class="bbps-vote-tools">
	        <?php if ( bbps_voting_is_admin() ):
		        if( isset($_POST['bbps_topic_feature_accepted']) ) {
			        update_post_meta($topic_id, '_bbps_topic_feature_accepted', $_POST['bbps_topic_feature_accepted']);
			        bbps_update_vote_count($topic_id);
		        }
		        $feature_accepted = get_post_meta( $topic_id, '_bbps_topic_feature_accepted', true );
			?>

				<div id="bbps_voting_forum_options" class="bbps-voting-admin">
					<form id="bbps-topic-vote-feature" name="bbps_support_feature" action="" method="post">
						<input type="hidden" value="bbps_feature_accepted" name="bbps_action">

						<div>
							<label for="bbps_topic_feature_accepted">Feature Accepted?</label>

							<select name="bbps_topic_feature_accepted" id="bbps_topic_feature_accepted">
								<option value="0">no</option>
								<option value="1" <?php echo $feature_accepted ? ' selected' : ''; ?>>yes accepted</option>
							</select>

							<input class="small" type="submit" value="Update" name="bbps_support_feature_accepted_btn" />
						</div>
					</form>
				</div>
			<?php endif;
		        $feature_accepted = get_post_meta( $topic_id, '_bbps_topic_feature_accepted', true );
			?>
		        <div id="bbps_voting_forum_options" class="bbps-voting-status">
					<?php if ( $feature_accepted ) { ?>
					    <div class="info-box info"><?php _e( 'This feature request has been <strong>accepted</strong>! Get notified when it\'s ready!', 'wpdesk' ); ?> <?php bbp_topic_subscription_link(); ?></div>
					<?php } else { ?>
						<div class="info-box">
							<p><?php _e( 'This feature request has not yet been accepted. Keep voting and be sure to subscribe for updates!', 'wpdesk' ); ?></p>

							<div id="bbps_voting_forum_options" class="bbps-voting">
								<div class="bbps-votes-bagde">
									<strong><?php _e( 'Votes:', 'wpdesk' ); ?> <?php echo count( $votes ); ?></strong>

									<?php if ( is_user_logged_in() ) {
										if ( in_array( $user_id, $votes) ) {
									    	$vote_uri = add_query_arg( array( 'action' => 'bbps_unvote_for_topic', 'topic_id' => $topic_id ) );
									    ?>

									    &mdash; <?php _e( 'Vote Successful. Thanks!', 'wpdesk' ); ?> (<a href="<?php echo $vote_uri;?>"><?php _e( 'undo vote', 'wpdesk' ); ?></a>)
									<?php } else {
									    $vote_uri = add_query_arg( array( 'action' => 'bbps_vote_for_topic', 'topic_id' => $topic_id ) );
									?>
									    &nbsp;&nbsp;&nbsp;<a href="<?php echo $vote_uri;?>" class="btn btn-alt small"><?php _e( 'Vote for this!', 'wpdesk' ); ?></a>
									    <?php }
									} else {
									    echo '(please login to vote)';
									} ?>
								</div>
						   </div>
						</div>
					<?php } ?>
		        </div>
        </div> <!-- bbps-vote-tools -->
    <?php
	}
}

function bbps_get_topic_votes($topic_id){
	$votes = trim(get_post_meta( $topic_id, '_bbps_topic_user_votes', true ));
    if(strlen($votes)){
        $votes = explode(',',$votes);
    }else{
        $votes = array();
    }
	//to do not hard code these if we let the users add their own satus
	return $votes;
}


// adds a class and status to the front of the topic title
function bbps_modify_vote_title($title, $topic_id = 0){
    $topic_id = bbp_get_topic_id( $topic_id );
    $forum_id = bbp_get_forum_id();
    if(bbps_is_voting_forum($forum_id)){
        $votes = bbps_get_topic_votes($topic_id);
	    if(isset($GLOBALS['bbps_feature_request_params']['type']) && $GLOBALS['bbps_feature_request_params']['type'] == 'popular'){
		    //  hack to get ids of displayed popular posts.
		    if(!isset($GLOBALS['bbps_popular_ids']))$GLOBALS['bbps_popular_ids']=array();
		    $GLOBALS['bbps_popular_ids'][] = $topic_id;
	    }
        if ( count( $votes ) ) {
            echo '<span class="bbps-badge">' . __( 'Votes:', 'wpdesk') . ' ' . count( $votes ) . '</span> ';
        }
	    if ( get_post_meta( $topic_id, '_bbps_topic_feature_accepted', true ) ) {
            // accepted feature, move it to the top.
            echo '<span class="bbps-badge info">' . __( 'Accepted!', 'wpdesk' ) . '</span> ';
        }
    }

}
add_action('bbp_theme_before_topic_title', 'bbps_modify_vote_title');

define( '_BBPS_FEATURE_ACCEPTED_VOTE_COUNT', 10000 );

function bbps_vote_topic(){
    if(is_user_logged_in()){
        $user_id = get_current_user_id();
        if($user_id){
            $topic_id = bbp_get_topic_id();
            $forum_id = bbp_get_forum_id();
            if(bbps_is_voting_forum($forum_id)){
                $votes = bbps_get_topic_votes($topic_id);
                if(!in_array($user_id, $votes)){
                    $votes[]=$user_id;
                    update_post_meta($topic_id, '_bbps_topic_user_votes', implode(',',$votes));
	                bbps_update_vote_count($topic_id);
                }
            }
        }
    }
}
function bbps_update_vote_count($topic_id){
	$votes = bbps_get_topic_votes($topic_id);
    $vote_count = count($votes);
    if(get_post_meta( $topic_id, '_bbps_topic_feature_accepted', true )){
        // accepted feature, move it to the top.
        $vote_count = count($votes) + _BBPS_FEATURE_ACCEPTED_VOTE_COUNT;
    }
    update_post_meta($topic_id, '_bbps_topic_user_votes_count', $vote_count);
}

function bbps_unvote_topic(){
	if(is_user_logged_in()){
        $user_id = get_current_user_id();
        if($user_id){
            $topic_id = bbp_get_topic_id();
            $forum_id = bbp_get_forum_id();
            if(bbps_is_voting_forum($forum_id)){
                $votes = bbps_get_topic_votes($topic_id);
                $key = array_search($user_id, $votes);
                if($key !== false){
                    unset($votes[$key]);
                    update_post_meta($topic_id, '_bbps_topic_user_votes', implode(',',$votes));
                    update_post_meta($topic_id, '_bbps_topic_user_votes_count', count($votes));
                }
            }
        }
    }
}

function dtbaker_filter_topics_vote_custom_order($clauses) {
    global $wp_query;
    // check for order by custom_order

    //if($_SERVER['REMOTE_ADDR'] == '124.191.165.183'){
    //print_r($wp_query);
        //echo '<pre>';
        if(isset($_REQUEST['dtbaker_debug'])){
            echo '<pre>';
            print_r($clauses);
        }
        if(false && preg_match('#([a-zA-Z_0-9]*postmeta)\.meta_key = \'_bbps_topic_user_votes_count\'#',$clauses['where'],$matches)){
            //print_r($clauses);
            //print_r($matches);
            // change the inner join to a left outer join,
            // and change the where so it is applied to the join, not the results of the query
            // ON (all_5_posts.ID = all_5_postmeta.post_id)
            $clauses['where'] = preg_replace('#\n#',' ',$clauses['where']);
            $join_matches = preg_split("#\n#",$clauses['join']);
                $clauses['join'] = '';
                /*if($_SERVER['REMOTE_ADDR'] == '124.191.165.183'){
                    print_r($join_matches);
                }*/
                foreach($join_matches as $join_match_id => $join_match){
                    if(strpos($join_match,$matches[1].'.post_id') !== false){
                        $join_matches[$join_match_id] = str_replace('INNER JOIN','LEFT OUTER JOIN',$join_matches[$join_match_id]);
                        $clauses['where'] = str_replace($matches[0],'1',$clauses['where']);
                        $join_matches[$join_match_id] .= ' AND '.$matches[0].' ';
                    }
                    $clauses['join'] .= $join_matches[$join_match_id].' ';
                }
                $clauses['where'] = str_replace('1 OR ','',$clauses['where']);

        }
	if(isset($_REQUEST['dtbaker_debug'])){
		print_r($clauses);
		echo '</pre>';
	}
	/*if($_SERVER['REMOTE_ADDR'] == '124.191.165.183'){
		print_r($clauses);
		echo '</pre>';
	}*/

   /* }else{
        //if ($wp_query->get('meta_key') == '_bbps_topic_user_votes_count' && $wp_query->get('orderby') == 'meta_value_num')
        if(preg_match('#([a-zA-Z_0-9]*postmeta)\.meta_key = \'_bbps_topic_user_votes_count\'#',$clauses['where'],$matches)){
            // change the inner join to a left outer join,
            // and change the where so it is applied to the join, not the results of the query
            // ON (all_5_posts.ID = all_5_postmeta.post_id)
            $clauses['join'] = preg_replace('#INNER JOIN#', 'LEFT OUTER JOIN', $clauses['join']).$clauses['where'];
            //print_r($matches);
            //$clauses['where'] = str_replace($matches[0], $matches[0] .' OR '.$matches[1].'.meta_key IS NULL', $clauses['where']); //.$clauses['where'];
            $clauses['where'] = '';
        }
    }*/
    return $clauses;
}
//add_filter('get_meta_sql', 'dtbaker_filter_topics_vote_custom_order', 10, 1);

/*function dtbaker_filter_topics_vote_custom_order_by($orderby) {

    $forum_id = bbp_get_forum_id();
    echo 'Forum: '.$forum_id;
    if($forum_id && bbps_is_voting_forum($forum_id)){
        $orderby .= '';
    }
    return $orderby;
}
add_filter( 'posts_orderby', 'dtbaker_filter_topics_vote_custom_order_by', 10, 1 );*/

function bbps_filter_bbp_after_has_topics_parse_args( $args ) {
    $forum_id = bbp_get_forum_id();

    if ( $forum_id && bbps_is_voting_forum( $forum_id ) ) {

        $args['meta_query'] = array();
        if ( isset( $_REQUEST['show_resolved'] ) && $_REQUEST[ 'show_resolved' ] ) {
            $args['meta_query'][] = array(
                'key' => '_bbps_topic_status',
                'value' => 2,
                'compare' => '='
            );
        } else {
	        $args['posts_per_page'] = 99;
	        $args['meta_key'] = '_bbps_topic_user_votes_count';
            $args['orderby'] = 'meta_value_num date';
            $args['order'] = 'DESC';
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_bbps_topic_status',
                    'compare' => 'NOT EXISTS',
                    'value' => '2',
                ),
                array(
                    'key' => '_bbps_topic_status',
                    'value' => 2,
                    'compare' => '!='
                ),
				array(
				    'key' => '_bbps_topic_user_votes_count',
				    'compare' => 'NOT EXISTS',
				    'value' => '0',
				),
            );
        }
    }

   return $args;
}
add_filter( 'bbp_after_has_topics_parse_args', 'bbps_filter_bbp_after_has_topics_parse_args', 10, 1 );


/* shortcode added by dtbaker */

add_shortcode('bbps-feature-requests','dtbaker_bbps_feature_requests');
function dtbaker_bbps_feature_requests($params){
    $result = '';
	remove_action('bbp_template_before_topics_loop', 'dtbaker_vote_bbp_template_before_topics_loop');
	remove_filter('bbp_after_has_topics_parse_args','bbps_filter_bbp_after_has_topics_parse_args',10,1);
	remove_filter('get_meta_sql', 'dtbaker_filter_topics_vote_custom_order', 10, 1);

    // filter the bbPress query args that are run when [bbp-topic-index] is executed.
	$GLOBALS['bbps_feature_request_params'] = $params;
    add_filter('bbp_after_has_topics_parse_args','dtbaker_bbps_feature_requests_parse_args',3,1);
    // adjust the generated 'where' SQL to perform a table comparison
    add_filter('get_meta_sql','dtbaker_filter_topics_vote_custom_order',3,6);
    add_filter('bbp_topic_pagination','dtbaker_bbps_feature_requests_bbp_topic_pagination',3,1);
    add_filter('bbp_get_forum_pagination_count','dtbaker_bbps_feature_requests_bbp_get_topic_pagination_count',3,1);
    add_filter('bbp_is_single_forum','dtbaker_bbps_feature_requests_bbp_is_single_forum',3,1);
    // run the built in bbpress shortcode which does everything nicely
    $result .= do_shortcode('[bbp-topic-index]');
    // undo our nasty hacks from above.
    remove_filter('bbp_after_has_topics_parse_args','dtbaker_bbps_feature_requests_parse_args',3,1);
    remove_filter('get_meta_sql','dtbaker_filter_topics_vote_custom_order',3,6);
    remove_filter('bbp_topic_pagination','dtbaker_bbps_feature_requests_bbp_topic_pagination',3,1);
    remove_filter('bbp_get_forum_pagination_count','dtbaker_bbps_feature_requests_bbp_get_topic_pagination_count',3,1);
    remove_filter('bbp_is_single_forum','dtbaker_bbps_feature_requests_bbp_is_single_forum',3,1);
    // (hopefully) output the list of unread posts to logged in users
    return $result;
}
function dtbaker_bbps_feature_requests_bbp_is_single_forum($str){
	return true;
}
function dtbaker_bbps_feature_requests_bbp_get_topic_pagination_count($str){
	return ' &nbsp; ';
}
function dtbaker_bbps_feature_requests_bbp_topic_pagination($args){
	$args['total'] = 1;
	return $args;
}
function dtbaker_bbps_my_meta_query( $clauses, $wp_query ) {
  global $wpdb;
  if ( $wp_query->get( 'dtbaker_bbps_custom_where' ) == 123 ) {
    $clauses['join'] .= "
      LEFT JOIN {$wpdb->postmeta} m_status1 ON ({$wpdb->posts}.ID = m_status1.post_id AND m_status1.meta_key = '_bbps_topic_status')
      LEFT JOIN {$wpdb->postmeta} m_status2 ON ({$wpdb->posts}.ID = m_status2.post_id AND m_status2.meta_key = '_bbps_topic_feature_accepted')
      LEFT JOIN {$wpdb->postmeta} m_status3 ON ({$wpdb->posts}.ID = m_status3.post_id AND m_status3.meta_key = '_bbps_topic_feature_funding_paid')

    ";
    $clauses['where'] .= "\n AND ( m_status1.post_id IS NULL OR CAST(m_status1.meta_value AS CHAR) = '1') " ;//OR (m_status2.meta_key = '_bbps_topic_status' AND CAST(m_status2.meta_value AS CHAR) != '2') ) )";
    $clauses['where'] .= "\n AND ( m_status2.post_id IS NULL OR CAST(m_status2.meta_value AS CHAR) != '1') " ;
    $clauses['where'] .= "\n AND ( m_status3.post_id IS NULL OR CAST(m_status3.meta_value AS CHAR) = '0') " ;
	  if(isset($GLOBALS['bbps_popular_ids']) && count($GLOBALS['bbps_popular_ids'])){
		  $clauses['where'] .= "\n AND ( {$wpdb->posts}.ID NOT IN ( " .implode(",",$GLOBALS['bbps_popular_ids'])." ) ) ";
	  }

  }
	//if(isset($_REQUEST['dtbaker_debug']))print_r($clauses);
  return $clauses;
}
add_filter( 'posts_clauses', 'dtbaker_bbps_my_meta_query', 10, 2 );


// filter the bbPress query args that are run when [bbp-topic-index] is executed.
function dtbaker_bbps_feature_requests_parse_args($args){
    if(isset($GLOBALS['bbps_feature_request_params']) && isset($GLOBALS['bbps_feature_request_params']['post_parent'])){
	    $args['post_parent'] = $GLOBALS['bbps_feature_request_params']['post_parent'];
	    $bbp = bbpress();
		$bbp->current_forum_id = $args['post_parent'];
    }
    if(isset($GLOBALS['bbps_feature_request_params']['limit'])){
	    $args['posts_per_page'] = $GLOBALS['bbps_feature_request_params']['limit'];
    }
    if(isset($GLOBALS['bbps_feature_request_params']['type']) && $GLOBALS['bbps_feature_request_params']['type'] == 'resolved'){
		$args['meta_query'] = array();
        $args['meta_query'][] = array(
            'key' => '_bbps_topic_status',
            'value' => 2,
            'compare' => '='
        );
    }else if(isset($GLOBALS['bbps_feature_request_params']['type']) && $GLOBALS['bbps_feature_request_params']['type'] == 'new'){
		$args['dtbaker_bbps_custom_where'] = 123;

    }else {
		$args['meta_query'] = array();
	    // copied from bbps_filter_bbp_after_has_topics_parse_args abpo
	    $args['orderby']        = 'meta_value_num';
	    $args['meta_key']       = '_bbps_topic_user_votes_count';
	    $args['order']          = 'DESC';
	    $args['meta_query']     = array(
		    'relation' => 'OR',
		    array(
			    'key'     => '_bbps_topic_status',
			    'compare' => 'NOT EXISTS',
			    'value'   => '2',
		    ),
		    array(
			    'key'     => '_bbps_topic_status',
			    'value'   => 2,
			    'compare' => '!='
		    )
	    );
    }
    return $args;
}

add_action( 'bbp_new_topic_post_extras', 'wpdesk_bpbbpst_save_support_type', 10, 1 );
/**
 * Add Meta Key Votes (FIX for sorting)
 *
 */
function wpdesk_bpbbpst_save_support_type( $topic_id = 0 ) {
    $forum_id = bbp_get_forum_id();

    if ( $forum_id && bbps_is_voting_forum( $forum_id ) ) {
		update_post_meta( $topic_id, '_bbps_topic_user_votes_count', 0 );
	}
}
