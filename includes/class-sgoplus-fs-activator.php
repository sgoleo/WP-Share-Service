<?php

namespace SGOplus\File_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	public static function activate() {
		// No custom tables needed for this version.
		// Flush rewrite rules for CPT
		\SGOplus\File_Share\CPT::register_post_type_static();
		flush_rewrite_rules();
	}
}
