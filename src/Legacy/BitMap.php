<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy;


use JDWX\DNSQuery\Data\RecordType;


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 0.6.0
 *
 */


/**
 * A class to handle converting RR bitmaps to arrays and back; used on NSEC
 * and NSEC3 RRs.
 *
 */
class BitMap {


    /**
     * builds a RR Bit map from an array of RR type names
     *
     * @param string[] $i_data a list of RR type names
     *
     * @return string The packed bit map
     */
    public static function arrayToBitMap( array $i_data ) : string {
        if ( count( $i_data ) == 0 ) {
            return '';
        }

        # Go through each RR.
        $max = 0;
        $bm = [];

        foreach ( $i_data as $rr ) {

            $rr = strtoupper( $rr );

            # Get the type id for the RR.
            $type = RecordType::tryNameToId( $rr );
            if ( is_int( $type ) ) {

                # Skip meta types or qTypes.
                if ( ( isset( Lookups::$rrQTypesById[ $type ] ) )
                    || ( isset( Lookups::$rrMetaTypesById[ $type ] ) )
                ) {
                    continue;
                }

            } else {

                # If it's not found, then it must be defined as TYPE<id>, per
                # RFC3845 section 2.2, if it's not, we ignore it.
                $xx = explode( 'TYPE', $rr );
                if ( count( $xx ) < 2 ) {
                    continue;
                }

                $type = (int) $xx[ 1 ];
            }

            # Build the current window.
            $currentWindow = (int) ( $type / 256 );

            $val = $type - $currentWindow * 256.0;
            if ( $val > $max ) {
                $max = $val;
            }

            $bm[ $currentWindow ][ $val ] = 1;
            $bm[ $currentWindow ][ 'length' ] = ceil( ( $max + 1 ) / 8 );
        }

        $output = '';

        foreach ( $bm as $window => $bitData ) {

            $bitString = '';

            for ( $ii = 0 ; $ii < $bitData[ 'length' ] * 8 ; $ii++ ) {
                if ( isset( $bitData[ $ii ] ) ) {
                    $bitString .= '1';
                } else {
                    $bitString .= '0';
                }
            }

            $output .= pack( 'CC', $window, $bitData[ 'length' ] );
            $output .= pack( 'H*', self::bigBaseConvert( $bitString ) );
        }

        return $output;
    }


    /**
     * a base_convert that handles large numbers; forced to 2/16
     *
     * @param string $number a bit string
     *
     * @return string The hexadecimal representation of the number
     */
    public static function bigBaseConvert( string $number ) : string {
        $result = '';

        $bin = substr( chunk_split( strrev( $number ), 4, '-' ), 0, -1 );
        $temp = preg_split( '[-]', $bin, -1, PREG_SPLIT_DELIM_CAPTURE );

        for ( $ii = count( $temp ) - 1 ; $ii >= 0 ; $ii-- ) {

            $result = $result . base_convert( strrev( $temp[ $ii ] ), 2, 16 );
        }

        return strtoupper( $result );
    }


    /**
     * parses a RR bitmap field defined in RFC3845, into an array of RR names.
     *
     * Type Bit Map(s) Field = ( Window Block # | Bitmap Length | Bitmap ) +
     *
     * @param string $data a bitmap string to parse
     *
     * @return string[]
     */
    public static function bitMapToArray( string $data ) : array {
        if ( strlen( $data ) == 0 ) {
            return [];
        }

        $output = [];
        $offset = 0;
        $length = strlen( $data );

        while ( $offset < $length ) {

            # Unpack the window and length values.
            /** @noinspection SpellCheckingInspection */
            $xx = unpack( '@' . $offset . '/Cwindow/Clength', $data );
            $offset += 2;

            # Copy out the bitmap value.
            $bitmap = unpack( 'C*', substr( $data, $offset, $xx[ 'length' ] ) );
            $offset += $xx[ 'length' ];

            # I'm not sure if there's a better way of doing this, but PHP doesn't
            # have a 'B' flag for unpack().
            $bitString = '';
            foreach ( $bitmap as $rrType ) {
                $bitString .= sprintf( '%08b', $rrType );
            }

            $bLen = strlen( $bitString );
            for ( $ii = 0 ; $ii < $bLen ; $ii++ ) {
                if ( $bitString[ $ii ] == '1' ) {
                    $typeId = $xx[ 'window' ] * 256 + $ii;
                    $typeName = RecordType::tryIdToName( $typeId ) ?? 'TYPE' . $typeId;
                    $output[] = $typeName;
                }
            }
        }

        return $output;
    }


}
