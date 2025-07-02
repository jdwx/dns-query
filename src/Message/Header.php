<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Data\AA;
use JDWX\DNSQuery\Data\FlagWord;
use JDWX\DNSQuery\Data\OpCode;
use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Data\RA;
use JDWX\DNSQuery\Data\RD;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Data\TC;
use JDWX\DNSQuery\Data\ZBits;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class Header implements HeaderInterface {


    private int $id;

    private FlagWord $flagWord;

    private int $qdCount;

    private int $anCount;

    private int $nsCount;

    private int $arCount;


    /**
     * @param int|null $i_id
     * @param int|FlagWord $i_flagWord
     * @param int|list<ResourceRecordInterface> $i_qdCount
     * @param int|list<ResourceRecordInterface> $i_anCount
     * @param int|list<ResourceRecordInterface> $i_nsCount
     * @param int|list<ResourceRecordInterface> $i_arCount
     */
    public function __construct( ?int         $i_id = null,
                                 int|FlagWord $i_flagWord = 0,
                                 int|array    $i_qdCount = 0,
                                 int|array    $i_anCount = 0,
                                 int|array    $i_nsCount = 0,
                                 int|array    $i_arCount = 0 ) {
        if ( is_int( $i_id ) ) {
            $this->setId( $i_id );
        } else {
            $this->setRandomId();
        }
        $this->flagWord = FlagWord::normalize( $i_flagWord );

        if ( is_array( $i_qdCount ) ) {
            $i_qdCount = count( $i_qdCount );
        }
        $this->qdCount = (int) $i_qdCount;

        if ( is_array( $i_anCount ) ) {
            $i_anCount = count( $i_anCount );
        }
        $this->anCount = (int) $i_anCount;

        if ( is_array( $i_nsCount ) ) {
            $i_nsCount = count( $i_nsCount );
        }
        $this->nsCount = (int) $i_nsCount;

        if ( is_array( $i_arCount ) ) {
            $i_arCount = count( $i_arCount );
        }
        $this->arCount = (int) $i_arCount;

    }


    public static function request( ?int $i_id = null ) : self {
        return new self(
            $i_id ?? random_int( 0, 65535 ),
        );
    }


    public static function response( HeaderInterface $i_reply, ReturnCode $i_rc ) : Header {
        return new self(
            $i_reply->id(),
            $i_reply->flagWord()->setQR( QR::RESPONSE )->setRCode( $i_rc ),
            $i_reply->getQDCount(),
        );
    }


    public function __toString() : string {
        $st = ';; ' . ( $this->flagWord->qr === QR::QUERY ? 'Query' : 'Query Response' ) . "\n";
        $st .= ';; ->>HEADER<<- opcode: ' . $this->opcode()
            . ', status: ' . $this->rCode()
            . ', id: ' . $this->id . "\n";
        $st .= ';; flags: '
            . $this->flagWord->flagString()
            . '; z: ' . $this->flagWord->zBits->bits;
        return $st;
    }


    public function aa() : string {
        return $this->getAA()->name;
    }


    public function aaValue() : int {
        return $this->getAA()->value;
    }


    public function flagWord() : FlagWord {
        return $this->getFlagWord();
    }


    public function flagWordValue() : int {
        return $this->flagWord->value();
    }


    public function getAA() : AA {
        return $this->flagWord->aa;
    }


    public function getANCount() : int {
        return $this->anCount;
    }


    public function getARCount() : int {
        return $this->arCount;
    }


    public function getFlagWord() : FlagWord {
        return $this->flagWord;
    }


    public function getId() : int {
        return $this->id;
    }


    public function getNSCount() : int {
        return $this->nsCount;
    }


    public function getOpCode() : OpCode {
        return $this->flagWord->opCode;
    }


    public function getQDCount() : int {
        return $this->qdCount;
    }


    public function getQR() : QR {
        return $this->flagWord->qr;
    }


    public function getRA() : RA {
        return $this->flagWord->ra;
    }


    public function getRCode() : ReturnCode {
        return $this->flagWord->rCode;
    }


    public function getRD() : RD {
        return $this->flagWord->rd;
    }


    public function getTC() : TC {
        return $this->flagWord->tc;
    }


    public function getZ() : ZBits {
        return $this->flagWord->zBits;
    }


    public function id() : int {
        return $this->getId();
    }


    public function opcode() : string {
        return $this->getOpCode()->name;
    }


    public function opcodeValue() : int {
        return $this->getOpCode()->value;
    }


    public function qr() : string {
        return $this->getQR()->name;
    }


    public function qrValue() : int {
        return $this->getQR()->value;
    }


    public function rCode() : string {
        return $this->getRCode()->name;
    }


    public function rCodeValue() : int {
        return $this->getRCode()->value;
    }


    public function ra() : string {
        return $this->getRA()->name;
    }


    public function raValue() : int {
        return $this->getRA()->value;
    }


    public function rd() : string {
        return $this->getRD()->name;
    }


    public function rdValue() : int {
        return $this->getRD()->value;
    }


    public function setAA( bool|int|string|AA $i_aa ) : void {
        $this->flagWord->aa = AA::normalize( $i_aa );
    }


    public function setANCount( int $i_uANCount ) : void {
        $this->anCount = $i_uANCount;
    }


    public function setARCount( int $i_uARCount ) : void {
        $this->arCount = $i_uARCount;
    }


    public function setFlagWord( int $i_uFlagWord ) : void {
        $this->flagWord = FlagWord::fromFlagWord( $i_uFlagWord );
    }


    public function setId( int $i_id ) : void {
        $this->id = $i_id;
    }


    public function setNSCount( int $i_uNSCount ) : void {
        $this->nsCount = $i_uNSCount;
    }


    public function setOpCode( bool|int|string|OpCode $i_opCode ) : void {
        $this->flagWord->opCode = OpCode::normalize( $i_opCode );
    }


    public function setQDCount( int $i_uQDCount ) : void {
        $this->qdCount = $i_uQDCount;
    }


    public function setQR( bool|int|string|QR $i_qr ) : void {
        $this->flagWord->setQR( $i_qr );
    }


    public function setRA( bool|int|string|RA $i_ra ) : void {
        $this->flagWord->ra = RA::normalize( $i_ra );
    }


    public function setRCode( int|string|ReturnCode $i_rc ) : void {
        $this->flagWord->setRCode( $i_rc );
    }


    public function setRD( bool|int|string|RD $i_rd ) : void {
        $this->flagWord->rd = RD::normalize( $i_rd );
    }


    public function setRandomId() : void {
        $this->id = random_int( 0, 65535 );
    }


    public function setTC( bool|int|string|TC $i_tc ) : void {
        $this->flagWord->tc = TC::normalize( $i_tc );
    }


    public function setZ( int|ZBits $i_z ) : void {
        $this->flagWord->zBits = ZBits::normalize( $i_z );
    }


    public function tc() : string {
        return $this->getTC()->name;
    }


    public function tcValue() : int {
        return $this->getTC()->value;
    }


    public function z() : ZBits {
        return $this->getZ();
    }


    public function zValue() : int {
        return $this->getZ()->bits;
    }


}
