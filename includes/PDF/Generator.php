<?php
/**
 * PDF generator.
 *
 * @package CertBuilder
 */

namespace CertBuilder\PDF;

use TCPDF;

/**
 * Class Generator
 *
 * Generates certificate PDFs from templates.
 */
class Generator {

	/**
	 * TCPDF instance.
	 *
	 * @var TCPDF|null
	 */
	private ?TCPDF $pdf = null;

	/**
	 * Registered fonts.
	 *
	 * @var array
	 */
	private array $registered_fonts = [];

	/**
	 * Generate a certificate PDF.
	 *
	 * @param int    $template_id Template ID.
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID.
	 * @param string $connector_id Connector ID.
	 * @param array  $extra_data Additional field values.
	 * @return string PDF content.
	 */
	public function generate( int $template_id, int $user_id, int $object_id, string $connector_id, array $extra_data = [] ): string {
		// Load template.
		$template = certbuilder()->template()->get( $template_id );
		if ( ! $template || empty( $template['data'] ) ) {
			throw new \Exception( 'Template not found or empty' );
		}

		$template_data = $template['data'];

		// Build context for field resolution.
		$context = array_merge(
			$extra_data,
			[
				'user_id'   => $user_id,
				'object_id' => $object_id,
				'connector' => $connector_id,
			]
		);

		// Get connector and resolve LMS-specific fields.
		$connector = certbuilder()->connectors()->get( $connector_id );
		if ( $connector && $connector->is_available() ) {
			foreach ( array_keys( $connector->get_dynamic_fields() ) as $field ) {
				if ( ! isset( $context[ $field ] ) ) {
					$context[ $field ] = $connector->get_field_value( $field, $user_id, $object_id );
				}
			}
		}

		// Initialize PDF.
		$this->init_pdf( $template_data );

		// Render background.
		$this->render_background( $template_data );

		// Render elements.
		if ( ! empty( $template_data['elements'] ) ) {
			// Sort by layer order if available.
			$elements = $template_data['elements'];
			usort(
				$elements,
				function ( $a, $b ) {
					$order_a = $a['layerOrder'] ?? 0;
					$order_b = $b['layerOrder'] ?? 0;
					return $order_a <=> $order_b;
				}
			);

			foreach ( $elements as $element ) {
				$this->render_element( $element, $context );
			}
		}

		// Return PDF content.
		return $this->pdf->Output( '', 'S' );
	}

	/**
	 * Generate PDF and save to file.
	 *
	 * @param int    $template_id Template ID.
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID.
	 * @param string $connector_id Connector ID.
	 * @param string $file_path File path to save to.
	 * @param array  $extra_data Additional field values.
	 * @return bool Success.
	 */
	public function generate_to_file( int $template_id, int $user_id, int $object_id, string $connector_id, string $file_path, array $extra_data = [] ): bool {
		try {
			$content = $this->generate( $template_id, $user_id, $object_id, $connector_id, $extra_data );

			return (bool) file_put_contents( $file_path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Initialize TCPDF with template settings.
	 *
	 * @param array $template Template data.
	 */
	private function init_pdf( array $template ): void {
		$width  = $template['size']['width'] ?? 792;
		$height = $template['size']['height'] ?? 612;

		// Determine orientation.
		$orientation = $template['orientation'] ?? 'landscape';
		$orientation = 'landscape' === $orientation ? 'L' : 'P';

		// Create custom page size.
		$page_size = [ $width, $height ];

		// Initialize TCPDF.
		$this->pdf = new TCPDF( $orientation, 'pt', $page_size, true, 'UTF-8', false );

		// Remove default header/footer.
		$this->pdf->setPrintHeader( false );
		$this->pdf->setPrintFooter( false );

		// Set margins to 0.
		$this->pdf->SetMargins( 0, 0, 0 );
		$this->pdf->SetAutoPageBreak( false, 0 );

		// Add a page.
		$this->pdf->AddPage();

		// Register fonts.
		$this->register_fonts();
	}

	/**
	 * Register fonts with TCPDF.
	 */
	private function register_fonts(): void {
		$fonts        = certbuilder()->fonts()->get_all();
		$fonts_dir    = CERTBUILDER_PATH . 'assets/fonts/';
		$tcpdf_fonts  = TCPDF_FONTS::getFontPath();

		foreach ( $fonts as $font_id => $font ) {
			foreach ( $font['variants'] as $variant ) {
				$file_path = $fonts_dir . ( $font['files'][ $variant ] ?? '' );

				if ( ! file_exists( $file_path ) ) {
					continue;
				}

				// Generate a unique font name.
				$font_name = sanitize_file_name( $font_id . '-' . $variant );

				// Convert TTF to TCPDF format if needed.
				$tcpdf_font = TCPDF_FONTS::addTTFfont( $file_path, 'TrueTypeUnicode', '', 32 );

				if ( $tcpdf_font ) {
					$this->registered_fonts[ $font_id ][ $variant ] = $tcpdf_font;
				}
			}
		}
	}

	/**
	 * Get TCPDF font name.
	 *
	 * @param string $font_family Font family ID.
	 * @param bool   $bold Is bold.
	 * @param bool   $italic Is italic.
	 * @return string TCPDF font name.
	 */
	private function get_tcpdf_font( string $font_family, bool $bold = false, bool $italic = false ): string {
		$variant = 'regular';
		if ( $bold && $italic ) {
			$variant = 'bolditalic';
		} elseif ( $bold ) {
			$variant = 'bold';
		} elseif ( $italic ) {
			$variant = 'italic';
		}

		// Check if we have this font registered.
		if ( isset( $this->registered_fonts[ $font_family ][ $variant ] ) ) {
			return $this->registered_fonts[ $font_family ][ $variant ];
		}

		// Fallback to regular if variant not available.
		if ( isset( $this->registered_fonts[ $font_family ]['regular'] ) ) {
			return $this->registered_fonts[ $font_family ]['regular'];
		}

		// Default fallback.
		return 'helvetica';
	}

	/**
	 * Render background.
	 *
	 * @param array $template Template data.
	 */
	private function render_background( array $template ): void {
		$background = $template['background'] ?? [];
		$width      = $template['size']['width'] ?? 792;
		$height     = $template['size']['height'] ?? 612;

		if ( empty( $background ) ) {
			// Default white background.
			$this->pdf->SetFillColor( 255, 255, 255 );
			$this->pdf->Rect( 0, 0, $width, $height, 'F' );
			return;
		}

		$type = $background['type'] ?? 'color';

		switch ( $type ) {
			case 'color':
				$color = $this->hex_to_rgb( $background['value'] ?? '#ffffff' );
				$this->pdf->SetFillColor( $color['r'], $color['g'], $color['b'] );
				$this->pdf->Rect( 0, 0, $width, $height, 'F' );
				break;

			case 'image':
				$image_url = $background['value'] ?? '';
				if ( $image_url ) {
					$image_path = $this->url_to_path( $image_url );
					if ( $image_path && file_exists( $image_path ) ) {
						$this->pdf->Image( $image_path, 0, 0, $width, $height, '', '', '', false, 300, '', false, false, 0 );
					}
				}
				break;

			case 'gradient':
				// TCPDF doesn't support gradients natively, render as solid color.
				$color = $this->hex_to_rgb( $background['startColor'] ?? '#ffffff' );
				$this->pdf->SetFillColor( $color['r'], $color['g'], $color['b'] );
				$this->pdf->Rect( 0, 0, $width, $height, 'F' );
				break;
		}
	}

	/**
	 * Render an element.
	 *
	 * @param array $element Element data.
	 * @param array $context Field values context.
	 */
	private function render_element( array $element, array $context ): void {
		$type = $element['type'] ?? '';

		switch ( $type ) {
			case 'text':
				$this->render_text( $element, $context );
				break;

			case 'dynamic_field':
				$this->render_dynamic_field( $element, $context );
				break;

			case 'image':
				$this->render_image( $element );
				break;

			case 'shape':
				$this->render_shape( $element );
				break;

			case 'qr_code':
				$this->render_qr_code( $element, $context );
				break;

			case 'line':
				$this->render_line( $element );
				break;
		}
	}

	/**
	 * Render text element.
	 *
	 * @param array $element Element data.
	 * @param array $context Context for variable replacement.
	 */
	private function render_text( array $element, array $context ): void {
		$content = $element['content'] ?? '';

		// Replace any {{field}} placeholders.
		$content = $this->replace_placeholders( $content, $context );

		$this->render_text_content( $element, $content );
	}

	/**
	 * Render dynamic field element.
	 *
	 * @param array $element Element data.
	 * @param array $context Context with field values.
	 */
	private function render_dynamic_field( array $element, array $context ): void {
		$field = $element['field'] ?? '';

		// Get the field value.
		$content = '';
		if ( isset( $context[ $field ] ) ) {
			$content = $context[ $field ];
		} else {
			// Try to resolve from fields registry.
			$content = certbuilder()->fields()->resolve( $field, $context );
		}

		// Apply prefix/suffix if defined.
		$prefix = $element['prefix'] ?? '';
		$suffix = $element['suffix'] ?? '';
		$content = $prefix . $content . $suffix;

		$this->render_text_content( $element, $content );
	}

	/**
	 * Render text content with styling.
	 *
	 * @param array  $element Element data with styling.
	 * @param string $content Text content to render.
	 */
	private function render_text_content( array $element, string $content ): void {
		if ( empty( $content ) ) {
			return;
		}

		$x          = $element['x'] ?? 0;
		$y          = $element['y'] ?? 0;
		$font_size  = $element['fontSize'] ?? 12;
		$font_family = $element['fontFamily'] ?? 'helvetica';
		$font_weight = $element['fontWeight'] ?? 'normal';
		$font_style  = $element['fontStyle'] ?? 'normal';
		$fill       = $element['fill'] ?? '#000000';
		$align      = $element['align'] ?? 'left';
		$width      = $element['width'] ?? 0;
		$rotation   = $element['rotation'] ?? 0;

		// Determine font style.
		$bold   = in_array( $font_weight, [ 'bold', '700', '800', '900' ], true );
		$italic = 'italic' === $font_style;

		// Get TCPDF font name.
		$tcpdf_font = $this->get_tcpdf_font( $font_family, $bold, $italic );

		// Set font.
		$style = '';
		if ( $bold ) {
			$style .= 'B';
		}
		if ( $italic ) {
			$style .= 'I';
		}

		$this->pdf->SetFont( $tcpdf_font, $style, $font_size );

		// Set text color.
		$color = $this->hex_to_rgb( $fill );
		$this->pdf->SetTextColor( $color['r'], $color['g'], $color['b'] );

		// Map alignment.
		$tcpdf_align = 'L';
		switch ( $align ) {
			case 'center':
				$tcpdf_align = 'C';
				break;
			case 'right':
				$tcpdf_align = 'R';
				break;
		}

		// Handle rotation.
		if ( $rotation && 0 !== $rotation ) {
			$this->pdf->StartTransform();
			$this->pdf->Rotate( -$rotation, $x, $y );
		}

		// Calculate text width if center/right aligned and no width specified.
		if ( 0 === $width && 'left' !== $align ) {
			$width = $this->pdf->GetStringWidth( $content );
		}

		// Adjust X for alignment.
		$adjusted_x = $x;
		if ( 'center' === $align && $width > 0 ) {
			$adjusted_x = $x - ( $width / 2 );
		} elseif ( 'right' === $align && $width > 0 ) {
			$adjusted_x = $x - $width;
		}

		// Render text.
		if ( $width > 0 ) {
			$this->pdf->SetXY( $adjusted_x, $y );
			$this->pdf->Cell( $width, $font_size, $content, 0, 0, $tcpdf_align );
		} else {
			$this->pdf->Text( $x, $y, $content );
		}

		// End rotation.
		if ( $rotation && 0 !== $rotation ) {
			$this->pdf->StopTransform();
		}
	}

	/**
	 * Render image element.
	 *
	 * @param array $element Element data.
	 */
	private function render_image( array $element ): void {
		$src    = $element['src'] ?? '';
		$x      = $element['x'] ?? 0;
		$y      = $element['y'] ?? 0;
		$width  = $element['width'] ?? 0;
		$height = $element['height'] ?? 0;

		if ( empty( $src ) ) {
			return;
		}

		// Convert URL to path.
		$image_path = $this->url_to_path( $src );

		if ( ! $image_path || ! file_exists( $image_path ) ) {
			// Try as direct URL.
			$image_path = $src;
		}

		// Handle rotation.
		$rotation = $element['rotation'] ?? 0;
		if ( $rotation && 0 !== $rotation ) {
			$this->pdf->StartTransform();
			$this->pdf->Rotate( -$rotation, $x + ( $width / 2 ), $y + ( $height / 2 ) );
		}

		$this->pdf->Image( $image_path, $x, $y, $width, $height, '', '', '', false, 300 );

		if ( $rotation && 0 !== $rotation ) {
			$this->pdf->StopTransform();
		}
	}

	/**
	 * Render shape element.
	 *
	 * @param array $element Element data.
	 */
	private function render_shape( array $element ): void {
		$shape_type = $element['shapeType'] ?? 'rectangle';
		$x          = $element['x'] ?? 0;
		$y          = $element['y'] ?? 0;
		$width      = $element['width'] ?? 100;
		$height     = $element['height'] ?? 100;
		$fill       = $element['fill'] ?? '#000000';
		$stroke     = $element['stroke'] ?? '';
		$stroke_width = $element['strokeWidth'] ?? 1;

		// Set fill color.
		$fill_color = $this->hex_to_rgb( $fill );
		$this->pdf->SetFillColor( $fill_color['r'], $fill_color['g'], $fill_color['b'] );

		// Set stroke if present.
		$style = 'F';
		if ( $stroke ) {
			$stroke_color = $this->hex_to_rgb( $stroke );
			$this->pdf->SetDrawColor( $stroke_color['r'], $stroke_color['g'], $stroke_color['b'] );
			$this->pdf->SetLineWidth( $stroke_width );
			$style = 'DF';
		}

		// Handle rotation.
		$rotation = $element['rotation'] ?? 0;
		if ( $rotation && 0 !== $rotation ) {
			$this->pdf->StartTransform();
			$this->pdf->Rotate( -$rotation, $x + ( $width / 2 ), $y + ( $height / 2 ) );
		}

		switch ( $shape_type ) {
			case 'rectangle':
				$this->pdf->Rect( $x, $y, $width, $height, $style );
				break;

			case 'circle':
				$radius = min( $width, $height ) / 2;
				$this->pdf->Circle( $x + $radius, $y + $radius, $radius, 0, 360, $style );
				break;

			case 'ellipse':
				$this->pdf->Ellipse( $x + ( $width / 2 ), $y + ( $height / 2 ), $width / 2, $height / 2, 0, 0, 360, $style );
				break;
		}

		if ( $rotation && 0 !== $rotation ) {
			$this->pdf->StopTransform();
		}
	}

	/**
	 * Render line element.
	 *
	 * @param array $element Element data.
	 */
	private function render_line( array $element ): void {
		$x1         = $element['x1'] ?? $element['x'] ?? 0;
		$y1         = $element['y1'] ?? $element['y'] ?? 0;
		$x2         = $element['x2'] ?? ( $x1 + ( $element['width'] ?? 100 ) );
		$y2         = $element['y2'] ?? $y1;
		$stroke     = $element['stroke'] ?? '#000000';
		$stroke_width = $element['strokeWidth'] ?? 1;

		$color = $this->hex_to_rgb( $stroke );
		$this->pdf->SetDrawColor( $color['r'], $color['g'], $color['b'] );
		$this->pdf->SetLineWidth( $stroke_width );

		$this->pdf->Line( $x1, $y1, $x2, $y2 );
	}

	/**
	 * Render QR code element.
	 *
	 * @param array $element Element data.
	 * @param array $context Context with certificate data.
	 */
	private function render_qr_code( array $element, array $context ): void {
		$x     = $element['x'] ?? 0;
		$y     = $element['y'] ?? 0;
		$size  = $element['size'] ?? $element['width'] ?? 80;

		// Get verification URL.
		$verify_url = certbuilder()->fields()->resolve( 'verify_url', $context );

		if ( empty( $verify_url ) ) {
			return;
		}

		// Generate QR code using TCPDF's built-in method.
		$style = [
			'border'  => false,
			'vpadding' => 0,
			'hpadding' => 0,
			'fgcolor' => [ 0, 0, 0 ],
			'bgcolor' => false,
		];

		$this->pdf->write2DBarcode( $verify_url, 'QRCODE,L', $x, $y, $size, $size, $style );
	}

	/**
	 * Replace placeholders in text.
	 *
	 * @param string $text Text with placeholders.
	 * @param array  $context Context values.
	 * @return string Text with placeholders replaced.
	 */
	private function replace_placeholders( string $text, array $context ): string {
		return preg_replace_callback(
			'/\{\{(\w+)\}\}/',
			function ( $matches ) use ( $context ) {
				$field = $matches[1];
				if ( isset( $context[ $field ] ) ) {
					return $context[ $field ];
				}
				return certbuilder()->fields()->resolve( $field, $context );
			},
			$text
		);
	}

	/**
	 * Convert hex color to RGB.
	 *
	 * @param string $hex Hex color code.
	 * @return array RGB values.
	 */
	private function hex_to_rgb( string $hex ): array {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return [
			'r' => hexdec( substr( $hex, 0, 2 ) ),
			'g' => hexdec( substr( $hex, 2, 2 ) ),
			'b' => hexdec( substr( $hex, 4, 2 ) ),
		];
	}

	/**
	 * Convert URL to local file path.
	 *
	 * @param string $url URL to convert.
	 * @return string|null File path or null.
	 */
	private function url_to_path( string $url ): ?string {
		$upload_dir = wp_upload_dir();

		// Check if it's an upload URL.
		if ( strpos( $url, $upload_dir['baseurl'] ) !== false ) {
			return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
		}

		// Check if it's a plugin asset.
		if ( strpos( $url, CERTBUILDER_URL ) !== false ) {
			return str_replace( CERTBUILDER_URL, CERTBUILDER_PATH, $url );
		}

		// Check if it's already a path.
		if ( file_exists( $url ) ) {
			return $url;
		}

		return null;
	}
}
