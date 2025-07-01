<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Question\OpaqueQuestion;
use JDWX\DNSQuery\ResourceRecord\OpaqueResourceRecord;
use JDWX\DNSQuery\Tests\Message\AbstractMessage;
use JDWX\DNSQuery\Transport\BufferInterface;


class OpaqueMessage extends AbstractMessage {


    public function __construct( public int   $uID = 0, public int $uFlags = 0,
                                 public array $rQuestion = [], public array $rAnswer = [],
                                 public array $rAuthority = [], public array $rAdditional = [] ) {}


    public static function fromBuffer( BufferInterface $i_buffer ) : self {
        $uID = $i_buffer->consumeUINT16();
        $uFlags = $i_buffer->consumeUINT16();
        $uQuestionCount = $i_buffer->consumeUINT16();
        $uAnswerCount = $i_buffer->consumeUINT16();
        $uAuthorityCount = $i_buffer->consumeUINT16();
        $uAdditionalCount = $i_buffer->consumeUINT16();

        $rQuestion = [];
        for ( $i = 0 ; $i < $uQuestionCount ; $i++ ) {
            $rQuestion[] = OpaqueQuestion::fromBuffer( $i_buffer );
        }

        $rAnswer = [];
        for ( $i = 0 ; $i < $uAnswerCount ; $i++ ) {
            $rAnswer[] = OpaqueResourceRecord::fromBuffer( $i_buffer );
        }

        $rAuthority = [];
        for ( $i = 0 ; $i < $uAuthorityCount ; $i++ ) {
            $rAuthority[] = OpaqueResourceRecord::fromBuffer( $i_buffer );
        }

        $rAdditional = [];
        for ( $i = 0 ; $i < $uAdditionalCount ; $i++ ) {
            $rAdditional[] = OpaqueResourceRecord::fromBuffer( $i_buffer );
        }

        return new self( $uID, $uFlags, $rQuestion, $rAnswer, $rAuthority, $rAdditional );

    }


    public function flagsValue() : int {
        return $this->uFlags;
    }


    public function getAdditional() : array {
        return $this->rAdditional;
    }


    public function getAnswer() : array {
        return $this->rAnswer;
    }


    public function getAuthority() : array {
        return $this->rAuthority;
    }


    public function getID() : int {
        return $this->uID;
    }


    public function getQuestion() : array {
        return $this->rQuestion;
    }


    public function setID( int $i_uID ) : void {
        $this->uID = $i_uID;
    }


}
