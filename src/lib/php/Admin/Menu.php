<?php
namespace WP\Admin;

use WP\App;

class Menu {
	private $app;

	private $self;

	public function __construct( App $app ) {
		$this->app = $app;

		$this->setSelf();
	}

	protected function setSelf() {
		$self = preg_replace( '|^.*/wp-admin/network/|i', '', $this->app['request.php_self'] );
		$self = preg_replace( '|^.*/wp-admin/|i', '', $self );
		$self = preg_replace( '|^.*/plugins/|i', '', $self );
		$self = preg_replace( '|^.*/mu-plugins/|i', '', $self );

		$this->self = $self;
	}

	protected function setAdminPageParent( $parent = '' ) {
		$typenow = $this->app['typenow'];
		$pagenow = $this->app['pagenow'];

		if ( ! empty ( $parent ) && 'admin.php' != $parent ) {
			return $parent;
		}

		$plugin_page = $this->app->get( 'plugin_page' );
		if ( $pagenow === 'admin.php' && $plugin_page ) {

			foreach ( (array) $this->app->menu as $parent_menu ) {
				if ( $parent_menu[2] == $plugin_page ) {
					$this->app->set( 'parent_file', $plugin_page );
					return;
				}
			}
			if ( isset( $this->app->_wp_menu_nopriv[ $plugin_page ] ) ) {
				$this->app->set( 'parent_file', $plugin_page );
				return;
			}
		}

		if ( $plugin_page && isset( $this->app->_wp_submenu_nopriv[ $pagenow ][ $plugin_page ] ) ) {
			$this->app->set( 'parent_file', $pagenow );
			return;
		}

		$parent_file = $this->app->get( 'parent_file' );
		$post_type_url = sprintf( '%s?post_type=%s', $pagenow, $typenow );
		foreach ( (array) $this->app->submenu as $submenu ) {
			foreach ( $submenu[ $parent ] as $sub ) {
				if ( ! empty( $typenow ) && $sub[2] === $post_type_url ) {
					$this->app->set( 'parent_file', $parent );
					return;
				} elseif ( $sub[2] === $pagenow && empty( $typenow ) && ( empty( $parent_file ) || false === strpos( $parent_file, '?' ) ) ) {
					$this->app->set( 'parent_file', $parent );
					return;
				} elseif ( $plugin_page && ( $plugin_page === $sub[2] ) ) {
					$this->app->set( 'parent_file', $parent );
					return;
				}
			}
		}

		if ( ! $this->app->get( 'parent_file' ) ) {
			$this->app->set( 'parent_file', '' );
		}
	}

	public function compile() {
		static $ran = null;

		if ( null !== $ran ) {
			return $ran;
		}

		$output = '';

		$typenow = $this->app['typenow'];
		$parent_file = $this->app->get( 'parent_file' );
		$submenu_file = $this->app->get( 'submenu_file' );
		$plugin_page = $this->app->get( 'plugin_page' );

		$first = true;
		// 0 = menu_title,
		// 1 = capability,
		// 2 = menu_slug,
		// 3 = page_title,
		// 4 = classes,
		// 5 = hookname,
		// 6 = icon_url
		foreach ( $this->app->menu as $item ) {
			$admin_is_parent = false;
			$classes = [];
			$aria_attributes = '';
			$aria_hidden = '';
			$is_separator = false;

			if ( $first ) {
				$classes[] = 'wp-first-item';
				$first = false;
			}

			$submenu_items = [];
			if ( ! empty( $this->app->submenu[ $item[2] ] ) ) {
				$classes[] = 'wp-has-submenu';
				$submenu_items = $this->app->submenu[ $item[2] ];
			}

			if (
				( $parent_file && $item[2] === $parent_file ) ||
				( empty( $typenow ) && $this->self === $item[2] )
			) {
				$classes[] = ! empty( $submenu_items ) ? 'wp-has-current-submenu wp-menu-open' : 'current';
			} else {
				$classes[] = 'wp-not-current-submenu';
				if ( ! empty( $submenu_items ) ) {
					$aria_attributes .= 'aria-haspopup="true"';
				}
			}

			if ( ! empty( $item[4] ) ) {
				$classes[] = esc_attr( $item[4] );
			}
			$class = $classes ? ' class="' . join( ' ', $classes ) . '"' : '';
			$id = ! empty( $item[5] ) ? ' id="' . preg_replace( '|[^a-zA-Z0-9_:.]|', '-', $item[5] ) . '"' : '';
			$img = $img_style = '';
			$img_class = ' dashicons-before';

			if ( false !== strpos( $class, 'wp-menu-separator' ) ) {
				$is_separator = true;
			}

			/*
			 * If the string 'none' (previously 'div') is passed instead of a URL, don't output
			 * the default menu image so an icon can be added to div.wp-menu-image as background
			 * with CSS. Dashicons and base64-encoded data:image/svg_xml URIs are also handled
			 * as special cases.
			 */
			if ( ! empty( $item[6] ) ) {
				$img = '<img src="' . $item[6] . '" alt="" />';

				if ( 'none' === $item[6] || 'div' === $item[6] ) {
					$img = '<br />';
				} elseif ( 0 === strpos( $item[6], 'data:image/svg+xml;base64,' ) ) {
					$img = '<br />';
					$img_style = ' style="background-image:url(\'' . esc_attr( $item[6] ) . '\')"';
					$img_class = ' svg';
				} elseif ( 0 === strpos( $item[6], 'dashicons-' ) ) {
					$img = '<br />';
					$img_class = ' dashicons-before ' . sanitize_html_class( $item[6] );
				}
			}
			$arrow = '<div class="wp-menu-arrow"><div></div></div>';

			$title = wptexturize( $item[0] );

			// hide separators from screen readers
			if ( $is_separator ) {
				$aria_hidden = ' aria-hidden="true"';
			}

			$output .= sprintf( '<li%s%s%s>', $class, $id, $aria_hidden );

			if ( $is_separator ) {
				$output .= '<div class="separator"></div>';
			} elseif ( ! empty( $submenu_items ) ) {
				$submenu_items = array_values( $submenu_items );  // Re-index.
				$menu_hook = get_plugin_page_hook( $submenu_items[0][2], $item[2] );
				$menu_file = $submenu_items[0][2];

				if ( false !== ( $pos = strpos( $menu_file, '?' ) ) ) {
					$menu_file = substr( $menu_file, 0, $pos );
				}

				$plugin_file = path_join( WP_PLUGIN_DIR, $menu_file );
				$admin_file = sprintf( '%s/wp-admin/%s', ABSPATH, $menu_file );

				if (
					! empty( $menu_hook ) ||
					(
						'index.php' !== $submenu_items[0][2] &&
						file_exists( $plugin_file ) &&
						! file_exists( $admin_file )
					)
				) {
					$admin_is_parent = true;
					$output .= sprintf(
						'<a href="admin.php?page=%s"%s %s>%s<div class="wp-menu-image%s"%s>%s</div><div class="wp-menu-name">%s</div></a>',
						$submenu_items[0][2],
						$class,
						$aria_attributes,
						$arrow,
						$img_class,
						$img_style,
						$img,
						$title
					);
				} else {
					$output .= sprintf(
						'<a href="%s"%s %s>%s<div class="wp-menu-image%s"%s>%s</div><div class="wp-menu-name">%s</div></a>',
						$submenu_items[0][2],
						$class,
						$aria_attributes,
						$arrow,
						$img_class,
						$img_style,
						$img,
						$title
					);
				}
			} elseif ( ! empty( $item[2] ) && current_user_can( $item[1] ) ) {
				$menu_hook = get_plugin_page_hook( $item[2], 'admin.php' );
				$menu_file = $item[2];
				$pos = strpos( $menu_file, '?' );

				if ( false !== $pos ) {
					$menu_file = substr( $menu_file, 0, $pos );
				}

				if (
					! empty( $menu_hook ) ||
					(
						( 'index.php' != $item[2] ) &&
						file_exists( WP_PLUGIN_DIR . "/{$menu_file}" ) &&
						! file_exists( ABSPATH . "/wp-admin/{$menu_file}" )
					)
				) {
					$admin_is_parent = true;
					$output .= sprintf(
						'<a href="admin.php?page=%s"%s %s>%s<div class="wp-menu-image%s"%s>%s</div><div class="wp-menu-name">%s</div></a>',
						$item[2],
						$class,
						$aria_attributes,
						$arrow,
						$img_class,
						$img_style,
						$img,
						$item[0]
					);
				} else {
					$output .= sprintf(
						'<a href="%s"%s %s>%s<div class="wp-menu-image%s"%s>%s</div><div class="wp-menu-name">%s</div></a>',
						$item[2],
						$class,
						$aria_attributes,
						$arrow,
						$img_class,
						$img_style,
						$img,
						$item[0]
					);
				}
			}

			if ( ! empty( $submenu_items ) ) {
				$output .= "<ul class='wp-submenu wp-submenu-wrap'>";
				$output .= "<li class='wp-submenu-head' aria-hidden='true'>{$item[0]}</li>";

				$first = true;

				// 0 = menu_title,
				// 1 = capability,
				// 2 = menu_slug,
				// 3 = page_title,
				// 4 = classes
				foreach ( $submenu_items as $sub_key => $sub_item ) {
					if ( ! current_user_can( $sub_item[1] ) ) {
						continue;
					}

					$class = [];
					if ( $first ) {
						$class[] = 'wp-first-item';
						$first = false;
					}

					$menu_file = $item[2];

					if ( false !== ( $pos = strpos( $menu_file, '?' ) ) ) {
						$menu_file = substr( $menu_file, 0, $pos );
					}

					// Handle current for post_type=post|page|foo pages, which won't match $self.
					$self_type = ! empty( $typenow ) ? $this->self . '?post_type=' . $typenow : 'nothing';

					if ( $submenu_file ) {
						if ( $submenu_file == $sub_item[2] ) {
							$class[] = 'current';
						}
					// If plugin_page is set the parent must either match the current page or not physically exist.
					// This allows plugin pages with the same hook to exist under different parents.
					} elseif (
						( ! $plugin_page && $this->self == $sub_item[2] ) ||
						(
							$plugin_page === $sub_item[2] &&
							(
								$item[2] === $self_type ||
								$item[2] === $this->self ||
								file_exists( $menu_file ) === false
							)
						)
					) {
						$class[] = 'current';
					}

					if ( ! empty( $sub_item[4] ) ) {
						$class[] = esc_attr( $sub_item[4] );
					}

					$class = $class ? ' class="' . join( ' ', $class ) . '"' : '';

					$menu_hook = get_plugin_page_hook( $sub_item[2], $item[2] );
					$sub_file = $sub_item[2];
					$pos = strpos( $sub_file, '?' );
					if ( false !== $pos ) {
						$sub_file = substr( $sub_file, 0, $pos);
					}

					$title = wptexturize( $sub_item[0] );

					if (
						! empty( $menu_hook ) ||
						( ( 'index.php' != $sub_item[2] ) &&
						file_exists( WP_PLUGIN_DIR . "/$sub_file" ) &&
						! file_exists( ABSPATH . "/wp-admin/$sub_file" ) )
					) {
						// If admin.php is the current page or if the parent exists as a file in the plugins or admin dir
						if (
							(
								! $admin_is_parent &&
								file_exists( WP_PLUGIN_DIR . "/$menu_file" ) &&
								! is_dir( WP_PLUGIN_DIR . "/{$item[2]}" )
							) ||
							file_exists( $menu_file )
						) {
							$sub_item_url = add_query_arg( [ 'page' => $sub_item[2] ], $item[2] );
						} else {
							$sub_item_url = add_query_arg( [ 'page' => $sub_item[2] ], 'admin.php' );
						}
						$sub_item_url = esc_url( $sub_item_url );
						$output .= sprintf(
							'<li%1$s><a href="%2$s"%1$s>%3$s</a></li>',
							$class,
							$sub_item_url,
							$title
						);
						$output .= "<li{$class}><a href='{$sub_item_url}'{$class}>{$title}</a></li>";
					} else {
						$output .= sprintf(
							'<li%1$s><a href="%2$s"%1$s>%3$s</a></li>',
							$class,
							$sub_item[2],
							$title
						);
					}
				}
				$output .= '</ul>';
			}
			$output .= '</li>';
		}

		$output .= '<li id="collapse-menu" class="hide-if-no-js"><div id="collapse-button"><div></div></div>';
		$output .= '<span>' . esc_html__( 'Collapse menu' ) . '</span>';
		$output .= '</li>';

		$ran = $output;

		return $output;
	}
}