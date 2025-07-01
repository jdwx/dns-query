<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


class TransportBuffer implements BufferInterface {


    use BufferTrait;


    public function __construct( private readonly TransportInterface $transport ) {}


    protected function fetchData() : ?string {
        return $this->transport->receive();
    }


}
