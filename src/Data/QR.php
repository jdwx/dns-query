<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


enum QR: int {


    case QUERY    = 0;

    case RESPONSE = 1;


    public static function fromName( string $name ) : QR {
        $qr = self::tryFromName( $name );
        if ( $qr instanceof self ) {
            return $qr;
        }
        throw new \InvalidArgumentException( "Invalid QR name: '$name'" );
    }


    public static function idToName( int $id ) : string {
        return self::tryFrom( $id )->name ?? throw new \InvalidArgumentException( "Invalid QR id: {$id}" );
    }


    public static function nameToId( string $name ) : int {
        return self::fromName( $name )->value;
    }


    public static function normalize( int|string|QR $i_qr ) : QR {
        if ( is_int( $i_qr ) ) {
            $i_qr = self::from( $i_qr );
        }
        if ( is_string( $i_qr ) ) {
            $i_qr = self::fromName( $i_qr );
        }
        return $i_qr;
    }


    public static function tryFromName( string $name ) : ?QR {
        $name = strtoupper( $name );
        return match ( $name ) {
            'QUERY' => self::QUERY,
            'RESPONSE' => self::RESPONSE,
            default => null,
        };
    }


}
