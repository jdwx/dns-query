<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


enum RA: int {


    case RECURSION_NOT_AVAILABLE = 0;

    case RECURSION_AVAILABLE     = 1;


    public static function fromBool( bool $bool ) : self {
        return $bool ? self::RECURSION_AVAILABLE : self::RECURSION_NOT_AVAILABLE;
    }


    public static function fromFlagWord( int $binary ) : self {
        return match ( ( $binary >> 7 ) & 0x1 ) {
            0 => self::RECURSION_NOT_AVAILABLE,
            1 => self::RECURSION_AVAILABLE,
        };
    }


    public function toBool() : bool {
        return (bool) $this->value;
    }


    public function toFlag() : string {
        return $this === RA::RECURSION_AVAILABLE ? 'ra ' : '';
    }


    public function toFlagWord() : int {
        return $this->value ? 128 : 0;
    }


}
