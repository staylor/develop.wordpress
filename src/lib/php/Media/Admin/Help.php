<?php
namespace WP\Media\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addUploadGrid() {
		$this->screen->add_help_tab( [
			'id'		=> 'overview',
			'title'		=> __( 'Overview' ),
			'content'	=>
				'<p>' . __( 'All the files you&#8217;ve uploaded are listed in the Media Library, with the most recent uploads listed first.' ) . '</p>' .
				'<p>' . __( 'You can view your media in a simple visual grid or a list with columns. Switch between these views using the icons to the left above the media.' ) . '</p>' .
				'<p>' . __( 'To delete media items, click the Bulk Select button at the top of the screen. Select any items you wish to delete, then click the Delete Selected button. Clicking the Cancel Selection button takes you back to viewing your media.' ) . '</p>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'attachment-details',
			'title'		=> __( 'Attachment Details' ),
			'content'	=>
				'<p>' . __( 'Clicking an item will display an Attachment Details dialog, which allows you to preview media and make quick edits. Any changes you make to the attachment details will be automatically saved.' ) . '</p>' .
				'<p>' . __( 'Use the arrow buttons at the top of the dialog, or the left and right arrow keys on your keyboard, to navigate between media items quickly.' ) . '</p>' .
				'<p>' . __( 'You can also delete individual items and access the extended edit screen from the details dialog.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Media_Library_Screen">Documentation on Media Library</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addUploadList() {
		$this->screen->add_help_tab( [
			'id'		=> 'overview',
			'title'		=> __( 'Overview' ),
			'content'	=>
				'<p>' . __( 'All the files you&#8217;ve uploaded are listed in the Media Library, with the most recent uploads listed first. You can use the Screen Options tab to customize the display of this screen.' ) . '</p>' .
				'<p>' . __( 'You can narrow the list by file type/status or by date using the dropdown menus above the media table.' ) . '</p>' .
				'<p>' . __( 'You can view your media in a simple visual grid or a list with columns. Switch between these views using the icons to the left above the media.' ) . '</p>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'actions-links',
			'title'		=> __( 'Available Actions' ),
			'content'	=>
				'<p>' . __( 'Hovering over a row reveals action links: Edit, Delete Permanently, and View. Clicking Edit or on the media file&#8217;s name displays a simple screen to edit that individual file&#8217;s metadata. Clicking Delete Permanently will delete the file from the media library (as well as from any posts to which it is currently attached). View will take you to the display page for that file.' ) . '</p>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'attaching-files',
			'title'		=> __( 'Attaching Files' ),
			'content'	=>
				'<p>' . __( 'If a media file has not been attached to any content, you will see that in the Uploaded To column, and can click on Attach to launch a small popup that will allow you to search for existing content and attach the file.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Media_Library_Screen">Documentation on Media Library</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( [
			'heading_views'      => __( 'Filter media items list' ),
			'heading_pagination' => __( 'Media items list navigation' ),
			'heading_list'       => __( 'Media items list' ),
		] );
	}
}