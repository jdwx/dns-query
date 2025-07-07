<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\FlagException;


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


    public static function normalize( bool|int|self $i_value ) : self {
        $x = self::tryNormalize( $i_value );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new FlagException( 'Invalid DOK value: ' . print_r( $i_value, true ) );
    }


    public static function tryNormalize( bool|int|self $i_value ) : ?self {
        if ( $i_value instanceof self ) {
            return $i_value;
        }
        if ( is_bool( $i_value ) ) {
            return self::fromBool( $i_value );
        }
        return self::tryFrom( $i_value );
    }


    public function is( bool|int|DOK $i_value ) : bool {
        return $this === self::tryNormalize( $i_value );
    }


    public function toFlag() : string {
        return $this === self::DNSSEC_OK ? 'do ' : '';
    }


    public function toFlagTTL() : int {
        return match ( $this ) {
            self::DNSSEC_NOT_SUPPORTED => 0,
            self::DNSSEC_OK => 0x8000,
        };
    }


}
