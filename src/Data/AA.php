<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\FlagException;


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


    public static function fromName( string $name ) : self {
        $aa = self::tryFromName( $name );
        if ( $aa instanceof self ) {
            return $aa;
        }
        throw new FlagException( "Invalid AA name: '{$name}'" );
    }


    public static function normalize( bool|int|string|AA $i_aa ) : AA {
        if ( is_bool( $i_aa ) ) {
            $i_aa = self::fromBool( $i_aa );
        }
        if ( is_int( $i_aa ) ) {
            $i_aa = self::from( $i_aa );
        }
        if ( is_string( $i_aa ) ) {
            $i_aa = self::fromName( $i_aa );
        }
        return $i_aa;
    }


    public static function tryFromName( string $name ) : ?self {
        return match ( strtolower( trim( $name ) ) ) {
            'aa', 'authoritative'
            => self::AUTHORITATIVE,
            'noaa', 'non-authoritative', 'non_authoritative', 'nonauthoritative'
            => self::NON_AUTHORITATIVE,
            default => null,
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
