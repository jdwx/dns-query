<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\Strict\TypeIs;


enum RecordClass: int {


    case IN   = 1; // Internet (RFC 1035)

    case CH   = 3; // Chaos (RFC 1035, obsolete)

    case HS   = 4; // Hesiod (RFC 1035, obsolete)

    case NONE = 254; // No class (RFC 2136)

    case ANY  = 255; // Any class (RFC 1035)


    public static function anyToId( int|string|self $i_value ) : int {
        if ( is_int( $i_value ) ) {
            return self::requireValidId( $i_value );
        }
        if ( is_string( $i_value ) ) {
            $x = self::tryFromName( $i_value );
            if ( $x instanceof self ) {
                return $x->value;
            }
            if ( preg_match( '/^CLASS(\d+)$/i', $i_value, $matches ) ) {
                $u = intval( $matches[ 1 ] );
                if ( $u >= 0 && $u <= 65535 ) {
                    return $u;
                }
            }
            throw new RecordClassException( "Invalid record class name: {$i_value}" );
        }
        $i_value = self::normalize( $i_value );
        return $i_value->value;
    }


    public static function anyToName( int|string|self $i_value ) : string {
        $id = self::anyToId( $i_value );
        $x = self::tryFrom( $id );
        if ( $x instanceof self ) {
            return $x->name;
        }
        return "CLASS{$id}";
    }


    public static function consume( ReadBufferInterface $i_buffer ) : self {
        $id = $i_buffer->consumeUINT16();
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
        if ( preg_match( '/CLASS(\d+)/i', $i_stName, $matches ) ) {
            $x = self::tryFrom( intval( $matches[ 1 ] ) );
            if ( $x instanceof self ) {
                return $x;
            }
        }
        throw new RecordClassException( "Invalid record class name: {$i_stName}" );
    }


    public static function idToName( int $i_id ) : string {
        $i_id = self::requireValidId( $i_id );
        $name = self::tryIdToName( $i_id );
        if ( is_string( $name ) ) {
            return $name;
        }
        return "CLASS{$i_id}";
    }


    public static function isKnownId( int $i_id ) : bool {
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


    public static function tryConsume( ReadBufferInterface $i_buffer ) : ?self {
        return self::tryFrom( $i_buffer->consumeUINT16() );
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


    protected static function requireValidId( int $i_id ) : int {
        if ( $i_id < 0 || $i_id > 65535 ) {
            throw new RecordClassException( "Invalid record class ID: {$i_id}" );
        }
        return $i_id;
    }


    public function is( int|string|self|ResourceRecord $i_value ) : bool {
        if ( $i_value instanceof ResourceRecord ) {
            $i_value = $i_value->classValue();
        }
        $i_value = self::normalize( $i_value );
        return $this === $i_value;
    }


    public function toBinary() : string {
        return pack( 'n', $this->value );
    }


}
