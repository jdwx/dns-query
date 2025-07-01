<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Message;


use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


abstract class AbstractMessage implements MessageInterface {


    public function additional( int $i_uIndex ) : ?ResourceRecordInterface {
        return $this->getAdditional()[ $i_uIndex ] ?? null;
    }


    public function answer( int $i_uIndex ) : ?ResourceRecordInterface {
        return $this->getAnswer()[ $i_uIndex ] ?? null;
    }


    public function authority( int $i_uIndex ) : ?ResourceRecordInterface {
        return $this->getAuthority()[ $i_uIndex ] ?? null;
    }


    public function id() : int {
        return $this->getID();
    }


    public function question( int $i_uIndex = 0 ) : ?QuestionInterface {
        return $this->getQuestion()[ $i_uIndex ] ?? null;
    }


}
