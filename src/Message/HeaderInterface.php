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


interface HeaderInterface extends \Stringable {


    public function aa() : string;


    public function aaValue() : int;


    public function flagWord() : FlagWord;


    public function flagWordValue() : int;


    public function getAA() : AA;


    public function getANCount() : int;


    public function getARCount() : int;


    public function getFlagWord() : FlagWord;


    public function getId() : int;


    public function getNSCount() : int;


    public function getOpCode() : OpCode;


    public function getQDCount() : int;


    public function getQR() : QR;


    public function getRA() : RA;


    public function getRCode() : ReturnCode;


    public function getRD() : RD;


    public function getTC() : TC;


    public function getZ() : ZBits;


    public function id() : int;


    public function opcode() : string;


    public function opcodeValue() : int;


    public function qr() : string;


    public function qrValue() : int;


    public function rCode() : string;


    public function rCodeValue() : int;


    public function ra() : string;


    public function raValue() : int;


    public function rd() : string;


    public function rdValue() : int;


    public function setAA( bool|int|string|AA $i_aa ) : void;


    public function setANCount( int $i_uANCount ) : void;


    public function setARCount( int $i_uARCount ) : void;


    public function setFlagWord( int $i_uFlagWord ) : void;


    public function setId( int $i_id ) : void;


    public function setNSCount( int $i_uNSCount ) : void;


    public function setOpCode( int|string|OpCode $i_opCode ) : void;


    public function setQDCount( int $i_uQDCount ) : void;


    public function setQR( bool|int|string|QR $i_qr ) : void;


    public function setRA( bool|int|string|RA $i_ra ) : void;


    public function setRCode( int|string|ReturnCode $i_rc ) : void;


    public function setRD( bool|int|string|RD $i_rd ) : void;


    public function setTC( bool|int|string|TC $i_tc ) : void;


    public function setZ( int|ZBits $i_z ) : void;


    public function tc() : string;


    public function tcValue() : int;


    public function z() : ZBits;


    public function zValue() : int;


}
