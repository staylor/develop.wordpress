<?php
namespace WP\Post\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addPost() {
		$this->addOverview(
			'<p>' . __( 'This screen provides access to all of your posts. You can customize the display of this screen to suit your workflow.' ) . '</p>'
		);

		$this->screen->add_help_tab( [
			'id'		=> 'screen-content',
			'title'		=> __( 'Screen Content' ),
			'content'	=>
				'<p>' . __( 'You can customize the display of this screen&#8217;s contents in a number of ways:' ) . '</p>' .
				'<ul>' .
					'<li>' . __( 'You can hide/display columns based on your needs and decide how many posts to list per screen using the Screen Options tab.' ) . '</li>' .
					'<li>' . __( 'You can filter the list of posts by post status using the text links above the posts list to only show posts with that status. The default view is to show all posts.' ) . '</li>' .
					'<li>' . __( 'You can view posts in a simple title list or with an excerpt using the Screen Options tab.' ) . '</li>' .
					'<li>' . __( 'You can refine the list to show only posts in a specific category or from a specific month by using the dropdown menus above the posts list. Click the Filter button after making your selection. You also can refine the list by clicking on the post author, category or tag in the posts list.' ) . '</li>' .
				'</ul>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'action-links',
			'title'		=> __( 'Available Actions' ),
			'content'	=>
				'<p>' . __( 'Hovering over a row in the posts list will display action links that allow you to manage your post. You can perform the following actions:' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Edit</strong> takes you to the editing screen for that post. You can also reach that screen by clicking on the post title.' ) . '</li>' .
					'<li>' . __( '<strong>Quick Edit</strong> provides inline access to the metadata of your post, allowing you to update post details without leaving this screen.' ) . '</li>' .
					'<li>' . __( '<strong>Trash</strong> removes your post from this list and places it in the trash, from which you can permanently delete it.' ) . '</li>' .
					'<li>' . __( '<strong>Preview</strong> will show you what your draft post will look like if you publish it. View will take you to your live site to view the post. Which link is available depends on your post&#8217;s status.' ) . '</li>' .
				'</ul>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'bulk-actions',
			'title'		=> __( 'Bulk Actions' ),
			'content'	=>
				'<p>' . __( 'You can also edit or move multiple posts to the trash at once. Select the posts you want to act on using the checkboxes, then select the action you want to take from the Bulk Actions menu and click Apply.' ) . '</p>' .
						'<p>' . __( 'When using Bulk Edit, you can change the metadata (categories, author, etc.) for all selected posts at once. To remove a post from the grouping, just click the x next to its name in the Bulk Edit area that appears.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Posts_Screen">Documentation on Managing Posts</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addPage() {
		$this->addOverview(
			'<p>' . __( 'Pages are similar to posts in that they have a title, body text, and associated metadata, but they are different in that they are not part of the chronological blog stream, kind of like permanent posts. Pages are not categorized or tagged, but can have a hierarchy. You can nest pages under other pages by making one the &#8220;Parent&#8221; of the other, creating a group of pages.' ) . '</p>'
		);

		$this->screen->add_help_tab( [
			'id'		=> 'managing-pages',
			'title'		=> __( 'Managing Pages' ),
			'content'	=>
				'<p>' . __( 'Managing pages is very similar to managing posts, and the screens can be customized in the same way.' ) . '</p>' .
				'<p>' . __( 'You can also perform the same types of actions, including narrowing the list by using the filters, acting on a page using the action links that appear when you hover over a row, or using the Bulk Actions menu to edit the metadata for multiple pages at once.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Pages_Screen">Documentation on Managing Pages</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}


	public function addEditFormAdvanced( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( 'post' == $post_type ) {
			$customize_display = '<p>' . __( 'The title field and the big Post Editing Area are fixed in place, but you can reposition all the other boxes using drag and drop. You can also minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Excerpt, Send Trackbacks, Custom Fields, Discussion, Slug, Author) or to choose a 1- or 2-column layout for this screen.' ) . '</p>';

			$this->screen->add_help_tab( [
				'id'      => 'customize-display',
				'title'   => __( 'Customizing This Display' ),
				'content' => $customize_display,
			] );

			$title_and_editor  = '<p>' . __( '<strong>Title</strong> &mdash; Enter a title for your post. After you enter a title, you&#8217;ll see the permalink below, which you can edit.' ) . '</p>';
			$title_and_editor .= '<p>' . __( '<strong>Post editor</strong> &mdash; Enter the text for your post. There are two modes of editing: Visual and Text. Choose the mode by clicking on the appropriate tab.' ) . '</p>';
			$title_and_editor .= '<p>' . __( 'Visual mode gives you an editor that is similar to a word processor. Click the Toolbar Toggle button to get a second row of controls.' ) . '</p>';
			$title_and_editor .= '<p>' . __( 'The Text mode allows you to enter HTML along with your post text. Note that &lt;p&gt; and &lt;br&gt; tags are converted to line breaks when switching to the Text editor to make it less cluttered. When you type, a single line break can be used instead of typing &lt;br&gt;, and two line breaks instead of paragraph tags. The line breaks are converted back to tags automatically.' ) . '</p>';
			$title_and_editor .= '<p>' . __( 'You can insert media files by clicking the icons above the post editor and following the directions. You can align or edit images using the inline formatting toolbar available in Visual mode.' ) . '</p>';
			$title_and_editor .= '<p>' . __( 'You can enable distraction-free writing mode using the icon to the right. This feature is not available for old browsers or devices with small screens, and requires that the full-height editor be enabled in Screen Options.' ) . '</p>';
			$title_and_editor .= '<p>' . __( 'Keyboard users: When you&#8217;re working in the visual editor, you can use <kbd>Alt + F10</kbd> to access the toolbar.' ) . '</p>';

			$this->screen->add_help_tab( [
				'id'      => 'title-post-editor',
				'title'   => __( 'Title and Post Editor' ),
				'content' => $title_and_editor,
			] );

			$this->screen->set_help_sidebar(
					'<p>' . sprintf(__( 'You can also create posts with the <a href="%s">Press This bookmarklet</a>.' ), 'tools.php' ) . '</p>' .
					'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
					'<p>' . __( '<a href="https://codex.wordpress.org/Posts_Add_New_Screen">Documentation on Writing and Editing Posts</a>' ) . '</p>' .
					'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
			);
		} elseif ( 'page' == $post_type ) {
			$about_pages = '<p>' . __( 'Pages are similar to posts in that they have a title, body text, and associated metadata, but they are different in that they are not part of the chronological blog stream, kind of like permanent posts. Pages are not categorized or tagged, but can have a hierarchy. You can nest pages under other pages by making one the &#8220;Parent&#8221; of the other, creating a group of pages.' ) . '</p>' .
				'<p>' . __( 'Creating a Page is very similar to creating a Post, and the screens can be customized in the same way using drag and drop, the Screen Options tab, and expanding/collapsing boxes as you choose. This screen also has the distraction-free writing space, available in both the Visual and Text modes via the Fullscreen buttons. The Page editor mostly works the same as the Post editor, but there are some Page-specific features in the Page Attributes box.' ) . '</p>';

			$this->screen->add_help_tab( [
				'id'      => 'about-pages',
				'title'   => __( 'About Pages' ),
				'content' => $about_pages,
			] );

			$this->screen->set_help_sidebar(
					'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
					'<p>' . __( '<a href="https://codex.wordpress.org/Pages_Add_New_Screen">Documentation on Adding New Pages</a>' ) . '</p>' .
					'<p>' . __( '<a href="https://codex.wordpress.org/Pages_Screen#Editing_Individual_Pages">Documentation on Editing Pages</a>' ) . '</p>' .
					'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
			);
		} elseif ( 'attachment' == $post_type ) {
			$this->addOverview(
				'<p>' . __( 'This screen allows you to edit four fields for metadata in a file within the media library.' ) . '</p>' .
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

		if ( 'post' == $post_type || 'page' == $post_type ) {
			$inserting_media = '<p>' . __( 'You can upload and insert media (images, audio, documents, etc.) by clicking the Add Media button. You can select from the images and files already uploaded to the Media Library, or upload new media to add to your page or post. To create an image gallery, select the images to add and click the &#8220;Create a new gallery&#8221; button.' ) . '</p>';
			$inserting_media .= '<p>' . __( 'You can also embed media from many popular websites including Twitter, YouTube, Flickr and others by pasting the media URL on its own line into the content of your post/page. Please refer to the Codex to <a href="https://codex.wordpress.org/Embeds">learn more about embeds</a>.' ) . '</p>';

			$this->screen->add_help_tab( [
				'id'		=> 'inserting-media',
				'title'		=> __( 'Inserting Media' ),
				'content' 	=> $inserting_media,
			] );
		}

		if ( 'post' == $post_type ) {
			$publish_box = '<p>' . __( 'Several boxes on this screen contain settings for how your content will be published, including:' ) . '</p>';
			$publish_box .= '<ul><li>' .
				__( '<strong>Publish</strong> &mdash; You can set the terms of publishing your post in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a post or making it stay at the top of your blog indefinitely (sticky). The Password protected option allows you to set an arbitrary password for each post. The Private option hides the post from everyone except editors and administrators. Publish (immediately) allows you to set a future or past date and time, so you can schedule a post to be published in the future or backdate a post.' ) .
			'</li>';

			if ( current_theme_supports( 'post-formats' ) && post_type_supports( 'post', 'post-formats' ) ) {
				$publish_box .= '<li>' . __( '<strong>Format</strong> &mdash; Post Formats designate how your theme will display a specific post. For example, you could have a <em>standard</em> blog post with a title and paragraphs, or a short <em>aside</em> that omits the title and contains a short text blurb. Please refer to the Codex for <a href="https://codex.wordpress.org/Post_Formats#Supported_Formats">descriptions of each post format</a>. Your theme could enable all or some of 10 possible formats.' ) . '</li>';
			}

			if ( current_theme_supports( 'post-thumbnails' ) && post_type_supports( 'post', 'thumbnail' ) ) {
				/* translators: %s: Featured Image */
				$publish_box .= '<li>' . sprintf( __( '<strong>%s</strong> &mdash; This allows you to associate an image with your post without inserting it. This is usually useful only if your theme makes use of the image as a post thumbnail on the home page, a custom header, etc.' ), esc_html( $post_type_object->labels->featured_image ) ) . '</li>';
			}

			$publish_box .= '</ul>';

			$this->screen->add_help_tab( [
				'id'      => 'publish-box',
				'title'   => __( 'Publish Settings' ),
				'content' => $publish_box,
			] );

			$discussion_settings  = '<p>' . __( '<strong>Send Trackbacks</strong> &mdash; Trackbacks are a way to notify legacy blog systems that you&#8217;ve linked to them. Enter the URL(s) you want to send trackbacks. If you link to other WordPress sites they&#8217;ll be notified automatically using pingbacks, and this field is unnecessary.' ) . '</p>';
			$discussion_settings .= '<p>' . __( '<strong>Discussion</strong> &mdash; You can turn comments and pings on or off, and if there are comments on the post, you can see them here and moderate them.' ) . '</p>';

			$this->screen->add_help_tab( [
				'id'      => 'discussion-settings',
				'title'   => __( 'Discussion Settings' ),
				'content' => $discussion_settings,
			] );
		} elseif ( 'page' == $post_type ) {
			$page_attributes = '<p>' . __( '<strong>Parent</strong> &mdash; You can arrange your pages in hierarchies. For example, you could have an &#8220;About&#8221; page that has &#8220;Life Story&#8221; and &#8220;My Dog&#8221; pages under it. There are no limits to how many levels you can nest pages.' ) . '</p>' .
				'<p>' . __( '<strong>Template</strong> &mdash; Some themes have custom templates you can use for certain pages that might have additional features or custom layouts. If so, you&#8217;ll see them in this dropdown menu.' ) . '</p>' .
				'<p>' . __( '<strong>Order</strong> &mdash; Pages are usually ordered alphabetically, but you can choose your own order by entering a number (1 for first, etc.) in this field.' ) . '</p>';

			$this->screen->add_help_tab( [
				'id' => 'page-attributes',
				'title' => __( 'Page Attributes' ),
				'content' => $page_attributes,
			] );
		}
	}
}
