<?php
namespace WP\IXR;
/**
 * @package IXR
 * @since 1.5.0
 */
class Value {
	/**
	 * @var mixed
	 */
	private $data;
	/**
	 * @var string
	 */
	private $type;

	public function __construct( $data, string $type = '' ) {
		$this->data = $data;
		if ( ! $type ) {
			$type = $this->calculateType();
		}
		$this->type = $type;
		if ( 'struct' === $type ) {
			// Turn all the values in the array in to new IXR_Value objects
			foreach ( $this->data as $key => $value ) {
				$this->data[ $key ] = new self( $value );
			}
		}

		if ( 'array' === $type ) {
			for ( $i = 0, $j = count( $this->data ); $i < $j; $i++ ) {
				$this->data[ $i ] = new self( $this->data[ $i ] );
			}
		}
	}

	private function calculateType(): string
	{
		if ( true === $this->data || false === $this->data ) {
			return 'boolean';
		}

		if ( is_integer( $this->data ) ) {
			return 'int';
		}

		if ( is_double( $this->data ) ) {
			return 'double';
		}

		// Deal with IXR object types base64 and date
		if ( is_object( $this->data ) ) {
			if ( $this->data instanceof Date ) {
				return 'date';
			}

			if ( $this->data instanceof Base64 ) {
				return 'base64';
			}

			$this->data = get_object_vars( $this->data );
			return 'struct';
		}

		if ( ! is_array( $this->data ) ) {
			return 'string';
		}

		// We have an array - is it an array or a struct?
		if ( $this->isStruct( $this->data ) ) {
			return 'struct';
		}
		return 'array';
	}

	public function getXml(): string
	{
		// Return XML for this value
		switch ( $this->type ) {
		case 'boolean':
			return '<boolean>' . ( $this->data ? '1' : '0' ) . '</boolean>';

		case 'int':
			return '<int>' . $this->data . '</int>';

		case 'double':
			return '<double>' . $this->data . '</double>';

		case 'string':
			return '<string>' . htmlspecialchars( $this->data ) . '</string>';

		case 'array':
			$return = "<array><data>\n";
			foreach ( $this->data as $item ) {
				$return .= '<value>' . $item->getXml() . "</value>\n";
			}
			return $return . '</data></array>';

		case 'struct':
			$return = "<struct>\n";
			foreach ( $this->data as $name => $value ) {
				$name = htmlspecialchars( $name );
				$return .= '<member><name>' . $name . '</name><value>';
				$return .= $value->getXml() . "</value></member>\n";
			}
			return $return . '</struct>';

		case 'date':
		case 'base64':
			return $this->data->getXml();
		}
		return false;
	}

	/**
	 * Checks whether or not the supplied array is a struct
	 *
	 * @param array $array
	 * @return bool
	 */
	private function isStruct( array $array ): bool
	{
		$expected = 0;
		foreach ( array_keys( $array ) as $key ) {
			if ( (string) $key !== (string) $expected ) {
				return true;
			}
			$expected++;
		}
		return false;
	}
}
