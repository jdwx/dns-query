<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use Generator;
use InvalidArgumentException;
use JDWX\Strict\OK;
use JDWX\Strict\TypeIs;
use LengthException;
use OutOfBoundsException;
use OutOfRangeException;


final class Binary {


    public static function packIPv4( string $i_stIPv4 ) : string {
        $st = inet_pton( $i_stIPv4 );
        if ( ! is_string( $st ) ) {
            throw new InvalidArgumentException( "Invalid IPv4 address: {$i_stIPv4}" );
        }
        return $st;
    }


    public static function packIPv6( string $i_stIPv6 ) : string {
        $st = inet_pton( $i_stIPv6 );
        if ( ! is_string( $st ) ) {
            throw new InvalidArgumentException( "Invalid IPv6 address: {$i_stIPv6}" );
        }
        return $st;
    }


    public static function packLabel( string $i_stLabel ) : string {
        if ( '' === $i_stLabel ) {
            throw new LengthException( 'Empty label in domain name.' );
        }
        if ( strlen( $i_stLabel ) > 63 ) {
            throw new LengthException( "Label in domain name exceeds 63 characters: {$i_stLabel}" );
        }
        return chr( strlen( $i_stLabel ) ) . $i_stLabel;
    }


    /**
     * @param list<string> $i_rLabels
     * @param array<string, int> $io_rLabelMap
     */
    public static function packLabels( array $i_rLabels, array &$io_rLabelMap, int $i_uOffset ) : string {
        $stOut = '';
        foreach ( self::splitLabelsArray( $i_rLabels ) as $stLabel => $rRest ) {
            $stRestKey = self::hashLabels( $rRest );

            # If there is a matching existing label, use a pointer to it.
            if ( isset( $io_rLabelMap[ $stRestKey ] ) ) {
                return $stOut . self::packPointer( $io_rLabelMap[ $stRestKey ] );
            }

            # No matching label before, but there is now!
            $io_rLabelMap[ $stRestKey ] = $i_uOffset;

            # Add the current label and keep going.
            $stOut .= self::packLabel( $stLabel );
            $i_uOffset += 1 + strlen( $stLabel );
        }
        return $stOut . chr( 0 );
    }


    /**
     * Packs a DNS name into a DNS packet using RFC1035 compression of labels, possibly adding
     * to the list of labels available for reuse in the process.
     *
     * @param array<string, int> $io_rLabelMap
     */
    public static function packName( string $i_stName, array &$io_rLabelMap, int $i_uOffset ) : string {
        $stOut = '';
        foreach ( self::splitLabels( $i_stName ) as $stLabel => $stRest ) {
            # If there is a matching existing label, use a pointer to it.
            if ( isset( $io_rLabelMap[ $stRest ] ) ) {
                return $stOut . self::packPointer( $io_rLabelMap[ $stRest ] );
            }

            # No matching label before, but there is now!
            $io_rLabelMap[ $stRest ] = $i_uOffset;

            # Add the current label and keep going.
            $stOut .= self::packLabel( $stLabel );
            $i_uOffset += 1 + strlen( $stLabel );
        }
        return $stOut . chr( 0 );
    }


    /**
     * This can be used as a fallback if you don't have the full packet to do label
     * compression. You can still reuse a list of existing labels (if you have one), but
     * you can't add to it.
     *
     * @param array<string, int>|null $i_rLabelMap
     */
    public static function packNameUncompressed( string $i_stName, ?array $i_rLabelMap = null ) : string {
        $stOut = '';
        foreach ( self::splitLabels( $i_stName ) as $stLabel => $stRest ) {
            if ( isset( $i_rLabelMap[ $stRest ] ) ) {
                return $stOut . self::packPointer( $i_rLabelMap[ $stRest ] );
            }
            $stOut .= self::packLabel( $stLabel );
        }
        return $stOut . chr( 0 );
    }


    public static function packPointer( int $i_uOffset ) : string {
        if ( $i_uOffset > 16_383 ) {
            throw new OutOfRangeException( "Offset {$i_uOffset} out of pointer range" );
        }
        $u1 = intdiv( $i_uOffset, 256 ) | 0xC0;
        $u2 = $i_uOffset % 256;
        return chr( $u1 ) . chr( $u2 );
    }


    public static function packUINT16( int $i_uValue ) : string {
        return OK::pack( 'n', $i_uValue );
    }


    public static function packUINT32( int $i_uValue ) : string {
        return OK::pack( 'N', $i_uValue );
    }


    public static function packUINT8( int $i_uValue ) : string {
        return chr( $i_uValue );
    }


    /** @return Generator<string, string> */
    public static function splitLabels( string $i_stName ) : Generator {
        $rParts = explode( '.', $i_stName );
        while ( ! empty( $rParts ) ) {
            $stRest = implode( '.', $rParts );
            $stLabel = array_shift( $rParts );
            yield $stLabel => $stRest;
        }
    }


    /** @param list<string> $i_rLabels */
    public static function splitLabelsArray( array $i_rLabels ) : Generator {
        while ( ! empty( $i_rLabels ) ) {
            $stLabel = reset( $i_rLabels );
            yield $stLabel => $i_rLabels;
            array_shift( $i_rLabels );
        }
    }


    /**
     *
     * Consume a string, incrementing the offset by the length of the consumed string.
     *
     * @param string $i_stData
     * @param int $io_uOffset
     * @param int $i_uLength
     * @return string
     */
    public static function tryConsume( string $i_stData, int &$io_uOffset, int $i_uLength ) : string {
        $st = substr( $i_stData, $io_uOffset, $i_uLength );
        $io_uOffset += strlen( $st );
        return $st;
    }


    public static function unpackIPv4( string $i_stData, int $i_uOffset = 0 ) : string {
        $st = substr( $i_stData, $i_uOffset, 4 );
        if ( strlen( $st ) < 4 ) {
            throw new OutOfBoundsException( 'Not enough data to unpack IPv4 address.' );
        }
        return OK::inet_ntop( $st );
    }


    public static function unpackIPv6( string $i_stData, int $i_uOffset = 0 ) : string {
        $st = substr( $i_stData, $i_uOffset, 16 );
        if ( strlen( $st ) < 16 ) {
            throw new OutOfBoundsException( 'Not enough data to unpack IPv6 address.' );
        }
        return OK::inet_ntop( $st );
    }


    public static function unpackNameLabel( string $i_stData, int $i_uOffset = 0 ) : string {
        $stFirstByte = substr( $i_stData, $i_uOffset, 1 );
        if ( '' === $stFirstByte ) {
            throw new OutOfBoundsException( "Not enough data to unpack name label at {$i_uOffset}." );
        }
        $uFirstByte = ord( $stFirstByte );
        if ( 0 === $uFirstByte ) {
            return $stFirstByte;
        }
        if ( 0xC0 === ( $uFirstByte & 0xC0 ) ) {
            $st = substr( $i_stData, $i_uOffset, 2 );
            if ( strlen( $st ) < 2 ) {
                throw new OutOfBoundsException( "Not enough data to unpack pointer at {$i_uOffset}." );
            }
            return $st;
        }
        $st = substr( $i_stData, $i_uOffset, $uFirstByte + 1 );
        if ( strlen( $st ) < $uFirstByte + 1 ) {
            throw new OutOfBoundsException( "Not enough data to unpack label at {$i_uOffset}." );
        }
        return $st;
    }


    public static function unpackPointer( string $i_st ) : ?int {
        $uLabel = ord( $i_st[ 0 ] );
        if ( ( $uLabel & 0xC0 ) !== 0xC0 || strlen( $i_st ) < 2 ) {
            return null;
        }
        return ( ( $uLabel & 0x3F ) << 8 ) + ord( $i_st[ 1 ] );
    }


    public static function unpackUINT16( string $i_stData, int $i_uOffset = 0 ) : int {
        $st = substr( $i_stData, $i_uOffset, 2 );
        if ( strlen( $st ) < 2 ) {
            throw new OutOfBoundsException( 'Not enough data to unpack 16-bit integer.' );
        }
        return TypeIs::int( OK::unpack( 'n', $st )[ 1 ] );
    }


    public static function unpackUINT32( string $i_stData, int $i_uOffset = 0 ) : int {
        $st = substr( $i_stData, $i_uOffset, 4 );
        if ( strlen( $st ) < 4 ) {
            throw new OutOfBoundsException( 'Not enough data to unpack 32-bit integer.' );
        }
        return TypeIs::int( OK::unpack( 'N', $st )[ 1 ] );
    }


    public static function unpackUINT8( string $i_stData, int $i_uOffset = 0 ) : int {
        $st = substr( $i_stData, $i_uOffset, 1 );
        if ( strlen( $st ) < 1 ) {
            throw new OutOfBoundsException( 'Not enough data to unpack 8-bit integer.' );
        }
        return TypeIs::int( ord( $st ) );
    }


    /** @param list<string> $i_rLabels */
    protected static function hashLabels( array $i_rLabels ) : string {
        $st = implode( chr( 0 ), $i_rLabels );
        return hash( 'sha256', $st, true );
    }


}
