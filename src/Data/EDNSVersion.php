<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


readonly class EDNSVersion {


    public function __construct( public int $value ) {
        if ( $this->value < 0 || $this->value > 255 ) {
            throw new \InvalidArgumentException( "EDNS version must be between 0 and 255, got {$this->value}" );
        }
    }


    public static function normalize( int|EDNSVersion $i_value ) : EDNSVersion {
        if ( $i_value instanceof EDNSVersion ) {
            return $i_value;
        }
        return new EDNSVersion( $i_value );
    }


    public function toFlagTTL() : int {
        return $this->value << 16; // Shift left by 16 bits to fit in the TTL field
    }


}
