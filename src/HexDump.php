<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\Strict\OK;


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


    public static function escape( string $i_stData ) : string {
        $stHex = '';
        $uMaxLen = strlen( $i_stData );
        for ( $ii = 0 ; $ii < $uMaxLen ; ++$ii ) {
            $ch = $i_stData[ $ii ];
            if ( ctype_print( $ch ) ) {
                $stHex .= $ch;
                continue;
            }
            $stHex .= sprintf( '\\x%02X', ord( $ch ) );
        }
        return $stHex;
    }


    public static function fromTcpDump( string $i_stDump ) : string {
        $stHex = '';
        foreach ( OK::preg_split( '/[\r\n]/', $i_stDump ) as $stLine ) {
            // $stLine = preg_replace( '/^\s*[0-9a-f]+:\s*([0-9a-f]{4}\s*){0,8}/i', '\\2', $stLine );
            $stLine = preg_replace( '/^\s*0x[0-9a-f]{4}:\s*(([0-9a-f]{2,4}\s*){1,8}).*$/i', '\\1', $stLine );
            $stLine = preg_replace( '/\s+/', '', $stLine );
            $stHex .= $stLine;
        }
        return hex2bin( $stHex );
    }


}
