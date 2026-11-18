<?php
/**
 * Uninstall: remove plugin options and cached counters.
 * Saved briefs (AI Briefs) and their PDFs are kept so no customer data is lost by accident;
 * delete them from wp-admin → AI Briefs before uninstalling if you want them gone.
 *
 * @package OgnividAIAssistant
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ognivid_ai_settings' );
delete_option( 'ognivid_ai_salt' );
delete_option( 'ognivid_ai_db_version' );
