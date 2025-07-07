<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\QuestionInterface;


interface ClientInterface {


    public function query( MessageInterface|QuestionInterface|string $i_request,
                           int|string|RecordType|null                $i_type = null,
                           int|string|RecordClass|null               $i_class = null ) : ?MessageInterface;


    public function queryMessage( MessageInterface $i_msg ) : ?MessageInterface;


}

