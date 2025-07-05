<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\AbstractBuffer;


class TransportBuffer extends AbstractBuffer {


    public function __construct( private readonly TransportInterface $transport ) {
        parent::__construct();
    }


    protected function fetchData() : ?string {
        return $this->transport->receive();
    }


}
