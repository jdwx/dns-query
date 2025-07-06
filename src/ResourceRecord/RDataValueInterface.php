<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use JDWX\DNSQuery\Data\RDataType;


interface RDataValueInterface {


    public function type() : RDataType;


    public function value() : mixed;


}