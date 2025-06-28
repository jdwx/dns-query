<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\Strict\TypeIs;


enum RecordClass : int {


    case IN = 1; // Internet (RFC 1035)

    case CH = 3; // Chaos (RFC 1035, obsolete)

    case HS = 4; // Hesiod (RFC 1035, obsolete)

    case NONE = 254; // No class (RFC 2136)

    case ANY = 255; // Any class (RFC 1035)


    public static function consume( string $i_stData, int &$io_iOffset ) : self {
        $id = Binary::consumeUINT16( $i_stData, $io_iOffset );
        return self::tryFrom( $id )
            ?? throw new RecordClassException( "Invalid record class ID in binary data: {$id}" );
    }


    public static function fromBinary( string $i_stBinary ) : self {
        $x = self::tryFromBinary( $i_stBinary );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordClassException( 'Invalid binary data for RecordClass' );
    }


    public static function fromName( string $i_stName ) : self {
        $x = self::tryFromName( $i_stName );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordClassException( "Invalid record class name: {$i_stName}" );
    }


    public static function idToName( int $i_id ) : string {
        $name = self::tryIdToName( $i_id );
        if ( is_string( $name ) ) {
            return $name;
        }
        throw new RecordClassException( "Invalid record class ID: {$i_id}" );
    }


    public static function isValidId( int $i_id ) : bool {
        return self::tryFrom( $i_id ) !== null;
    }


    public static function isValidName( string $i_stName ) : bool {
        return self::tryFromName( $i_stName ) !== null;
    }


    public static function nameToId( string $i_stName ) : int {
        $id = self::tryNameToId( $i_stName );
        if ( is_int( $id ) ) {
            return $id;
        }
        throw new RecordClassException( "Invalid record class name: {$i_stName}" );
    }


    public static function normalize( int|string|self $i_value ) : self {
        if ( is_int( $i_value ) ) {
            return self::tryFrom( $i_value )
                ?? throw new RecordClassException( "Invalid record class ID: {$i_value}" );
        }
        if ( is_string( $i_value ) ) {
            return self::fromName( $i_value );
        }
        return $i_value;
    }


    public static function tryConsume( string $i_stData, int &$io_iOffset ) : ?self {
        return self::tryFrom( Binary::consumeUINT16( $i_stData, $io_iOffset ) );
    }


    public static function tryFromBinary( string $i_stBinary ) : ?self {
        if ( strlen( $i_stBinary ) !== 2 ) {
            throw new RecordClassException( 'Invalid binary length for RecordClass' );
        }
        $value = TypeIs::int( unpack( 'n', $i_stBinary )[ 1 ] );
        return self::tryFrom( $value );
    }


    public static function tryFromName( string $i_stName ) : ?self {
        return match ( trim( strtoupper( $i_stName ) ) ) {
            'IN' => self::IN,
            'CH' => self::CH,
            'HS' => self::HS,
            'NONE' => self::NONE,
            'ANY' => self::ANY,
            default => null,
        };
    }


    public static function tryIdToName( int $i_id ) : ?string {
        return match ( $i_id ) {
            self::IN->value => 'IN',
            self::CH->value => 'CH',
            self::HS->value => 'HS',
            self::NONE->value => 'NONE',
            self::ANY->value => 'ANY',
            default => null,
        };
    }


    public static function tryNameToId( string $i_stName ) : ?int {
        return self::tryFromName( $i_stName )?->value;
    }


    public function is( int|string|self $i_value ) : bool {
        $i_value = self::normalize( $i_value );
        return $this === $i_value;
    }


    public function toBinary() : string {
        return pack( 'n', $this->value );
    }


}
