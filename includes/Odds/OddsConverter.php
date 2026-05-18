<?php
/**
 * Converts odds between decimal, fractional, and American formats.
 *
 * Reference: standard betting odds conversion formulas.
 *
 * @package WPOddsComparison\Odds
 */

declare(strict_types=1);

namespace WPOddsComparison\Odds;

/**
 * Stateless odds conversion utility.
 */
class OddsConverter {

	public const FORMAT_DECIMAL    = 'decimal';
	public const FORMAT_FRACTIONAL = 'fractional';
	public const FORMAT_AMERICAN   = 'american';

	/**
	 * Format decimal odds for display.
	 *
	 * @param float  $decimal Decimal odds (e.g. 2.50).
	 * @param string $format  Target format.
	 */
	public function format( float $decimal, string $format ): string {
		switch ( $format ) {
			case self::FORMAT_FRACTIONAL:
				return $this->to_fractional( $decimal );
			case self::FORMAT_AMERICAN:
				return $this->to_american( $decimal );
			default:
				return number_format( $decimal, 2, '.', '' );
		}
	}

	/**
	 * Convert any supported input to decimal.
	 *
	 * @param string|float $value  Input value.
	 * @param string       $format Source format.
	 */
	public function to_decimal( $value, string $format ): float {
		switch ( $format ) {
			case self::FORMAT_FRACTIONAL:
				return $this->fractional_to_decimal( (string) $value );
			case self::FORMAT_AMERICAN:
				return $this->american_to_decimal( (float) $value );
			default:
				return max( 1.01, (float) $value );
		}
	}

	/**
	 * Decimal to fractional string (e.g. 3/2).
	 */
	public function to_fractional( float $decimal ): string {
		if ( $decimal <= 1.0 ) {
			return '0/1';
		}

		$profit = $decimal - 1.0;
		$gcd    = $this->gcd( (int) round( $profit * 100 ), 100 );

		$num = (int) round( $profit * 100 ) / $gcd;
		$den = 100 / $gcd;

		return $num . '/' . $den;
	}

	/**
	 * Decimal to American moneyline.
	 */
	public function to_american( float $decimal ): string {
		if ( $decimal <= 1.0 ) {
			return '0';
		}

		if ( $decimal >= 2.0 ) {
			$american = (int) round( ( $decimal - 1 ) * 100 );
			return '+' . $american;
		}

		$american = (int) round( -100 / ( $decimal - 1 ) );

		return (string) $american;
	}

	/**
	 * Fractional string to decimal.
	 */
	public function fractional_to_decimal( string $fractional ): float {
		if ( strpos( $fractional, '/' ) === false ) {
			return max( 1.01, (float) $fractional );
		}

		list( $num, $den ) = array_map( 'floatval', explode( '/', $fractional, 2 ) );
		if ( $den <= 0 ) {
			return 1.01;
		}

		return 1.0 + ( $num / $den );
	}

	/**
	 * American moneyline to decimal.
	 */
	public function american_to_decimal( float $american ): float {
		if ( $american >= 100 ) {
			return 1.0 + ( $american / 100 );
		}

		if ( $american <= -100 ) {
			return 1.0 + ( 100 / abs( $american ) );
		}

		return 1.01;
	}

	/**
	 * Greatest common divisor for fraction reduction.
	 */
	private function gcd( int $a, int $b ): int {
		$a = abs( $a );
		$b = abs( $b );

		while ( 0 !== $b ) {
			$temp = $b;
			$b    = $a % $b;
			$a    = $temp;
		}

		return max( 1, $a );
	}
}
