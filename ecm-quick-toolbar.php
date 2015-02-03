<?php
/**
 * Plugin Name: Quick Toolbar
 * Plugin URI: http://www.ecommnet.uk
 * Description: Add frequently used menu links and custom links to the Admin Toolbar.
 * Version: 0.1
 * Author: Ecommnet
 * Author URI: http://www.ecommnet.uk
 * License: GPL2
 */

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('ECM_Quick_Toolbar')) {

	class ECM_Quick_Toolbar {
		/**
		 * Plugin version number
		 * @var string
		 */
		public $version = '0.1';

		/**
		 * Single instance of the class
		 */
		protected static $_instance = null;

		/**
		 * Instance of the class
		 */
		public static function instance() {
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Initiate the plugin by setting up actions and filters
		 */
		public function __construct() {

			// Enqueue Styles and Scripts
			add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
			add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

			// Adding Menu Items
			add_action('admin_menu', array($this, 'add_admin_menu'));

			// Register Settings
			add_action('admin_init', array($this, 'register_settings'));

			// Submit Custom Links
			add_action('admin_init', array($this, 'custom_submit'));

			// Adding Items to Toolbar
			add_action('admin_bar_menu', array($this, 'add_toolbar_items'), 100);

			// AJAX Actions
			add_action('wp_ajax_ecmqt_delete_custom_link', array($this, 'delete_custom_link'));

			// Load plugin options if available
			$this->item_options = get_option('_ecmqt_items');
			$this->custom_item_options = get_option('_ecmqt_custom_items');
		}

		public function enqueue_styles() {
			wp_register_style( 'ecm_wp_admin_css', plugin_dir_url( __FILE__ ) . '/css/admin-styles.css', false, $version );
			wp_enqueue_style( 'ecm_wp_admin_css' );
			wp_enqueue_script( 'ecmqt_scripts', plugin_dir_url( __FILE__ ) . '/js/ecmqt-scripts.js', array(), $version, true );

		}

		public function register_settings() {
			register_setting( 'ecmqt-settings-group', '_ecmqt_items' );
			register_setting( 'ecmqt-custom-settings-group', '_ecmqt_custom_items' );
		}

		public function add_admin_menu() {
			add_menu_page( 'Quick Toolbar Links', 'Quick Toolbar', 'manage_options', 'ecm-quick-toolbar', array($this, 'admin_page'), 'dashicons-admin-links' );
			add_submenu_page( 'ecm-quick-toolbar', 'Custom Quick Links', 'Edit Custom Links', 'manage_options', 'ecm-custom-quick-toolbar', array($this, 'admin_custom_page') );
		}

		public function add_toolbar_items($admin_bar){
			$options = get_option( '_ecmqt_items');
			$custom_options = get_option( '_ecmqt_custom_items');

			if (!empty($options) && isset($options)) {
				// Top Level Menus
				$j = 2000;
				$user_ID = get_current_user_id();
				foreach($options as $option) {
					$decoded = unserialize(base64_decode($option));
					if (isset($decoded[2]) && !empty($decoded[2])) {
						$allowed = user_can( $user_ID, $decoded[3] );
						if ($allowed == true) {
							$admin_bar->add_menu( array(
								'id'    => 'ecmqt_'.date("Y-m-d-his").$decoded[2][0],
								'title' => $decoded[2][1],
								'href'  => $decoded[2][2],
								'meta' 	=> array('class' => 'ecmqt-menu-item ecmqt-has-submenu')
							));
						}
					} else {
						$allowed = user_can( $user_ID, $decoded[3] );
						if ($allowed == true) {
							$admin_bar->add_menu( array(
								'id'    => 'ecmqt_'.date("Y-m-d-his").$j,
								'title' => $decoded[0],
								'href'  => $decoded[1],
								'meta' 	=> array('class' => 'ecmqt-menu-item')
							));
							$j++;
						}
					}
				}
				// Submenus
				$i = 1000;
				foreach($options as $option) {
					$decoded = unserialize(base64_decode($option));
					$user_ID = get_current_user_id();
					$allowed = user_can( $user_ID, $decoded[3] );
					if ( $allowed == true ) {
						if (isset($decoded[2]) && !empty($decoded[2])) {
							$admin_bar->add_menu( array(
								'id'    => 'ecmqt_'.date("Y-m-d-his").$i,
								'title' => $decoded[0],
								'href'  => $decoded[1],
								'parent' => 'ecmqt_'.date("Y-m-d-his").$decoded[2][0],
								'meta' 	=> array('class' => 'ecmqt-submenu-item')
							));
							$i++;
						}
					}
				}
			}

			// Custom Links
			if (!empty($custom_options) && isset($custom_options)) {
				// Custom Top Level Links
				$co = 3000;
				foreach ($custom_options as $key => $custom_option) {
					if (isset($custom_options[$key][4]) && !empty($custom_options[$key][4])) {
						if (isset($custom_options[$key][2]) && !empty($custom_options[$key][2]) && $custom_options[$key][2] == true) {
							$meta = array('class' => 'ecmqt-menu-item ecmqt-has-submenu', 'target' => '_blank');
						} else {
							$meta = array('class' => 'ecmqt-menu-item ecmqt-has-submenu');
						}

						$admin_bar->add_menu( array(
							'id'    => 'ecmqt_'.date("Y-m-d-his").$key,
							'title' => $custom_options[$key][0],
							'href'  => $custom_options[$key][1],
							'meta' 	=> $meta
						));
					} else {
						if (isset($custom_options[$key][2]) && !empty($custom_options[$key][2]) && $custom_options[$key][2] == true) {
							$meta = array('class' => 'ecmqt-menu-item', 'target' => '_blank');
						} else {
							$meta = array('class' => 'ecmqt-menu-item');
						}
						$admin_bar->add_menu( array(
							'id'    => 'ecmqt_'.date("Y-m-d-his").$co,
							'title' => $custom_options[$key][0],
							'href'  => $custom_options[$key][1],
							'meta' 	=> $meta
						));
					}
					$co++;
				}
				// Custom Submenus
				$cos = 4000;
				foreach($custom_options as $custom_option) {
					if (isset($custom_option[4]) && !empty($custom_option[4])) {
						foreach ($custom_option[4] as $custom_menu_item) {
							if (isset($custom_menu_item[2]) && !empty($custom_menu_item[2]) && $custom_menu_item[2] == true) {
								$meta = array('class' => 'ecmqt-submenu-item', 'target' => '_blank');
							} else {
								$meta = array('class' => 'ecmqt-submenu-item');
							}
							$admin_bar->add_menu( array(
								'id'    => 'ecmqt_'.date("Y-m-d-his").$cos,
								'title' => $custom_menu_item[0],
								'href'  => $custom_menu_item[1],
								'parent' => 'ecmqt_'.date("Y-m-d-his").$custom_menu_item[4],
								'meta' 	=> $meta
							));
							$cos++;
						}
					}
				}

			}
		}

		public function admin_page() {
			$i = 0;
			$x = 0;
			$items = $this->get_items();
			if (isset($items) && !empty($items)) { ?>
				<div class="wrap">
				<h2>Quick Toolbar Links <a href="<?php echo get_admin_url() . 'admin.php?page=ecm-custom-quick-toolbar';?>" class="add-new-h2">Edit Custom Toolbar Links</a></h2>
				<br/>
				<form method="post" action="options.php" id="_ecmqt_quicklinks_options">
					<?php settings_fields( 'ecmqt-settings-group' ); ?>
					<?php do_settings_sections( 'ecmqt-settings-group' ); ?>
					<table summary="config_menu" class="widefat">
						<thead>
						<tr>
							<th>Menu Items</th>
							<th>Add to Quick Links</th>
						</tr>
						</thead>
						<?php
						$options = get_option( '_ecmqt_items');
						$selections = array();
						if (isset($options) && !empty($options)) {
							foreach ($options as $option) {
								$selections[] = unserialize(base64_decode($option));
							}
						}

						foreach ($items as $ecm_menu_item) {
							$checked = '';
							foreach ($selections as $checked_item) {
								if ($ecm_menu_item['name'] == $checked_item[0] && $ecm_menu_item['link'] == $checked_item[1]) {
									$checked = ' checked';
								}
							}
							echo '<tr class="ecmqt-heading">' . "\n";
							echo "\t" . '<td>' . $ecm_menu_item['name'] . '</td>';
							echo "\t" . '<td>';
							if (isset($ecm_menu_item['subpages']) && !empty($ecm_menu_item['subpages'])) {
								echo '';
							} else {
								echo '<input id="check_menu_'. $x .'" type="checkbox"' . ' name="_ecmqt_items[]" value="' . base64_encode(serialize(array( $ecm_menu_item['name'] ,  $ecm_menu_item['link'], '', $ecm_menu_item['permissions']))) . '"' . $checked . '/>';
							}
							echo '</td>' . "\n";
							echo '</tr>';
							$x++;

							if (isset($ecm_menu_item['subpages']) && !empty($ecm_menu_item['subpages'])) {
								$header_found = false;
								$bg_found = false;
								foreach ($ecm_menu_item['subpages'] as $ecm_submenu_item) {
									$sub_checked = '';
									$ecm_class = ( ' class="alternate"' == $ecm_class ) ? '' : ' class="alternate"';

									if ($ecm_menu_item['name'] == 'Appearance') {
										if ($ecm_submenu_item['name'] == 'Header') {
											if ($header_found == true) {
												continue;
											} else {
												$header_found = true;
											}
										}
										if ($ecm_submenu_item['name'] == 'Background') {
											if ($bg_found == true) {
												continue;
											} else {
												$bg_found = true;
											}
										}
									}

									foreach ($selections as $checked_subitem) {
										if ($ecm_submenu_item['name'] == $checked_subitem[0] && $ecm_submenu_item['link'] == $checked_subitem[1]) {
											$sub_checked = ' checked';
										}
									}

									echo '<tr' . $ecm_class . '>' . "\n";
									echo '<td> &mdash; ' . $ecm_submenu_item['name'] . '</td>' . "\n";
									echo '<td><input id="check_menu_'.$x .'" type="checkbox" name="_ecmqt_items[]" value="' . base64_encode(serialize(array( $ecm_submenu_item['name'],  $ecm_submenu_item['link'], array($ecm_submenu_item['parent']['id'], $ecm_submenu_item['parent']['name'], $ecm_submenu_item['parent']['link']), $ecm_submenu_item['permissions']))) . '"' . $sub_checked . '/></td>' . "\n";
									echo '</tr>' . "\n";
									$x++;
								}
							}
							$i++;
							$x++;
						} ?>
					</table>
					<?php submit_button(); ?>
				</form>
			<?php }

			// Ecommnet Footer
			add_action('in_admin_footer', array($this, 'admin_footer'));
			?>
			</div>
		<?php

		}

		public function admin_custom_page() {
			$this->custom_form();
		}

		public function delete_custom_link() {
			$custom_link = $_POST['ecmqt_id'];
			$custom_options = get_option( '_ecmqt_custom_items');

			foreach ($custom_options as $key => $custom_option) {
				$custom_option_id = $custom_options[$key][3];
				if ($custom_option_id == $custom_link) {
					unset($custom_options[$key]);
				}
				if (isset($custom_options[$key][4]) && !empty($custom_options[$key][4])) {
					foreach ($custom_options[$key][4] as $sub_key => $sub_option) {
						$sub_option_id = $custom_options[$key][4][$sub_key][3];
						if ($sub_option_id == $custom_link) {
							unset($custom_options[$key][4][$sub_key]);
						}
					}
				}
			}
			update_option( '_ecmqt_custom_items', $custom_options );
			exit;
		}

		public function custom_submit() {
			global $pagenow;
			if($pagenow == 'admin.php' && $_GET['page'] == 'ecm-custom-quick-toolbar') {
				if(isset($_POST['submit']) && isset($_POST['_ecmqt_custom_quicklinks_options']) && $_POST['_ecmqt_custom_quicklinks_options'] == '_ecmqt_custom_quicklinks_options') {
					$title = $_POST["_ecmqt_custom_items_title"];
					$link = $_POST["_ecmqt_custom_items_link"];
					$target = $_POST["_ecmqt_custom_items_target"];
					$parent = $_POST["_ecmqt_custom_items_parent"];

					if ($target == true) {
						$new_window = true;
					} else {
						$new_window = false;
					}

					$custom_options = get_option('_ecmqt_custom_items');
					$unique = date('dmyHis');
					if ($parent == 'no-parent' || $parent == '') {
						$custom_options[] = array($title, $link, $new_window, $unique);
					} else {
						foreach ($custom_options as $key => $value) {
							$option_key = '_ecmqt_parent_' . $key;
							if ($option_key == $parent) {
								$custom_options[$key][4][] = array($title, $link, $new_window, $unique, $key);
							}
						}
					}

					update_option( '_ecmqt_custom_items', $custom_options );

					wp_redirect( $_SERVER['REQUEST_URI'] );
					exit;
				}
			}
		}

		public function custom_form() { ?>
			<div class="wrap">
				<h2>Custom Quick Toolbar Links <a href="<?php echo get_admin_url() . 'admin.php?page=ecm-quick-toolbar';?>" class="add-new-h2">Edit Quick Toolbar Links</a></h2>
				<form id="_ecmqt_custom_quicklinks_options" name="_ecmqt_custom_quicklinks_options" method="post" action="">
					<label for="_ecmqt_custom_items_title">Link Title *</label><br/>
					<input type="text" name="_ecmqt_custom_items_title" id="_ecmqt_custom_items_title" maxlength="50" size="40" required><br/><br/>
					<label for="_ecmqt_custom_items_link">Link URL</label><br/>
					<input type="text" name="_ecmqt_custom_items_link" id="_ecmqt_custom_items_link" size="40">
					<p class="description">Leave blank if you're using this link as a menu title.</p><br/>
					<input type="checkbox" name="_ecmqt_custom_items_target" id="_ecmqt_custom_items_target" value="Target"><label for="_ecmqt_custom_items_target"> Open in New Window / Tab</label><br/><br/>
					<?php $custom_options = get_option( '_ecmqt_custom_items');
					if (isset($custom_options) && !empty($custom_options)) { ?>
						<select name="_ecmqt_custom_items_parent" id="_ecmqt_custom_items_parent">
							<option value="no-parent">Select Parent</option>
							<?php foreach($custom_options as $key => $custom_option) {
								echo '<option value="_ecmqt_parent_' . $key . '">' . $custom_option[0] . '</option>';
							} ?>
						</select><br/><br/>
					<?php } ?>
					<input name="submit" type="submit" value="Add Custom Quick Link &raquo;" class="button button-primary">
					<input type="hidden" name="_ecmqt_custom_quicklinks_options" value="_ecmqt_custom_quicklinks_options">
				</form><br/><br/>

				<?php
				if (isset($custom_options) && !empty($custom_options)) {
					$this->list_custom_items();
				}
				// Ecommnet Footer
				add_action('in_admin_footer', array($this, 'admin_footer'));

				?>
			</div>
		<?php }

		public function list_custom_items() {
			$custom_options = get_option( '_ecmqt_custom_items'); ?>

			<table class="widefat" id="_ecmqt_custom_links">
				<thead>
				<tr>
					<th>Custom Quick Links</th>
					<th></th>
				</tr>
				</thead>
				<?php
				$x = 1;
				foreach ($custom_options as $custom_option) {
					echo '<tr class="ecmqt-heading">';
					echo '<td>' . $custom_option[0] . '</td>';
					echo '<td class="ecmqt-custom-link-delete"><a id="ecmqt_delete_' . $x .'" onClick="ecmqtDelete(\'' . $custom_option[3] .'\',\''. $custom_option[0] . '\')">Delete</a></td>';
					echo '</tr>';
					$x++;
					if (isset($custom_option[4]) && !empty($custom_option[4])) {
						foreach ($custom_option[4] as $co_submenu) {
							$ecm_class = ( ' class="alternate"' == $ecm_class ) ? '' : ' class="alternate"';
							echo '<tr ' . $ecm_class . '>';
							echo '<td>&mdash; ' . $co_submenu[0] . '</td>';
							echo '<td class="ecmqt-custom-link-delete"><a id="ecmqt_delete_' . $x .'" onclick="ecmqtDelete(\'' . $co_submenu[3] .'\',\''. $co_submenu[0] . '\')">Delete</a></td>';
							echo '</tr>';
							$x++;
						}
					}


				} ?>
			</table>
		<?php
		}

		public function admin_footer() { ?>
			<div class="ecommnet-footer">
				<a target="_blank" href="http://www.ecommnet.uk/">
					<img src="<?php echo ECM_Quick_Toolbar()->plugin_url() ?>/images/logo.png" alt="Ecommnet"/>
				</a>
				<p class="quick-toolbar">This plugin is made by Ecommnet Ltd, we provide WordPress solutions, web development and design, security consultancy and more. <a target="_blank" href="http://www.ecommnet.uk/">Click here</a> to find out more about our services.</p>
			</div>
		<?php }

		/**
		 * Return the plugin URL
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit(plugins_url('/', __FILE__ ));
		}

		/**
		 * Return the plugin directory path
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit(plugin_dir_path(__FILE__));
		}

		public function get_items() {
			global $menu, $submenu, $self, $parent_file, $submenu_file, $plugin_page, $typenow;

			$items = array();
			$submenu_as_parent = true;

			$first = true;
			foreach ( $menu as $key => $item ) {
				$admin_is_parent = false;
				$class = array();
				$aria_attributes = '';
				$is_separator = false;

				if ( $first ) {
					$class[] = 'wp-first-item';
					$first = false;
				}

				$submenu_items = false;
				if ( ! empty( $submenu[$item[2]] ) ) {
					$class[] = 'wp-has-submenu';
					$submenu_items = $submenu[$item[2]];
				}

				if ( ( $parent_file && $item[2] == $parent_file ) || ( empty($typenow) && $self == $item[2] ) ) {
					$class[] = ! empty( $submenu_items ) ? 'wp-has-current-submenu wp-menu-open' : 'current';
				} else {
					$class[] = 'wp-not-current-submenu';
					if ( ! empty( $submenu_items ) )
						$aria_attributes .= 'aria-haspopup="true"';
				}

				if ( ! empty( $item[4] ) )
					$class[] = esc_attr( $item[4] );

				$class = $class ? ' class="' . join( ' ', $class ) . '"' : '';

				if ( false !== strpos( $class, 'wp-menu-separator' ) ) {
					$is_separator = true;
				}

				if ( $is_separator ) {
				} elseif ( $submenu_as_parent && ! empty( $submenu_items ) ) {
					$submenu_items = array_values( $submenu_items );  // Re-index.
					$menu_hook = get_plugin_page_hook( $submenu_items[0][2], $item[2] );
					$menu_file = $submenu_items[0][2];
					if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
						$menu_file = substr( $menu_file, 0, $pos );
					if ( ! empty( $menu_hook ) || ( ( 'index.php' != $submenu_items[0][2] ) && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! file_exists( ABSPATH . "/wp-admin/$menu_file" ) ) ) {
						$admin_is_parent = true;

						$items[$key] = array(
							'name' => $item[0],
							'link' => get_admin_url() . "admin.php?page={$submenu_items[0][2]}",
							'permissions' => $item[1]
						);

					} else {
						$items[$key] = array(
							'name' => $item[0],
							'link' => get_admin_url() . $submenu_items[0][2],
							'permissions' => $item[1]
						);
					}
				} elseif ( ! empty( $item[2] ) && current_user_can( $item[1] ) ) {
					$menu_hook = get_plugin_page_hook( $item[2], 'admin.php' );
					$menu_file = $item[2];
					if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
						$menu_file = substr( $menu_file, 0, $pos );
					if ( ! empty( $menu_hook ) || ( ( 'index.php' != $item[2] ) && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! file_exists( ABSPATH . "/wp-admin/$menu_file" ) ) ) {
						$admin_is_parent = true;

						$items[$key] = array(
							'name' => $item[0],
							'link' => get_admin_url() . "admin.php?page={$item[2]}",
							'permissions' => $item[1]
						);

					} else {
						$items[$key] = array(
							'name' => $item[0],
							'link' => get_admin_url() . $item[2],
							'permissions' => $item[1]
						);
					}
				}

				if ( ! empty( $submenu_items ) ) {

					$first = true;

					foreach ( $submenu_items as $sub_key => $sub_item ) {
						if ( ! current_user_can( $sub_item[1] ) )
							continue;

						$class = array();
						if ( $first ) {
							$class[] = 'wp-first-item';
							$first = false;
						}

						$menu_file = $item[2];

						if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
							$menu_file = substr( $menu_file, 0, $pos );

						$self_type = ! empty( $typenow ) ? $self . '?post_type=' . $typenow : 'nothing';

						if ( isset( $submenu_file ) ) {
							if ( $submenu_file == $sub_item[2] )
								$class[] = 'current';
						} else if (
							( ! isset( $plugin_page ) && $self == $sub_item[2] ) ||
							( isset( $plugin_page ) && $plugin_page == $sub_item[2] && ( $item[2] == $self_type || $item[2] == $self || file_exists($menu_file) === false ) )
						) {
							$class[] = 'current';
						}

						if ( ! empty( $sub_item[4] ) ) {
							$class[] = esc_attr( $sub_item[4] );
						}

						$menu_hook = get_plugin_page_hook($sub_item[2], $item[2]);
						$sub_file = $sub_item[2];
						if ( false !== ( $pos = strpos( $sub_file, '?' ) ) )
							$sub_file = substr($sub_file, 0, $pos);

						if ( ! empty( $menu_hook ) || ( ( 'index.php' != $sub_item[2] ) && file_exists( WP_PLUGIN_DIR . "/$sub_file" ) && ! file_exists( ABSPATH . "/wp-admin/$sub_file" ) ) ) {
							if ( ( ! $admin_is_parent && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! is_dir( WP_PLUGIN_DIR . "/{$item[2]}" ) ) || file_exists( $menu_file ) )
								$sub_item_url = add_query_arg( array( 'page' => $sub_item[2] ), $item[2] );
							else
								$sub_item_url = add_query_arg( array( 'page' => $sub_item[2] ), 'admin.php' );

							$sub_item_url = esc_url( $sub_item_url );

							$items[$key]['subpages'][] = array(
								'name' => $sub_item[0],
								'link' => get_admin_url() . $sub_item_url,
								'parent' => array('id' => $key, 'name' => $items[$key]['name'], 'link' => $items[$key]['link']),
								'permissions' => $sub_item[1]
							);
						} else {

							$items[$key]['subpages'][] = array(
								'name' => $sub_item[0],
								'link' => get_admin_url() . $sub_item[2],
								'parent' => array('id' => $key, 'name' => $items[$key]['name'], 'link' => $items[$key]['link']),
								'permissions' => $sub_item[1]

							);
						}
					}
				}
			}
			return $items;
		}
	}
}

/**
 * Returns the main instance of SEO Checker
 */
function ECM_Quick_Toolbar() {
	return ECM_Quick_Toolbar::instance();
}

ECM_Quick_Toolbar();