<?php /** @noinspection PhpPropertyNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\RR\RR;


class Message {


    public int $id;

    public QR $qr;

    public int $opcode = 0;

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
        return $msg;
    }


}
