<?php
namespace WP\Revision\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addMain() {
		/* Revisions Help Tab */

		$revisions_overview  = '<p>' . __( 'This screen is used for managing your content revisions.' ) . '</p>';
		$revisions_overview .= '<p>' . __( 'Revisions are saved copies of your post or page, which are periodically created as you update your content. The red text on the left shows the content that was removed. The green text on the right shows the content that was added.' ) . '</p>';
		$revisions_overview .= '<p>' . __( 'From this screen you can review, compare, and restore revisions:' ) . '</p>';
		$revisions_overview .= '<ul><li>' . __( 'To navigate between revisions, <strong>drag the slider handle left or right</strong> or <strong>use the Previous or Next buttons</strong>.' ) . '</li>';
		$revisions_overview .= '<li>' . __( 'Compare two different revisions by <strong>selecting the &#8220;Compare any two revisions&#8221; box</strong> to the side.' ) . '</li>';
		$revisions_overview .= '<li>' . __( 'To restore a revision, <strong>click Restore This Revision</strong>.' ) . '</li></ul>';

		$this->screen->add_help_tab( [
			'id'      => 'revisions-overview',
			'title'   => __( 'Overview' ),
			'content' => $revisions_overview
		] );

		$revisions_sidebar  = '<p><strong>' . __( 'For more information:' ) . '</strong></p>';
		$revisions_sidebar .= '<p>' . __( '<a href="https://codex.wordpress.org/Revision_Management">Revisions Management</a>' ) . '</p>';
		$revisions_sidebar .= '<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>';

		$this->screen->set_help_sidebar( $revisions_sidebar );
	}
}
