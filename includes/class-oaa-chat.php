<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end widget + REST endpoints.
 *
 * Conversation state lives server-side (transient keyed by a random session id), so visitors
 * cannot inject assistant turns and Claude's thinking blocks are replayed unchanged.
 */
class OAA_Chat {

	const NS          = 'ognivid-ai/v1';
	const SESSION_TTL = 7200; // 2 hours

	public static function visible() {
		$o = OAA_Settings::get();
		if ( empty( $o['enabled'] ) || is_admin() ) {
			return false;
		}
		if ( ! empty( $o['test_mode'] ) && ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return true;
	}

	public static function enqueue() {
		if ( ! self::visible() ) {
			return;
		}
		$o = OAA_Settings::get();
		wp_enqueue_style( 'oaa-widget', OAA_URL . 'assets/widget.css', array(), OAA_VERSION );
		wp_enqueue_script( 'oaa-widget', OAA_URL . 'assets/widget.js', array(), OAA_VERSION, true );
		wp_localize_script(
			'oaa-widget',
			'OAA_CFG',
			array(
				'rest'     => esc_url_raw( rest_url( self::NS ) ),
				'position' => $o['position'],
				'aiOn'     => 'off' !== $o['provider'] && '' !== $o['api_key'],
				'maxChars' => (int) $o['max_chars'],
				'wa'       => preg_replace( '/\D+/', '', $o['whatsapp'] ),
				'test'     => ! empty( $o['test_mode'] ),
				// Only logged-in users get a REST nonce (needed for test mode); public cached pages never carry one.
				'nonce'    => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
			)
		);
	}

	public static function render_root() {
		if ( self::visible() ) {
			echo '<div id="oaa-root" data-no-translation></div>';
		}
	}

	public static function routes() {
		register_rest_route( self::NS, '/chat', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'chat' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( self::NS, '/form', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'form' ), 'permission_callback' => '__return_true' ) );
	}

	/* ---------- helpers ---------- */

	private static function ip_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		return substr( hash( 'sha256', $ip . get_option( 'oaa_salt', 'oaa' ) ), 0, 20 );
	}

	/** Increment a counter; return the new value. */
	private static function bump( $key, $ttl ) {
		$v = (int) get_transient( $key ) + 1;
		set_transient( $key, $v, $ttl );
		return $v;
	}

	/** Reject requests that clearly don't come from this site. */
	private static function same_origin() {
		$site = wp_parse_url( home_url(), PHP_URL_HOST );
		foreach ( array( 'HTTP_ORIGIN', 'HTTP_REFERER' ) as $h ) {
			if ( ! empty( $_SERVER[ $h ] ) ) {
				$host = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER[ $h ] ) ), PHP_URL_HOST );
				return $host === $site;
			}
		}
		return false;
	}

	private static function lang( $req ) {
		return 'id' === $req->get_param( 'lang' ) ? 'id' : 'en';
	}

	private static function msg( $lang, $key ) {
		$m = array(
			'en' => array(
				'limit'   => 'You have reached the message limit for now. You can still send us your request with the short form below.',
				'busy'    => 'Our AI assistant is not available right now. Please use the short form below and we will get back to you.',
				'refusal' => 'Sorry, I can\'t help with that here. If you have a project in mind, tell me about it, or use the short form below.',
				'done'    => 'Thank you, %s! Your project brief (ref. %s) has been sent to the OGNIVID team. We will contact you at %s, usually within 1-2 business days.',
				'email'   => 'That email address doesn\'t look valid. Could you check it?',
				'closed'  => 'This conversation is complete. Start a new chat if you want to send another request.',
				'spam'    => 'Something went wrong. Please try again.',
			),
			'id' => array(
				'limit'   => 'Batas pesan untuk saat ini sudah tercapai. Anda tetap bisa mengirim permintaan lewat formulir singkat di bawah.',
				'busy'    => 'Asisten AI kami sedang tidak tersedia. Silakan isi formulir singkat di bawah dan kami akan menghubungi Anda.',
				'refusal' => 'Maaf, saya tidak bisa membantu hal tersebut di sini. Jika Anda punya rencana proyek, ceritakan kepada saya, atau gunakan formulir singkat di bawah.',
				'done'    => 'Terima kasih, %s! Project brief Anda (ref. %s) sudah terkirim ke tim OGNIVID. Kami akan menghubungi Anda di %s, biasanya dalam 1-2 hari kerja.',
				'email'   => 'Alamat email tersebut sepertinya tidak valid. Bisa dicek kembali?',
				'closed'  => 'Percakapan ini sudah selesai. Mulai chat baru jika ingin mengirim permintaan lain.',
				'spam'    => 'Terjadi kesalahan. Silakan coba lagi.',
			),
		);
		return $m[ $lang ][ $key ];
	}

	private static function fallback( $lang, $key, $sid = '' ) {
		return new WP_REST_Response( array( 'sid' => $sid, 'reply' => self::msg( $lang, $key ), 'mode' => 'form', 'done' => false ), 200 );
	}

	private static function transcript( $s ) {
		$lines = array();
		foreach ( $s['log'] as $l ) {
			$lines[] = ( 'user' === $l[0] ? 'Customer: ' : 'Assistant: ' ) . $l[1];
		}
		return implode( "\n\n", $lines );
	}

	/* ---------- endpoints ---------- */

	public static function chat( WP_REST_Request $req ) {
		$o    = OAA_Settings::get();
		$lang = self::lang( $req );
		if ( ! self::same_origin() ) {
			return new WP_REST_Response( array( 'error' => 'origin' ), 403 );
		}
		if ( ! self::visible_for_rest() ) {
			return new WP_REST_Response( array( 'error' => 'disabled' ), 403 );
		}
		$text = trim( sanitize_textarea_field( (string) $req->get_param( 'message' ) ) );
		$sid  = preg_replace( '/[^A-Za-z0-9]/', '', (string) $req->get_param( 'sid' ) );
		if ( '' === $text ) {
			return new WP_REST_Response( array( 'error' => 'empty' ), 400 );
		}
		$text = mb_substr( $text, 0, (int) $o['max_chars'] );

		if ( 'off' === $o['provider'] || '' === $o['api_key'] ) {
			return self::fallback( $lang, 'busy', $sid );
		}
		if ( self::bump( 'oaa_ip_' . self::ip_key() . '_' . gmdate( 'YmdH' ), HOUR_IN_SECONDS ) > (int) $o['hourly_per_ip'] ) {
			return self::fallback( $lang, 'limit', $sid );
		}

		$s = $sid ? get_transient( 'oaa_s_' . $sid ) : false;
		if ( ! is_array( $s ) || $s['provider'] !== $o['provider'] ) {
			$daily = (int) get_transient( 'oaa_daily_' . wp_date( 'Ymd' ) );
			if ( $daily >= (int) $o['daily_sessions'] ) {
				return self::fallback( $lang, 'limit' );
			}
			set_transient( 'oaa_daily_' . wp_date( 'Ymd' ), $daily + 1, DAY_IN_SECONDS );
			$sid = wp_generate_password( 24, false );
			$s   = array( 'provider' => $o['provider'], 'messages' => array(), 'log' => array(), 'turns' => 0, 'done' => false, 'lang' => $lang );
		}
		if ( $s['done'] ) {
			return new WP_REST_Response( array( 'sid' => $sid, 'reply' => self::msg( $lang, 'closed' ), 'done' => true, 'mode' => 'chat' ), 200 );
		}
		if ( $s['turns'] >= (int) $o['max_turns'] ) {
			return self::fallback( $lang, 'limit', $sid );
		}

		$s['turns']++;
		$s['messages'][] = OAA_Provider::user_message( $text );
		$s['log'][]      = array( 'user', $text );

		$reply = '';
		$done  = false;
		for ( $i = 0; $i < 3; $i++ ) { // at most 2 self-corrections (e.g. invalid email)
			$r = OAA_Provider::call( $s['messages'], $s['lang'] );
			if ( ! $r['ok'] ) {
				error_log( 'OGNIVID AI Assistant: ' . $r['error'] ); // phpcs:ignore
				// Drop the unanswered user turn so the history stays valid.
				array_pop( $s['messages'] );
				array_pop( $s['log'] );
				$s['turns']--;
				set_transient( 'oaa_s_' . $sid, $s, self::SESSION_TTL );
				return self::fallback( $lang, $r['refusal'] ? 'refusal' : 'busy', $sid );
			}
			$s['messages'][] = $r['assistant'];
			if ( '' !== $r['text'] ) {
				$reply      = $r['text'];
				$s['log'][] = array( 'assistant', $r['text'] );
			}
			if ( ! $r['tool'] ) {
				break;
			}
			$brief = OAA_Brief::normalize( $r['tool']['input'] );
			if ( ! is_email( $brief['customer']['email'] ) || '' === $brief['customer']['name'] ) {
				$s['messages'][] = OAA_Provider::tool_error( $r['tool'], 'The customer name or email is missing or invalid. Ask the customer for a valid name and email address, then submit again.' );
				continue;
			}
			$id = OAA_Brief::submit( $brief, 'ai', self::transcript( $s ) );
			if ( is_wp_error( $id ) ) {
				$s['messages'][] = OAA_Provider::tool_error( $r['tool'], 'Could not submit: ' . $id->get_error_message() );
				continue;
			}
			$done      = true;
			$s['done'] = true;
			$reply     = sprintf( self::msg( $brief['language'], 'done' ), $brief['customer']['name'], get_post_meta( $id, '_oaa_ref', true ), $brief['customer']['email'] );
			$s['log'][] = array( 'assistant', $reply );
			break;
		}
		set_transient( 'oaa_s_' . $sid, $s, self::SESSION_TTL );
		if ( '' === $reply ) {
			$reply = 'id' === $lang ? 'Bisa dijelaskan sedikit lagi?' : 'Could you tell me a bit more?';
		}
		return new WP_REST_Response( array( 'sid' => $sid, 'reply' => $reply, 'done' => $done, 'mode' => 'chat' ), 200 );
	}

	/** Same visibility rule as the widget, evaluated for REST calls (cookie auth may be absent). */
	private static function visible_for_rest() {
		$o = OAA_Settings::get();
		if ( empty( $o['enabled'] ) ) {
			return false;
		}
		// In test mode, require a logged-in admin (the widget sends the REST nonce for this).
		return empty( $o['test_mode'] ) || current_user_can( 'manage_options' );
	}

	public static function form( WP_REST_Request $req ) {
		$o    = OAA_Settings::get();
		$lang = self::lang( $req );
		if ( ! self::same_origin() || ! self::visible_for_rest() ) {
			return new WP_REST_Response( array( 'error' => 'forbidden' ), 403 );
		}
		// Honeypot + minimum fill time against bots.
		if ( '' !== (string) $req->get_param( 'website' ) || ( time() - (int) $req->get_param( 'ts' ) ) < 4 ) {
			return new WP_REST_Response( array( 'ok' => false, 'reply' => self::msg( $lang, 'spam' ) ), 400 );
		}
		if ( self::bump( 'oaa_form_' . self::ip_key() . '_' . gmdate( 'YmdH' ), HOUR_IN_SECONDS ) > 5 ) {
			return new WP_REST_Response( array( 'ok' => false, 'reply' => self::msg( $lang, 'limit' ) ), 429 );
		}
		$f     = function ( $k ) use ( $req ) {
			return trim( sanitize_textarea_field( (string) $req->get_param( $k ) ) );
		};
		$split = function ( $v ) {
			return array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n;]+/', $v ) ) ) );
		};
		$problem = $f( 'problem' );
		$brief   = array(
			'language'               => $lang,
			'customer'               => array( 'name' => $f( 'name' ), 'email' => $f( 'email' ), 'company' => $f( 'company' ), 'phone' => $f( 'phone' ), 'role' => '' ),
			'title'                  => mb_substr( $f( 'title' ) ? $f( 'title' ) : $problem, 0, 80 ),
			'summary'                => mb_substr( $problem, 0, 300 ),
			'problem'                => $problem,
			'goals'                  => $split( $f( 'goals' ) ),
			'current_systems'        => $split( $f( 'systems' ) ),
			'requirements'           => array(),
			'constraints'            => array(),
			'urgency'                => $f( 'urgency' ),
			'timeline'               => $f( 'timeline' ),
			'budget'                 => $f( 'budget' ),
			'suggested_services'     => $split( $f( 'service' ) ),
			'initial_recommendation' => '',
			'open_questions'         => array(),
			'consent_to_contact'     => true,
		);
		if ( '' === $brief['customer']['name'] || ! is_email( $brief['customer']['email'] ) || mb_strlen( $problem ) < 10 ) {
			return new WP_REST_Response( array( 'ok' => false, 'reply' => self::msg( $lang, 'email' ) ), 400 );
		}
		$id = OAA_Brief::submit( $brief, 'form' );
		if ( is_wp_error( $id ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'reply' => self::msg( $lang, 'spam' ) ), 500 );
		}
		return new WP_REST_Response( array( 'ok' => true, 'reply' => sprintf( self::msg( $lang, 'done' ), $brief['customer']['name'], get_post_meta( $id, '_oaa_ref', true ), $brief['customer']['email'] ) ), 200 );
	}
}
