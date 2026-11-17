<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Project briefs: storage (private post type), PDF and HTML email.
 */
class OAA_Brief {

	const CPT = 'oaa_brief';

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
		$dir = trailingslashit( $up['basedir'] ) . 'oaa-briefs';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php // Silence is golden.\n" ); // phpcs:ignore
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
		$en = array(
			'doc' => 'Project Brief', 'customer' => 'Customer', 'name' => 'Name', 'email' => 'Email', 'company' => 'Company', 'phone' => 'Phone / WhatsApp', 'role' => 'Role',
			'summary' => 'Summary', 'problem' => 'Problem', 'goals' => 'Goals', 'systems' => 'Current systems', 'req' => 'Requirements', 'constraints' => 'Constraints',
			'urgency' => 'Urgency', 'timeline' => 'Timeline', 'budget' => 'Budget', 'services' => 'Relevant services', 'reco' => 'Initial recommendation (non-binding)',
			'questions' => 'Open questions', 'source' => 'Source', 'ai' => 'AI assistant on ognivid.com', 'form' => 'Brief form on ognivid.com', 'date' => 'Date', 'ref' => 'Reference',
			'none' => 'Not specified', 'urg' => array( 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical' ),
			'mail_hi' => 'Hi %s,', 'mail_body' => 'Thank you for contacting OGNIVID. Here is a copy of the project brief we received. Our team will review it and get back to you, usually within 1-2 business days.',
			'mail_wa' => 'Need to add something? Reply to this email or chat with us on WhatsApp:', 'mail_subject_c' => 'Your project brief: %s', 'transcript' => 'Conversation transcript',
		);
		$id = array(
			'doc' => 'Project Brief', 'customer' => 'Pelanggan', 'name' => 'Nama', 'email' => 'Email', 'company' => 'Perusahaan', 'phone' => 'Telepon / WhatsApp', 'role' => 'Jabatan',
			'summary' => 'Ringkasan', 'problem' => 'Masalah', 'goals' => 'Tujuan', 'systems' => 'Sistem saat ini', 'req' => 'Kebutuhan', 'constraints' => 'Batasan',
			'urgency' => 'Urgensi', 'timeline' => 'Target waktu', 'budget' => 'Anggaran', 'services' => 'Layanan terkait', 'reco' => 'Rekomendasi awal (tidak mengikat)',
			'questions' => 'Pertanyaan terbuka', 'source' => 'Sumber', 'ai' => 'Asisten AI di ognivid.com', 'form' => 'Formulir brief di ognivid.com', 'date' => 'Tanggal', 'ref' => 'Referensi',
			'none' => 'Belum ditentukan', 'urg' => array( 'low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi', 'critical' => 'Kritis' ),
			'mail_hi' => 'Halo %s,', 'mail_body' => 'Terima kasih telah menghubungi OGNIVID. Berikut salinan project brief yang kami terima. Tim kami akan meninjaunya dan menghubungi Anda, biasanya dalam 1-2 hari kerja.',
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
		update_post_meta( $post_id, '_oaa_brief', wp_slash( wp_json_encode( $b ) ) );
		update_post_meta( $post_id, '_oaa_source', $source );
		update_post_meta( $post_id, '_oaa_ref', $ref );
		if ( $transcript ) {
			update_post_meta( $post_id, '_oaa_transcript', wp_slash( $transcript ) );
		}
		$pdf = self::make_pdf( $b, $ref, $source, $post_id );
		if ( $pdf ) {
			update_post_meta( $post_id, '_oaa_pdf', $pdf );
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
		$h    .= '<div style="background:#111111;color:#fff;padding:18px 22px;border-radius:10px 10px 0 0"><div style="font-size:12px;letter-spacing:2px;color:#7cff3b">OGNIVID</div><div style="font-size:20px;font-weight:bold;margin-top:4px">' . esc_html( $t['doc'] ) . ': ' . esc_html( $b['title'] ) . '</div></div>';
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
		$o       = OAA_Settings::get();
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
		wp_mail( $o['recipient'], $urgent . '[OGNIVID Brief] ' . $b['title'] . ' — ' . $c['name'] . ' (' . $ref . ')', $html . $extra, $headers, $att );

		if ( ! empty( $o['copy_to_customer'] ) ) {
			$wa    = preg_replace( '/\D+/', '', $o['whatsapp'] );
			$intro = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#111827;max-width:680px;margin-bottom:16px"><p>' . esc_html( sprintf( $t['mail_hi'], $c['name'] ) ) . '</p><p>' . esc_html( $t['mail_body'] ) . '</p>';
			if ( $wa ) {
				$intro .= '<p>' . esc_html( $t['mail_wa'] ) . ' <a href="https://wa.me/' . esc_attr( $wa ) . '">+' . esc_html( $wa ) . '</a></p>';
			}
			$intro .= '<p>— OGNIVID</p></div>';
			wp_mail( $c['email'], sprintf( $t['mail_subject_c'], $b['title'] ) . ' (' . $ref . ')', $intro . $html, array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: OGNIVID <' . $o['recipient'] . '>' ), $att );
		}
	}

	/** Convert UTF-8 to the core-font encoding FPDF uses. */
	private static function pdf_text( $s ) {
		$s = str_replace( array( '“', '”', '‘', '’', '–', '—', '…', '•' ), array( '"', '"', "'", "'", '-', '-', '...', '-' ), (string) $s );
		$r = function_exists( 'iconv' ) ? @iconv( 'UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s ) : false; // phpcs:ignore
		return false === $r ? mb_convert_encoding( $s, 'Windows-1252', 'UTF-8' ) : $r;
	}

	public static function make_pdf( $b, $ref, $source, $post_id ) {
		if ( ! class_exists( 'FPDF' ) ) {
			require_once OAA_DIR . 'includes/fpdf/fpdf.php';
		}
		try {
			$t   = self::t( $b['language'] );
			$c   = $b['customer'];
			$x   = function ( $s ) {
				return self::pdf_text( $s );
			};
			$pdf = new FPDF( 'P', 'mm', 'A4' );
			$pdf->SetMargins( 18, 18, 18 );
			$pdf->SetAutoPageBreak( true, 18 );
			$pdf->SetTitle( $x( 'OGNIVID ' . $t['doc'] . ' ' . $ref ) );
			$pdf->SetAuthor( 'OGNIVID' );
			$pdf->AddPage();
			// Header band.
			$pdf->SetFillColor( 17, 17, 17 );
			$pdf->Rect( 0, 0, 210, 34, 'F' );
			$pdf->SetXY( 18, 9 );
			$pdf->SetTextColor( 124, 255, 59 );
			$pdf->SetFont( 'Helvetica', 'B', 9 );
			$pdf->Cell( 0, 5, 'OGNIVID', 0, 1 );
			$pdf->SetX( 18 );
			$pdf->SetTextColor( 255, 255, 255 );
			$pdf->SetFont( 'Helvetica', 'B', 15 );
			$pdf->MultiCell( 174, 7, $x( $t['doc'] . ': ' . $b['title'] ), 0, 'L' );
			$pdf->SetY( 42 );
			$pdf->SetTextColor( 17, 24, 39 );

			$kv = function ( $k, $v ) use ( $pdf, $x ) {
				$pdf->SetFont( 'Helvetica', '', 9 );
				$pdf->SetTextColor( 107, 114, 128 );
				$pdf->Cell( 50, 6, $x( $k ), 0, 0 );
				$pdf->SetTextColor( 17, 24, 39 );
				$pdf->MultiCell( 124, 6, $x( '' !== $v ? $v : '-' ), 0, 'L' );
			};
			$h2 = function ( $s ) use ( $pdf, $x ) {
				$pdf->Ln( 3 );
				$pdf->SetFont( 'Helvetica', 'B', 11 );
				$pdf->SetTextColor( 17, 24, 39 );
				$pdf->Cell( 0, 7, $x( $s ), 0, 1 );
				$pdf->SetDrawColor( 229, 231, 235 );
				$pdf->Line( 18, $pdf->GetY(), 192, $pdf->GetY() );
				$pdf->Ln( 2 );
			};
			$para = function ( $s ) use ( $pdf, $x, $t ) {
				$pdf->SetFont( 'Helvetica', '', 10 );
				$pdf->SetTextColor( '' !== $s ? 17 : 107, '' !== $s ? 24 : 114, '' !== $s ? 39 : 128 );
				$pdf->MultiCell( 0, 5.5, $x( '' !== $s ? $s : $t['none'] ), 0, 'L' );
			};
			$list = function ( $items ) use ( $pdf, $x, $para ) {
				if ( empty( $items ) ) {
					$para( '' );
					return;
				}
				$pdf->SetFont( 'Helvetica', '', 10 );
				$pdf->SetTextColor( 17, 24, 39 );
				foreach ( $items as $i ) {
					$pdf->Cell( 5, 5.5, '-', 0, 0 );
					$pdf->MultiCell( 0, 5.5, $x( $i ), 0, 'L' );
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
			$pdf->Output( 'F', $file );
			return file_exists( $file ) ? $file : '';
		} catch ( \Throwable $e ) {
			return '';
		}
	}

	public static function meta_boxes() {
		add_meta_box( 'oaa_brief_view', 'Project Brief', array( __CLASS__, 'render_box' ), self::CPT, 'normal', 'high' );
	}

	public static function render_box( $post ) {
		$b = json_decode( (string) get_post_meta( $post->ID, '_oaa_brief', true ), true );
		if ( ! $b ) {
			echo '<p>No data.</p>';
			return;
		}
		$ref = get_post_meta( $post->ID, '_oaa_ref', true );
		$pdf = get_post_meta( $post->ID, '_oaa_pdf', true );
		if ( $pdf && file_exists( $pdf ) ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=oaa_brief_pdf&post=' . $post->ID ), 'oaa_pdf_' . $post->ID );
			echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Download PDF</a></p>';
		}
		echo self::html( self::normalize( $b ), $ref, get_post_meta( $post->ID, '_oaa_source', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		$tr = get_post_meta( $post->ID, '_oaa_transcript', true );
		if ( $tr ) {
			echo '<h3>Conversation transcript</h3><pre style="white-space:pre-wrap;background:#f6f7f7;padding:12px;max-height:480px;overflow:auto">' . esc_html( $tr ) . '</pre>';
		}
	}

	public static function download_pdf() {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'oaa_pdf_' . $id ) ) {
			wp_die( 'Not allowed', 403 );
		}
		$pdf = get_post_meta( $id, '_oaa_pdf', true );
		if ( ! $pdf || ! file_exists( $pdf ) ) {
			wp_die( 'PDF not found', 404 );
		}
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( get_post_meta( $id, '_oaa_ref', true ) . '.pdf' ) . '"' );
		header( 'Content-Length: ' . filesize( $pdf ) );
		readfile( $pdf ); // phpcs:ignore
		exit;
	}
}
