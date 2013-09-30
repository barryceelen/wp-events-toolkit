<?php
/**
 * @package   Events_Toolkit
 * @author    Barry Ceelen <b@rryceelen.com>
 * @license   GPL-2.0+
 * @link      http://github.com/barryceelen/wp-events-toolkit
 * @copyright 2013 Barry Ceelen
 */
?>
<style media='screen'>
	#adminmenu #menu-posts-<?php echo $post_type; ?> div.wp-menu-image {
		background: url('<?php echo $images_url; ?>menu.png') no-repeat 3px -34px;
	}
	#menu-posts-<?php echo $post_type; ?>:hover .wp-menu-image,
	#menu-posts-<?php echo $post_type; ?>.wp-has-current-submenu .wp-menu-image {
		background-position: 3px -2px !important;
	}
	#icon-edit.icon32-posts-<?php echo $post_type; ?> {
		background: url('<?php echo $images_url; ?>icon.png') no-repeat;
		background-position: -4px -6px !important;
	}
	.mp6 #adminmenu #menu-posts-<?php echo $post_type; ?> div.wp-menu-image {
		margin-top: -2px;
	}
	.mp6 #adminmenu #menu-posts-<?php echo $post_type; ?> div.wp-menu-image:before {
		content: '\f145';
	}
</style>
