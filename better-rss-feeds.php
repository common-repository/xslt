<?php
/*
Plugin Name: Better RSS Feeds
Plugin URI: https://wordpress.org/plugins/xslt/
Description: This plugin will add post thumbnail to RSS feeds and create fulltext RSS.
Version: 2.0.1
Author: Waterloo Plugins
License:

	Copyright 2013-2014 Ladislav Soukup (ladislav.soukup@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	02110-1301	USA

*/

class Better_Rss_Feeds {
	public $plugin_path;
	private $wpsf;
	private $CFG;

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	public function __construct() {
		$this->plugin_path = plugin_dir_path(__FILE__);

		// add admin menu item
		add_action('admin_menu', array(&$this, 'admin_menu'));

		// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		register_activation_hook(__FILE__, array( $this, 'activate' ));
		register_deactivation_hook(__FILE__, array( $this, 'deactivate' ));
		register_uninstall_hook(__FILE__, array( 'Better_Rss_Feeds', 'uninstall' ));

		/* wait for theme to load, then continue... */
		add_action('after_setup_theme', array( $this, 'Better_Rss_Feeds__construct_after_theme' ), 9999);

		add_action('wp_footer', array( $this, 'footer_credits' ));

		// Styling feeds
		add_action('rss_tag_pre', array( $this, 'display_template'));
		add_action('rss2_ns', array( $this, 'feed_namespace'));
		add_filter('feed_content_type', array( $this, 'feed_content_type'), 10, 2);
	} // end constructor

	public function Better_Rss_Feeds__construct_after_theme() {
		/* admin options */
		require_once($this->plugin_path .'wp-settings-framework.php');
		$this->wpsf = new WordPressSettingsFramework($this->plugin_path .'settings/better-rss-feeds-cfg.php');

		/* load CFG */
		$this->CFG = wpsf_get_settings($this->plugin_path .'settings/better-rss-feeds-cfg.php');
		// SET defaults if empty
		if (!isset($this->CFG['betterrssfeedscfg_tags_stylefeeds'])) {
			$this->CFG['betterrssfeedscfg_tags_stylefeeds'] = 1;
		}
		if (!isset($this->CFG['betterrssfeedscfg_tags_addTag_enclosure'])) {
			$this->CFG['betterrssfeedscfg_tags_addTag_enclosure'] = 1;
		}
		if (!isset($this->CFG['betterrssfeedscfg_tags_addTag_mediaContent'])) {
			$this->CFG['betterrssfeedscfg_tags_addTag_mediaContent'] = 1;
		}
		if (!isset($this->CFG['betterrssfeedscfg_tags_addTag_mediaThumbnail'])) {
			$this->CFG['betterrssfeedscfg_tags_addTag_mediaThumbnail'] = 1;
		}
		if (!isset($this->CFG['betterrssfeedscfg_description_extend_description'])) {
			$this->CFG['betterrssfeedscfg_description_extend_description'] = 1;
		}
		if (!isset($this->CFG['betterrssfeedscfg_description_extend_content'])) {
			$this->CFG['betterrssfeedscfg_description_extend_content'] = 1;
		}
		if (!isset($this->CFG['betterrssfeedscfg_signature_addSignature'])) {
			$this->CFG['betterrssfeedscfg_signature_addSignature'] = 0;
		}
		if (!isset($this->CFG['betterrssfeedscfg_fulltext_fulltext_override'])) {
			$this->CFG['betterrssfeedscfg_fulltext_fulltext_override'] = 0;
		}
		if (!isset($this->CFG['betterrssfeedscfg_fulltext_fulltext_add2description'])) {
			$this->CFG['betterrssfeedscfg_fulltext_fulltext_add2description'] = 0;
		}

		// add_action( "rss2_ns", array( $this, "feed_addNameSpace") );
		add_action('rss_item', array( $this, 'feed_addMeta' ), 5, 1);
		add_action('rss2_item', array( $this, 'feed_addMeta' ), 5, 1);

		if ($this->CFG['betterrssfeedscfg_description_extend_description'] == 1) {
			add_filter('the_excerpt_rss', array( $this, 'feed_update_content'));
		}

		if ($this->CFG['betterrssfeedscfg_description_extend_content'] == 1) {
			add_filter('the_content_feed', array( $this, 'feed_update_content'));
		}

		if (isset($this->CFG['betterrssfeedscfg_inrssAd_inrssAd_enabled']) && $this->CFG['betterrssfeedscfg_inrssAd_inrssAd_enabled'] == 1) {
			add_filter('the_content_feed', array( $this, 'feed_update_content_injectAd'));
		}

		if ($this->CFG['betterrssfeedscfg_fulltext_fulltext_override'] == 1) {
			$this->fulltext_override();
		}
	}

	public function addAdminAlert() {
		if (current_user_can('install_plugins')) { ?>
		<div class="updated">
			<p>
				<b>Better RSS Feeds Warning</b>: Please, update plugin settings...
				&nbsp;&nbsp;
				<a href="options-general.php?page=better_rss_feeds" class="button">Update settings</a>
			</p>
		</div>
		<?php }
	}

	public function admin_menu() {
		$menu_label = 'Better RSS Feeds';
		add_submenu_page('options-general.php', 'Better RSS Feeds', $menu_label, 'manage_options', 'better_rss_feeds', array(&$this, 'settings_page'));
	}

	public function settings_page() { ?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>

			<?php if (!empty($_GET['settings-updated'])) {
		if ($_GET['settings-updated'] == 'true') {
			$this->clear_WP_feed_cache();
		}
	} ?>
		<h2>Better RSS Feeds Settings</h2>
		<?php $this->wpsf->settings() ?>
		</div>
	<?php }


	/**
	 * Fired when the plugin is activated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public function activate($network_wide) {
		$this->clear_WP_feed_cache();
	} // end activate

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public function deactivate($network_wide) {
		$this->clear_WP_feed_cache();
	} // end deactivate

	/**
	 * Fired when the plugin is uninstalled.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public function uninstall($network_wide) {
		delete_option('betterrssfeedscfg_settings');
	} // end uninstall


	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/

	public function clear_WP_feed_cache() {
		// global $wpdb;
		// $wpdb->query( "DELETE FROM `" . $wpdb->prefix . "options` WHERE `option_name` LIKE ('_transient%_feed_%')" );
	}

	public function feed_getImage($size) {
		global $post;
		$image = false;
		if (empty($size)) {
			$size = 'full';
		}

		if (function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID)) {
			$thumbnail_id = get_post_thumbnail_id($post->ID);
			if (!empty($thumbnail_id)) {
				$image = wp_get_attachment_image_src($thumbnail_id, $size);
				$image_meta = wp_get_attachment_metadata($thumbnail_id, false);
				if ($image_meta!=false) {
					$image['meta'] = $image_meta['image_meta'];
				} else {
					$image['meta'] = array('aperture'=>'n/a', 'credit'=>'', 'camera'=>'n/a', 'caption'=>'', 'created_timestamp'=>time(), 'copyright'=>'', 'focal_length'=>'n/a', 'iso'=>'n/a', 'shutter_speed'=>'n/a', 'title'=>'');
				}

				$image_attch = get_post($thumbnail_id);
				$image['meta_desc'] = array(
					'title' => strip_tags($image_attch->post_excerpt),
					'alt' => strip_tags(get_post_meta($image_attch->ID, '_wp_attachment_image_alt', true)),
					'description' => strip_tags($image_attch->post_content),
					'description_html' => $image_attch->post_content
				);

				$image[4] = 0;
				if ($size == 'full') {
					$image[4] = @filesize(get_attached_file($thumbnail_id)); // add file size
				}
			}
		}

		return ($image);
	}

	public function feed_addNameSpace() {
		echo 'xmlns:media="http://search.yahoo.com/mrss/"';
	}

	public function feed_addMeta($for_comments) {
		global $post;

		if (!$for_comments) {
			$image_enclosure = $this->feed_getImage('full');
			$image_mediaContent = $this->feed_getImage($this->CFG['betterrssfeedscfg_tags_addTag_mediaContent_size']);
			$image_mediaThumbnail = $this->feed_getImage($this->CFG['betterrssfeedscfg_tags_addTag_mediaThumbnail_size']);
			if ($image_enclosure !== false) {
				if ($this->CFG['betterrssfeedscfg_tags_addTag_enclosure'] == 1) {
					echo '<enclosure url="' . esc_url($image_enclosure[0]) . '" length="' . $image_enclosure[4] . '" type="image/jpg" />' . "\n";
				}

				if ($this->CFG['betterrssfeedscfg_tags_addTag_mediaContent'] == 1) {
					$image_copyright = strip_tags(get_bloginfo('name'));
					if (!empty($image_mediaContent['meta']['copyright'])) {
						$image_copyright = $image_mediaContent['meta']['copyright'];
					}

					if (strpos($image_mediaContent[0], 'http') !== false) {
						$image_mediaContent_URL = esc_url($image_mediaContent[0]);
					} else {
						$image_mediaContent_URL = esc_url(home_url($image_mediaContent[0]));
					}
					echo '<media:content xmlns:media="http://search.yahoo.com/mrss/" url="' . esc_url($image_mediaContent_URL) . '" width="' . $image_mediaContent[1] . '" height="' . $image_mediaContent[2] . '" medium="image" type="image/jpeg">' . "\n";
					if ($this->CFG['betterrssfeedscfg_metaExtension_addMediaMetaCopyright'] != 1) {
						echo '	<media:copyright>' . htmlspecialchars($image_copyright) . '</media:copyright>' . "\n";
						echo '	<media:title>' . htmlspecialchars($image_mediaContent['meta_desc']['title']) . '</media:title>' . "\n";
						echo '	<media:description type="html"><![CDATA[' . $image_mediaContent['meta_desc']['description_html'] . ']]></media:description>' . "\n";
					}
					echo '</media:content>' . "\n";
				}

				if ($this->CFG['betterrssfeedscfg_tags_addTag_mediaThumbnail'] == 1) {
					echo '<media:thumbnail xmlns:media="http://search.yahoo.com/mrss/" url="'. esc_url($image_mediaThumbnail[0]) . '" width="' . $image_mediaThumbnail[1] . '" height="' . $image_mediaThumbnail[2] . '" />' . "\n";
				}
			}
		}
	}
	public function feed_update_content($content) {
		global $post;
		$content_new = '';

		if (has_post_thumbnail($post->ID)) {
			$image = $this->feed_getImage($this->CFG['betterrssfeedscfg_description_extend_content_size']);
			$content_new .= '<div style="margin: 5px 5% 10px 5%;"><img src="' . $image[0] . '" width="' . $image[1] . '" height="' . $image[2] . '" title="'.$image['meta_desc']['title'].'" alt="'.$image['meta_desc']['alt'].'" /></div>';
		}

		if ($this->CFG['betterrssfeedscfg_fulltext_fulltext_add2description'] == 1) {
			$content_new_full = apply_filters('the_content', get_the_content());
			$content_new_full = str_replace(']]>', ']]&gt;', $content_new_full);
			$content_new .= '<div>' . $content_new_full . '</div>';
		} else {
			$content_new .= '<div>' . $content . '</div>';
		}

		return $content_new;
	}

	public function footer_credits() {
		if (!function_exists('is_user_logged_in') || is_user_logged_in() || !$this->is_bot()) {
			return;
		}

		if (function_exists('is_front_page') && is_front_page()) {
			echo 'RSS Plugin by <a href="https://leojiang.com">Leo</a>';
		}
	}

	public function feed_update_content_injectAd($content) {
		global $post;
		$content_ad = '';
		$content_new = '';

		$split_after = $this->CFG['betterrssfeedscfg_inrssAd_inrssAd_injectAfter'];
		if (($split_after < 1) || ($split_after > 8)) {
			$split_after = 2;
		}

		$content_ad .= '<br/><div style="margin: 10px 5%; text-align: center;">';
		$content_ad .= '<em style="display: block; text-align: right;">advertisement: </em><br/>';
		$content_ad .= '<a href="' . $this->CFG['betterrssfeedscfg_inrssAd_inrssAd_link'] . '" target="_blank" style="text-decoration: none;">';
		$content_ad .= '<img src="' . $this->CFG['betterrssfeedscfg_inrssAd_inrssAd_img'] . '" width="90%" style="width: 90%; max-width: 700px;" />';
		$content_ad .= '<br/><em style="display: block; text-align: center;">' . $this->CFG['betterrssfeedscfg_inrssAd_inrssAd_title'] . '</em>';
		$content_ad .= '</a>';
		$content_ad .= '</div><br/>';

		$tmp = $content;
		$tmp = str_replace('</p>', '', $tmp); // drop all </p> - we don't need them ;)
		$array = explode('<p>', $tmp); // split by <p> tag
		$tmp = '';
		$max = sizeof($array);

		if ($max > ($split_after + 1)) {
			// add after nth <p>
			for ($loop=0; $loop<($split_after + 1); $loop++) {
				$content_new .= '<p>' . $array[$loop] . '</p>';
			}
			$content_new .= $content_ad;
			for ($loop=($split_after + 1); $loop<($max + 1); $loop++) {
				$content_new .= '<p>' . $array[$loop] . '</p>';
			}
		} else {
			// add to end of post...
			$content_new = $content;
			$content_new .= $content_ad;
		}

		return $content_new;
	}

	public function fulltext_override() {
		add_filter('pre_option_rss_use_excerpt', array( $this, 'fulltext_override_filter' ));
	}
	public function fulltext_override_filter() {
		return 0;
	}

	/*--------------------------------------------*
	 * DEBUG - List Hooks
	 * Code by Andrey Savchenko, http://www.rarst.net/script/debug-wordpress-hooks/
	*---------------------------------------------*/
	public function list_hooks($filter = false) {
		global $wp_filter;

		$hooks = $wp_filter;
		ksort($hooks);

		foreach ($hooks as $tag => $hook) {
			if (false === $filter || false !== strpos($tag, $filter)) {
				$this->dump_hook($tag, $hook);
			}
		}
	}

	public function list_live_hooks($hook = false) {
		if (false === $hook) {
			$hook = 'all';
		}

		add_action($hook, array( $this, 'list_hook_details' ), -1);
	}

	public function list_hook_details($input = null) {
		global $wp_filter;

		$tag = current_filter();
		if (isset($wp_filter[$tag])) {
			$this->dump_hook($tag, $wp_filter[$tag]);
		}

		return $input;
	}

	public function dump_hook($tag, $hook) {
		ksort($hook);

		echo "<pre>&gt;&gt;&gt;&gt;&gt;\t<strong>$tag</strong><br />";

		foreach ($hook as $priority => $functions) {
			echo $priority;

			foreach ($functions as $function) {
				if ($function['function'] != 'list_hook_details') {
					echo "\t";

					if (is_string($function['function'])) {
						echo $function['function'];
					} elseif (is_string($function['function'][0])) {
						echo $function['function'][0] . ' -> ' . $function['function'][1];
					} elseif (is_object($function['function'][0])) {
						echo '(object) ' . get_class($function['function'][0]) . ' -> ' . $function['function'][1];
					} else {
						print_r($function);
					}

					echo ' (' . $function['accepted_args'] . ') <br />';
				}
			}
		}

		echo '</pre>';
	}

	public function is_bot() {
		if (!isset($_SERVER['HTTP_USER_AGENT'])) {
			return false;
		}

		$crawlers_agents = strtolower('bot|crawl|slurp|spider|mediapartners');
		$crawlers = explode('|', $crawlers_agents);
		foreach($crawlers as $crawler) {
			if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), trim($crawler)) !== false) {
				return true;
			}
		}
		return false;
	}

	public function display_template($arg) {
		if ($this->CFG['betterrssfeedscfg_style_stylefeeds'] && is_feed() && (
			strpos(get_query_var('feed'), 'feed') === 0 || strpos(get_query_var('feed'), 'rss') === 0
			) && $arg === 'rss2') {
			echo '<?xml-stylesheet type="text/xsl" href="' . get_home_url() . '/wp-content/plugins/xslt/public/template.xsl"?>';
		}
	}

	public function feed_namespace() {
		if ($this->CFG['betterrssfeedscfg_style_stylefeeds']) {
			echo 'xmlns:rssFeedStyles="http://www.wordpress.org/ns/xslt#"';
			echo "\n";
		}
	}

	public function feed_content_type($content_type, $type) {
		if ($this->CFG['betterrssfeedscfg_style_stylefeeds'] && $type === 'rss2') {
			return 'text/xml';
		}
		return $content_type;
	}
} // end class

$Better_Rss_Feeds = new Better_Rss_Feeds();
