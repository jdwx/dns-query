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


    public static function consumeName( string $i_stData, int &$io_uOffset ) : string {
        # Names can be encoded as a series of labels, or as a pointer to a previously defined name, or
        # a combination of both.
        $stOut = '';
        while ( true ) {
            $stLabel = self::consumeNameLabel( $i_stData, $io_uOffset );
            if ( chr( 0 ) === $stLabel ) {
                return $stOut;
            }
            $uPointer = self::unpackPointer( $stLabel );
            if ( is_int( $uPointer ) ) {
                return $stOut . self::expandNamePointer( $i_stData, $uPointer );
            }

            $stOut .= substr( $stLabel, 1 );
        }
    }


    public static function consumeNameLabel( string $i_stData, int &$io_uOffset ) : string {
        $stLabel = self::unpackNameLabel( $i_stData, $io_uOffset );
        $io_uOffset += strlen( $stLabel );
        return $stLabel;
    }


    public static function consumeUINT16( string $i_stData, int &$io_iOffset ) : int {
        $st = self::consume( $i_stData, $io_iOffset, 2 );
        return self::unpackUINT16( $st );
    }


    public static function consumeUINT32( string $i_stData, int &$io_iOffset ) : int {
        $st = self::consume( $i_stData, $io_iOffset, 4 );
        return self::unpackUINT32( $st );
    }


    public static function expandNamePointer( string $i_stData, int $i_uOffset ) : string {
        $stOut = '';
        while ( true ) {
            $stLabel = self::unpackNameLabel( $i_stData, $i_uOffset );
            if ( chr( 0 ) === $stLabel ) {
                return $stOut;
            }
            $uPointer = self::unpackPointer( $stLabel );
            if ( is_int( $uPointer ) ) {
                return $stOut . self::expandNamePointer( $i_stData, $uPointer );
            }
            $stOut .= substr( $stLabel, 1 );
            $i_uOffset += strlen( $stLabel );
        }
    }


    public static function packLabel( string $i_stLabel ) : string {
        if ( '' === $i_stLabel ) {
            throw new \LengthException( 'Empty label in domain name.' );
        }
        if ( strlen( $i_stLabel ) > 63 ) {
            throw new \LengthException( "Label in domain name exceeds 63 characters: {$i_stLabel}" );
        }
        return chr( strlen( $i_stLabel ) ) . $i_stLabel;
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
            throw new \OutOfRangeException( "Offset {$i_uOffset} out of pointer range" );
        }
        $u1 = intdiv( $i_uOffset, 256 ) | 0xC0;
        $u2 = $i_uOffset % 256;
        return chr( $u1 ) . chr( $u2 );
    }


    public static function packUINT16( int $i_iValue ) : string {
        return OK::pack( 'n', $i_iValue );
    }


    public static function packUINT32( int $i_iValue ) : string {
        return OK::pack( 'N', $i_iValue );
    }


    /** @return \Generator<string, string> */
    public static function splitLabels( string $i_stName ) : \Generator {
        $rParts = explode( '.', $i_stName );
        while ( ! empty( $rParts ) ) {
            $stRest = implode( '.', $rParts );
            $stLabel = array_shift( $rParts );
            yield $stLabel => $stRest;
        }
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


    public static function unpackNameLabel( string $i_stData, int $i_uOffset = 0 ) : string {
        $stFirstByte = self::consume( $i_stData, $i_uOffset, 1 );
        $uFirstByte = ord( $stFirstByte );
        if ( 0 === $uFirstByte ) {
            return $stFirstByte;
        }
        if ( 0xC0 === ( $uFirstByte & 0xC0 ) ) {
            return $stFirstByte . self::consume( $i_stData, $i_uOffset, 1 );
        }
        return $stFirstByte . self::consume( $i_stData, $i_uOffset, ord( $stFirstByte ) );
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
            throw new \OutOfBoundsException( 'Not enough data to unpack 16-bit integer.' );
        }
        return TypeIs::int( OK::unpack( 'n', $st )[ 1 ] );
    }


    public static function unpackUINT32( string $i_stData, int $i_uOffset = 0 ) : int {
        $st = substr( $i_stData, $i_uOffset, 4 );
        if ( strlen( $st ) < 4 ) {
            throw new \OutOfBoundsException( 'Not enough data to unpack 32-bit integer.' );
        }
        return TypeIs::int( OK::unpack( 'N', $st )[ 1 ] );
    }


}
