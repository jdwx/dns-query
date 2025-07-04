<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


enum SSHFPAlgorithm: int {


    case Reserved = 0;

    case RSA      = 1;

    case DSA      = 2;

    case ECDSA    = 3;

    case Ed25519  = 4;

    case Ed448    = 5;


}
