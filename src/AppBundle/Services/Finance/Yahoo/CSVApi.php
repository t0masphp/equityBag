<?php

namespace AppBundle\Services\Finance\Yahoo;

use AppBundle\Entity\Share;
use AppBundle\Services\Finance\FinanceApiInterface;
use AppBundle\Services\Finance\HttpClient;
use AppBundle\Services\Finance\Yahoo\Exception\ApiException;

class CSVApi implements FinanceApiInterface {

    private $symbolAutocompleteUrl = 'http://autoc.finance.yahoo.com/autoc?';
    private $tableUrl = 'http://ichart.finance.yahoo.com/table.csv?';

    /**
     * @var int $timeout
     */
    private $timeout;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @param HttpClient $client
     * @param int $timeout
     */
    public function __construct( HttpClient $client, $timeout = 5 ) {
        $this->client  = $client;
        $this->timeout = $timeout;
    }

    /**
     * @param array $symbols
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return array
     */
    public function getHistoricalData( array $symbols, \DateTime $startDate, \DateTime $endDate ) {
        $result = [ ];
        /**
         * @var Share $share
         */
        foreach ( $symbols as $symbol ) {
            $data = $this->fetchChart( $symbol, $startDate, $endDate );
            if ( $data ) {
                foreach ( $data as $dayParams ) {
                    $dayValues                  = array_values( $dayParams );
                    $date                       = $dayValues[0];
                    $openCost                   = floatval( $dayValues[1] );
                    $result[ $date ][ $symbol ] = [ $date, $openCost ];
                }
            }
        }
        ksort( $result );

        return $result;
    }

    /**
     * @param $searchTerm
     *
     * @return array
     */
    public function search( $searchTerm ) {
        return $this->execQuery( $this->symbolAutocompleteUrl . 'query=' . urlencode( $searchTerm ) . '&lang=en-US&region=US&corsDomain=finance.yahoo.com', 'json' );
    }

    /**
     * http://ichart.finance.yahoo.com/table.csv?s={symbol}&{key}={value}.
     *
     * Keys:
     * a - Start Month (0-based; 0=January, 11=December)
     * b - Start Day
     * c - Start Year
     * d - End Month (0-based; 0=January, 11=December)
     * e - End Day
     * f - End Year
     * g - Always use the letter d
     *
     * @param string $symbol
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return array
     */
    private function fetchChart( $symbol, \DateTime $startDate = null, \DateTime $endDate = null ) {
        $startDate = isset( $startDate ) ? $startDate : new \DateTime( '-7 days' );
        $endDate   = isset( $endDate ) ? $endDate : new \DateTime();
        $query     = http_build_query( [
            'a' => $this->decreaseData( $startDate->format( 'm' ) ),
            'b' => $startDate->format( 'd' ),
            'c' => $startDate->format( 'Y' ),
            'd' => $this->decreaseData( $endDate->format( 'm' ) ),
            'e' => $endDate->format( 'd' ),
            'f' => $endDate->format( 'Y' ),
            's' => $symbol,
        ] );

        $data = $this->execQuery( $this->tableUrl . $query );

        if ( $data ) {
            return array_values( $this->parse( $data ) );
        }

        return [ ];
    }

    /**
     * Prepare csv data
     *
     * @param string $data
     *
     * @return array
     */
    private function parse( $data ) {
        $data = preg_split( '/\r\n|\r|\n/', trim( $data ) );
        unset( $data[0] );

        array_walk( $data, function ( &$item ) {
            $item = str_getcsv( $item );
        } );

        return $data;
    }

    /**
     * Perform month for request
     * (0-based; 0=January, 11=December)
     *
     * @param $value
     *
     * @return mixed
     */
    private function decreaseData( $value ) {
        $value = (int) ( $value - 1 );

        return max( $value, 0 );
    }

    /**
     * Execute the query
     *
     * @param $url
     * @param string $type
     *
     * @return mixed
     * @throws ApiException
     */
    private function execQuery( $url, $type = 'text/csv' ) {
        try {
            $this->client->setUrl( $url );
            $this->client->setTimeout( $this->timeout );

            return $this->client->execute( $type );
        } catch ( \HttpException $e ) {
            throw new ApiException( "Yahoo Finance API is not available.", ApiException::UNAVIALABLE, $e );
        }
    }
}
