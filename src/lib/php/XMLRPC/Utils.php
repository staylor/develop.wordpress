<?php
namespace WP\XMLRPC;

use WP\IXR\{Date,Error};
use function WP\getApp;

trait Utils {
	public $error;

	/**
	 * Flags that the user authentication has failed in this instance of wp_xmlrpc_server.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $auth_failed = false;

	/**
	 * Check user's credentials. Deprecated.
	 *
	 * @since 1.5.0
	 * @deprecated 2.8.0 Use wp_xmlrpc_server::login()
	 * @see wp_xmlrpc_server::login()
	 *
	 * @param string $username User's username.
	 * @param string $password User's password.
	 * @return bool Whether authentication passed.
	 */
	public function login_pass_ok( $username, $password ) {
		return (bool) $this->login( $username, $password );
	}

	/**
	 * Log user in.
	 *
	 * @since 2.8.0
	 *
	 * @param string $username User's username.
	 * @param string $password User's password.
	 * @return \WP_User|bool \WP_User object if authentication passed, false otherwise
	 */
	public function login( $username, $password ) {
		/*
		 * Respect old get_option() filters left for back-compat when the 'enable_xmlrpc'
		 * option was deprecated in 3.5.0. Use the 'xmlrpc_enabled' hook instead.
		 */
		$option_enabled = apply_filters( 'pre_option_enable_xmlrpc', false );
		if ( false === $option_enabled ) {
			$option_enabled = apply_filters( 'option_enable_xmlrpc', true );
		}

		/**
		 * Filters whether XML-RPC methods requiring authentication are enabled.
		 *
		 * Contrary to the way it's named, this filter does not control whether XML-RPC is *fully*
		 * enabled, rather, it only controls whether XML-RPC methods requiring authentication - such
		 * as for publishing purposes - are enabled.
		 *
		 * Further, the filter does not control whether pingbacks or other custom endpoints that don't
		 * require authentication are enabled. This behavior is expected, and due to how parity was matched
		 * with the `enable_xmlrpc` UI option the filter replaced when it was introduced in 3.5.
		 *
		 * To disable XML-RPC methods that require authentication, use:
		 *
		 *     add_filter( 'xmlrpc_enabled', '__return_false' );
		 *
		 * For more granular control over all XML-RPC methods and requests, see the {@see 'xmlrpc_methods'}
		 * and {@see 'xmlrpc_element_limit'} hooks.
		 *
		 * @since 3.5.0
		 *
		 * @param bool $enabled Whether XML-RPC is enabled. Default true.
		 */
		$enabled = apply_filters( 'xmlrpc_enabled', $option_enabled );

		if ( ! $enabled ) {
			$this->error = new Error( 405, sprintf( __( 'XML-RPC services are disabled on this site.' ) ) );
			return false;
		}

		if ( $this->auth_failed ) {
			$user = new \WP_Error( 'login_prevented' );
		} else {
			$user = wp_authenticate( $username, $password );
		}

		if ( is_wp_error( $user ) ) {
			$this->error = new Error( 403, __( 'Incorrect username or password.' ) );

			// Flag that authentication has failed once on this wp_xmlrpc_server instance
			$this->auth_failed = true;

			/**
			 * Filters the XML-RPC user login error message.
			 *
			 * @since 3.5.0
			 *
			 * @param string  $error The XML-RPC error message.
			 * @param WP_User $user  WP_User object.
			 */
			$this->error = apply_filters( 'xmlrpc_login_error', $this->error, $user );
			return false;
		}

		wp_set_current_user( $user->ID );
		return $user;
	}

	/**
	 * Escape string or array of strings for database.
	 *
	 * @since 1.5.2
	 *
	 * @param string|array $data Escape single string or array of strings.
	 * @return string|void Returns with string is passed, alters by-reference
	 *                     when array is passed.
	 */
	public function escape( &$data ) {
		if ( ! is_array( $data ) ) {
			return wp_slash( $data );
		}

		foreach ( $data as &$v ) {
			if ( is_array( $v ) ) {
				$this->escape( $v );
			} elseif ( ! is_object( $v ) ) {
				$v = wp_slash( $v );
			}
		}
	}

	/**
	 * Helper method for filtering out elements from an array.
	 *
	 * @since 3.4.0
	 *
	 * @param int $count Number to compare to one.
	 */
	private function _is_greater_than_one( $count ) {
		return $count > 1;
	}

	/**
	 * Encapsulate the logic for sticking a post
	 * and determining if the user has permission to do so
	 *
	 * @since 4.3.0
	 * @access private
	 *
	 * @param array $post_data
	 * @param bool  $update
	 * @return void|Error
	 */
	private function _toggle_sticky( $post_data, $update = false ) {
		$post_type = get_post_type_object( $post_data['post_type'] );

		// Private and password-protected posts cannot be stickied.
		if ( 'private' === $post_data['post_status'] || ! empty( $post_data['post_password'] ) ) {
			// Error if the client tried to stick the post, otherwise, silently unstick.
			if ( ! empty( $post_data['sticky'] ) ) {
				return new Error( 401, __( 'Sorry, you cannot stick a private post.' ) );
			}

			if ( $update ) {
				unstick_post( $post_data['ID'] );
			}
		} elseif ( isset( $post_data['sticky'] ) )  {
			if ( ! current_user_can( $post_type->cap->edit_others_posts ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to stick this post.' ) );
			}

			$sticky = wp_validate_boolean( $post_data['sticky'] );
			if ( $sticky ) {
				stick_post( $post_data['ID'] );
			} else {
				unstick_post( $post_data['ID'] );
			}
		}
	}

	/**
	 * Helper method for wp_newPost() and wp_editPost(), containing shared logic.
	 *
	 * @since 3.4.0
	 * @access protected
	 *
	 * @see wp_insert_post()
	 *
	 * @param WP_User         $user           The post author if post_author isn't set in $content_struct.
	 * @param array|Error $content_struct Post data to insert.
	 * @return Error|string
	 */
	protected function _insert_post( $user, $content_struct ) {
		$defaults = [
			'post_status' => 'draft',
			'post_type' => 'post',
			'post_author' => 0,
			'post_password' => '',
			'post_excerpt' => '',
			'post_content' => '',
			'post_title' => ''
		];

		$post_data = wp_parse_args( $content_struct, $defaults );
		$post_type = get_post_type_object( $post_data['post_type'] );
		if ( ! $post_type ) {
			return new Error( 403, __( 'Invalid post type.' ) );
		}

		$update = ! empty( $post_data['ID'] );

		if ( $update ) {
			if ( ! get_post( $post_data['ID'] ) ) {
				return new Error( 401, __( 'Invalid post ID.' ) );
			}

			if ( ! current_user_can( 'edit_post', $post_data['ID'] ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
			}

			if ( $post_data['post_type'] != get_post_type( $post_data['ID'] ) ) {
				return new Error( 401, __( 'The post type may not be changed.' ) );
			}
		} elseif ( ! current_user_can( $post_type->cap->create_posts ) || ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to post on this site.' ) );
		}

		switch ( $post_data['post_status'] ) {
		case 'draft':
		case 'pending':
			break;
		case 'private':
			if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to create private posts in this post type.' ) );
			}
			break;

		case 'publish':
		case 'future':
			if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to publish posts in this post type.' ) );
			}
			break;

		default:
			if ( ! get_post_status_object( $post_data['post_status'] ) ) {
				$post_data['post_status'] = 'draft';
			}
			break;
		}

		if ( ! empty( $post_data['post_password'] ) && ! current_user_can( $post_type->cap->publish_posts ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to create password protected posts in this post type.' ) );
		}

		$post_data['post_author'] = absint( $post_data['post_author'] );
		if ( ! empty( $post_data['post_author'] ) && $post_data['post_author'] != $user->ID ) {
			if ( ! current_user_can( $post_type->cap->edit_others_posts ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to create posts as this user.' ) );
			}
			$author = get_userdata( $post_data['post_author'] );

			if ( ! $author ) {
				return new Error( 404, __( 'Invalid author ID.' ) );
			}
		} else {
			$post_data['post_author'] = $user->ID;
		}

		if ( isset( $post_data['comment_status'] ) && ! in_array( $post_data['comment_status'], [ 'open', 'closed' ] ) ) {
			unset( $post_data['comment_status'] );
		}

		if ( isset( $post_data['ping_status'] ) && ! in_array( $post_data['ping_status'], [ 'open', 'closed' ] ) ) {
			unset( $post_data['ping_status'] );
		}

		// Do some timestamp voodoo.
		if ( ! empty( $post_data['post_date_gmt'] ) ) {
			// We know this is supposed to be GMT, so we're going to slap that Z on there by force.
			$dateCreated = rtrim( $post_data['post_date_gmt']->getIso(), 'Z' ) . 'Z';
		} elseif ( ! empty( $post_data['post_date'] ) ) {
			$dateCreated = $post_data['post_date']->getIso();
		}

		// Default to not flagging the post date to be edited unless it's intentional.
		$post_data['edit_date'] = false;

		if ( ! empty( $dateCreated ) ) {
			$post_data['post_date'] = get_date_from_gmt( iso8601_to_datetime( $dateCreated ) );
			$post_data['post_date_gmt'] = iso8601_to_datetime( $dateCreated, 'GMT' );

			// Flag the post date to be edited.
			$post_data['edit_date'] = true;
		}

		if ( ! isset( $post_data['ID'] ) ) {
			$post_data['ID'] = get_default_post_to_edit( $post_data['post_type'], true )->ID;
		}
		$post_ID = $post_data['ID'];

		if ( 'post' === $post_data['post_type'] ) {
			$error = $this->_toggle_sticky( $post_data, $update );
			if ( $error ) {
				return $error;
			}
		}

		if ( isset( $post_data['post_thumbnail'] ) ) {
			// empty value deletes, non-empty value adds/updates.
			if ( ! $post_data['post_thumbnail'] ) {
				delete_post_thumbnail( $post_ID );
			} elseif ( ! get_post( absint( $post_data['post_thumbnail'] ) ) ) {
				return new Error( 404, __( 'Invalid attachment ID.' ) );
			}
			set_post_thumbnail( $post_ID, $post_data['post_thumbnail'] );
			unset( $content_struct['post_thumbnail'] );
		}

		if ( isset( $post_data['custom_fields'] ) ) {
			$this->set_custom_fields( $post_ID, $post_data['custom_fields'] );
		}

		if ( isset( $post_data['terms'] ) || isset( $post_data['terms_names'] ) ) {
			$post_type_taxonomies = get_object_taxonomies( $post_data['post_type'], 'objects' );

			// Accumulate term IDs from terms and terms_names.
			$terms = [];

			// First validate the terms specified by ID.
			if ( isset( $post_data['terms'] ) && is_array( $post_data['terms'] ) ) {
				$taxonomies = array_keys( $post_data['terms'] );

				// Validating term ids.
				foreach ( $taxonomies as $taxonomy ) {
					if ( ! array_key_exists( $taxonomy , $post_type_taxonomies ) ) {
						return new Error( 401, __( 'Sorry, one of the given taxonomies is not supported by the post type.' ) );
					}

					if ( ! current_user_can( $post_type_taxonomies[$taxonomy]->cap->assign_terms ) ) {
						return new Error( 401, __( 'Sorry, you are not allowed to assign a term to one of the given taxonomies.' ) );
					}

					$term_ids = $post_data['terms'][$taxonomy];
					$terms[ $taxonomy ] = [];
					foreach ( $term_ids as $term_id ) {
						$term = get_term_by( 'id', $term_id, $taxonomy );

						if ( ! $term ) {
							return new Error( 403, __( 'Invalid term ID.' ) );
						}
						$terms[$taxonomy][] = (int) $term_id;
					}
				}
			}

			// Now validate terms specified by name.
			if ( isset( $post_data['terms_names'] ) && is_array( $post_data['terms_names'] ) ) {
				$taxonomies = array_keys( $post_data['terms_names'] );

				foreach ( $taxonomies as $taxonomy ) {
					if ( ! array_key_exists( $taxonomy , $post_type_taxonomies ) ) {
						return new Error( 401, __( 'Sorry, one of the given taxonomies is not supported by the post type.' ) );
					}

					if ( ! current_user_can( $post_type_taxonomies[$taxonomy]->cap->assign_terms ) ) {
						return new Error( 401, __( 'Sorry, you are not allowed to assign a term to one of the given taxonomies.' ) );
					}

					/*
					 * For hierarchical taxonomies, we can't assign a term when multiple terms
					 * in the hierarchy share the same name.
					 */
					$ambiguous_terms = [];
					if ( is_taxonomy_hierarchical( $taxonomy ) ) {
						$tax_term_names = get_terms( $taxonomy, [ 'fields' => 'names', 'hide_empty' => false ] );

						// Count the number of terms with the same name.
						$tax_term_names_count = array_count_values( $tax_term_names );

						// Filter out non-ambiguous term names.
						$ambiguous_tax_term_counts = array_filter( $tax_term_names_count, [ $this, '_is_greater_than_one' ] );

						$ambiguous_terms = array_keys( $ambiguous_tax_term_counts );
					}

					$term_names = $post_data['terms_names'][$taxonomy];
					foreach ( $term_names as $term_name ) {
						if ( in_array( $term_name, $ambiguous_terms ) ) {
							return new Error( 401, __( 'Ambiguous term name used in a hierarchical taxonomy. Please use term ID instead.' ) );
						}

						$term = get_term_by( 'name', $term_name, $taxonomy );

						if ( ! $term ) {
							// Term doesn't exist, so check that the user is allowed to create new terms.
							if ( ! current_user_can( $post_type_taxonomies[$taxonomy]->cap->edit_terms ) ) {
								return new Error( 401, __( 'Sorry, you are not allowed to add a term to one of the given taxonomies.' ) );
							}

							// Create the new term.
							$term_info = wp_insert_term( $term_name, $taxonomy );
							if ( is_wp_error( $term_info ) ) {
								return new Error( 500, $term_info->get_error_message() );
							}
							$terms[$taxonomy][] = (int) $term_info['term_id'];
						} else {
							$terms[$taxonomy][] = (int) $term->term_id;
						}
					}
				}
			}

			$post_data['tax_input'] = $terms;
			unset( $post_data['terms'], $post_data['terms_names'] );
		} else {
			// Do not allow direct submission of 'tax_input', clients must use 'terms' and/or 'terms_names'.
			unset( $post_data['tax_input'], $post_data['post_category'], $post_data['tags_input'] );
		}

		if ( isset( $post_data['post_format'] ) ) {
			$format = set_post_format( $post_ID, $post_data['post_format'] );

			if ( is_wp_error( $format ) ) {
				return new Error( 500, $format->get_error_message() );
			}
			unset( $post_data['post_format'] );
		}

		// Handle enclosures.
		$enclosure = isset( $post_data['enclosure'] ) ? $post_data['enclosure'] : null;
		$this->add_enclosure_if_new( $post_ID, $enclosure );

		$this->attach_uploads( $post_ID, $post_data['post_content'] );

		/**
		 * Filters post data array to be inserted via XML-RPC.
		 *
		 * @since 3.4.0
		 *
		 * @param array $post_data      Parsed array of post data.
		 * @param array $content_struct Post data array.
		 */
		$data = apply_filters( 'xmlrpc_wp_insert_post_data', $post_data, $content_struct );

		$id = $update ? wp_update_post( $data, true ) : wp_insert_post( $data, true );
		if ( is_wp_error( $id ) ) {
			return new Error( 500, $id->get_error_message() );
		}

		if ( ! $id ) {
			return new Error( 401, __( 'Sorry, your entry could not be posted.' ) );
		}
		return strval( $id );
	}

	/**
	 * Prepares media item data for return in an XML-RPC object.
	 *
	 * @access protected
	 *
	 * @param object $media_item     The unprepared media item data.
	 * @param string $thumbnail_size The image size to use for the thumbnail URL.
	 * @return array The prepared media item data.
	 */
	protected function _prepare_media_item( $media_item, $thumbnail_size = 'thumbnail' ) {
		$_media_item = [
			'attachment_id'    => strval( $media_item->ID ),
			'date_created_gmt' => $this->_convert_date_gmt( $media_item->post_date_gmt, $media_item->post_date ),
			'parent'           => $media_item->post_parent,
			'link'             => wp_get_attachment_url( $media_item->ID ),
			'title'            => $media_item->post_title,
			'caption'          => $media_item->post_excerpt,
			'description'      => $media_item->post_content,
			'metadata'         => wp_get_attachment_metadata( $media_item->ID ),
			'type'             => $media_item->post_mime_type
		];

		$thumbnail_src = image_downsize( $media_item->ID, $thumbnail_size );
		if ( $thumbnail_src ) {
			$_media_item['thumbnail'] = $thumbnail_src[0];
		} else {
			$_media_item['thumbnail'] = $_media_item['link'];
		}
		/**
		 * Filters XML-RPC-prepared data for the given media item.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $_media_item    An array of media item data.
		 * @param object $media_item     Media item object.
		 * @param string $thumbnail_size Image size.
		 */
		return apply_filters( 'xmlrpc_prepare_media_item', $_media_item, $media_item, $thumbnail_size );
	}

	/**
	 * Convert a WordPress GMT date string to an Date object.
	 *
	 * @access protected
	 *
	 * @param string $date_gmt WordPress GMT date string.
	 * @param string $date     Date string.
	 * @return Date Date object.
	 */
	protected function _convert_date_gmt( $date_gmt, $date ) {
		if ( $date !== '0000-00-00 00:00:00' && $date_gmt === '0000-00-00 00:00:00' ) {
			return new Date( get_gmt_from_date( mysql2date( 'Y-m-d H:i:s', $date, false ), 'Ymd\TH:i:s' ) );
		}
		return $this->_convert_date( $date_gmt );
	}

	/**
	 * Convert a WordPress date string to an Date object.
	 *
	 * @access protected
	 *
	 * @param string $date Date string to convert.
	 * @return Date Date object.
	 */
	protected function _convert_date( $date ) {
		if ( $date === '0000-00-00 00:00:00' ) {
			return new Date( '00000000T00:00:00Z' );
		}
		return new Date( mysql2date( 'Ymd\TH:i:s', $date, false ) );
	}

	/**
	 * Checks if the method received at least the minimum number of arguments.
	 *
	 * @since 3.4.0
	 * @access protected
	 *
	 * @param string|array $args Sanitize single string or array of strings.
	 * @param int $count         Minimum number of arguments.
	 * @return bool if `$args` contains at least $count arguments.
	 */
	protected function minimum_args( $args, $count ) {
		if ( count( $args ) < $count ) {
			$this->error = new Error( 400, __( 'Insufficient arguments passed to this XML-RPC method.' ) );
			return false;
		}

		return true;
	}

	/**
	 * Retrieve custom fields for post.
	 *
	 * @since 2.5.0
	 *
	 * @param int $id Post ID.
	 * @return array Custom fields, if exist.
	 */
	public function get_custom_fields( $id ) {
		$post_id = (int) $id;

		$custom_fields = [];

		foreach ( (array) has_meta($post_id) as $meta ) {
			// Don't expose protected fields.
			if ( ! current_user_can( 'edit_post_meta', $post_id , $meta['meta_key'] ) ) {
				continue;
			}

			$custom_fields[] = [
				'id'    => $meta['meta_id'],
				'key'   => $meta['meta_key'],
				'value' => $meta['meta_value']
			];
		}

		return $custom_fields;
	}

	/**
	 * Set custom fields for post.
	 *
	 * @since 2.5.0
	 *
	 * @param int $id Post ID.
	 * @param array $fields Custom fields.
	 */
	public function set_custom_fields( $id, $fields ) {
		$post_id = (int) $id;

		foreach ( (array) $fields as $meta ) {
			if ( isset($meta['id']) ) {
				$meta['id'] = (int) $meta['id'];
				$pmeta = get_metadata_by_mid( 'post', $meta['id'] );
				if ( isset($meta['key']) ) {
					$meta['key'] = wp_unslash( $meta['key'] );
					if ( $meta['key'] !== $pmeta->meta_key ) {
						continue;
					}
					$meta['value'] = wp_unslash( $meta['value'] );
					if ( current_user_can( 'edit_post_meta', $post_id, $meta['key'] ) ) {
						update_metadata_by_mid( 'post', $meta['id'], $meta['value'] );
					}
				} elseif ( current_user_can( 'delete_post_meta', $post_id, $pmeta->meta_key ) ) {
					delete_metadata_by_mid( 'post', $meta['id'] );
				}
			} elseif ( current_user_can( 'add_post_meta', $post_id, wp_unslash( $meta['key'] ) ) ) {
				add_post_meta( $post_id, $meta['key'], $meta['value'] );
			}
		}
	}

	/**
	 * Adds an enclosure to a post if it's new.
	 *
	 * @since 2.8.0
	 *
	 * @param integer $post_ID   Post ID.
	 * @param array   $enclosure Enclosure data.
	 */
	public function add_enclosure_if_new( $post_ID, $enclosure ) {
		if ( is_array( $enclosure ) && isset( $enclosure['url'], $enclosure['length'], $enclosure['type'] ) ) {
			$encstring = $enclosure['url'] . "\n" . $enclosure['length'] . "\n" . $enclosure['type'] . "\n";
			$found = false;
			$enclosures = get_post_meta( $post_ID, 'enclosure' );
			if ( $enclosures ) {
				foreach ( $enclosures as $enc ) {
					// This method used to omit the trailing new line. #23219
					if ( rtrim( $enc, "\n" ) == rtrim( $encstring, "\n" ) ) {
						$found = true;
						break;
					}
				}
			}
			if ( ! $found ) {
				add_post_meta( $post_ID, 'enclosure', $encstring );
			}
		}
	}

	/**
	 * Attach upload to a post.
	 *
	 * @since 2.1.0
	 *
	 * @param int $post_ID Post ID.
	 * @param string $post_content Post Content for attachment.
	 */
	public function attach_uploads( $post_ID, $post_content ) {
		$app = getApp();
		$db = $app['db'];

		// find any unattached files
		$attachments = $db->get_results( "SELECT ID, guid FROM {$db->posts} WHERE post_parent = '0' AND post_type = 'attachment'" );
		if ( is_array( $attachments ) ) {
			foreach ( $attachments as $file ) {
				if ( ! empty( $file->guid ) && strpos( $post_content, $file->guid ) !== false ) {
					$db->update( $db->posts, [ 'post_parent' => $post_ID ], [ 'ID' => $file->ID ] );
				}
			}
		}
	}
}