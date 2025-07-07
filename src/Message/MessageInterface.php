<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;
use Stringable;


interface MessageInterface extends Stringable {


    public function __construct( ?HeaderInterface $header = null );


    public static function request( string|QuestionInterface|MessageInterface|null $i_domain = null,
                                    int|string|RecordType                          $i_type = RecordType::ANY,
                                    int|string|RecordClass                         $i_class = RecordClass::IN ) : static;


    public static function requestEmpty() : static;


    public static function requestFromMessage( MessageInterface $i_msg ) : static;


    public static function requestFromQuestion( QuestionInterface $i_question ) : static;


    public static function response( MessageInterface      $i_request,
                                     int|string|ReturnCode $i_rc = ReturnCode::NOERROR ) : static;


    public function additional( int $i_uIndex ) : ?ResourceRecordInterface;


    public function answer( int $i_uIndex ) : ?ResourceRecordInterface;


    public function authority( int $i_uIndex ) : ?ResourceRecordInterface;


    public function countAdditional() : int;


    public function countAnswer() : int;


    public function countAuthority() : int;


    public function countQuestion() : int;


    /** @return list<ResourceRecordInterface> */
    public function getAdditional() : array;


    /** @return list<ResourceRecordInterface> */
    public function getAnswer() : array;


    /** @return list<ResourceRecordInterface> */
    public function getAuthority() : array;


    /** @return list<QuestionInterface> */
    public function getQuestion() : array;


    public function header() : HeaderInterface;


    public function id() : int;


    public function question( int $i_uIndex = 0 ) : ?QuestionInterface;


}