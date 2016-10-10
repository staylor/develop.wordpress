<?php
namespace WP\Post\Admin;

use WP\Admin\Screen;

class Help {
	protected $screen;

	public function __construct( Screen $screen ) {
		$this->screen = $screen;
	}

	public function addPost() {
		$this->screen->add_help_tab( [
			'id'		=> 'overview',
			'title'		=> __('Overview'),
			'content'	=>
				'<p>' . __('This screen provides access to all of your posts. You can customize the display of this screen to suit your workflow.') . '</p>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'screen-content',
			'title'		=> __('Screen Content'),
			'content'	=>
				'<p>' . __('You can customize the display of this screen&#8217;s contents in a number of ways:') . '</p>' .
				'<ul>' .
					'<li>' . __('You can hide/display columns based on your needs and decide how many posts to list per screen using the Screen Options tab.') . '</li>' .
					'<li>' . __( 'You can filter the list of posts by post status using the text links above the posts list to only show posts with that status. The default view is to show all posts.' ) . '</li>' .
					'<li>' . __('You can view posts in a simple title list or with an excerpt using the Screen Options tab.') . '</li>' .
					'<li>' . __('You can refine the list to show only posts in a specific category or from a specific month by using the dropdown menus above the posts list. Click the Filter button after making your selection. You also can refine the list by clicking on the post author, category or tag in the posts list.') . '</li>' .
				'</ul>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'action-links',
			'title'		=> __('Available Actions'),
			'content'	=>
				'<p>' . __('Hovering over a row in the posts list will display action links that allow you to manage your post. You can perform the following actions:') . '</p>' .
				'<ul>' .
					'<li>' . __('<strong>Edit</strong> takes you to the editing screen for that post. You can also reach that screen by clicking on the post title.') . '</li>' .
					'<li>' . __('<strong>Quick Edit</strong> provides inline access to the metadata of your post, allowing you to update post details without leaving this screen.') . '</li>' .
					'<li>' . __('<strong>Trash</strong> removes your post from this list and places it in the trash, from which you can permanently delete it.') . '</li>' .
					'<li>' . __('<strong>Preview</strong> will show you what your draft post will look like if you publish it. View will take you to your live site to view the post. Which link is available depends on your post&#8217;s status.') . '</li>' .
				'</ul>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'bulk-actions',
			'title'		=> __('Bulk Actions'),
			'content'	=>
				'<p>' . __('You can also edit or move multiple posts to the trash at once. Select the posts you want to act on using the checkboxes, then select the action you want to take from the Bulk Actions menu and click Apply.') . '</p>' .
						'<p>' . __('When using Bulk Edit, you can change the metadata (categories, author, etc.) for all selected posts at once. To remove a post from the grouping, just click the x next to its name in the Bulk Edit area that appears.') . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="https://codex.wordpress.org/Posts_Screen">Documentation on Managing Posts</a>') . '</p>' .
			'<p>' . __('<a href="https://wordpress.org/support/">Support Forums</a>') . '</p>'
		);
	}

	public function addPage() {
		$this->screen->add_help_tab( [
			'id'		=> 'overview',
			'title'		=> __('Overview'),
			'content'	=>
				'<p>' . __('Pages are similar to posts in that they have a title, body text, and associated metadata, but they are different in that they are not part of the chronological blog stream, kind of like permanent posts. Pages are not categorized or tagged, but can have a hierarchy. You can nest pages under other pages by making one the &#8220;Parent&#8221; of the other, creating a group of pages.') . '</p>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'managing-pages',
			'title'		=> __('Managing Pages'),
			'content'	=>
				'<p>' . __('Managing pages is very similar to managing posts, and the screens can be customized in the same way.') . '</p>' .
				'<p>' . __('You can also perform the same types of actions, including narrowing the list by using the filters, acting on a page using the action links that appear when you hover over a row, or using the Bulk Actions menu to edit the metadata for multiple pages at once.') . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="https://codex.wordpress.org/Pages_Screen">Documentation on Managing Pages</a>') . '</p>' .
			'<p>' . __('<a href="https://wordpress.org/support/">Support Forums</a>') . '</p>'
		);
	}
}
