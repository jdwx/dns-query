<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


final class HexDump {


    public static function dump( string $i_stData ) : string {
        $uOffset = 0;
        $uMaxLen = strlen( $i_stData );
        $st = '';

        while ( $uOffset < $uMaxLen ) {
            $st .= sprintf( '0x%04X: ', $uOffset );
            $stHex = '';
            $stText = '';
            for ( $ii = 0 ; $ii < 16 ; ++$ii ) {
                if ( $uOffset + $ii < $uMaxLen ) {
                    $byte = ord( $i_stData[ $uOffset + $ii ] );
                    $stHex .= sprintf( '%02X ', $byte );
                    if ( $byte >= 32 && $byte <= 126 ) {
                        $stText .= chr( $byte );
                    } else {
                        $stText .= '.';
                    }
                } else {
                    $stHex .= '   ';
                    $stText .= ' ';
                }
                if ( $ii === 7 ) {
                    $stHex .= ' ';
                }
            }
            $st .= $stHex . ' ' . $stText . PHP_EOL;
            $uOffset += 16;
        }

        return $st;
    }


}
