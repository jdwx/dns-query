<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;


class Question {


    public function __construct( public string      $stName, public RecordType $type,
                                 public RecordClass $class ) {}


}
