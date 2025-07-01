<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\FlagException;


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


    public static function fromName( string $name ) : self {
        $x = self::tryFromName( $name );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new FlagException( "Invalid TC name: {$name}" );
    }


    public static function normalize( bool|int|string|TC $value ) : self {
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
            'notc', 'not_truncated' => self::NOT_TRUNCATED,
            'tc', 'truncated' => self::TRUNCATED,
            default => null,
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
