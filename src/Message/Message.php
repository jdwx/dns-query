<?php /** @noinspection PhpPropertyNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Buffer\WriteBuffer;
use JDWX\DNSQuery\Codecs\PresentationEncoder;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class Message implements MessageInterface {


    private readonly HeaderInterface $header;


    /**
     * @param ?HeaderInterface $header
     * @param list<QuestionInterface> $question
     * @param list<ResourceRecordInterface> $answer
     * @param list<ResourceRecordInterface> $authority
     * @param list<ResourceRecordInterface> $additional
     */
    public function __construct( ?HeaderInterface $header = null,
                                 private array    $question = [],
                                 private array    $answer = [],
                                 private array    $authority = [],
                                 private array    $additional = [] ) {
        $this->header = $header ?? new Header();
    }


    public static function request( string|QuestionInterface $domain,
                                    int|string|RecordType    $type = RecordType::ANY,
                                    int|string|RecordClass   $class = RecordClass::IN ) : self {
        $header = Header::request();
        if ( ! $domain instanceof QuestionInterface ) {
            $type = RecordType::normalize( $type );
            $class = RecordClass::normalize( $class );
            $domain = new Question( $domain, $type, $class );
        }
        $header->setQDCount( 1 );
        return new self( $header, [ $domain ] );
    }


    public static function response( MessageInterface $i_request, int|string|ReturnCode $i_rc = ReturnCode::NOERROR ) : self {
        $header = Header::response( $i_request->header(), $i_rc );
        $msg = new self(
            $header,
            $i_request->getQuestion(),
        );
        return $msg;
    }


    public function __toString() : string {
        $enc = new PresentationEncoder();
        $wri = new WriteBuffer();
        $enc->encodeMessage( $wri, $this );
        return $wri->end();
    }


    public function addAdditional( ResourceRecordInterface $i_additional ) : void {
        $this->additional[] = $i_additional;
        $this->header->setARCount( count( $this->additional ) );
    }


    public function addAnswer( ResourceRecordInterface $i_answer ) : void {
        $this->answer[] = $i_answer;
        $this->header->setANCount( count( $this->answer ) );
    }


    public function addAuthority( ResourceRecordInterface $i_authority ) : void {
        $this->authority[] = $i_authority;
        $this->header->setNSCount( count( $this->authority ) );
    }


    public function addQuestion( QuestionInterface $i_question ) : void {
        $this->question[] = $i_question;
        $this->header->setQDCount( count( $this->question ) );
    }


    public function additional( int $i_uIndex ) : ?ResourceRecordInterface {
        return $this->additional[ $i_uIndex ] ?? null;
    }


    public function answer( int $i_uIndex ) : ?ResourceRecordInterface {
        return $this->answer[ $i_uIndex ] ?? null;
    }


    public function authority( int $i_uIndex ) : ?ResourceRecordInterface {
        return $this->authority[ $i_uIndex ] ?? null;
    }


    public function countAdditional() : int {
        return count( $this->additional );
    }


    public function countAnswer() : int {
        return count( $this->answer );
    }


    public function countAuthority() : int {
        return count( $this->authority );
    }


    public function countQuestion() : int {
        return count( $this->question );
    }


    public function getAdditional() : array {
        return $this->additional;
    }


    public function getAnswer() : array {
        return $this->answer;
    }


    public function getAuthority() : array {
        return $this->authority;
    }


    public function getQuestion() : array {
        return $this->question;
    }


    public function header() : HeaderInterface {
        return $this->header;
    }


    public function id() : int {
        return $this->header->id();
    }


    public function question( int $i_uIndex = 0 ) : ?QuestionInterface {
        return $this->question[ $i_uIndex ] ?? null;
    }


    public function setQuestion( QuestionInterface $i_question ) : void {
        $this->question = [ $i_question ];
        $this->header->setQDCount( 1 );
    }


}
