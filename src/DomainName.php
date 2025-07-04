<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\Quote\Operators\DelimiterOperator;
use JDWX\Quote\Operators\QuoteOperator;
use JDWX\Quote\Parser;
use JDWX\Quote\SegmentType;


final class DomainName {


    /** @param list<string> $i_rName */
    public static function format( array $i_rName ) : string {
        # If any label in the name includes a protected character, we need to quote it.
        # In this case, that means: dot, space, double quote or backslash.
        $rLabels = [];
        foreach ( $i_rName as $stLabel ) {
            if ( str_contains( $stLabel, '.' ) || str_contains( $stLabel, ' ' )
                || str_contains( $stLabel, '"' ) || str_contains( $stLabel, '\\' ) ) {
                $stLabel = str_replace( '\\', '\\\\', $stLabel );
                $stLabel = str_replace( '"', '\\"', $stLabel );
                $rLabels[] = '"' . $stLabel . '"';
            } else {
                $rLabels[] = $stLabel;
            }
        }
        return implode( '.', $rLabels );
    }


    /**
     * @param list<string>|string $i_name
     * @param list<string> $i_rOrigin
     * @return list<string>
     */
    public static function normalize( array|string $i_name, array $i_rOrigin = [] ) : array {
        if ( is_string( $i_name ) ) {
            $i_name = self::parse( $i_name, $i_rOrigin );
        } elseif ( ! empty( $i_rOrigin ) && ! empty( $i_name[ count( $i_name ) - 1 ] ) ) {
            $i_name = array_merge( $i_name, $i_rOrigin );
        }
        if ( empty( $i_name[ count( $i_name ) - 1 ] ) ) {
            array_pop( $i_name );
        }
        return array_map( fn( string $x ) => strtolower( trim( $x ) ), $i_name );
    }


    /**
     * @param list<string> $i_rOrigin
     * @return list<string>
     */
    public static function parse( string $i_name, array $i_rOrigin = [] ) : array {
        $bUseOrigin = ! str_ends_with( $i_name, '.' );
        $parser = new Parser(
            hardQuote: QuoteOperator::double(),
            delimiter: new DelimiterOperator( [ '.' ] ),
        );
        $r = [];
        $stLabel = '';
        foreach ( $parser->parse( $i_name ) as $chunk ) {
            if ( SegmentType::DELIMITER === $chunk->type ) {
                if ( ! empty( $stLabel ) ) {
                    $r[] = $stLabel;
                    $stLabel = '';
                }
                continue;
            }
            $stLabel .= strtolower( $chunk->value );
        }
        if ( ! empty( $stLabel ) ) {
            $r[] = $stLabel;
        }
        if ( $bUseOrigin ) {
            $r = array_merge( $r, $i_rOrigin );
        }
        return $r;
    }


}
