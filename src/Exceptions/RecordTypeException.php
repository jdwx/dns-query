<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Exceptions;


use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;


class RecordTypeException extends Exception {


    public function __construct( string          $i_message = '', int $i_code = Lookups::E_RR_INVALID,
                                 ?\Exception     $i_previous = null, ?RequestPacket $i_request = null,
                                 ?ResponsePacket $i_response = null ) {
        parent::__construct( $i_message, $i_code, $i_previous, $i_request, $i_response );
    }


}
