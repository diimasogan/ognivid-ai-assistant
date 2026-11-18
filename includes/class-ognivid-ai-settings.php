<?php
/**
 * Settings page and options.
 *
 * @package OgnividAIAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings (Settings → OGNIVID AI Assistant).
 */
class Ognivid_AI_Settings {

	const OPTION     = 'ognivid_ai_settings';
	const DB_VERSION = 2;

	/**
	 * Default settings. Business details start from the site's own name and admin email.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'          => 1,
			'test_mode'        => 1, // Only logged-in administrators see the widget until this is switched off.
			'provider'         => 'anthropic',
			'api_key'          => '',
			'model'            => 'claude-opus-5-5',
			'effort'           => 'medium',
			'custom_base_url'  => '',
			'business_name'    => get_bloginfo( 'name' ),
			'business_profile' => '',
			'services'         => "Consulting\nImplementation\nSupport & maintenance",
			'instructions'     => '',
			'greeting_en'      => '',
			'greeting_id'      => '',
			'recipient'        => get_option( 'admin_email' ),
			'whatsapp'         => '',
			'position'         => 'left',
			'accent'           => '#7cff3b',
			'max_turns'        => 25,
			'max_chars'        => 1500,
			'hourly_per_ip'    => 40,
			'daily_sessions'   => 60,
			'copy_to_customer' => 1,
		);
	}

	/**
	 * Read one setting (or all of them).
	 *
	 * @param string|null $key Setting key.
	 * @return mixed
	 */
	public static function get( $key = null ) {
		$opts = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		return null === $key ? $opts : ( isset( $opts[ $key ] ) ? $opts[ $key ] : null );
	}

	/**
	 * Services list as an array (one per line in the settings).
	 *
	 * @return string[]
	 */
	public static function services() {
		$list = array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', (string) self::get( 'services' ) ) ) );
		return array_slice( array_values( array_unique( $list ) ), 0, 12 );
	}

	/** Activation: create options, salt and storage. */
	public static function activate() {
		self::maybe_upgrade();
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, self::defaults(), '', false );
		}
		if ( false === get_option( 'ognivid_ai_salt' ) ) {
			add_option( 'ognivid_ai_salt', wp_generate_password( 32, true, true ), '', false );
		}
		update_option( 'ognivid_ai_db_version', self::DB_VERSION, false );
		Ognivid_AI_Brief::register_post_type();
		Ognivid_AI_Brief::ensure_storage();
	}

	/**
	 * One-time upgrade from the 1.x storage names (oaa_*), used by early installs.
	 */
	public static function maybe_upgrade() {
		if ( (int) get_option( 'ognivid_ai_db_version', 0 ) >= self::DB_VERSION ) {
			return;
		}
		global $wpdb;
		$old = get_option( 'oaa_settings' );
		if ( is_array( $old ) && false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, $old, '', false );
		}
		$salt = get_option( 'oaa_salt' );
		if ( $salt && false === get_option( 'ognivid_ai_salt' ) ) {
			add_option( 'ognivid_ai_salt', $salt, '', false );
		}
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $wpdb->posts, array( 'post_type' => 'ognivid_ai_brief' ), array( 'post_type' => 'oaa_brief' ) );
		$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = REPLACE(meta_key, '_oaa_', '_ognivid_ai_') WHERE meta_key LIKE '\\_oaa\\_%'" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
		delete_option( 'oaa_settings' );
		delete_option( 'oaa_salt' );
		update_option( 'ognivid_ai_db_version', self::DB_VERSION, false );
	}

	/** Settings page under Settings. */
	public static function admin_menu() {
		add_options_page( 'OGNIVID AI Assistant', 'OGNIVID AI Assistant', 'manage_options', 'ognivid-ai-assistant', array( __CLASS__, 'page' ) );
	}

	/** Register the option. */
	public static function register() {
		register_setting(
			'ognivid_ai_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize the submitted settings.
	 *
	 * @param array $in Raw input.
	 * @return array
	 */
	public static function sanitize( $in ) {
		$in  = is_array( $in ) ? wp_unslash( $in ) : array();
		$old = self::get();
		$d   = self::defaults();
		$out = array();

		$out['enabled']          = empty( $in['enabled'] ) ? 0 : 1;
		$out['test_mode']        = empty( $in['test_mode'] ) ? 0 : 1;
		$out['copy_to_customer'] = empty( $in['copy_to_customer'] ) ? 0 : 1;

		$prov            = isset( $in['provider'] ) ? sanitize_key( $in['provider'] ) : $d['provider'];
		$out['provider'] = array_key_exists( $prov, Ognivid_AI_Provider::providers() ) ? $prov : $d['provider'];

		// Keep the stored key when the field is left blank (it is never printed back to the page).
		$key            = isset( $in['api_key'] ) ? trim( $in['api_key'] ) : '';
		$out['api_key'] = '' === $key ? $old['api_key'] : sanitize_text_field( $key );
		if ( ! empty( $in['clear_key'] ) ) {
			$out['api_key'] = '';
		}

		$out['model']           = isset( $in['model'] ) && '' !== trim( $in['model'] ) ? sanitize_text_field( $in['model'] ) : $d['model'];
		$eff                    = isset( $in['effort'] ) ? sanitize_key( $in['effort'] ) : $d['effort'];
		$out['effort']          = in_array( $eff, array( 'low', 'medium', 'high' ), true ) ? $eff : $d['effort'];
		$out['custom_base_url'] = isset( $in['custom_base_url'] ) ? esc_url_raw( trim( $in['custom_base_url'] ) ) : '';

		$out['business_name']    = isset( $in['business_name'] ) && '' !== trim( $in['business_name'] ) ? sanitize_text_field( $in['business_name'] ) : $d['business_name'];
		foreach ( array( 'business_profile' => 6000, 'services' => 1500, 'instructions' => 4000, 'greeting_en' => 600, 'greeting_id' => 600 ) as $k => $max ) {
			$out[ $k ] = isset( $in[ $k ] ) ? mb_substr( sanitize_textarea_field( $in[ $k ] ), 0, $max ) : $d[ $k ];
		}

		$out['recipient'] = isset( $in['recipient'] ) && is_email( trim( $in['recipient'] ) ) ? sanitize_email( $in['recipient'] ) : $d['recipient'];
		$out['whatsapp']  = isset( $in['whatsapp'] ) ? preg_replace( '/\D+/', '', $in['whatsapp'] ) : '';
		$out['position']  = isset( $in['position'] ) && 'right' === $in['position'] ? 'right' : 'left';
		$accent           = isset( $in['accent'] ) ? sanitize_hex_color( $in['accent'] ) : '';
		$out['accent']    = $accent ? $accent : $d['accent'];

		foreach ( array( 'max_turns' => array( 5, 60 ), 'max_chars' => array( 200, 5000 ), 'hourly_per_ip' => array( 5, 500 ), 'daily_sessions' => array( 1, 5000 ) ) as $k => $r ) {
			$v         = isset( $in[ $k ] ) ? absint( $in[ $k ] ) : $d[ $k ];
			$out[ $k ] = max( $r[0], min( $r[1], $v ) );
		}
		return $out;
	}

	/** Render the settings page. */
	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o     = self::get();
		$n     = self::OPTION;
		$today = (int) get_transient( 'ognivid_ai_daily_' . wp_date( 'Ymd' ) );
		$keys  = array();
		foreach ( Ognivid_AI_Provider::providers() as $pv ) {
			if ( $pv['keys'] ) {
				$keys[] = $pv['keys'];
			}
		}
		$field = function ( $k ) use ( $n ) {
			return $n . '[' . $k . ']';
		};
		?>
		<div class="wrap">
			<h1>OGNIVID AI Assistant</h1>
			<p>An AI intake assistant that interviews visitors and emails you a structured project brief (HTML + PDF). Briefs are also saved under <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ognivid_ai_brief' ) ); ?>">AI Briefs</a>.</p>
			<p><strong>AI conversations today:</strong> <?php echo esc_html( $today ); ?> / <?php echo esc_html( $o['daily_sessions'] ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'ognivid_ai_settings_group' ); ?>

				<h2 class="title">Status</h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row">Visibility</th><td>
						<label><input type="checkbox" name="<?php echo esc_attr( $field( 'enabled' ) ); ?>" value="1" <?php checked( $o['enabled'] ); ?>> Show the assistant on the website</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $field( 'test_mode' ) ); ?>" value="1" <?php checked( $o['test_mode'] ); ?>> <strong>Test mode</strong>: only logged-in administrators can see and use it</label>
					</td></tr>
				</table>

				<h2 class="title">Your business</h2>
				<p>The assistant uses this to introduce your business, understand requests and suggest relevant services.</p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="oaa-bn">Business name</label></th><td><input id="oaa-bn" type="text" class="regular-text" name="<?php echo esc_attr( $field( 'business_name' ) ); ?>" value="<?php echo esc_attr( $o['business_name'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="oaa-bp">About your business</label></th><td><textarea id="oaa-bp" class="large-text" rows="6" name="<?php echo esc_attr( $field( 'business_profile' ) ); ?>" placeholder="What you do, who you help, notable work, location…"><?php echo esc_textarea( $o['business_profile'] ); ?></textarea></td></tr>
					<tr><th scope="row"><label for="oaa-sv">Services</label></th><td><textarea id="oaa-sv" class="large-text" rows="4" name="<?php echo esc_attr( $field( 'services' ) ); ?>"><?php echo esc_textarea( $o['services'] ); ?></textarea><p class="description">One per line. Each brief is tagged with the relevant services.</p></td></tr>
					<tr><th scope="row"><label for="oaa-in">Extra instructions</label></th><td><textarea id="oaa-in" class="large-text" rows="4" name="<?php echo esc_attr( $field( 'instructions' ) ); ?>" placeholder="Optional. For example: always ask about the deadline; we only work with companies."><?php echo esc_textarea( $o['instructions'] ); ?></textarea></td></tr>
					<tr><th scope="row">Greeting</th><td>
						<textarea class="large-text" rows="2" name="<?php echo esc_attr( $field( 'greeting_en' ) ); ?>" placeholder="English greeting (optional)"><?php echo esc_textarea( $o['greeting_en'] ); ?></textarea>
						<textarea class="large-text" rows="2" name="<?php echo esc_attr( $field( 'greeting_id' ) ); ?>" placeholder="Indonesian greeting (optional)"><?php echo esc_textarea( $o['greeting_id'] ); ?></textarea>
						<p class="description">First message in the chat window. Leave empty for a default greeting that uses your business name.</p>
					</td></tr>
				</table>

				<h2 class="title">AI provider</h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row">Provider</th><td>
						<select name="<?php echo esc_attr( $field( 'provider' ) ); ?>">
							<?php foreach ( Ognivid_AI_Provider::providers() as $k => $pv ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $o['provider'], $k ); ?>><?php echo esc_html( $pv['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">If the provider fails, runs out of credit or hits the daily limit, visitors automatically get the brief form instead. No data is sent to any provider until you add an API key.</p>
					</td></tr>
					<tr><th scope="row">API key</th><td>
						<input type="password" autocomplete="new-password" class="regular-text" name="<?php echo esc_attr( $field( 'api_key' ) ); ?>" value="" placeholder="<?php echo esc_attr( $o['api_key'] ? 'Saved (…' . substr( $o['api_key'], -4 ) . '). Leave blank to keep.' : 'Paste your API key' ); ?>">
						<?php if ( $o['api_key'] ) : ?>
							<label><input type="checkbox" name="<?php echo esc_attr( $field( 'clear_key' ) ); ?>" value="1"> Remove saved key</label>
						<?php endif; ?>
						<p class="description">Where to get a key: <?php echo esc_html( implode( ' · ', $keys ) ); ?>. The key is stored on your server only.</p>
					</td></tr>
					<tr><th scope="row">Model</th><td>
						<input type="text" class="regular-text" list="ognivid-ai-models" name="<?php echo esc_attr( $field( 'model' ) ); ?>" value="<?php echo esc_attr( $o['model'] ); ?>">
						<datalist id="ognivid-ai-models">
							<?php foreach ( Ognivid_AI_Provider::providers()['anthropic']['models'] as $mid ) : ?>
								<option value="<?php echo esc_attr( $mid ); ?>"></option>
							<?php endforeach; ?>
						</datalist>
						<p class="description">Anthropic: claude-opus-5-5 (best), claude-sonnet-5-5, claude-haiku-5-5 (cheapest). Other providers: type a model ID from that provider’s model list.</p>
					</td></tr>
					<tr><th scope="row">Effort (Claude)</th><td>
						<select name="<?php echo esc_attr( $field( 'effort' ) ); ?>">
							<?php foreach ( array( 'low', 'medium', 'high' ) as $e ) : ?>
								<option value="<?php echo esc_attr( $e ); ?>" <?php selected( $o['effort'], $e ); ?>><?php echo esc_html( ucfirst( $e ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td></tr>
					<tr><th scope="row">Custom endpoint</th><td><input type="url" class="regular-text" name="<?php echo esc_attr( $field( 'custom_base_url' ) ); ?>" value="<?php echo esc_attr( $o['custom_base_url'] ); ?>" placeholder="https://example.com/v1/chat/completions"><p class="description">Only for “Custom OpenAI-compatible endpoint”.</p></td></tr>
				</table>

				<h2 class="title">Delivery &amp; appearance</h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row">Send briefs to</th><td><input type="email" class="regular-text" name="<?php echo esc_attr( $field( 'recipient' ) ); ?>" value="<?php echo esc_attr( $o['recipient'] ); ?>"><br><label><input type="checkbox" name="<?php echo esc_attr( $field( 'copy_to_customer' ) ); ?>" value="1" <?php checked( $o['copy_to_customer'] ); ?>> Email a copy of the brief to the customer</label></td></tr>
					<tr><th scope="row">WhatsApp number</th><td><input type="text" class="regular-text" name="<?php echo esc_attr( $field( 'whatsapp' ) ); ?>" value="<?php echo esc_attr( $o['whatsapp'] ); ?>"><p class="description">Optional follow-up link for customers (international format, for example 6281234567890).</p></td></tr>
					<tr><th scope="row">Button position</th><td><select name="<?php echo esc_attr( $field( 'position' ) ); ?>"><option value="left" <?php selected( $o['position'], 'left' ); ?>>Bottom left</option><option value="right" <?php selected( $o['position'], 'right' ); ?>>Bottom right</option></select></td></tr>
					<tr><th scope="row">Accent colour</th><td><input type="text" class="small-text" name="<?php echo esc_attr( $field( 'accent' ) ); ?>" value="<?php echo esc_attr( $o['accent'] ); ?>" pattern="#[0-9a-fA-F]{6}"> <span class="description">Hex colour for buttons and highlights.</span></td></tr>
				</table>

				<h2 class="title">Limits</h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row">Abuse protection</th><td>
						<label>Messages per conversation <input type="number" min="5" max="60" name="<?php echo esc_attr( $field( 'max_turns' ) ); ?>" value="<?php echo esc_attr( $o['max_turns'] ); ?>" class="small-text"></label><br>
						<label>Characters per message <input type="number" min="200" max="5000" name="<?php echo esc_attr( $field( 'max_chars' ) ); ?>" value="<?php echo esc_attr( $o['max_chars'] ); ?>" class="small-text"></label><br>
						<label>Messages per visitor (IP) per hour <input type="number" min="5" max="500" name="<?php echo esc_attr( $field( 'hourly_per_ip' ) ); ?>" value="<?php echo esc_attr( $o['hourly_per_ip'] ); ?>" class="small-text"></label><br>
						<label>AI conversations per day (whole site) <input type="number" min="1" max="5000" name="<?php echo esc_attr( $field( 'daily_sessions' ) ); ?>" value="<?php echo esc_attr( $o['daily_sessions'] ); ?>" class="small-text"></label>
					</td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
