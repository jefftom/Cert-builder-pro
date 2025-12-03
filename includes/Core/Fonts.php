<?php
/**
 * Font management.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Core;

/**
 * Class Fonts
 *
 * Manages certificate fonts.
 */
class Fonts {

	/**
	 * Available fonts.
	 *
	 * @var array
	 */
	private array $fonts = [];

	/**
	 * Initialize fonts.
	 */
	public function init(): void {
		$this->register_default_fonts();

		/**
		 * Fires after default fonts are registered.
		 *
		 * @param Fonts $fonts Fonts instance.
		 */
		do_action( 'certbuilder_register_fonts', $this );
	}

	/**
	 * Register default fonts.
	 */
	private function register_default_fonts(): void {
		// Serif fonts.
		$this->register(
			'playfair-display',
			[
				'name'     => 'Playfair Display',
				'category' => 'serif',
				'variants' => [ 'regular', 'bold', 'italic', 'bolditalic' ],
				'files'    => [
					'regular'    => 'playfair-display-regular.ttf',
					'bold'       => 'playfair-display-bold.ttf',
					'italic'     => 'playfair-display-italic.ttf',
					'bolditalic' => 'playfair-display-bolditalic.ttf',
				],
			]
		);

		$this->register(
			'lora',
			[
				'name'     => 'Lora',
				'category' => 'serif',
				'variants' => [ 'regular', 'bold', 'italic', 'bolditalic' ],
				'files'    => [
					'regular'    => 'lora-regular.ttf',
					'bold'       => 'lora-bold.ttf',
					'italic'     => 'lora-italic.ttf',
					'bolditalic' => 'lora-bolditalic.ttf',
				],
			]
		);

		$this->register(
			'merriweather',
			[
				'name'     => 'Merriweather',
				'category' => 'serif',
				'variants' => [ 'regular', 'bold', 'italic', 'bolditalic' ],
				'files'    => [
					'regular'    => 'merriweather-regular.ttf',
					'bold'       => 'merriweather-bold.ttf',
					'italic'     => 'merriweather-italic.ttf',
					'bolditalic' => 'merriweather-bolditalic.ttf',
				],
			]
		);

		$this->register(
			'crimson-text',
			[
				'name'     => 'Crimson Text',
				'category' => 'serif',
				'variants' => [ 'regular', 'bold', 'italic' ],
				'files'    => [
					'regular' => 'crimson-text-regular.ttf',
					'bold'    => 'crimson-text-bold.ttf',
					'italic'  => 'crimson-text-italic.ttf',
				],
			]
		);

		// Sans-serif fonts.
		$this->register(
			'montserrat',
			[
				'name'     => 'Montserrat',
				'category' => 'sans-serif',
				'variants' => [ 'regular', 'bold', 'italic', 'bolditalic' ],
				'files'    => [
					'regular'    => 'montserrat-regular.ttf',
					'bold'       => 'montserrat-bold.ttf',
					'italic'     => 'montserrat-italic.ttf',
					'bolditalic' => 'montserrat-bolditalic.ttf',
				],
			]
		);

		$this->register(
			'open-sans',
			[
				'name'     => 'Open Sans',
				'category' => 'sans-serif',
				'variants' => [ 'regular', 'bold', 'italic', 'bolditalic' ],
				'files'    => [
					'regular'    => 'open-sans-regular.ttf',
					'bold'       => 'open-sans-bold.ttf',
					'italic'     => 'open-sans-italic.ttf',
					'bolditalic' => 'open-sans-bolditalic.ttf',
				],
			]
		);

		$this->register(
			'raleway',
			[
				'name'     => 'Raleway',
				'category' => 'sans-serif',
				'variants' => [ 'regular', 'bold', 'italic', 'bolditalic' ],
				'files'    => [
					'regular'    => 'raleway-regular.ttf',
					'bold'       => 'raleway-bold.ttf',
					'italic'     => 'raleway-italic.ttf',
					'bolditalic' => 'raleway-bolditalic.ttf',
				],
			]
		);

		$this->register(
			'roboto',
			[
				'name'     => 'Roboto',
				'category' => 'sans-serif',
				'variants' => [ 'regular', 'bold', 'italic', 'bolditalic' ],
				'files'    => [
					'regular'    => 'roboto-regular.ttf',
					'bold'       => 'roboto-bold.ttf',
					'italic'     => 'roboto-italic.ttf',
					'bolditalic' => 'roboto-bolditalic.ttf',
				],
			]
		);

		$this->register(
			'lato',
			[
				'name'     => 'Lato',
				'category' => 'sans-serif',
				'variants' => [ 'regular', 'bold', 'italic', 'bolditalic' ],
				'files'    => [
					'regular'    => 'lato-regular.ttf',
					'bold'       => 'lato-bold.ttf',
					'italic'     => 'lato-italic.ttf',
					'bolditalic' => 'lato-bolditalic.ttf',
				],
			]
		);

		$this->register(
			'poppins',
			[
				'name'     => 'Poppins',
				'category' => 'sans-serif',
				'variants' => [ 'regular', 'bold', 'italic', 'bolditalic' ],
				'files'    => [
					'regular'    => 'poppins-regular.ttf',
					'bold'       => 'poppins-bold.ttf',
					'italic'     => 'poppins-italic.ttf',
					'bolditalic' => 'poppins-bolditalic.ttf',
				],
			]
		);

		// Script/handwriting fonts.
		$this->register(
			'great-vibes',
			[
				'name'     => 'Great Vibes',
				'category' => 'handwriting',
				'variants' => [ 'regular' ],
				'files'    => [
					'regular' => 'great-vibes-regular.ttf',
				],
			]
		);

		$this->register(
			'dancing-script',
			[
				'name'     => 'Dancing Script',
				'category' => 'handwriting',
				'variants' => [ 'regular', 'bold' ],
				'files'    => [
					'regular' => 'dancing-script-regular.ttf',
					'bold'    => 'dancing-script-bold.ttf',
				],
			]
		);

		$this->register(
			'pacifico',
			[
				'name'     => 'Pacifico',
				'category' => 'handwriting',
				'variants' => [ 'regular' ],
				'files'    => [
					'regular' => 'pacifico-regular.ttf',
				],
			]
		);

		$this->register(
			'alex-brush',
			[
				'name'     => 'Alex Brush',
				'category' => 'handwriting',
				'variants' => [ 'regular' ],
				'files'    => [
					'regular' => 'alex-brush-regular.ttf',
				],
			]
		);

		$this->register(
			'allura',
			[
				'name'     => 'Allura',
				'category' => 'handwriting',
				'variants' => [ 'regular' ],
				'files'    => [
					'regular' => 'allura-regular.ttf',
				],
			]
		);

		// Display fonts.
		$this->register(
			'cinzel',
			[
				'name'     => 'Cinzel',
				'category' => 'display',
				'variants' => [ 'regular', 'bold' ],
				'files'    => [
					'regular' => 'cinzel-regular.ttf',
					'bold'    => 'cinzel-bold.ttf',
				],
			]
		);

		$this->register(
			'cormorant-garamond',
			[
				'name'     => 'Cormorant Garamond',
				'category' => 'serif',
				'variants' => [ 'regular', 'bold', 'italic' ],
				'files'    => [
					'regular' => 'cormorant-garamond-regular.ttf',
					'bold'    => 'cormorant-garamond-bold.ttf',
					'italic'  => 'cormorant-garamond-italic.ttf',
				],
			]
		);
	}

	/**
	 * Register a font.
	 *
	 * @param string $id Font ID.
	 * @param array  $args Font arguments.
	 */
	public function register( string $id, array $args ): void {
		$defaults = [
			'name'     => $id,
			'category' => 'sans-serif',
			'variants' => [ 'regular' ],
			'files'    => [],
		];

		$this->fonts[ $id ] = array_merge( $defaults, $args );
	}

	/**
	 * Get a font by ID.
	 *
	 * @param string $id Font ID.
	 * @return array|null Font data or null.
	 */
	public function get( string $id ): ?array {
		return $this->fonts[ $id ] ?? null;
	}

	/**
	 * Get all fonts.
	 *
	 * @return array All fonts.
	 */
	public function get_all(): array {
		return $this->fonts;
	}

	/**
	 * Get fonts grouped by category.
	 *
	 * @return array Fonts grouped by category.
	 */
	public function get_grouped(): array {
		$grouped = [];

		foreach ( $this->fonts as $id => $font ) {
			$category = $font['category'];

			if ( ! isset( $grouped[ $category ] ) ) {
				$grouped[ $category ] = [];
			}

			$grouped[ $category ][ $id ] = $font;
		}

		return $grouped;
	}

	/**
	 * Get font file path.
	 *
	 * @param string $id Font ID.
	 * @param string $variant Font variant (regular, bold, italic, bolditalic).
	 * @return string|null File path or null.
	 */
	public function get_file_path( string $id, string $variant = 'regular' ): ?string {
		$font = $this->get( $id );

		if ( ! $font || ! isset( $font['files'][ $variant ] ) ) {
			return null;
		}

		return CERTBUILDER_PATH . 'assets/fonts/' . $font['files'][ $variant ];
	}

	/**
	 * Get font file URL.
	 *
	 * @param string $id Font ID.
	 * @param string $variant Font variant.
	 * @return string|null File URL or null.
	 */
	public function get_file_url( string $id, string $variant = 'regular' ): ?string {
		$font = $this->get( $id );

		if ( ! $font || ! isset( $font['files'][ $variant ] ) ) {
			return null;
		}

		return CERTBUILDER_URL . 'assets/fonts/' . $font['files'][ $variant ];
	}

	/**
	 * Check if a font file exists.
	 *
	 * @param string $id Font ID.
	 * @param string $variant Font variant.
	 * @return bool Whether the font file exists.
	 */
	public function font_exists( string $id, string $variant = 'regular' ): bool {
		$path = $this->get_file_path( $id, $variant );

		return $path && file_exists( $path );
	}

	/**
	 * Get fonts for builder (simplified format for React).
	 *
	 * @return array Fonts for builder.
	 */
	public function get_for_builder(): array {
		$result = [];

		foreach ( $this->fonts as $id => $font ) {
			$result[] = [
				'id'       => $id,
				'name'     => $font['name'],
				'category' => $font['category'],
				'variants' => $font['variants'],
			];
		}

		return $result;
	}
}
