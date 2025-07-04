<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


enum SSHFPType: int {


    case Reserved = 0;

    case SHA1     = 1;

    case SHA256   = 2;


}
