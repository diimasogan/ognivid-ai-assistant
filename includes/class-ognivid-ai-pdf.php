<?php
/**
 * Minimal PDF writer for project briefs (A4, Helvetica, text, lines and boxes).
 *
 * Built for this plugin so it needs no external library. Uses the 14 standard
 * PDF fonts, so nothing is embedded; text is encoded as Windows-1252.
 *
 * @package OgnividAIAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tiny PDF document builder. Units are millimetres, origin top-left.
 */
class Ognivid_AI_PDF {

	const W = 210.0;
	const H = 297.0;
	const K = 2.834645669; // Points per millimetre.

	/** @var array Character widths (1/1000 em) for Helvetica and Helvetica-Bold. */
	private static $widths = array();

	/** @var string[] Finished page content streams. */
	private $pages = array();

	/** @var string Current page stream. */
	private $buf = '';

	/** @var float Current y (mm). */
	public $y = 0.0;

	/** @var float Margins (mm). */
	public $margin = 18.0;

	/** @var string Current font: '' (regular) or 'B'. */
	private $style = '';

	/** @var float Font size (pt). */
	private $size = 10.0;

	/** @var string Document title. */
	private $title = '';

	/** @var string Document author. */
	private $author = '';

	/**
	 * Constructor.
	 *
	 * @param string $title  Title metadata.
	 * @param string $author Author metadata.
	 */
	public function __construct( $title = '', $author = '' ) {
		$this->title  = $title;
		$this->author = $author;
		if ( ! self::$widths ) {
			self::$widths = array(
				''  => array( 278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584,350,556,350,222,556,333,1000,556,556,333,1000,667,333,1000,350,611,350,350,222,222,333,333,350,556,1000,333,1000,500,333,944,350,500,667,278,333,556,556,556,556,260,556,333,737,370,556,584,333,737,333,400,584,333,333,333,556,537,278,333,333,365,556,834,834,834,611,667,667,667,667,667,667,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,500,556,556,556,556,278,278,278,278,556,556,556,556,556,556,556,584,611,556,556,556,556,500,556,500 ),
				'B' => array( 278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584,350,556,350,278,556,500,1000,556,556,333,1000,667,333,1000,350,611,350,350,278,278,500,500,350,556,1000,333,1000,556,333,944,350,500,667,278,333,556,556,556,556,280,556,333,737,370,556,584,333,737,333,400,584,333,333,333,611,556,278,333,333,365,556,834,834,834,611,722,722,722,722,722,722,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,556,556,556,556,556,278,278,278,278,611,611,611,611,611,611,611,584,611,611,611,611,611,556,611,556 ),
			);
		}
	}

	/**
	 * Convert UTF-8 text to Windows-1252 for the standard fonts.
	 *
	 * @param string $s Text.
	 * @return string
	 */
	public static function enc( $s ) {
		$s = str_replace( array( "\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", "\u{2013}", "\u{2014}", "\u{2026}", "\u{2022}" ), array( '"', '"', "'", "'", '-', '-', '...', '-' ), (string) $s );
		$r = function_exists( 'iconv' ) ? iconv( 'UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s ) : false;
		return false === $r ? mb_convert_encoding( $s, 'Windows-1252', 'UTF-8' ) : $r;
	}

	/** Start a new page. */
	public function add_page() {
		if ( '' !== $this->buf || $this->pages ) {
			$this->pages[] = $this->buf;
		}
		$this->buf = '';
		$this->y   = $this->margin;
	}

	/**
	 * Set font.
	 *
	 * @param string $style '' or 'B'.
	 * @param float  $size  Size in points.
	 */
	public function font( $style, $size ) {
		$this->style = 'B' === $style ? 'B' : '';
		$this->size  = (float) $size;
	}

	/**
	 * Set fill/stroke/text colour.
	 *
	 * @param int[]  $rgb  Colour.
	 * @param string $what 'text', 'fill' or 'draw'.
	 */
	public function color( $rgb, $what = 'text' ) {
		$c = sprintf( '%.3F %.3F %.3F', $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255 );
		if ( 'draw' === $what ) {
			$this->buf .= $c . " RG\n";
		} else {
			$this->buf .= $c . " rg\n"; // Text and fill share the non-stroking colour.
		}
	}

	/**
	 * Filled rectangle.
	 *
	 * @param float $x X.
	 * @param float $y Y.
	 * @param float $w Width.
	 * @param float $h Height.
	 */
	public function rect( $x, $y, $w, $h ) {
		$this->buf .= sprintf( "%.2F %.2F %.2F %.2F re f\n", $x * self::K, ( self::H - $y ) * self::K, $w * self::K, -$h * self::K );
	}

	/**
	 * Horizontal line.
	 *
	 * @param float $x1 Start x.
	 * @param float $x2 End x.
	 * @param float $y  Y.
	 */
	public function hline( $x1, $x2, $y ) {
		$this->buf .= sprintf( "0.3 w %.2F %.2F m %.2F %.2F l S\n", $x1 * self::K, ( self::H - $y ) * self::K, $x2 * self::K, ( self::H - $y ) * self::K );
	}

	/**
	 * Width of a Windows-1252 string in mm.
	 *
	 * @param string $s Encoded text.
	 * @return float
	 */
	public function width( $s ) {
		$w  = 0;
		$cw = self::$widths[ $this->style ];
		$n  = strlen( $s );
		for ( $i = 0; $i < $n; $i++ ) {
			$w += $cw[ ord( $s[ $i ] ) ];
		}
		return $w * $this->size / 1000 / self::K;
	}

	/**
	 * Write one line of text at a position (baseline = y + size).
	 *
	 * @param float  $x X.
	 * @param float  $y Top y.
	 * @param string $s Encoded text.
	 */
	public function text( $x, $y, $s ) {
		$esc        = str_replace( array( '\\', '(', ')', "\r" ), array( '\\\\', '\\(', '\\)', '' ), $s );
		$base       = $y + $this->size / self::K * 0.8;
		$this->buf .= sprintf( "BT /F%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", 'B' === $this->style ? '2' : '1', $this->size, $x * self::K, ( self::H - $base ) * self::K, $esc );
	}

	/**
	 * Word-wrapped paragraph starting at the current y; adds pages as needed.
	 *
	 * @param float  $x    Left x.
	 * @param float  $w    Width.
	 * @param string $utf8 UTF-8 text.
	 * @param float  $lh   Line height (mm).
	 */
	public function para( $x, $w, $utf8, $lh = 5.4 ) {
		foreach ( preg_split( '/\r\n|\r|\n/', self::enc( $utf8 ) ) as $line ) {
			$words = explode( ' ', $line );
			$cur   = '';
			foreach ( $words as $word ) {
				$try = '' === $cur ? $word : $cur . ' ' . $word;
				if ( '' !== $cur && $this->width( $try ) > $w ) {
					$this->line_out( $x, $cur, $lh );
					$cur = $word;
				} else {
					$cur = $try;
				}
			}
			$this->line_out( $x, $cur, $lh );
		}
	}

	/**
	 * Output one wrapped line, breaking the page when needed.
	 *
	 * @param float  $x  X.
	 * @param string $s  Encoded text.
	 * @param float  $lh Line height.
	 */
	private function line_out( $x, $s, $lh ) {
		$this->need( $lh );
		$this->text( $x, $this->y, $s );
		$this->y += $lh;
	}

	/**
	 * Make sure there is room for $h mm, otherwise start a new page.
	 *
	 * @param float $h Height needed.
	 */
	public function need( $h ) {
		if ( $this->y + $h > self::H - $this->margin ) {
			$this->add_page();
			$this->buf .= "0.067 0.094 0.153 rg\n"; // Default text colour on new pages.
		}
	}

	/**
	 * Build the PDF bytes.
	 *
	 * @return string
	 */
	public function output() {
		$pages   = $this->pages;
		$pages[] = $this->buf;
		$objs    = array();
		$n       = count( $pages );
		// 1 catalog, 2 pages, 3-4 fonts, 5 info, then page + content pairs.
		$kids = array();
		for ( $i = 0; $i < $n; $i++ ) {
			$kids[] = ( 6 + 2 * $i ) . ' 0 R';
		}
		$objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
		$objs[2] = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . $n . ' >>';
		$objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
		$objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
		$objs[5] = '<< /Title (' . $this->pdf_str( self::enc( $this->title ) ) . ') /Author (' . $this->pdf_str( self::enc( $this->author ) ) . ') /Producer (OGNIVID AI Assistant) >>';
		foreach ( $pages as $i => $stream ) {
			$p             = 6 + 2 * $i;
			$data          = function_exists( 'gzcompress' ) ? gzcompress( $stream ) : $stream;
			$filter        = function_exists( 'gzcompress' ) ? ' /Filter /FlateDecode' : '';
			$objs[ $p ]    = sprintf( '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', self::W * self::K, self::H * self::K, $p + 1 );
			$objs[ $p + 1 ] = '<< /Length ' . strlen( $data ) . $filter . " >>\nstream\n" . $data . "\nendstream";
		}
		$out     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = array();
		ksort( $objs );
		foreach ( $objs as $id => $body ) {
			$offsets[ $id ] = strlen( $out );
			$out           .= $id . " 0 obj\n" . $body . "\nendobj\n";
		}
		$xref = strlen( $out );
		$out .= 'xref' . "\n0 " . ( count( $objs ) + 1 ) . "\n0000000000 65535 f \n";
		foreach ( $offsets as $off ) {
			$out .= sprintf( "%010d 00000 n \n", $off );
		}
		$out .= 'trailer << /Size ' . ( count( $objs ) + 1 ) . " /Root 1 0 R /Info 5 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
		return $out;
	}

	/**
	 * Escape a PDF literal string.
	 *
	 * @param string $s Text.
	 * @return string
	 */
	private function pdf_str( $s ) {
		return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $s );
	}
}
