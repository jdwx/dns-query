<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\OptResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;
use Stringable;


interface MessageInterface extends Stringable {


    public function additional( int $i_uIndex ) : ?ResourceRecordInterface;


    public function answer( int $i_uIndex ) : ?ResourceRecordInterface;


    public function authority( int $i_uIndex ) : ?ResourceRecordInterface;


    /** @return list<ResourceRecordInterface> */
    public function getAdditional() : array;


    /** @return list<ResourceRecordInterface> */
    public function getAnswer() : array;


    /** @return list<ResourceRecordInterface> */
    public function getAuthority() : array;

    public function getOpt() : ?OptResourceRecord;

    /** @return list<QuestionInterface> */
    public function getQuestion() : array;

    public function header() : HeaderInterface;


    public function id() : int;


    public function opt() : ?OptResourceRecord;


    public function question( int $i_uIndex = 0 ) : ?QuestionInterface;


}