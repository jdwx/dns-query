<?php /** @noinspection PhpPropertyNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Data\AA;
use JDWX\DNSQuery\Data\OpCode;
use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Data\RA;
use JDWX\DNSQuery\Data\RD;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Data\TC;
use JDWX\DNSQuery\Data\ZBits;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class Message implements MessageInterface {


    /**
     * @param list<QuestionInterface> $question
     * @param list<ResourceRecordInterface> $answer
     * @param list<ResourceRecordInterface> $authority
     * @param list<ResourceRecordInterface> $additional
     * @param list<ResourceRecordInterface> $opt
     */
    public function __construct( private HeaderInterface $header,
                                 private array           $question = [],
                                 private array           $answer = [],
                                 private array           $authority = [],
                                 private array           $additional = [],
                                 private array           $opt = [] ) {}


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

        if ( count( $this->opt ) > 0 ) {
            /** @noinspection SpellCheckingInspection */
            $st .= ";; OPT PSEUDOSECTION:\n";
            foreach ( $this->opt as $opt ) {
                $st .= ';' . $opt . "\n";
            }
            $st .= "\n";
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


    public function getQuestion() : array {
        return $this->question;
    }


    public function header() : HeaderInterface {
        return $this->header;
    }


    public function question( int $i_uIndex = 0 ) : ?QuestionInterface {
        return $this->question[ $i_uIndex ] ?? null;
    }


    public function setFlagWord( int $i_iWord ) : void {
        $this->qr = QR::fromFlagWord( $i_iWord );
        $this->opcode = OpCode::fromFlagWord( $i_iWord );
        $this->aa = AA::fromFlagWord( $i_iWord );
        $this->tc = TC::fromFlagWord( $i_iWord );
        $this->rd = RD::fromFlagWord( $i_iWord );
        $this->ra = RA::fromFlagWord( $i_iWord );
        $this->z = ZBits::fromFlagWord( $i_iWord );
        $this->returnCode = ReturnCode::fromFlagWord( $i_iWord );
    }


}
