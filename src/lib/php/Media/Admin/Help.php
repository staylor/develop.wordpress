<?php
namespace WP\Media\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addNew() {
		$this->addOverview(
			'<p>' . __( 'You can upload media files here without creating a post first. This allows you to upload files to use with posts and pages later and/or to get a web link for a particular file that you can share. There are three options for uploading files:' ) . '</p>' .
			'<ul>' .
				'<li>' . __( '<strong>Drag and drop</strong> your files into the area below. Multiple files are allowed.' ) . '</li>' .
				'<li>' . __( 'Clicking <strong>Select Files</strong> opens a navigation window showing you files in your operating system. Selecting <strong>Open</strong> after clicking on the file you want activates a progress bar on the uploader screen.' ) . '</li>' .
				'<li>' . __( 'Revert to the <strong>Browser Uploader</strong> by clicking the link below the drag and drop box.' ) . '</li>' .
			'</ul>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Media_Add_New_Screen">Documentation on Uploading Media Files</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addEdit() {
		$this->addOverview(
			'<p>' . __( 'This screen allows you to edit five fields for metadata in a file within the media library.' ) . '</p>' .
			'<p>' . __( 'For images only, you can click on Edit Image under the thumbnail to expand out an inline image editor with icons for cropping, rotating, or flipping the image as well as for undoing and redoing. The boxes on the right give you more options for scaling the image, for cropping it, and for cropping the thumbnail in a different way than you crop the original image. You can click on Help in those boxes to get more information.' ) . '</p>' .
			'<p>' . __( 'Note that you crop the image by clicking on it (the Crop icon is already selected) and dragging the cropping frame to select the desired part. Then click Save to retain the cropping.' ) . '</p>' .
			'<p>' . __( 'Remember to click Update Media to save metadata entered or changed.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Media_Add_New_Screen#Edit_Media">Documentation on Edit Media</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addUploadGrid() {
		$this->addOverview(
			'<p>' . __( 'All the files you&#8217;ve uploaded are listed in the Media Library, with the most recent uploads listed first.' ) . '</p>' .
			'<p>' . __( 'You can view your media in a simple visual grid or a list with columns. Switch between these views using the icons to the left above the media.' ) . '</p>' .
			'<p>' . __( 'To delete media items, click the Bulk Select button at the top of the screen. Select any items you wish to delete, then click the Delete Selected button. Clicking the Cancel Selection button takes you back to viewing your media.' ) . '</p>'
		);

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
		$this->addOverview(
			'<p>' . __( 'All the files you&#8217;ve uploaded are listed in the Media Library, with the most recent uploads listed first. You can use the Screen Options tab to customize the display of this screen.' ) . '</p>' .
			'<p>' . __( 'You can narrow the list by file type/status or by date using the dropdown menus above the media table.' ) . '</p>' .
			'<p>' . __( 'You can view your media in a simple visual grid or a list with columns. Switch between these views using the icons to the left above the media.' ) . '</p>'
		);

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