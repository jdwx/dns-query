<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


class ZBits implements \Stringable {


    public function __construct( public int $bits = 0 ) {}


    public static function fromFlagWord( int $binary ) : self {
        return new self( ( $binary >> 4 ) & 0x7 );
    }


    public static function normalize( int|ZBits $value ) : self {
        if ( is_int( $value ) ) {
            return new self( $value );
        }
        return $value;
    }


    public function __toString() : string {
        return strval( $this->bits );
    }


    public function toFlagWord() : int {
        return ( $this->bits & 0x7 ) << 4;
    }


}
