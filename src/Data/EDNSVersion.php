<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\FlagException;


readonly class EDNSVersion {


    public function __construct( public int $value ) {
        if ( $this->value < 0 || $this->value > 255 ) {
            throw new FlagException( "EDNS version must be between 0 and 255, got {$this->value}" );
        }
    }


    public static function from( int $i_uValue ) : self {
        return new self( $i_uValue );
    }


    public static function fromFlagTTL( int $i_flagTTL ) : self {
        $i_flagTTL &= 0xFF0000; // Mask to get the version bits
        return new self( $i_flagTTL >> 16 ); // Shift right to get the version
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
