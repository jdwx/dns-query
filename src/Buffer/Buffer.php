<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Buffer;


class Buffer extends AbstractBuffer {


    public function append( string $i_stData ) : int {
        $uTell = $this->length();
        $this->stData .= $i_stData;
        return $uTell;
    }


    protected function fetchData() : ?string {
        return null;
    }


}
