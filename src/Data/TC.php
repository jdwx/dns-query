<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


enum TC: int {


    case NOT_TRUNCATED = 0;

    case TRUNCATED     = 1;


    public static function fromBool( bool $bool ) : self {
        return $bool ? self::TRUNCATED : self::NOT_TRUNCATED;
    }


    public static function fromFlagWord( int $binary ) : self {
        return match ( ( $binary >> 9 ) & 0x1 ) {
            0 => self::NOT_TRUNCATED,
            1 => self::TRUNCATED,
        };
    }


    public function toBool() : bool {
        return (bool) $this->value;
    }


    public function toFlag() : string {
        return $this === TC::TRUNCATED ? 'tc ' : '';
    }


    public function toFlagWord() : int {
        return $this->value ? 512 : 0;
    }


}
