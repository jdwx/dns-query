<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Data\OptionCode;


class Option {


    public function __construct( public int $code, public string $data ) {
    }


    public function code() : ?OptionCode {
        return OptionCode::tryFrom( $this->code );
    }


    public function dataLength() : int {
        return strlen( $this->data );
    }


}