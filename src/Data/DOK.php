<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use InvalidArgumentException;


/** Regrettably, this can't be called DO because that's a reserved word. */
enum DOK: int {


    case DNSSEC_NOT_SUPPORTED = 0;

    case DNSSEC_OK            = 1;


    public static function normalize( bool|int|DOK $i_value ) : DOK {
        if ( $i_value instanceof DOK ) {
            return $i_value;
        }
        if ( is_bool( $i_value ) ) {
            return $i_value ? self::DNSSEC_OK : self::DNSSEC_NOT_SUPPORTED;
        }
        return match ( $i_value ) {
            0 => self::DNSSEC_NOT_SUPPORTED,
            1 => self::DNSSEC_OK,
            default => throw new InvalidArgumentException( "Invalid DOK value: {$i_value}" ),
        };
    }


    public function toFlagTTL() : int {
        return match ( $this ) {
            self::DNSSEC_NOT_SUPPORTED => 0,
            self::DNSSEC_OK => 0x8000,
        };
    }


}
