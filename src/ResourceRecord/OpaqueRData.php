<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use JDWX\DNSQuery\Data\RDataType;


class OpaqueRData extends AbstractRData {


    public function __construct( public string $stData ) {}


    public function __toString() : string {
        return bin2hex( $this->stData );
    }


    public function offsetGet( mixed $offset ) : ?string {
        if ( 'rdata' !== $offset ) {
            return null;
        }
        return $this->stData;
    }


    public function offsetSet( mixed $offset, mixed $value ) : void {
        if ( 'rdata' !== $offset ) {
            throw new \InvalidArgumentException( "Invalid RData key: \"{$offset}\"" );
        }
        if ( ! is_string( $value ) ) {
            throw new \InvalidArgumentException( 'Value must be a string, ' . get_debug_type( $value ) . ' given' );
        }
        $this->stData = $value;
    }


    public function toArray() : array {
        return [ 'rdata' => $this->stData ];
    }


    /**
     * @return \Generator<string, RDataValueInterface>
     */
    public function values() : \Generator {
        yield 'rdata' => new RDataValue( RDataType::HexBinary, $this->stData );
    }


    protected function validKeys() : array {
        return [ 'rdata' ];
    }


}
