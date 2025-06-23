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
use JDWX\DNSQuery\RR\OPT;
use JDWX\DNSQuery\RR\RR;


class Message implements \Stringable {


    public int $id = 0;

    public QR $qr = QR::QUERY;

    public OpCode $opcode = OpCode::QUERY;

    public AA $aa = AA::NON_AUTHORITATIVE;

    public TC $tc = TC::NOT_TRUNCATED;

    public RD $rd = RD::RECURSION_DESIRED;

    public RA $ra = RA::RECURSION_NOT_AVAILABLE;

    public ZBits $z;

    public ReturnCode $returnCode = ReturnCode::NOERROR;

    /** @var list<Question> */
    public array $question = [];

    /** @var list<RR> */
    public array $answer = [];

    /** @var list<RR> */
    public array $authority = [];

    /** @var list<RR> */
    public array $additional = [];

    /** @var list<OPT> */
    public array $opt = [];


    public function __construct() {
        $this->z = new ZBits();
    }


    public static function request( string                 $domain, int|string|RecordType $type = RecordType::ANY,
                                    int|string|RecordClass $class = RecordClass::IN ) : self {
        $type = RecordType::normalize( $type );
        $class = RecordClass::normalize( $class );
        $msg = new self();
        $msg->setRandomId();
        $msg->question[] = new Question( $domain, $type, $class );
        return $msg;
    }


    public static function response( Message $i_request ) : self {
        $msg = new self();
        $msg->qr = QR::RESPONSE;
        $msg->id = $i_request->id;
        $msg->question = $i_request->question;
        $msg->rd = $i_request->rd;
        return $msg;
    }


    public function __toString() : string {
        $st = ';; ' . ( $this->qr === QR::QUERY ? 'Query' : 'Query Response' ) . "\n";
        $st .= ';; ->>HEADER<<- opcode: ' . $this->opcode->name
            . ', status: ' . $this->returnCode->name
            . ', id: ' . $this->id . "\n";
        $st .= ';; flags: ' . ( $this->qr === QR::RESPONSE ? 'qr ' : '' )
            . trim( $this->aa->toFlag()
                . $this->tc->toFlag()
                . $this->rd->toFlag()
                . $this->ra->toFlag()
            )
            . '; z: ' . $this->z
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


    public function getFlagWord() : int {
        return $this->qr->toFlagWord()
            | $this->opcode->toFlagWord()
            | $this->aa->toFlagWord()
            | $this->tc->toFlagWord()
            | $this->rd->toFlagWord()
            | $this->ra->toFlagWord()
            | $this->z->toFlagWord()
            | $this->returnCode->toFlagWord();
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


    public function setRandomId() : void {
        $this->id = random_int( 0, 65535 );
    }


}
