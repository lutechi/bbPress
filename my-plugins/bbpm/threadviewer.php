<?php
/**
 * @package bbPM
 * @version 1.0.2
 * @author Nightgunner5
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License, Version 3 or higher
 */

global $bbpm, $the_pm, $bb_post;

$user_id = (int)bb_get_current_user_info('ID');

$bb_post = true; // Hax

$get = (int) $get;

$messagechain = $bbpm->get_thread( $get );
$members = $bbpm->get_thread_members( $get );
$bbpm->mark_read( $get );
/* printf( 'Thread ID: %1$s', $get ); */

$user_is_topic_starter = ($user_id == $messagechain[0]->from->ID);
$can_edit_others_tags = bb_current_user_can('edit_others_tags');

$voices = array();
foreach ( $messagechain as $pm ) {
	$voices[(int)$pm->from->ID]++;
}
add_filter( 'get_post_author_id', array( &$bbpm, 'post_author_id_filter' ) );

?>
<div class="infobox" role="main">
<div id="topic-info">
<span id="topic_labels"></span>
<h2 class="topictitle"><?php echo esc_html( $bbpm->get_thread_title( $get ) ); ?></h2>
<span id="topic_posts">(<?php printf( _n( 'One post', '%s posts', count( $messagechain ) ), bb_number_format_i18n( count( $messagechain ) ) ); ?>)</span>
<span id="topic_voices">(<?php printf( _n( 'One voice', '%s voices', count( $voices ) ), bb_number_format_i18n( count( $voices ) ) ); ?>)</span>

<ul class="topicmeta">
	<li><?php printf( __( 'Started %1$s ago by %2$s' ), bb_since( $messagechain[0]->date ), '<a href="' . get_user_profile_link( $messagechain[0]->from->ID ) . '">' . get_user_display_name( $messagechain[0]->from->ID ) . '</a>' ); ?></li>
<?php if ( 1 < count( $messagechain ) ) : ?>
	<li><?php printf( __( '<a href="%1$s">Latest reply</a> from %2$s' ), esc_attr( '#pm-' . $messagechain[count( $messagechain ) - 1]->ID ), '<a href="' . get_user_profile_link( $messagechain[count( $messagechain ) - 1]->from->ID ) . '">' . get_user_display_name( $messagechain[count( $messagechain ) - 1]->from->ID ) . '</a>' ); ?></li>
<?php endif; ?>
</ul>
</div>

<div id="topic-tags" class="resp-rem">
<p><?php _e( 'Members', 'bbpm' ); ?>:</p>

<ul>
<?php

foreach ( $members as $member ) {
	if ( isset( $voices[$member] ) )
		echo '<li><a href="' . get_user_profile_link( $member ) . '">' . get_user_display_name( $member ) . '</a>' . (($user_id != $member && ($user_is_topic_starter || $can_edit_others_tags))?' [<a href="' . $bbpm->thread_remove_member_url(get_user_display_name( $member ), $get) . '">x</a>] ':'') . '</li>';
	else
		echo '<li><em><a href="' . get_user_profile_link( $member ) . '">' . get_user_display_name( $member ) . '</a></em>' . (($user_id != $member && ($user_is_topic_starter || $can_edit_others_tags))?' [<a href="' . $bbpm->thread_remove_member_url(get_user_display_name( $member ), $get) . '">x</a>] ':'') . '</li>';
}

?>
</ul>

<?php if ( ($bbpm->settings['users_per_thread'] == 0 || $bbpm->settings['users_per_thread'] > count( $members )) && ($user_is_topic_starter || $can_edit_others_tags) ) { ?>
<form id="tag-form" action="<?php echo BB_PLUGIN_URL . basename( dirname( __FILE__ ) ) . '/pm.php'; ?>" method="post">
<p>
<input type="text" id="tag" name="tag"/>
<input type="hidden" id="pm_thread" name="pm_thread" value="<?php echo $get; ?>"/>
<?php bb_nonce_field( 'bbpm-add-member-' . $get ); ?>
<input id="tagformsub" type="submit" value="<?php _e( 'Add &raquo;' ); ?>"/>
</p>
<script type="text/javascript">//<![CDATA[
jQuery(function($){
	var autocompleteTimeout, ul = $('<ul/>').css({
		position: 'absolute',
		zIndex: 10000,
		backgroundColor: '#fff',
		fontSize: '1.2em',
		padding: 2,
		marginTop: -1,
		MozBorderRadius: 2,
		WebkitBorderRadius: 2,
		borderRadius: 2,
		border: '1px solid #ccc',
		borderTopWidth: '0'
	}).insertAfter('#tag').hide();
	$('#tag').attr('autocomplete', 'off').keyup(function(){
		// IE compat
		if(document.selection) {
			// The current selection
			var range = document.selection.createRange();
			// We'll use this as a 'dummy'
			var stored_range = range.duplicate();
			// Select all text
			stored_range.moveToElementText(this);
			// Now move 'dummy' end point to end point of original range
			stored_range.setEndPoint('EndToEnd', range);
			// Now we can calculate start and end points
			this.selectionStart = stored_range.text.length - range.text.length;
			this.selectionEnd = this.selectionStart + range.text.length;
		}

		try {
			clearTimeout(autocompleteTimeout);
		} catch (ex) {}

		if (!this.value.length) {
			ul.empty();
			ul.hide();
			return;
		}

		autocompleteTimeout = setTimeout(function(text, pos){
			$.post('<?php echo addslashes( bb_get_plugin_uri( bb_plugin_basename( __FILE__ ) ) ); ?>/pm.php', {
				search: text,
				pos: pos,
				thread: <?php echo $get; ?>,
				_wpnonce: '<?php echo bb_create_nonce( 'bbpm-user-search' ); ?>'
			}, function(data){
				ul.empty();
				if (data.length)
					ul.show();
				else
					ul.hide();
				$.each(data, function(i, name){
					if (name.length)
						$('<li/>').css({
							listStyle: 'none'
						}).text(name).click(function(){
							$('#tag').val($(this).text());
							ul.empty();
							ul.hide();
						}).appendTo(ul);
				});
			}, 'json');
		}, 750, this.value, this.selectionStart);
	}).blur(function(){
		setTimeout(function(){
			ul.empty();
			ul.hide();
		}, 500);
		try {
			clearTimeout(autocompleteTimeout);
		} catch (ex) {}
	});
});
//]]></script>
</form>
<?php } ?>
</div>

<div style="clear:both;"></div>
</div>

<ol id="thread">
<?php
foreach ( $messagechain as $i => $the_pm ) { ?>
<li id="pm-<?php echo $the_pm->ID; ?>"<?php alt_class( 'bbpm_thread' );

// if ( $the_pm->thread_depth )
// 	echo ' style="margin-left: ' . ( $the_pm->thread_depth * 1.25 ) . 'em"';

?>>

<div class="threadauthor resp-rem">
	<?php echo bb_get_avatar( $the_pm->from->ID, 48 ); ?>
	<p><strong><?php echo apply_filters( 'post_author', apply_filters( 'get_post_author', empty( $the_pm->from->display_name ) ? $the_pm->from->data->user_login : $the_pm->from->display_name, $the_pm->from->ID ) );?></strong><br />
		<small>
			<?php
				$title = get_user_title( $the_pm->from->ID );
				echo apply_filters( 'post_author_title_link', apply_filters( 'get_post_author_title_link', '<a href="' . get_user_profile_link( $the_pm->from->ID ) . '">' . $title . '</a>', 0 ), 0 );
			?>
		</small>
	</p>
</div>
<div class="threadpost">
	<div class="threadauthor-horiz resp-add">
		<div class="threadauthor-horiz-left"><?php echo bb_get_avatar( $the_pm->from->ID, 48 ); ?></div>
		<div class="threadauthor-horiz-center"><strong><?php echo apply_filters( 'post_author', apply_filters( 'get_post_author', empty( $the_pm->from->display_name ) ? $the_pm->from->data->user_login : $the_pm->from->display_name, $the_pm->from->ID ) );?></strong></div>
		<div class="threadauthor-horiz-right"><?php
			$title = get_user_title( $the_pm->from->ID );
			echo apply_filters( 'post_author_title_link', apply_filters( 'get_post_author_title_link', '<a href="' . get_user_profile_link( $the_pm->from->ID ) . '">' . $title . '</a>', 0 ), 0 );
		?></div>
	</div>
	<div class="edit_post" style="display:none;">
		<form method="post" action="<?php echo BB_PLUGIN_URL . basename( dirname( __FILE__ ) ) . '/edit.php'; ?>" style="padding-left:0px; padding-top:0px; padding-bottom:0px;">
			<?php
				$edit_text = apply_filters( 'edit_text', $the_pm->text );
				$line_count = substr_count( $edit_text, "\n" )+1;
				do_action( 'post_form_pre_post' );
			?>
			<textarea id="unedited_message" class="no-smilies" style="display:none;"><?php echo $edit_text; ?></textarea>
			<textarea name="message" class="no-smilies" cols="50" rows="<?php echo $bbpm->rowsForEditTextArea($line_count); ?>" id="message" tabindex="3" style="width:100%"><?php echo $edit_text; ?></textarea>
			<p class="submit">
				<input type="submit" class="pm-edit-cancel" value="<?php echo attribute_escape( __( 'Cancel', 'bbpm' ) ); ?>" tabindex="4" />
				<input type="submit" class="pm-edit-submit" value="<?php echo attribute_escape( __( 'Edit Message &raquo;', 'bbpm' ) ); ?>" tabindex="4" />
			</p>
			<p><?php _e('Allowed markup:'); ?> <code><?php allowed_markup(); ?></code>. <br /><?php _e('You can also put code in between backtick ( <code>`</code> ) characters.'); ?></p>
			<?php bb_nonce_field( 'bbpm-reply-' . $the_pm->ID ); ?>
			<input type="hidden" value="<?php echo $the_pm->ID; ?>" name="id" id="id" />
			<?php do_action( 'post_form_post_post' ); do_action( 'post_form' ); ?>
		</form>
	</div>
	<div class="post"><?php echo apply_filters( 'post_text', apply_filters( 'get_post_text', $the_pm->text ) ); ?></div>
	<div class="poststuff">
		<span title="<?php echo(bbpm_format_time($the_pm->date)); ?>"><?php printf( __( 'Sent %s ago', 'bbpm' ), bb_since( $the_pm->date ) ); ?></span>
		<a href="<?php echo $the_pm->read_link; ?>">#</a>
		<?php if ($user_id == $the_pm->from->ID || bb_current_user_can('edit_others_posts')) { ?> 
		    <a href="<?php echo $the_pm->reply_link; ?>" id="<?php echo $i; ?>" class="pm-edit"><?php _e( 'Edit', 'bbpm' ); ?></a><?php } 
		?>
	</div>
</div>
</li>
<?php
}
?>
</ol>
<script type="text/javascript">
var edit_submitted = false;
jQuery.fn.selectRange = function(start, end) {
    return this.each(function() {
        if (this.setSelectionRange) {
            this.focus();
            this.setSelectionRange(start, end);
        } else if (this.createTextRange) {
            var range = this.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        }
    });
};

jQuery(function($){
	$('.pm-reply').click(function(){
		$('#static-respond').hide('normal');
		$('#respond').hide('normal', function(){$(this).remove()});
		var pm = $(this).parents('li');
		$('<div id="respond"/>').appendTo(pm).hide();
		$.get($(this).attr('href'), {}, function(page){
			page = page.substr(page.indexOf('<div id="respond">') + 18);
			page = page.substr(0, page.indexOf('</form>') + 7);
			$('#respond').html(page).find('textarea').css({width: '99%'}).end().find('#reply').append(' ').append($('<a href="#"><small style="font-size:small"><?php echo addslashes( __( '[Cancel]', 'bbpm' ) ); ?></small></a>').click(function(){
				$('#respond').hide('normal', function(){
					$(this).remove();
					$('#static-respond').show('normal');
				});
				return false;
			})).end().show('fast');
			$('#message')[0].focus();
<?php if ( function_exists( 'bb_smilies_init' ) ) { // Compat with bbPress Smilies ?>
			bbField = undefined;
			bb_smilies_init();
			bbField.style.width = '99%';
<?php } ?>
		}, 'text');
		return false;
	});
	$('.pm-edit').click(function(){
		if (edit_submitted) return false;
<?php if ( $bbpm->settings['edit_textarea_autofocus'] ) { ?>
		$(this).parent().parent().find('.post').hide().end().find('.edit_post').show().find('#message').focus().selectRange(0, 0);
<?php } else { ?>
		$(this).parent().parent().find('.post').hide().end().find('.edit_post').show().find('#message');
<?php } ?>
		$(this).hide();
		return false;
	});
	$('.pm-edit-submit').click(function(){
		var post_element = $(this).parent().parent().parent().parent();
		var unedited_text = post_element.find('#unedited_message').val();
		var edited_text = post_element.find('#message').val();
		if ($.trim(edited_text) == $.trim(unedited_text)) {
			post_element.find('.pm-edit-cancel').click();
			return false;
		}

		if (edit_submitted) return false;
		edit_submitted = true;
	});
	$('.pm-edit-cancel').click(function(){
		if (edit_submitted) return false;
		var post_element = $(this).parent().parent().parent().parent();
		var unedited_text = post_element.find('.edit_post').hide().end().find('.post').show().end().find('.pm-edit').show().end().find('#unedited_message').val();
		post_element.find('#message').val(unedited_text);
		return false;
	});
});
</script>


<div id="static-respond">
<h2 id="reply"><?php _e( 'Reply', 'bbpm' ); ?></h2>
<form class="postform pm-form" method="post" action="<?php echo BB_PLUGIN_URL . basename( dirname( __FILE__ ) ) . '/pm.php'; ?>">
<fieldset>
<?php do_action( 'post_form_pre_post' ); ?>
<p>
	<label for="message"><?php _e( 'Message:', 'bbpm' ); ?><br/></label>
	<textarea name="message" cols="50" rows="8" id="message" tabindex="3"></textarea>
</p>
<p class="submit">
	<input type="submit" id="postformsub" name="Submit" value="<?php echo attribute_escape( __( 'Send Message &raquo;', 'bbpm' ) ); ?>" tabindex="4" />
</p>

<p><?php _e('Allowed markup:'); ?> <code><?php allowed_markup(); ?></code>. <br /><?php _e('You can also put code in between backtick ( <code>`</code> ) characters.'); ?></p>

<?php bb_nonce_field( 'bbpm-reply-' . $the_pm->ID ); ?>

<input type="hidden" value="<?php echo $the_pm->ID; ?>" name="reply_to" id="reply_to" />

<?php do_action( 'post_form_post_post' ); do_action( 'post_form' ); ?>
</fieldset>
</form>
</div>

