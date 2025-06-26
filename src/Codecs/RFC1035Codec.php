<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\Question;
use JDWX\DNSQuery\RR\OPT;
use JDWX\DNSQuery\RR\RR;


class RFC1035Codec implements CodecInterface {


    public function decode( string $i_packet ) : Message {
        $msg = new Message();

        $uOffset = 0;
        $msg->id = Binary::consumeUINT16( $i_packet, $uOffset );
        $msg->setFlagWord( Binary::consumeUINT16( $i_packet, $uOffset ) );

        $qCount = Binary::consumeUINT16( $i_packet, $uOffset );
        $aCount = Binary::consumeUINT16( $i_packet, $uOffset );
        $auCount = Binary::consumeUINT16( $i_packet, $uOffset );
        $adCount = Binary::consumeUINT16( $i_packet, $uOffset );

        for ( $ii = 0 ; $ii < $qCount ; ++$ii ) {
            $msg->question[] = Question::fromBinary( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $aCount ; ++$ii ) {
            $msg->answer[] = RR::fromBinary( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $auCount ; ++$ii ) {
            $msg->authority[] = RR::fromBinary( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $adCount ; ++$ii ) {
            $rr = RR::fromBinary( $i_packet, $uOffset );
            if ( $rr instanceof OPT ) {
                $msg->opt[] = $rr;
            } else {
                $msg->additional[] = $rr;
            }
        }
        return $msg;
    }


    public function encode( Message $i_msg ) : string {
        $st = Binary::packUINT16( $i_msg->id )
            . Binary::packUINT16( $i_msg->getFlagWord() )
            . Binary::packUINT16( count( $i_msg->question ) )
            . Binary::packUINT16( count( $i_msg->answer ) )
            . Binary::packUINT16( count( $i_msg->authority ) )
            . Binary::packUINT16( count( $i_msg->additional ) + count( $i_msg->opt ) );

        $rLabelMap = [];

        foreach ( $i_msg->question as $q ) {
            $st .= $q->toBinary( $rLabelMap, strlen( $st ) );
        }

        foreach ( $i_msg->answer as $rr ) {
            $st .= $rr->toBinary( $rLabelMap, strlen( $st ) );
        }

        foreach ( $i_msg->authority as $rr ) {
            $st .= $rr->toBinary( $rLabelMap, strlen( $st ) );
        }

        foreach ( $i_msg->additional as $rr ) {
            $st .= $rr->toBinary( $rLabelMap, strlen( $st ) );
        }

        foreach ( $i_msg->opt as $rr ) {
            $st .= $rr->toBinary( $rLabelMap, strlen( $st ) );
        }

        return $st;
    }


}
