<?php
global $wpsf_settings, $Better_Rss_Feeds;
$CFG = wpsf_get_settings($Better_Rss_Feeds->plugin_path .'settings/better-rss-feeds-cfg.php');

$thumbs = array( 'full' => '= Full size =' );
$thumbs_raw = get_intermediate_image_sizes();
foreach ($thumbs_raw as $th) {
	$thumbs[$th] = $th;
}

$wpsf_settings[] = array(
	'section_id' => 'style',
	'section_title' => 'Style RSS feeds',
	'section_description' => 'Make RSS feeds look pretty.',
	'section_order' => 5,
	'fields' => array(
		array(
			'id' => 'stylefeeds',
			'title' => 'Style RSS Feeds',
			'desc' => '',
			'type' => 'checkbox',
			'std' => 1
		)
	)
);

$wpsf_settings[] = array(
	'section_id' => 'tags',
	'section_title' => 'Add Image RSS Feed tags',
	'section_description' => '"enclosure" and "media:content" / "media:thumbnail" tags in RSS feed are used to tell RSS parser about post thumbnail.',
	'section_order' => 10,
	'fields' => array(
		array(
			'id' => 'addTag_enclosure',
			'title' => 'Add "enclosure" tag',
			'desc' => '',
			'type' => 'checkbox',
			'std' => 1
		),
		array(
			'id' => 'addTag_mediaContent',
			'title' => 'Add "media:content" tag',
			'desc' => '',
			'type' => 'checkbox',
			'std' => 1
		),
		array(
			'id' => 'addTag_mediaContent_size',
			'title' => ' - image size',
			'type' => 'select',
			'type' => 'select',
			'choices' => $thumbs
		),
		array(
			'id' => 'addTag_mediaThumbnail',
			'title' => 'Add "media:thumbnail" tag',
			'desc' => '',
			'type' => 'checkbox',
			'std' => 1
		),
		array(
			'id' => 'addTag_mediaThumbnail_size',
			'title' => ' - image size',
			'type' => 'select',
			'type' => 'select',
			'choices' => $thumbs
		)
	)
);

$wpsf_settings[] = array(
	'section_id' => 'description',
	'section_title' => 'Extend HTML content',
	'section_description' => 'This will extend the HTML code of "description" and "content:encoded" tags with 90% wide image before the text.',
	'section_order' => 20,
	'fields' => array(
		array(
			'id' => 'extend_description',
			'title' => 'Extend "description" (excerpt)',
			'desc' => '',
			'type' => 'checkbox',
			'std' => 1
		),
		array(
			'id' => 'extend_content',
			'title' => 'Extend "content:encoded" HTML',
			'desc' => '',
			'type' => 'checkbox',
			'std' => 1
		),
		array(
			'id' => 'extend_content_size',
			'title' => ' - image size',
			'type' => 'select',
			'type' => 'select',
			'choices' => $thumbs
		)
	)
);

$rss_use_excerpt = get_option('rss_use_excerpt');

$wpsf_settings[] = array(
	'section_id' => 'fulltext',
	'section_title' => 'RSS Feed fulltext override',
	'section_description' => 'Override "excerpt only" RSS feed when requested with "secret" key.',
	'section_order' => 25,
	'fields' => array(
		array(
			'id' => 'fulltext_wp_option',
			'title' => 'WordPress RSS Feed mode',
			'desc' => '',
			'type' => 'custom',
			'std' => $rss_use_excerpt ? 'Excerpt only - there is only excerpt in the standard RSS Feed...<br />However, requesting feed url with special "secret key" will display full content of each post (great for services like Google Currents).' : 'Fulltext - your feed already contains whole post content.'
		),
		array(
			'id' => 'fulltext_override',
			'title' => 'Enable fulltext override',
			'desc' => $rss_use_excerpt ? '<em>When enabled, you\'re RSS feed content will be full HTML.' : '<em>You don\'t need to override WordPress settings - your feed already contains full post content.</em>' ,
			'type' => 'checkbox',
			'std' => 0
		),
		array(
			'id' => 'fulltext_add2description',
			'title' => 'Fulltext in &lt;description&gt; tag ',
			'desc' => '<em>When enabled, &lt;description&gt; tag in RSS feed will be replaced with full article.</em>' ,
			'type' => 'checkbox',
			'std' => 0
		)
	)
);
