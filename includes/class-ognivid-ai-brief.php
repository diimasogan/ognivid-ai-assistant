<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Project briefs: storage (private post type), PDF and HTML email.
 */
class Ognivid_AI_Brief {

	const CPT = 'ognivid_ai_brief';

	public static function register_post_type() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array( 'name' => 'AI Briefs', 'singular_name' => 'AI Brief', 'edit_item' => 'Project Brief', 'search_items' => 'Search briefs', 'not_found' => 'No briefs yet' ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-format-chat',
				'menu_position'   => 26,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'show_in_rest'    => false,
			)
		);
	}

	/** Private folder for PDFs. */
	public static function ensure_storage() {
		$up  = wp_upload_dir();
		$dir = trailingslashit( $up['basedir'] ) . 'ognivid-ai-briefs';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if ( $wp_filesystem ) {
			if ( ! $wp_filesystem->exists( $dir . '/.htaccess' ) ) {
				$wp_filesystem->put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n", FS_CHMOD_FILE );
			}
			if ( ! $wp_filesystem->exists( $dir . '/index.php' ) ) {
				$wp_filesystem->put_contents( $dir . '/index.php', "<?php // Silence is golden.\n", FS_CHMOD_FILE );
			}
		}
		return $dir;
	}

	/** Normalise any incoming brief (AI tool input or form) into the full schema. */
	public static function normalize( $b ) {
		$b    = is_array( $b ) ? $b : array();
		$s    = function ( $v, $max = 4000 ) {
			return mb_substr( trim( sanitize_textarea_field( is_scalar( $v ) ? (string) $v : '' ) ), 0, $max );
		};
		$l    = function ( $v ) use ( $s ) {
			$out = array();
			foreach ( (array) $v as $i ) {
				$i = $s( $i, 600 );
				if ( '' !== $i ) {
					$out[] = $i;
				}
			}
			return array_slice( $out, 0, 20 );
		};
		$c    = isset( $b['customer'] ) && is_array( $b['customer'] ) ? $b['customer'] : array();
		$urg  = isset( $b['urgency'] ) && in_array( $b['urgency'], array( 'low', 'medium', 'high', 'critical' ), true ) ? $b['urgency'] : 'medium';
		$lang = isset( $b['language'] ) && 'id' === $b['language'] ? 'id' : 'en';
		return array(
			'language'               => $lang,
			'customer'               => array(
				'name'    => $s( isset( $c['name'] ) ? $c['name'] : '', 120 ),
				'email'   => sanitize_email( isset( $c['email'] ) ? $c['email'] : '' ),
				'company' => $s( isset( $c['company'] ) ? $c['company'] : '', 160 ),
				'phone'   => $s( isset( $c['phone'] ) ? $c['phone'] : '', 40 ),
				'role'    => $s( isset( $c['role'] ) ? $c['role'] : '', 120 ),
			),
			'title'                  => $s( isset( $b['title'] ) ? $b['title'] : '', 160 ),
			'summary'                => $s( isset( $b['summary'] ) ? $b['summary'] : '', 1500 ),
			'problem'                => $s( isset( $b['problem'] ) ? $b['problem'] : '' ),
			'goals'                  => $l( isset( $b['goals'] ) ? $b['goals'] : array() ),
			'current_systems'        => $l( isset( $b['current_systems'] ) ? $b['current_systems'] : array() ),
			'requirements'           => $l( isset( $b['requirements'] ) ? $b['requirements'] : array() ),
			'constraints'            => $l( isset( $b['constraints'] ) ? $b['constraints'] : array() ),
			'urgency'                => $urg,
			'timeline'               => $s( isset( $b['timeline'] ) ? $b['timeline'] : '', 300 ),
			'budget'                 => $s( isset( $b['budget'] ) ? $b['budget'] : '', 300 ),
			'suggested_services'     => $l( isset( $b['suggested_services'] ) ? $b['suggested_services'] : array() ),
			'initial_recommendation' => $s( isset( $b['initial_recommendation'] ) ? $b['initial_recommendation'] : '', 2000 ),
			'open_questions'         => $l( isset( $b['open_questions'] ) ? $b['open_questions'] : array() ),
			'consent_to_contact'     => ! empty( $b['consent_to_contact'] ),
		);
	}

	/** Labels in the brief's language. */
	public static function t( $lang ) {
		$bn   = Ognivid_AI_Settings::get( 'business_name' );
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$en   = array(
			'doc' => 'Project Brief', 'customer' => 'Customer', 'name' => 'Name', 'email' => 'Email', 'company' => 'Company', 'phone' => 'Phone / WhatsApp', 'role' => 'Role',
			'summary' => 'Summary', 'problem' => 'Problem', 'goals' => 'Goals', 'systems' => 'Current systems', 'req' => 'Requirements', 'constraints' => 'Constraints',
			'urgency' => 'Urgency', 'timeline' => 'Timeline', 'budget' => 'Budget', 'services' => 'Relevant services', 'reco' => 'Initial recommendation (non-binding)',
			'questions' => 'Open questions', 'source' => 'Source', 'ai' => 'AI assistant on ' . $host, 'form' => 'Brief form on ' . $host, 'date' => 'Date', 'ref' => 'Reference',
			'none' => 'Not specified', 'urg' => array( 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical' ),
			'mail_hi' => 'Hi %s,', 'mail_body' => 'Thank you for contacting ' . $bn . '. Here is a copy of the project brief we received. Our team will review it and get back to you, usually within 1-2 business days.',
			'mail_wa' => 'Need to add something? Reply to this email or chat with us on WhatsApp:', 'mail_subject_c' => 'Your project brief: %s', 'transcript' => 'Conversation transcript',
		);
		$id = array(
			'doc' => 'Project Brief', 'customer' => 'Pelanggan', 'name' => 'Nama', 'email' => 'Email', 'company' => 'Perusahaan', 'phone' => 'Telepon / WhatsApp', 'role' => 'Jabatan',
			'summary' => 'Ringkasan', 'problem' => 'Masalah', 'goals' => 'Tujuan', 'systems' => 'Sistem saat ini', 'req' => 'Kebutuhan', 'constraints' => 'Batasan',
			'urgency' => 'Urgensi', 'timeline' => 'Target waktu', 'budget' => 'Anggaran', 'services' => 'Layanan terkait', 'reco' => 'Rekomendasi awal (tidak mengikat)',
			'questions' => 'Pertanyaan terbuka', 'source' => 'Sumber', 'ai' => 'Asisten AI di ' . $host, 'form' => 'Formulir brief di ' . $host, 'date' => 'Tanggal', 'ref' => 'Referensi',
			'none' => 'Belum ditentukan', 'urg' => array( 'low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi', 'critical' => 'Kritis' ),
			'mail_hi' => 'Halo %s,', 'mail_body' => 'Terima kasih telah menghubungi ' . $bn . '. Berikut salinan project brief yang kami terima. Tim kami akan meninjaunya dan menghubungi Anda, biasanya dalam 1-2 hari kerja.',
			'mail_wa' => 'Ada yang ingin ditambahkan? Balas email ini atau chat kami di WhatsApp:', 'mail_subject_c' => 'Project brief Anda: %s', 'transcript' => 'Transkrip percakapan',
		);
		return 'id' === $lang ? $id : $en;
	}

	/**
	 * Save, render PDF and send emails.
	 *
	 * @return int|WP_Error post ID
	 */
	public static function submit( $brief, $source, $transcript = '' ) {
		$b = self::normalize( $brief );
		if ( ! is_email( $b['customer']['email'] ) ) {
			return new WP_Error( 'email', 'invalid email' );
		}
		if ( '' === $b['title'] ) {
			$b['title'] = mb_substr( $b['summary'] ? $b['summary'] : $b['problem'], 0, 80 );
		}
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'private',
				'post_title'  => $b['title'] . ' — ' . $b['customer']['name'],
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		$ref = 'OGN-' . wp_date( 'ymd' ) . '-' . $post_id;
		update_post_meta( $post_id, '_ognivid_ai_brief', wp_slash( wp_json_encode( $b ) ) );
		update_post_meta( $post_id, '_ognivid_ai_source', $source );
		update_post_meta( $post_id, '_ognivid_ai_ref', $ref );
		update_post_meta( $post_id, '_ognivid_ai_status', 'new' );
		if ( $transcript ) {
			update_post_meta( $post_id, '_ognivid_ai_transcript', wp_slash( $transcript ) );
		}
		$pdf = self::make_pdf( $b, $ref, $source, $post_id );
		if ( $pdf ) {
			update_post_meta( $post_id, '_ognivid_ai_pdf', $pdf );
		}
		self::send_emails( $b, $ref, $source, $pdf, $transcript );
		return $post_id;
	}

	private static function list_html( $items, $none ) {
		if ( empty( $items ) ) {
			return '<p style="margin:0;color:#6b7280">' . esc_html( $none ) . '</p>';
		}
		$h = '<ul style="margin:0;padding-left:20px">';
		foreach ( $items as $i ) {
			$h .= '<li style="margin:2px 0">' . esc_html( $i ) . '</li>';
		}
		return $h . '</ul>';
	}

	/** HTML version of the brief (email + admin screen). */
	public static function html( $b, $ref, $source ) {
		$t   = self::t( $b['language'] );
		$c   = $b['customer'];
		$row = function ( $k, $v ) {
			return '<tr><td style="padding:6px 10px;color:#6b7280;width:38%;vertical-align:top">' . esc_html( $k ) . '</td><td style="padding:6px 10px;vertical-align:top">' . $v . '</td></tr>';
		};
		$sec = function ( $title, $body ) {
			return '<h3 style="margin:22px 0 8px;font-size:15px;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:6px">' . esc_html( $title ) . '</h3>' . $body;
		};
		$p     = function ( $v ) use ( $t ) {
			return '<p style="margin:0;white-space:pre-line">' . ( '' !== $v ? esc_html( $v ) : '<span style="color:#6b7280">' . esc_html( $t['none'] ) . '</span>' ) . '</p>';
		};
		$email = esc_html( $c['email'] );
		$wa    = preg_replace( '/\D+/', '', $c['phone'] );
		$h     = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#111827;max-width:680px">';
		$h    .= '<div style="background:#111111;color:#fff;padding:18px 22px;border-radius:10px 10px 0 0"><div style="font-size:12px;letter-spacing:2px;color:' . esc_attr( Ognivid_AI_Settings::get( 'accent' ) ) . '">' . esc_html( strtoupper( Ognivid_AI_Settings::get( 'business_name' ) ) ) . '</div><div style="font-size:20px;font-weight:bold;margin-top:4px">' . esc_html( $t['doc'] ) . ': ' . esc_html( $b['title'] ) . '</div></div>';
		$h    .= '<div style="border:1px solid #e5e7eb;border-top:0;padding:4px 22px 22px;border-radius:0 0 10px 10px">';
		$h    .= '<table style="width:100%;border-collapse:collapse;margin-top:12px;font-size:13px">';
		$h    .= $row( $t['ref'], esc_html( $ref ) ) . $row( $t['date'], esc_html( wp_date( 'j F Y, H:i' ) ) ) . $row( $t['source'], esc_html( 'form' === $source ? $t['form'] : $t['ai'] ) );
		$h    .= $row( $t['urgency'], '<strong>' . esc_html( $t['urg'][ $b['urgency'] ] ) . '</strong>' ) . $row( $t['timeline'], esc_html( $b['timeline'] ? $b['timeline'] : $t['none'] ) ) . $row( $t['budget'], esc_html( $b['budget'] ? $b['budget'] : $t['none'] ) );
		$h    .= '</table>';
		$h    .= $sec( $t['customer'], '<table style="width:100%;border-collapse:collapse;font-size:13px">' . $row( $t['name'], esc_html( $c['name'] ) ) . $row( $t['email'], '<a href="mailto:' . $email . '">' . $email . '</a>' ) . $row( $t['company'], esc_html( $c['company'] ? $c['company'] : '-' ) ) . $row( $t['role'], esc_html( $c['role'] ? $c['role'] : '-' ) ) . $row( $t['phone'], $c['phone'] ? esc_html( $c['phone'] ) . ( strlen( $wa ) >= 8 ? ' &middot; <a href="https://wa.me/' . esc_attr( $wa ) . '">WhatsApp</a>' : '' ) : '-' ) . '</table>' );
		$h    .= $sec( $t['summary'], $p( $b['summary'] ) );
		$h    .= $sec( $t['problem'], $p( $b['problem'] ) );
		$h    .= $sec( $t['goals'], self::list_html( $b['goals'], $t['none'] ) );
		$h    .= $sec( $t['systems'], self::list_html( $b['current_systems'], $t['none'] ) );
		$h    .= $sec( $t['req'], self::list_html( $b['requirements'], $t['none'] ) );
		$h    .= $sec( $t['constraints'], self::list_html( $b['constraints'], $t['none'] ) );
		$h    .= $sec( $t['services'], self::list_html( $b['suggested_services'], $t['none'] ) );
		$h    .= $sec( $t['reco'], $p( $b['initial_recommendation'] ) );
		$h    .= $sec( $t['questions'], self::list_html( $b['open_questions'], $t['none'] ) );
		$h    .= '</div></div>';
		return $h;
	}

	private static function send_emails( $b, $ref, $source, $pdf, $transcript ) {
		$o       = Ognivid_AI_Settings::get();
		$t       = self::t( $b['language'] );
		$c       = $b['customer'];
		$html    = self::html( $b, $ref, $source );
		$headers = array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $c['name'] ) . ' <' . $c['email'] . '>' );
		$att     = $pdf && file_exists( $pdf ) ? array( $pdf ) : array();
		$extra   = '';
		if ( $transcript ) {
			$extra = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:680px;margin-top:18px"><h3 style="font-size:14px;color:#6b7280">' . esc_html( $t['transcript'] ) . '</h3><pre style="white-space:pre-wrap;font-family:inherit;font-size:12px;color:#374151;background:#f9fafb;padding:12px;border-radius:8px">' . esc_html( $transcript ) . '</pre></div>';
		}
		$urgent = in_array( $b['urgency'], array( 'high', 'critical' ), true ) ? '[' . strtoupper( $b['urgency'] ) . '] ' : '';
		wp_mail( $o['recipient'], $urgent . '[' . $o['business_name'] . ' Brief] ' . $b['title'] . ' — ' . $c['name'] . ' (' . $ref . ')', $html . $extra, $headers, $att );

		if ( ! empty( $o['copy_to_customer'] ) ) {
			$wa    = preg_replace( '/\D+/', '', $o['whatsapp'] );
			$intro = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#111827;max-width:680px;margin-bottom:16px"><p>' . esc_html( sprintf( $t['mail_hi'], $c['name'] ) ) . '</p><p>' . esc_html( $t['mail_body'] ) . '</p>';
			if ( $wa ) {
				$intro .= '<p>' . esc_html( $t['mail_wa'] ) . ' <a href="https://wa.me/' . esc_attr( $wa ) . '">+' . esc_html( $wa ) . '</a></p>';
			}
			$intro .= '<p>— ' . esc_html( $o['business_name'] ) . '</p></div>';
			wp_mail( $c['email'], sprintf( $t['mail_subject_c'], $b['title'] ) . ' (' . $ref . ')', $intro . $html, array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $o['business_name'] ) . ' <' . $o['recipient'] . '>' ), $att );
		}
	}

	/**
	 * Render the brief as an A4 PDF in the private storage folder.
	 *
	 * @param array  $b       Normalised brief.
	 * @param string $ref     Reference.
	 * @param string $source  ai|form.
	 * @param int    $post_id Brief post ID.
	 * @return string File path, or '' on failure.
	 */
	public static function make_pdf( $b, $ref, $source, $post_id ) {
		if ( ! class_exists( 'Ognivid_AI_PDF' ) ) {
			require_once OGNIVID_AI_DIR . 'includes/class-ognivid-ai-pdf.php';
		}
		try {
			$t   = self::t( $b['language'] );
			$c   = $b['customer'];
			$bn  = Ognivid_AI_Settings::get( 'business_name' );
			$rgb = sscanf( Ognivid_AI_Settings::get( 'accent' ), '#%02x%02x%02x' );
			$ink = array( 17, 24, 39 );
			$mut = array( 107, 114, 128 );
			$pdf = new Ognivid_AI_PDF( $bn . ' ' . $t['doc'] . ' ' . $ref, $bn );
			$x   = 18.0;
			$w   = 174.0;
			$pdf->add_page();

			// Header band.
			$pdf->color( array( 17, 17, 17 ), 'fill' );
			$pdf->rect( 0, 0, 210, 34 );
			$pdf->color( array( (int) $rgb[0], (int) $rgb[1], (int) $rgb[2] ) );
			$pdf->font( 'B', 9 );
			$pdf->text( $x, 9, Ognivid_AI_PDF::enc( strtoupper( $bn ) ) );
			$pdf->color( array( 255, 255, 255 ) );
			$pdf->font( 'B', 15 );
			$pdf->y = 15;
			$pdf->para( $x, $w, $t['doc'] . ': ' . $b['title'], 7 );
			$pdf->y = 42;

			$kv = function ( $k, $v ) use ( $pdf, $x, $ink, $mut ) {
				$pdf->need( 6 );
				$top = $pdf->y;
				$pdf->font( '', 9 );
				$pdf->color( $mut );
				$pdf->text( $x, $top, Ognivid_AI_PDF::enc( $k ) );
				$pdf->color( $ink );
				$pdf->para( $x + 50, 124, '' !== (string) $v ? (string) $v : '-', 5.6 );
				$pdf->y = max( $pdf->y, $top + 6 );
			};
			$h2 = function ( $s ) use ( $pdf, $x, $ink ) {
				$pdf->y += 3;
				$pdf->need( 14 );
				$pdf->font( 'B', 11 );
				$pdf->color( $ink );
				$pdf->text( $x, $pdf->y, Ognivid_AI_PDF::enc( $s ) );
				$pdf->y += 7;
				$pdf->color( array( 229, 231, 235 ), 'draw' );
				$pdf->hline( $x, $x + 174, $pdf->y );
				$pdf->y += 2.5;
			};
			$para = function ( $s ) use ( $pdf, $x, $w, $ink, $mut, $t ) {
				$pdf->font( '', 10 );
				$pdf->color( '' !== $s ? $ink : $mut );
				$pdf->para( $x, $w, '' !== $s ? $s : $t['none'] );
			};
			$list = function ( $items ) use ( $pdf, $x, $w, $ink, $para ) {
				if ( empty( $items ) ) {
					$para( '' );
					return;
				}
				$pdf->font( '', 10 );
				$pdf->color( $ink );
				foreach ( $items as $i ) {
					$pdf->need( 5.4 );
					$pdf->text( $x, $pdf->y, '-' );
					$pdf->para( $x + 5, $w - 5, $i );
				}
			};

			$kv( $t['ref'], $ref );
			$kv( $t['date'], wp_date( 'j F Y, H:i' ) );
			$kv( $t['source'], 'form' === $source ? $t['form'] : $t['ai'] );
			$kv( $t['urgency'], $t['urg'][ $b['urgency'] ] );
			$kv( $t['timeline'], $b['timeline'] ? $b['timeline'] : $t['none'] );
			$kv( $t['budget'], $b['budget'] ? $b['budget'] : $t['none'] );
			$h2( $t['customer'] );
			$kv( $t['name'], $c['name'] );
			$kv( $t['email'], $c['email'] );
			$kv( $t['company'], $c['company'] );
			$kv( $t['role'], $c['role'] );
			$kv( $t['phone'], $c['phone'] );
			$h2( $t['summary'] );
			$para( $b['summary'] );
			$h2( $t['problem'] );
			$para( $b['problem'] );
			$h2( $t['goals'] );
			$list( $b['goals'] );
			$h2( $t['systems'] );
			$list( $b['current_systems'] );
			$h2( $t['req'] );
			$list( $b['requirements'] );
			$h2( $t['constraints'] );
			$list( $b['constraints'] );
			$h2( $t['services'] );
			$list( $b['suggested_services'] );
			$h2( $t['reco'] );
			$para( $b['initial_recommendation'] );
			$h2( $t['questions'] );
			$list( $b['open_questions'] );

			$dir  = self::ensure_storage();
			$file = $dir . '/brief-' . $post_id . '-' . wp_generate_password( 10, false ) . '.pdf';
			global $wp_filesystem;
			if ( ! $wp_filesystem || ! $wp_filesystem->put_contents( $file, $pdf->output(), FS_CHMOD_FILE ) ) {
				return '';
			}
			return $file;
		} catch ( \Throwable $e ) {
			return '';
		}
	}

	/* ---------- admin: inbox experience ---------- */

	public static function statuses() {
		return array(
			'new'       => 'New',
			'read'      => 'Read',
			'contacted' => 'Contacted',
			'proposal'  => 'Proposal sent',
			'won'       => 'Won',
			'closed'    => 'Closed',
		);
	}

	public static function status_of( $post_id ) {
		$s = get_post_meta( $post_id, '_ognivid_ai_status', true );
		return isset( self::statuses()[ $s ] ) ? $s : 'read';
	}

	public static function count_new() {
		$q = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'any',
				'meta_key'       => '_ognivid_ai_status', // phpcs:ignore
				'meta_value'     => 'new', // phpcs:ignore
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => false,
			)
		);
		return (int) $q->found_posts;
	}

	/** "AI Briefs (3)" style badge for unread briefs. */
	public static function menu_badge() {
		global $menu;
		$n = self::count_new();
		if ( ! $n || ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=' . self::CPT === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod count-' . $n . '"><span class="pending-count">' . $n . '</span></span>'; // phpcs:ignore
			}
		}
	}

	public static function columns( $cols ) {
		return array(
			'cb'           => $cols['cb'],
			'title'        => 'Brief',
			'ognivid_ai_customer' => 'Customer',
			'ognivid_ai_urgency'  => 'Urgency',
			'ognivid_ai_source'   => 'Source',
			'ognivid_ai_status'   => 'Status',
			'date'         => 'Received',
		);
	}

	public static function column( $col, $post_id ) {
		$b = json_decode( (string) get_post_meta( $post_id, '_ognivid_ai_brief', true ), true );
		$b = $b ? self::normalize( $b ) : null;
		switch ( $col ) {
			case 'ognivid_ai_customer':
				if ( $b ) {
					$c = $b['customer'];
					echo '<strong>' . esc_html( $c['name'] ) . '</strong>' . ( $c['company'] ? '<br>' . esc_html( $c['company'] ) : '' );
					echo '<br><a href="mailto:' . esc_attr( $c['email'] ) . '">' . esc_html( $c['email'] ) . '</a>';
					$wa = preg_replace( '/\D+/', '', $c['phone'] );
					if ( strlen( $wa ) >= 8 ) {
						echo ' · <a href="https://wa.me/' . esc_attr( $wa ) . '" target="_blank" rel="noopener">WhatsApp</a>';
					}
				}
				break;
			case 'ognivid_ai_urgency':
				if ( $b ) {
					$colors = array( 'low' => '#6b7280', 'medium' => '#2563eb', 'high' => '#d97706', 'critical' => '#dc2626' );
					echo '<span style="display:inline-block;padding:2px 8px;border-radius:999px;color:#fff;font-size:12px;background:' . esc_attr( $colors[ $b['urgency'] ] ) . '">' . esc_html( ucfirst( $b['urgency'] ) ) . '</span>';
				}
				break;
			case 'ognivid_ai_source':
				echo 'form' === get_post_meta( $post_id, '_ognivid_ai_source', true ) ? 'Form' : 'AI chat';
				break;
			case 'ognivid_ai_status':
				$s = self::status_of( $post_id );
				$st = 'new' === $s ? 'font-weight:700;color:#2e8a0e' : '';
				echo '<span style="' . esc_attr( $st ) . '">' . esc_html( self::statuses()[ $s ] ) . '</span>';
				break;
		}
	}

	/**
	 * Admin list styles (bold unread rows, column widths).
	 *
	 * @param string $hook Current admin page.
	 */
	public static function admin_css( $hook ) {
		if ( ! in_array( $hook, array( 'edit.php', 'post.php' ), true ) || self::CPT !== get_post_type() && ( ! isset( $_GET['post_type'] ) || self::CPT !== $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		wp_register_style( 'ognivid-ai-admin', false, array(), OGNIVID_AI_VERSION );
		wp_enqueue_style( 'ognivid-ai-admin' );
		wp_add_inline_style( 'ognivid-ai-admin', '.post-type-ognivid_ai_brief tr.ognivid-ai-new .row-title{font-weight:800}.post-type-ognivid_ai_brief tr.ognivid-ai-new{box-shadow:inset 3px 0 0 #2e8a0e}.post-type-ognivid_ai_brief .column-ognivid_ai_customer{width:24%}.post-type-ognivid_ai_brief .column-ognivid_ai_urgency,.post-type-ognivid_ai_brief .column-ognivid_ai_source,.post-type-ognivid_ai_brief .column-ognivid_ai_status{width:10%}' );
	}

	public static function row_class( $classes, $class, $post_id ) {
		if ( is_admin() && self::CPT === get_post_type( $post_id ) && 'new' === self::status_of( $post_id ) ) {
			$classes[] = 'ognivid-ai-new';
		}
		return $classes;
	}

	/** Status filter above the list. */
	public static function filter_dropdown( $post_type ) {
		if ( self::CPT !== $post_type ) {
			return;
		}
		$cur = isset( $_GET['ognivid_ai_status'] ) ? sanitize_key( $_GET['ognivid_ai_status'] ) : ''; // phpcs:ignore
		echo '<select name="ognivid_ai_status"><option value="">All statuses</option>';
		foreach ( self::statuses() as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select>';
	}

	public static function apply_filter( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || self::CPT !== $query->get( 'post_type' ) ) {
			return;
		}
		$s = isset( $_GET['ognivid_ai_status'] ) ? sanitize_key( $_GET['ognivid_ai_status'] ) : ''; // phpcs:ignore
		if ( $s && isset( self::statuses()[ $s ] ) ) {
			if ( 'read' === $s ) {
				$query->set( 'meta_query', array( 'relation' => 'OR', array( 'key' => '_ognivid_ai_status', 'value' => 'read' ), array( 'key' => '_ognivid_ai_status', 'compare' => 'NOT EXISTS' ) ) );
			} else {
				$query->set( 'meta_key', '_ognivid_ai_status' );
				$query->set( 'meta_value', $s );
			}
		}
	}

	public static function meta_boxes() {
		add_meta_box( 'ognivid_ai_brief_view', 'Project Brief', array( __CLASS__, 'render_box' ), self::CPT, 'normal', 'high' );
		add_meta_box( 'ognivid_ai_brief_status', 'Follow-up', array( __CLASS__, 'render_status_box' ), self::CPT, 'side', 'high' );
	}

	public static function render_status_box( $post ) {
		wp_nonce_field( 'ognivid_ai_status_' . $post->ID, 'ognivid_ai_status_nonce' );
		$cur = self::status_of( $post->ID );
		if ( 'new' === $cur ) {
			$cur = 'read'; // viewing this screen marks the brief as read
		}
		echo '<p><label for="ognivid_ai_status_field"><strong>Status</strong></label><br><select id="ognivid_ai_status_field" name="ognivid_ai_status_field" style="width:100%">';
		foreach ( self::statuses() as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></p>';
		echo '<p><label for="ognivid_ai_note_field"><strong>Internal note</strong></label><textarea id="ognivid_ai_note_field" name="ognivid_ai_note_field" rows="5" style="width:100%">' . esc_textarea( (string) get_post_meta( $post->ID, '_ognivid_ai_note', true ) ) . '</textarea></p>';
		$b = json_decode( (string) get_post_meta( $post->ID, '_ognivid_ai_brief', true ), true );
		if ( $b ) {
			$b    = self::normalize( $b );
			$subj = rawurlencode( 'Re: ' . $b['title'] . ' (' . get_post_meta( $post->ID, '_ognivid_ai_ref', true ) . ')' );
			echo '<p><a class="button" style="width:100%;text-align:center" href="mailto:' . esc_attr( $b['customer']['email'] ) . '?subject=' . esc_attr( $subj ) . '">Reply by email</a></p>';
			$wa = preg_replace( '/\D+/', '', $b['customer']['phone'] );
			if ( strlen( $wa ) >= 8 ) {
				echo '<p><a class="button" style="width:100%;text-align:center" target="_blank" rel="noopener" href="https://wa.me/' . esc_attr( $wa ) . '">Chat on WhatsApp</a></p>';
			}
		}
		echo '<p class="description">Click “Update” to save the status and note.</p>';
	}

	public static function save_status( $post_id ) {
		if ( ! isset( $_POST['ognivid_ai_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ognivid_ai_status_nonce'] ) ), 'ognivid_ai_status_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$s = isset( $_POST['ognivid_ai_status_field'] ) ? sanitize_key( $_POST['ognivid_ai_status_field'] ) : '';
		if ( isset( self::statuses()[ $s ] ) ) {
			update_post_meta( $post_id, '_ognivid_ai_status', $s );
		}
		if ( isset( $_POST['ognivid_ai_note_field'] ) ) {
			update_post_meta( $post_id, '_ognivid_ai_note', sanitize_textarea_field( wp_unslash( $_POST['ognivid_ai_note_field'] ) ) );
		}
	}

	public static function render_box( $post ) {
		$b = json_decode( (string) get_post_meta( $post->ID, '_ognivid_ai_brief', true ), true );
		if ( ! $b ) {
			echo '<p>No data.</p>';
			return;
		}
		if ( 'new' === get_post_meta( $post->ID, '_ognivid_ai_status', true ) ) {
			update_post_meta( $post->ID, '_ognivid_ai_status', 'read' ); // opening a brief marks it as read
		}
		$ref = get_post_meta( $post->ID, '_ognivid_ai_ref', true );
		$pdf = get_post_meta( $post->ID, '_ognivid_ai_pdf', true );
		if ( $pdf && file_exists( $pdf ) ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=ognivid_ai_brief_pdf&post=' . $post->ID ), 'ognivid_ai_pdf_' . $post->ID );
			echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Download PDF</a></p>';
		}
		echo wp_kses_post( self::html( self::normalize( $b ), $ref, get_post_meta( $post->ID, '_ognivid_ai_source', true ) ) );
		$tr = get_post_meta( $post->ID, '_ognivid_ai_transcript', true );
		if ( $tr ) {
			echo '<h3>Conversation transcript</h3><pre style="white-space:pre-wrap;background:#f6f7f7;padding:12px;max-height:480px;overflow:auto">' . esc_html( $tr ) . '</pre>';
		}
	}

	public static function download_pdf() {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'ognivid_ai_pdf_' . $id ) ) {
			wp_die( 'Not allowed', 403 );
		}
		$pdf = get_post_meta( $id, '_ognivid_ai_pdf', true );
		if ( ! $pdf || ! file_exists( $pdf ) ) {
			wp_die( 'PDF not found', 404 );
		}
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( get_post_meta( $id, '_ognivid_ai_ref', true ) . '.pdf' ) . '"' );
		header( 'Content-Length: ' . filesize( $pdf ) );
		readfile( $pdf ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
}
