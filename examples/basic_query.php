<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Resolver;


require_once '../vendor/autoload.php';


try {

    # This is the very simplest interface into the library.  It's designed to be as similar as
    # possible to PHP's built-in dns_get_record() function.

    # By default, it will learn what nameserver(s) to use from your system's /etc/resolv.conf file.
    $out = Resolver::dns_get_record( 'google.com', DNS_MX );
    var_dump( $out );

    # A custom resolver can be used.
    $out = Resolver::dns_get_record( 'google.com', DNS_MX, '1.1.1.1' );
    var_dump( $out );

    # Or a list of resolvers.
    $out = Resolver::dns_get_record( 'google.com', DNS_MX, [ '8.8.4.4', '8.8.8.8' ] );
    var_dump( $out );

    # You can also specify a custom resolv.conf file if you prefer.  Both the nameservers and the
    # default domain will be used.
    $out = Resolver::dns_get_record( 'www', DNS_A, i_resolvConf: 'cloudflare-resolv.conf' );
    var_dump( $out );

} catch ( Exception $ex ) {
    echo $ex->getMessage();
}

