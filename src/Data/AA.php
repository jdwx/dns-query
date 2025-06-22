<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


enum AA: int {


    case NON_AUTHORITATIVE = 0;

    case AUTHORITATIVE     = 1;


    public static function fromBool( bool $bool ) : self {
        return $bool ? self::AUTHORITATIVE : self::NON_AUTHORITATIVE;
    }


    public static function fromFlagWord( int $binary ) : self {
        return match ( ( $binary >> 10 ) & 0x1 ) {
            0 => self::NON_AUTHORITATIVE,
            1 => self::AUTHORITATIVE,
        };
    }


    public function toBool() : bool {
        return (bool) $this->value;
    }


    public function toFlag() : string {
        return $this === AA::AUTHORITATIVE ? 'aa ' : '';
    }


    public function toFlagWord() : int {
        return $this->value ? 1024 : 0;
    }


}
