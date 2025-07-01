<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Data\AA;
use JDWX\DNSQuery\Data\OpCode;
use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Data\RA;
use JDWX\DNSQuery\Data\RD;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Data\TC;
use JDWX\DNSQuery\Data\ZBits;


class Header implements HeaderInterface {


    private int $id;

    private ReturnCode $rCode;

    private QR $qr = QR::QUERY;

    private OpCode $opCode;

    private AA $aa;

    private TC $tc;

    private RD $rd;

    private RA $ra;

    private ZBits $zBits;


    public function __construct( ?int                  $i_id = null,
                                 int|string|QR         $i_qr = QR::QUERY,
                                 int|string|OpCode     $i_opCode = OpCode::QUERY,
                                 bool|int|string|AA    $i_aa = AA::NON_AUTHORITATIVE,
                                 bool|int|string|TC    $i_tc = TC::NOT_TRUNCATED,
                                 bool|int|string|RD    $i_rd = RD::RECURSION_DESIRED,
                                 bool|int|string|RA    $i_ra = RA::RECURSION_NOT_AVAILABLE,
                                 int|ZBits             $i_z = 0,
                                 int|string|ReturnCode $i_rc = ReturnCode::NOERROR ) {
        if ( is_int( $i_id ) ) {
            $this->setId( $i_id );
        } else {
            $this->setRandomId();
        }
        $this->setQR( $i_qr );
        $this->setOpCode( $i_opCode );
        $this->setAA( $i_aa );
        $this->setTC( $i_tc );
        $this->setRD( $i_rd );
        $this->setRA( $i_ra );
        $this->setZ( $i_z );
        $this->setRCode( $i_rc );
    }


    public static function request( ?int $i_id = null ) : self {
        return new self(
            $i_id ?? random_int( 0, 65535 ),
        );
    }


    public static function response( HeaderInterface $i_reply, ReturnCode $i_rc ) : Header {
        return new self(
            $i_reply->id(),
            $i_reply->qrValue(),
            $i_reply->opcodeValue(),
            $i_reply->aaValue(),
            $i_reply->tcValue(),
            $i_reply->rdValue(),
            $i_reply->raValue(),
            $i_reply->zValue(),
            $i_rc
        );
    }


    public function __toString() : string {
        $st = ';; ' . ( $this->qr === QR::QUERY ? 'Query' : 'Query Response' ) . "\n";
        $st .= ';; ->>HEADER<<- opcode: ' . $this->opcode()
            . ', status: ' . $this->rCode()
            . ', id: ' . $this->id . "\n";
        $st .= ';; flags: ' . ( $this->qr === QR::RESPONSE ? 'qr ' : '' )
            . trim( $this->aa->toFlag()
                . $this->tc->toFlag()
                . $this->rd->toFlag()
                . $this->ra->toFlag()
            )
            . '; z: ' . $this->zBits->bits;
        return $st;
    }


    public function aa() : string {
        return $this->getAA()->name;
    }


    public function aaValue() : int {
        return $this->getAA()->value;
    }


    public function getAA() : AA {
        return $this->aa;
    }


    public function getFlagWord() : int {
        return $this->qr->toFlagWord()
            | $this->opCode->toFlagWord()
            | $this->aa->toFlagWord()
            | $this->tc->toFlagWord()
            | $this->rd->toFlagWord()
            | $this->ra->toFlagWord()
            | $this->zBits->toFlagWord()
            | $this->rCode->toFlagWord();
    }


    public function getId() : int {
        return $this->id;
    }


    public function getOpCode() : OpCode {
        return $this->opCode;
    }


    public function getQR() : QR {
        return $this->qr;
    }


    public function getRA() : RA {
        return $this->ra;
    }


    public function getRCode() : ReturnCode {
        return $this->rCode;
    }


    public function getRD() : RD {
        return $this->rd;
    }


    public function getTC() : TC {
        return $this->tc;
    }


    public function getZ() : ZBits {
        return $this->zBits;
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
        $this->aa = AA::normalize( $i_aa );
    }


    public function setId( int $i_id ) : void {
        $this->id = $i_id;
    }


    public function setOpCode( bool|int|string|OpCode $i_opCode ) : void {
        $this->opCode = OpCode::normalize( $i_opCode );
    }


    public function setQR( bool|int|string|QR $i_qr ) : void {
        $this->qr = QR::normalize( $i_qr );
    }


    public function setRA( bool|int|string|RA $i_ra ) : void {
        $this->ra = RA::normalize( $i_ra );
    }


    public function setRCode( int|string|ReturnCode $i_rc ) : void {
        $this->rCode = ReturnCode::normalize( $i_rc );
    }


    public function setRD( bool|int|string|RD $i_rd ) : void {
        $this->rd = RD::normalize( $i_rd );
    }


    public function setRandomId() : void {
        $this->id = random_int( 0, 65535 );
    }


    public function setTC( bool|int|string|TC $i_tc ) : void {
        $this->tc = TC::normalize( $i_tc );
    }


    public function setZ( int|ZBits $i_z ) : void {
        $this->zBits = ZBits::normalize( $i_z );
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
