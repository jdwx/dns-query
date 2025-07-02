<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


enum QR: int {


    case QUERY    = 0;

    case RESPONSE = 1;


    public static function fromBool( bool $bool ) : self {
        return $bool ? self::RESPONSE : self::QUERY;
    }


    public static function fromFlagWord( int $binary ) : self {
        return self::from( ( $binary >> 15 ) & 0x1 );
    }


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


    public static function normalize( bool|int|string|QR $i_qr ) : QR {
        if ( is_bool( $i_qr ) ) {
            $i_qr = self::fromBool( $i_qr );
        }
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


    public function toFlag() : string {
        return $this === QR::RESPONSE ? 'qr ' : '';
    }


    public function toFlagWord() : int {
        return $this->value ? 32768 : 0;
    }


}
