<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


/** Regrettably, this can't be called DO because that's a reserved word. */
enum DOK: int {


    case DNSSEC_NOT_SUPPORTED = 0;

    case DNSSEC_OK            = 1;


    public static function fromBool( bool $bool ) : self {
        return $bool ? self::DNSSEC_OK : self::DNSSEC_NOT_SUPPORTED;
    }


    public static function fromFlagTTL( int $i_flagTTL ) : self {
        $i_flagTTL &= 0x8000;
        return $i_flagTTL ? self::DNSSEC_OK : self::DNSSEC_NOT_SUPPORTED;
    }


    public static function normalize( bool|int|DOK $i_value ) : DOK {
        if ( is_bool( $i_value ) ) {
            return self::fromBool( $i_value );
        }
        if ( is_int( $i_value ) ) {
            return self::from( $i_value );
        }
        return $i_value;
    }


    public function toFlagTTL() : int {
        return match ( $this ) {
            self::DNSSEC_NOT_SUPPORTED => 0,
            self::DNSSEC_OK => 0x8000,
        };
    }


}
