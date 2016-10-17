<?php
namespace WP\Comment\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addEditComments() {
		$this->addOverview(
			'<p>' . __( 'You can manage comments made on your site similar to the way you manage posts and other content. This screen is customizable in the same ways as other management screens, and you can act on comments using the on-hover action links or the Bulk Actions.' ) . '</p>'
		);

		$this->screen->add_help_tab( [
			'id'		=> 'moderating-comments',
			'title'		=> __( 'Moderating Comments' ),
			'content'	=>
					'<p>' . __( 'A red bar on the left means the comment is waiting for you to moderate it.' ) . '</p>' .
					'<p>' . __( 'In the <strong>Author</strong> column, in addition to the author&#8217;s name, email address, and blog URL, the commenter&#8217;s IP address is shown. Clicking on this link will show you all the comments made from this IP address.' ) . '</p>' .
					'<p>' . __( 'In the <strong>Comment</strong> column, hovering over any comment gives you options to approve, reply (and approve), quick edit, edit, spam mark, or trash that comment.' ) . '</p>' .
					'<p>' . __( 'In the <strong>In Response To</strong> column, there are three elements. The text is the name of the post that inspired the comment, and links to the post editor for that entry. The View Post link leads to that post on your live site. The small bubble with the number in it shows the number of approved comments that post has received. If there are pending comments, a red notification circle with the number of pending comments is displayed. Clicking the notification circle will filter the comments screen to show only pending comments on that post.' ) . '</p>' .
					'<p>' . __( 'In the <strong>Submitted On</strong> column, the date and time the comment was left on your site appears. Clicking on the date/time link will take you to that comment on your live site.' ) . '</p>' .
					'<p>' . __( 'Many people take advantage of keyboard shortcuts to moderate their comments more quickly. Use the link to the side to learn more.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Administration_Screens#Comments">Documentation on Comments</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Comment_Spam">Documentation on Comment Spam</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Keyboard_Shortcuts">Documentation on Keyboard Shortcuts</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( [
			'heading_views'      => __( 'Filter comments list' ),
			'heading_pagination' => __( 'Comments list navigation' ),
			'heading_list'       => __( 'Comments list' ),
		] );
	}

	public function addEditComment() {
		$this->screen->add_help_tab( [
			'id'      => 'overview',
			'title'   => __('Overview'),
			'content' =>
				'<p>' . __( 'You can edit the information left in a comment if needed. This is often useful when you notice that a commenter has made a typographical error.' ) . '</p>' .
				'<p>' . __( 'You can also moderate the comment from this screen using the Status box, where you can also change the timestamp of the comment.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Administration_Screens#Comments">Documentation on Comments</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}