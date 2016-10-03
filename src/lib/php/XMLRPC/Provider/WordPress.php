<?php
namespace WP\XMLRPC\Provider;

use WP\XMLRPC\{Server,Utils as ServerUtils};

class WordPress implements ProviderInterface {
	use ServerUtils;
	use WordPress\Utils;
	use WordPress\Comment;
	use WordPress\Media;
	use WordPress\Multisite;
	use WordPress\Option;
	use WordPress\Page;
	use WordPress\Post;
	use WordPress\Revision;
	use WordPress\Taxonomy;
	use WordPress\Term;
	use WordPress\User;

	public function register( Server $server ): ProviderInterface
	{
		$server->addMethods( [
			'wp.getUsersBlogs'		=> [ $this, 'wp_getUsersBlogs' ],
			'wp.newPost'			=> [ $this, 'wp_newPost' ],
			'wp.editPost'			=> [ $this, 'wp_editPost' ],
			'wp.deletePost'			=> [ $this, 'wp_deletePost' ],
			'wp.getPost'			=> [ $this, 'wp_getPost' ],
			'wp.getPosts'			=> [ $this, 'wp_getPosts' ],
			'wp.newTerm'			=> [ $this, 'wp_newTerm' ],
			'wp.editTerm'			=> [ $this, 'wp_editTerm' ],
			'wp.deleteTerm'			=> [ $this, 'wp_deleteTerm' ],
			'wp.getTerm'			=> [ $this, 'wp_getTerm' ],
			'wp.getTerms'			=> [ $this, 'wp_getTerms' ],
			'wp.getTaxonomy'		=> [ $this, 'wp_getTaxonomy' ],
			'wp.getTaxonomies'		=> [ $this, 'wp_getTaxonomies' ],
			'wp.getUser'			=> [ $this, 'wp_getUser' ],
			'wp.getUsers'			=> [ $this, 'wp_getUsers' ],
			'wp.getProfile'			=> [ $this, 'wp_getProfile' ],
			'wp.editProfile'		=> [ $this, 'wp_editProfile' ],
			'wp.getPage'			=> [ $this, 'wp_getPage' ],
			'wp.getPages'			=> [ $this, 'wp_getPages' ],
			'wp.newPage'			=> [ $this, 'wp_newPage' ],
			'wp.deletePage'			=> [ $this, 'wp_deletePage' ],
			'wp.editPage'			=> [ $this, 'wp_editPage' ],
			'wp.getPageList'		=> [ $this, 'wp_getPageList' ],
			'wp.getAuthors'			=> [ $this, 'wp_getAuthors' ],
			'wp.getTags'			=> [ $this, 'wp_getTags' ],
			'wp.newCategory'		=> [ $this, 'wp_newCategory' ],
			'wp.deleteCategory'		=> [ $this, 'wp_deleteCategory' ],
			'wp.suggestCategories'	=> [ $this, 'wp_suggestCategories' ],
			'wp.deleteFile'			=> [ $this, 'wp_deletePost' ],		// Alias
			'wp.getCommentCount'	=> [ $this, 'wp_getCommentCount' ],
			'wp.getPostStatusList'	=> [ $this, 'wp_getPostStatusList' ],
			'wp.getPageStatusList'	=> [ $this, 'wp_getPageStatusList' ],
			'wp.getPageTemplates'	=> [ $this, 'wp_getPageTemplates' ],
			'wp.getOptions'			=> [ $this, 'wp_getOptions' ],
			'wp.setOptions'			=> [ $this, 'wp_setOptions' ],
			'wp.getComment'			=> [ $this, 'wp_getComment' ],
			'wp.getComments'		=> [ $this, 'wp_getComments' ],
			'wp.deleteComment'		=> [ $this, 'wp_deleteComment' ],
			'wp.editComment'		=> [ $this, 'wp_editComment' ],
			'wp.newComment'			=> [ $this, 'wp_newComment' ],
			'wp.getCommentStatusList' => [ $this, 'wp_getCommentStatusList' ],
			'wp.getMediaItem'		=> [ $this, 'wp_getMediaItem' ],
			'wp.getMediaLibrary'	=> [ $this, 'wp_getMediaLibrary' ],
			'wp.getPostFormats'     => [ $this, 'wp_getPostFormats' ],
			'wp.getPostType'		=> [ $this, 'wp_getPostType' ],
			'wp.getPostTypes'		=> [ $this, 'wp_getPostTypes' ],
			'wp.getRevisions'		=> [ $this, 'wp_getRevisions' ],
			'wp.restoreRevision'	=> [ $this, 'wp_restoreRevision' ],
		] );

		// initialize the options
		$this->get_blog_options();

		return $this;
	}
}
