<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


class OpaqueRData implements RDataInterface {


    public function __construct( public string $stData ) {}


    public function __toString() : string {
        return bin2hex( $this->stData );
    }


    public function toArray() : array {
        return [ 'rdata' => $this->stData ];
    }


}
