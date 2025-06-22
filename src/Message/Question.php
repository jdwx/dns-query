<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;


class Question implements \Stringable {


    public function __construct( public string      $stName, public RecordType $type,
                                 public RecordClass $class ) {}


    public function __toString() : string {
        return $this->stName . ' ' . $this->class->name . ' ' . $this->type->name;
    }


}
