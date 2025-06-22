<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\Strict\OK;
use JDWX\Strict\TypeIs;


final class Binary {


    public static function consume( string $i_stData, int &$io_iOffset, int $i_Length ) : string {
        $st = self::tryConsume( $i_stData, $io_iOffset, $i_Length );
        if ( strlen( $st ) !== $i_Length ) {
            throw new \OutOfBoundsException( "Failed to consume {$i_Length} bytes from data." );
        }
        return $st;
    }


    public static function consume16BitInt( string $i_stData, int &$io_iOffset ) : int {
        $st = self::consume( $i_stData, $io_iOffset, 2 );
        return self::unpack16BitInt( $st );
    }


    public static function pack32BitInt( int $i_iValue ) : string {
        return OK::pack( 'N', $i_iValue );
    }


    public static function packNameUncompressed( string $i_stName ) : string {
        $st = '';
        $parts = explode( '.', $i_stName );
        foreach ( $parts as $part ) {
            if ( strlen( $part ) > 63 ) {
                throw new \LengthException( "Label in domain name exceeds 63 characters: {$part}" );
            }
            $st .= chr( strlen( $part ) ) . $part;
        }
        return $st . chr( 0 );
    }


    /**
     *
     * Consume a string, incrementing the offset by the length of the consumed string.
     *
     * @param string $i_stData
     * @param int $io_iOffset
     * @param int $i_Length
     * @return string
     */
    public static function tryConsume( string $i_stData, int &$io_iOffset, int $i_Length ) : string {
        $st = substr( $i_stData, $io_iOffset, $i_Length );
        $io_iOffset += strlen( $st );
        return $st;
    }


    public static function unpack16BitInt( string $i_stData ) : int {
        if ( strlen( $i_stData ) < 2 ) {
            throw new \OutOfBoundsException( 'Not enough data to unpack 16-bit integer.' );
        }
        return TypeIs::int( OK::unpack( 'n', $i_stData )[ 1 ] );
    }


}
