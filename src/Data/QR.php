<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


enum QR: int {


    case QUERY    = 0;

    case RESPONSE = 1;


    public function normalize( int|QR $i_qr ) : QR {
        if ( is_int( $i_qr ) ) {
            $i_qr = self::from( $i_qr );
        }
        return $i_qr;
    }


}
