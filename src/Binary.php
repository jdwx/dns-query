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


    public static function consume32BitInt( string $i_stData, int &$io_iOffset ) : int {
        $st = self::consume( $i_stData, $io_iOffset, 4 );
        return self::unpack32BitInt( $st );
    }


    public static function consumeName( string $i_stData, int &$io_uOffset ) : string {
        # Names can be encoded as a series of labels, or as a pointer to a previously defined name, or
        # a combination of both.
        $st = '';
        while ( true ) {
            $st = self::consume( $i_stData, $io_uOffset, 1 );
            if ( $st === chr( 0 ) ) {
                return $st;
            }
            if ( ( ord( $st ) & 0xC0 ) === 0xC0 ) {
                # Pointer to a previously defined name.
                $st2 = self::consume( $i_stData, $io_uOffset, 1 );
                $pointer = ( ( ord( $st ) & 0x3F ) << 8 ) | ord( $st2 );
                return $st . $st2;
            }
        }

    }


    public static function pack16BitInt( int $i_iValue ) : string {
        return OK::pack( 'n', $i_iValue );
    }


    public static function pack32BitInt( int $i_iValue ) : string {
        return OK::pack( 'N', $i_iValue );
    }


    /** @param array<string, int> $io_rLabelMap */
    public static function packName( string $i_stName, array &$io_rLabelMap, int $i_uOffset ) : string {
        $st = '';
        $parts = explode( '.', $i_stName );
        while ( count( $parts ) > 0 ) {

            # If there is a matching existing label, use a pointer to it.
            $part = implode( '.', $parts );
            if ( isset( $io_rLabelMap[ $part ] ) ) {
                $pointer = $io_rLabelMap[ $part ];
                $st .= chr( 0xC0 | ( ( $pointer >> 8 ) & 0x3F ) ) . chr( $pointer & 0xFF );
                return $st;
            }

            # No matching label before, but there is now!
            $io_rLabelMap[ $part ] = $i_uOffset;

            $label = array_shift( $parts );
            if ( strlen( $label ) > 63 ) {
                throw new \LengthException( "Label in domain name exceeds 63 characters: {$label}" );
            }
            $st .= chr( strlen( $label ) ) . $label;
            $i_uOffset += 1 + strlen( $label );
        }
        return $st . chr( 0 );
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


    public static function unpack16BitInt( string $i_stData, int $i_uOffset = 0 ) : int {
        $st = substr( $i_stData, $i_uOffset, 2 );
        if ( strlen( $st ) < 2 ) {
            throw new \OutOfBoundsException( 'Not enough data to unpack 16-bit integer.' );
        }
        return TypeIs::int( OK::unpack( 'n', $i_stData )[ 1 ] );
    }


    public static function unpack32BitInt( string $i_stData, int $i_uOffset = 0 ) : int {
        $st = substr( $i_stData, $i_uOffset, 4 );
        if ( strlen( $st ) < 4 ) {
            throw new \OutOfBoundsException( 'Not enough data to unpack 32-bit integer.' );
        }
        return TypeIs::int( OK::unpack( 'N', $i_stData )[ 1 ] );
    }


}
