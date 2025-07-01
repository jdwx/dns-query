<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\FlagException;


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


    public static function fromName( string $name ) : self {
        $x = self::tryFromName( $name );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new FlagException( "Invalid RA name: $name" );
    }


    public static function normalize( bool|int|string|RA $value ) : self {
        if ( is_bool( $value ) ) {
            return self::fromBool( $value );
        }
        if ( is_int( $value ) ) {
            return self::fromFlagWord( $value );
        }
        if ( is_string( $value ) ) {
            return self::fromName( $value );
        }
        return $value;
    }


    public static function tryFromName( string $name ) : ?self {
        return match ( $name ) {
            'nora', 'recursion_not_available' => self::RECURSION_NOT_AVAILABLE,
            'ra', 'recursion_available' => self::RECURSION_AVAILABLE,
            default => null,
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
