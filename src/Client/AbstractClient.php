<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\QuestionInterface;


abstract class AbstractClient implements ClientInterface {


    /** @var class-string */
    private readonly string $classMessage;


    /** @param class-string|null $classMessage */
    public function __construct( ?string $classMessage = null ) {
        $classMessage ??= Message::class;
        assert( is_a( $classMessage, MessageInterface::class, true ),
            $classMessage . ' does not implement ' . MessageInterface::class . '.' );
        $this->classMessage = $classMessage;
    }


    public function query( string|MessageInterface|QuestionInterface $i_request,
                           int|string|RecordType|null                $i_type = null,
                           int|string|RecordClass|null               $i_class = null ) : ?MessageInterface {
        return static::queryMessage( ( $this->classMessage )::request( $i_request, $i_type, $i_class ) );
    }


    abstract public function queryMessage( MessageInterface $i_msg ) : ?MessageInterface;


    protected function response( MessageInterface $i_msg ) : MessageInterface {
        return ( $this->classMessage )::response( $i_msg );
    }


}
