<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Exceptions;


use JDWX\DNSQuery\Legacy\Lookups;
use JDWX\DNSQuery\Legacy\Packet\RequestPacket;
use JDWX\DNSQuery\Legacy\Packet\ResponsePacket;


class RecordException extends Exception {


    public function __construct( string          $i_message = '', int $i_code = Lookups::E_RR_INVALID,
                                 ?\Exception     $i_previous = null, ?RequestPacket $i_request = null,
                                 ?ResponsePacket $i_response = null ) {
        parent::__construct( $i_message, $i_code, $i_previous, $i_request, $i_response );
    }


}
