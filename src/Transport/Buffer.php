<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


class Buffer implements BufferInterface {


    use BufferTrait;


    public function __construct( string $i_stData, int $i_uStartingOffset = 0 ) {
        $this->stData = $i_stData;
        $this->uOffset = $i_uStartingOffset;
    }


    public function append( string $i_stData ) : int {
        $uTell = $this->length();
        $this->stData .= $i_stData;
        return $uTell;
    }


    protected function fetchData() : ?string {
        return null;
    }


}
