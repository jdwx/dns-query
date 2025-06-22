<?php /** @noinspection PhpPropertyNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Data\OpCode;
use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\RR\RR;


class Message implements \Stringable {


    public int $id;

    public QR $qr;

    public OpCode $opcode = OpCode::QUERY;

    public bool $aa = false;

    public bool $tc = false;

    public bool $rd = true;

    public bool $ra = false;

    public int $z = 0;

    public ReturnCode $returnCode = ReturnCode::NOERROR;

    /** @var list<Question> */
    public array $question = [];

    /** @var list<RR> */
    public array $answer = [];

    /** @var list<RR> */
    public array $authority = [];

    /** @var list<RR> */
    public array $additional = [];


    public function __construct( int|QR $i_qr, public ?int $i_id = null ) {
        $this->qr = $i_qr->normalize( $i_qr );
        $this->id = $i_id ?? random_int( 0, 65535 );
    }


    public static function request( string                 $domain, int|string|RecordType $type = RecordType::ANY,
                                    int|string|RecordClass $class = RecordClass::IN ) : self {
        $msg = new self( QR::QUERY );
        $msg->question[] = new Question( $domain, $type, $class );
        return $msg;
    }


    public static function response( Message $i_request ) : self {
        $msg = new self( QR::RESPONSE, $i_request->id );
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
            . ( $this->aa ? 'aa ' : '' )
            . ( $this->tc ? 'tc ' : '' )
            . ( $this->rd ? 'rd ' : '' )
            . ( $this->ra ? 'ra ' : '' )
            . '; z: ' . $this->z . ' '
            . '; QUERY: ' . count( $this->question )
            . ', ANSWER: ' . count( $this->answer )
            . ', AUTHORITY: ' . count( $this->authority )
            . ', ADDITIONAL: ' . count( $this->additional ) . "\n\n";

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


}
