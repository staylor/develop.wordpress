<?php
namespace WP\Term\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	private function getCategoryOverview() {
		$help = '<p>' . sprintf( __( 'You can use categories to define sections of your site and group related posts. The default category is &#8220;Uncategorized&#8221; until you change it in your <a href="%s">writing settings</a>.' ), 'options-writing.php' ) . '</p>';
		return $help . '<p>' . __( 'What&#8217;s the difference between categories and tags? Normally, tags are ad-hoc keywords that identify important information in your post (names, subjects, etc) that may or may not recur in other posts, while categories are pre-determined sections. If you think of your site like a book, the categories are like the Table of Contents and the tags are like the terms in the index.' ) . '</p>';
	}

	private function getLinkCategoryOverview() {
		$help = '<p>' . __( 'You can create groups of links by using Link Categories. Link Category names must be unique and Link Categories are separate from the categories you use for posts.' ) . '</p>';
		return $help . '<p>' . __( 'You can delete Link Categories in the Bulk Action pull-down, but that action does not delete the links within the category. Instead, it moves them to the default Link Category.' ) . '</p>';
	}

	private function getTermOverview() {
		$help = '<p>' . __( 'You can assign keywords to your posts using <strong>tags</strong>. Unlike categories, tags have no hierarchy, meaning there&#8217;s no relationship from one tag to another.' ) . '</p>';
		return $help . '<p>' . __( 'What&#8217;s the difference between categories and tags? Normally, tags are ad-hoc keywords that identify important information in your post (names, subjects, etc) that may or may not recur in other posts, while categories are pre-determined sections. If you think of your site like a book, the categories are like the Table of Contents and the tags are like the terms in the index.' ) . '</p>';
	}

	private function setSidebar( $taxnow ) {
		$help = '<p><strong>' . __( 'For more information:' ) . '</strong></p>';

		if ( 'category' == $taxnow ) {
			$help .= '<p>' . __( '<a href="https://codex.wordpress.org/Posts_Categories_Screen">Documentation on Categories</a>' ) . '</p>';
		} elseif ( 'link_category' == $taxnow ) {
			$help .= '<p>' . __( '<a href="https://codex.wordpress.org/Links_Link_Categories_Screen">Documentation on Link Categories</a>' ) . '</p>';
		} else {
			$help .= '<p>' . __( '<a href="https://codex.wordpress.org/Posts_Tags_Screen">Documentation on Tags</a>' ) . '</p>';
		}
		$help .= '<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>';

		$this->screen->set_help_sidebar( $help );
	}

	private function getAddText( $taxnow ) {
		if ( 'category' == $taxnow ) {
			$help = '<p>' . __( 'When adding a new category on this screen, you&#8217;ll fill in the following fields:' ) . '</p>';
		} else {
			$help = '<p>' . __( 'When adding a new tag on this screen, you&#8217;ll fill in the following fields:' ) . '</p>';
		}

		$items = [];
		$items[] = __( '<strong>Name</strong> &mdash; The name is how it appears on your site.' );

		if ( ! global_terms_enabled() ) {
			$items[] = __( '<strong>Slug</strong> &mdash; The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' );
		}

		if ( 'category' == $taxnow ) {
			$items[] = __( '<strong>Parent</strong> &mdash; Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have child categories for Bebop and Big Band. Totally optional. To create a subcategory, just choose another category from the Parent dropdown.' );
		}

		$items[] = __( '<strong>Description</strong> &mdash; The description is not prominent by default; however, some themes may display it.' );

		$help .= '<ul>';
		foreach ( $items as $item ) {
			$help .= '<li>' . $item . '</li>';
		}
		$help .= '</ul>';

		return $help . '<p>' . __( 'You can change the display of this screen using the Screen Options tab to set how many items are displayed per screen and to display/hide columns in the table.' ) . '</p>';
	}

	public function addEditTags( $taxnow ) {
		if ( 'category' == $taxnow ) {
			$help = $this->getCategoryOverview();
		} elseif ( 'link_category' == $taxnow ) {
			$help = $this->getLinkCategoryOverview();
		} else {
			$help = $this->getTermOverview();
		}

		$this->screen->add_help_tab( [
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => $help,
		] );

		if ( ! in_array( $taxnow, [ 'category', 'post_tag' ] ) ) {
			return;
		}

		$this->screen->add_help_tab( [
			'id'      => 'adding-terms',
			'title'   => 'category' == $taxnow ? __( 'Adding Categories' ) : __( 'Adding Tags' ),
			'content' => $this->getAddText( $taxnow ),
		] );

		$this->setSidebar( $taxnow );
	}
}
