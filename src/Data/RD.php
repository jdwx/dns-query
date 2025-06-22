<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


enum RD: int {


    case RECURSION_NOT_DESIRED = 0;

    case RECURSION_DESIRED     = 1;


    public static function fromBool( bool $bool ) : self {
        return $bool ? self::RECURSION_DESIRED : self::RECURSION_NOT_DESIRED;
    }


    public static function fromFlagWord( int $binary ) : self {
        return match ( ( $binary >> 8 ) & 0x1 ) {
            0 => self::RECURSION_NOT_DESIRED,
            1 => self::RECURSION_DESIRED,
        };
    }


    public function toBool() : bool {
        return (bool) $this->value;
    }


    public function toFlag() : string {
        return $this === RD::RECURSION_DESIRED ? 'rd ' : '';
    }


    public function toFlagWord() : int {
        return $this->value ? 256 : 0;
    }


}
