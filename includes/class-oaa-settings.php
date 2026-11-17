<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings (Settings → OGNIVID AI Assistant).
 */
class OAA_Settings {

	const OPTION = 'oaa_settings';

	public static function defaults() {
		return array(
			'enabled'          => 1,
			'test_mode'        => 1,          // only logged-in admins see the widget
			'provider'         => 'anthropic', // see OAA_Provider::providers()
			'api_key'          => '',
			'model'            => 'claude-opus-5-5',
			'effort'           => 'medium',
			'custom_base_url'  => '',
			'recipient'        => 'hello@ognivid.com',
			'whatsapp'         => '6285280052326',
			'position'         => 'left',     // left | right
			'max_turns'        => 25,         // user messages per conversation
			'max_chars'        => 1500,       // characters per user message
			'hourly_per_ip'    => 40,         // user messages per IP per hour
			'daily_sessions'   => 60,         // AI conversations per day (site-wide); form after that
			'copy_to_customer' => 1,
		);
	}

	public static function get( $key = null ) {
		$opts = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		return null === $key ? $opts : ( isset( $opts[ $key ] ) ? $opts[ $key ] : null );
	}

	public static function activate() {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, self::defaults(), '', false );
		}
		if ( false === get_option( 'oaa_salt' ) ) {
			add_option( 'oaa_salt', wp_generate_password( 32, true, true ), '', false );
		}
		OAA_Brief::register_post_type();
		OAA_Brief::ensure_storage();
	}

	public static function admin_menu() {
		add_options_page( 'OGNIVID AI Assistant', 'OGNIVID AI Assistant', 'manage_options', 'oaa-settings', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting(
			'oaa_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function sanitize( $in ) {
		$old = self::get();
		$d   = self::defaults();
		$out = array();
		$out['enabled']          = empty( $in['enabled'] ) ? 0 : 1;
		$out['test_mode']        = empty( $in['test_mode'] ) ? 0 : 1;
		$out['copy_to_customer'] = empty( $in['copy_to_customer'] ) ? 0 : 1;
		$prov                    = isset( $in['provider'] ) ? sanitize_key( $in['provider'] ) : $d['provider'];
		$out['provider']         = array_key_exists( $prov, OAA_Provider::providers() ) ? $prov : $d['provider'];
		// Keep the stored key when the field is left blank (it is never echoed back to the page).
		$key                     = isset( $in['api_key'] ) ? trim( wp_unslash( $in['api_key'] ) ) : '';
		$out['api_key']          = '' === $key ? $old['api_key'] : sanitize_text_field( $key );
		if ( ! empty( $in['clear_key'] ) ) {
			$out['api_key'] = '';
		}
		$out['model']            = isset( $in['model'] ) && '' !== trim( $in['model'] ) ? sanitize_text_field( $in['model'] ) : $d['model'];
		$eff                     = isset( $in['effort'] ) ? sanitize_key( $in['effort'] ) : $d['effort'];
		$out['effort']           = in_array( $eff, array( 'low', 'medium', 'high' ), true ) ? $eff : $d['effort'];
		$out['custom_base_url']  = isset( $in['custom_base_url'] ) ? esc_url_raw( trim( $in['custom_base_url'] ) ) : '';
		$out['recipient']        = isset( $in['recipient'] ) && is_email( trim( $in['recipient'] ) ) ? sanitize_email( $in['recipient'] ) : $d['recipient'];
		$out['whatsapp']         = isset( $in['whatsapp'] ) ? preg_replace( '/\D+/', '', $in['whatsapp'] ) : '';
		$out['position']         = isset( $in['position'] ) && 'right' === $in['position'] ? 'right' : 'left';
		foreach ( array( 'max_turns' => array( 5, 60 ), 'max_chars' => array( 200, 5000 ), 'hourly_per_ip' => array( 5, 500 ), 'daily_sessions' => array( 1, 5000 ) ) as $k => $r ) {
			$v         = isset( $in[ $k ] ) ? absint( $in[ $k ] ) : $d[ $k ];
			$out[ $k ] = max( $r[0], min( $r[1], $v ) );
		}
		return $out;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o      = self::get();
		$n      = self::OPTION;
		$today  = (int) get_transient( 'oaa_daily_' . wp_date( 'Ymd' ) );
		?>
		<div class="wrap">
			<h1>OGNIVID AI Assistant</h1>
			<p>Intake assistant that interviews visitors and emails you a Project Brief (HTML + PDF). Briefs are also saved under <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=oaa_brief' ) ); ?>">AI Briefs</a>.</p>
			<p><strong>AI conversations today:</strong> <?php echo esc_html( $today ); ?> / <?php echo esc_html( $o['daily_sessions'] ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'oaa_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr><th>Status</th><td>
						<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[enabled]" value="1" <?php checked( $o['enabled'] ); ?>> Show the assistant on the website</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[test_mode]" value="1" <?php checked( $o['test_mode'] ); ?>> <strong>Test mode</strong>: only logged-in administrators can see it</label>
					</td></tr>
					<tr><th>AI provider</th><td>
						<select name="<?php echo esc_attr( $n ); ?>[provider]">
							<?php foreach ( OAA_Provider::providers() as $k => $pv ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $o['provider'], $k ); ?>><?php echo esc_html( $pv['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">If the provider fails, runs out of credit or hits the daily limit, visitors automatically get the brief form instead.</p>
					</td></tr>
					<tr><th>API key</th><td>
						<input type="password" autocomplete="new-password" class="regular-text" name="<?php echo esc_attr( $n ); ?>[api_key]" value="" placeholder="<?php echo $o['api_key'] ? esc_attr( 'Saved (…' . substr( $o['api_key'], -4 ) . '). Leave blank to keep.' ) : 'Paste your API key'; ?>">
						<?php if ( $o['api_key'] ) : ?><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[clear_key]" value="1"> Remove saved key</label><?php endif; ?>
						<p class="description">Where to get a key: <?php $kk = array(); foreach ( OAA_Provider::providers() as $pv ) { if ( $pv['keys'] && 'your provider' !== $pv['keys'] ) { $kk[] = esc_html( $pv['keys'] ); } } echo implode( ' · ', $kk ); // phpcs:ignore ?>. The key is stored on your server only.</p>
					</td></tr>
					<tr><th>Model</th><td>
						<input type="text" class="regular-text" list="oaa-models" name="<?php echo esc_attr( $n ); ?>[model]" value="<?php echo esc_attr( $o['model'] ); ?>">
						<datalist id="oaa-models"><?php foreach ( OAA_Provider::providers()['anthropic']['models'] as $mid ) { echo '<option value="' . esc_attr( $mid ) . '">'; } ?></datalist>
						<p class="description">Anthropic: claude-opus-5-5 (best), claude-sonnet-5-5, claude-haiku-5-5 (cheapest). Other providers: type a model ID from that provider’s model list (for example an OpenAI GPT model for ChatGPT).</p>
					</td></tr>
					<tr><th>Effort (Claude)</th><td>
						<select name="<?php echo esc_attr( $n ); ?>[effort]"><?php foreach ( array( 'low', 'medium', 'high' ) as $e ) : ?><option value="<?php echo esc_attr( $e ); ?>" <?php selected( $o['effort'], $e ); ?>><?php echo esc_html( ucfirst( $e ) ); ?></option><?php endforeach; ?></select>
						<p class="description">Medium is a good balance of answer quality, speed and cost for interviews.</p>
					</td></tr>
					<tr><th>Custom endpoint</th><td><input type="url" class="regular-text" name="<?php echo esc_attr( $n ); ?>[custom_base_url]" value="<?php echo esc_attr( $o['custom_base_url'] ); ?>" placeholder="https://example.com/v1/chat/completions"><p class="description">Only for “Custom OpenAI-compatible endpoint”.</p></td></tr>
					<tr><th>Send briefs to</th><td><input type="email" class="regular-text" name="<?php echo esc_attr( $n ); ?>[recipient]" value="<?php echo esc_attr( $o['recipient'] ); ?>"><br><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[copy_to_customer]" value="1" <?php checked( $o['copy_to_customer'] ); ?>> Email a copy of the brief to the customer</label></td></tr>
					<tr><th>Your WhatsApp number</th><td><input type="text" class="regular-text" name="<?php echo esc_attr( $n ); ?>[whatsapp]" value="<?php echo esc_attr( $o['whatsapp'] ); ?>"><p class="description">Shown to customers as a follow-up link (international format, e.g. 62852…).</p></td></tr>
					<tr><th>Button position</th><td><select name="<?php echo esc_attr( $n ); ?>[position]"><option value="left" <?php selected( $o['position'], 'left' ); ?>>Bottom left</option><option value="right" <?php selected( $o['position'], 'right' ); ?>>Bottom right</option></select></td></tr>
					<tr><th>Limits</th><td>
						<label>Messages per conversation <input type="number" min="5" max="60" name="<?php echo esc_attr( $n ); ?>[max_turns]" value="<?php echo esc_attr( $o['max_turns'] ); ?>" class="small-text"></label><br>
						<label>Characters per message <input type="number" min="200" max="5000" name="<?php echo esc_attr( $n ); ?>[max_chars]" value="<?php echo esc_attr( $o['max_chars'] ); ?>" class="small-text"></label><br>
						<label>Messages per visitor (IP) per hour <input type="number" min="5" max="500" name="<?php echo esc_attr( $n ); ?>[hourly_per_ip]" value="<?php echo esc_attr( $o['hourly_per_ip'] ); ?>" class="small-text"></label><br>
						<label>AI conversations per day (whole site) <input type="number" min="1" max="5000" name="<?php echo esc_attr( $n ); ?>[daily_sessions]" value="<?php echo esc_attr( $o['daily_sessions'] ); ?>" class="small-text"></label>
					</td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
