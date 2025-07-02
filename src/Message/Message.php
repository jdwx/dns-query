<?php /** @noinspection PhpPropertyNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\OptResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class Message implements MessageInterface {


    /**
     * @param HeaderInterface $header
     * @param list<QuestionInterface> $question
     * @param list<ResourceRecordInterface> $answer
     * @param list<ResourceRecordInterface> $authority
     * @param list<ResourceRecordInterface> $additional
     * @param ?OptResourceRecord $opt
     */
    public function __construct( private readonly HeaderInterface $header,
                                 private array                    $question = [],
                                 private array                    $answer = [],
                                 private array                    $authority = [],
                                 private array                    $additional = [],
                                 private ?OptResourceRecord       $opt = null ) {}


    public static function request( string|Question        $domain,
                                    int|string|RecordType  $type = RecordType::ANY,
                                    int|string|RecordClass $class = RecordClass::IN ) : self {
        $header = Header::request();
        if ( ! $domain instanceof Question ) {
            $type = RecordType::normalize( $type );
            $class = RecordClass::normalize( $class );
            $domain = new Question( $domain, $type, $class );
        }
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

        $st = $this->header
            . '; QUERY: ' . count( $this->question )
            . ', ANSWER: ' . count( $this->answer )
            . ', AUTHORITY: ' . count( $this->authority )
            . ', ADDITIONAL: ' . count( $this->additional ) . "\n\n";

        if ( $this->opt instanceof OptResourceRecord ) {
            /** @noinspection SpellCheckingInspection */
            $st .= ";; OPT PSEUDOSECTION:\n"
                . ';' . $this->opt . "\n\n";
        }

        if ( count( $this->question ) > 0 ) {
            $st .= ";; QUESTION SECTION:\n";
            foreach ( $this->question as $q ) {
                $st .= ';' . $q . "\n";
            }
            $st .= "\n";
        }

        if ( count( $this->answer ) > 0 ) {
            $st .= ";; ANSWER SECTION:\n";
            foreach ( $this->answer as $rr ) {
                $st .= $rr . "\n";
            }
            $st .= "\n";
        }

        if ( count( $this->authority ) > 0 ) {
            $st .= ";; AUTHORITY SECTION:\n";
            foreach ( $this->authority as $rr ) {
                $st .= $rr . "\n";
            }
            $st .= "\n";
        }

        if ( count( $this->additional ) > 0 ) {
            $st .= ";; ADDITIONAL SECTION:\n";
            foreach ( $this->additional as $rr ) {
                $st .= $rr . "\n";
            }
            $st .= "\n";
        }

        return $st;
    }


    public function addAdditional( ResourceRecordInterface $i_additional ) : void {
        $this->additional[] = $i_additional;
        $uCount = count( $this->additional );
        if ( $this->opt instanceof OptResourceRecord ) {
            $uCount += 1; // OPT is counted as additional
        }
        $this->header->setARCount( $uCount );
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


    public function getAdditional() : array {
        return $this->additional;
    }


    public function getAnswer() : array {
        return $this->answer;
    }


    public function getAuthority() : array {
        return $this->authority;
    }


    public function getOpt() : ?OptResourceRecord {
        return $this->opt;
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


    public function opt() : ?OptResourceRecord {
        return $this->opt;
    }


    public function question( int $i_uIndex = 0 ) : ?QuestionInterface {
        return $this->question[ $i_uIndex ] ?? null;
    }


    public function setOpt( ?OptResourceRecord $i_opt ) : void {
        $this->opt = $i_opt;
    }


    public function setQuestion( QuestionInterface $i_question ) : void {
        $this->question = [ $i_question ];
        $this->header->setQDCount( 1 );
    }


}
