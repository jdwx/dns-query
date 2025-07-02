<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\FlagException;


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


    public static function fromName( string $name ) : self {
        $x = self::tryFromName( $name );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new FlagException( "Invalid RD name: {$name}" );
    }


    public static function normalize( bool|int|string|RD $value ) : self {
        if ( is_bool( $value ) ) {
            return self::fromBool( $value );
        }
        if ( is_int( $value ) ) {
            return self::from( $value );
        }
        if ( is_string( $value ) ) {
            return self::fromName( $value );
        }
        return $value;
    }


    public static function tryFromName( string $name ) : ?self {
        return match ( strtolower( trim( $name ) ) ) {
            'nord', 'recursion_not_desired' => self::RECURSION_NOT_DESIRED,
            'rd', 'recursion_desired' => self::RECURSION_DESIRED,
            default => null,
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
