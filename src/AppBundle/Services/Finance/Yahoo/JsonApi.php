<?php

namespace AppBundle\Services\Finance\Yahoo;

use AppBundle\Services\Finance\FinanceApiInterface;
use AppBundle\Services\Finance\HttpClient;
use AppBundle\Services\Finance\Yahoo\Exception\ApiException;

class JsonApi implements FinanceApiInterface {
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
     * Search for stocks
     *
     * @param $searchTerm
     *
     * @return mixed
     * @throws ApiException
     */
    public function search( $searchTerm ) {
        $url = "http://autoc.finance.yahoo.com/autoc?query=" . urlencode( $searchTerm ) . "&lang=en-US&region=US&corsDomain=finance.yahoo.com";
        try {
            $client   = new HttpClient( $url, $this->timeout );
            $response = $client->execute();
        } catch ( \HttpException $e ) {
            throw new ApiException( "Yahoo Search API is not available.", ApiException::UNAVIALABLE, $e );
        }

        //Remove callback function from response
        $response = preg_replace( "#^YAHOO\\.Finance\\.SymbolSuggest\\.ssCallback\\((.*)\\)$#", "$1", $response );

        $decoded = json_decode( $response, true );
        if ( ! isset( $decoded['ResultSet']['Result'] ) ) {
            throw new ApiException( "Yahoo Search API returned an invalid result.", ApiException::INVALID_RESULT );
        }

        return $decoded['ResultSet']['Result'];
    }


    /**
     * Get historical data for a symbol
     *
     * @param array $symbols
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return mixed
     * @throws ApiException
     */
    public function getHistoricalData( array $symbols, \DateTime $startDate, \DateTime $endDate ) {
        $result = [ ];
        $query  = "select * from yahoo.finance.historicaldata where startDate='" . $startDate->format( "Y-m-d" ) . "' and endDate='" . $endDate->format( "Y-m-d" ) . "' and symbol in ('" . implode( "','", $symbols ) . "')";
        $data   = $this->execQuery( $query )['query'];
        if ( array_key_exists( 'results', $data ) ) {
            if ( array_key_exists( 'quote', $data['results'] ) ) {
                foreach ( $data['results']['quote'] as $row ) {
                    $date                       = $row['Date'];
                    $symbol                     = $row['Symbol'];
                    $openCost                   = floatval( $row['Open'] );
                    $result[ $date ][ $symbol ] = [ $date, $openCost ];
                }
            }
        }
        ksort( $result );

        return $result;
    }


    /**
     * Get quotes for one or multiple symbols
     *
     * @param $symbols
     *
     * @return array
     * @throws ApiException
     */
    public function getQuotes( $symbols ) {
        if ( is_string( $symbols ) ) {
            $symbols = array( $symbols );
        }
        $query = "select * from yahoo.finance.quotes where symbol in ('" . implode( "','", $symbols ) . "')";

        return $this->execQuery( $query );
    }


    /**
     * Get quotes list for one or multiple symbols
     *
     * @param $symbols
     *
     * @return array
     * @throws ApiException
     */
    public function getQuotesList( $symbols ) {
        if ( is_string( $symbols ) ) {
            $symbols = array( $symbols );
        }
        $query = "select * from yahoo.finance.quoteslist where symbol in ('" . implode( "','", $symbols ) . "')";

        return $this->execQuery( $query );
    }


    /**
     * Execute the query
     *
     * @param $query
     *
     * @return mixed
     * @throws ApiException
     * @throws \HttpException
     */
    private function execQuery( $query ) {
        try {
            $url      = $this->createUrl( $query );
            $client   = new HttpClient( $url, $this->timeout );
            $response = $client->execute();
        } catch ( \HttpException $e ) {
            throw new ApiException( "Yahoo Finance API is not available.", ApiException::UNAVIALABLE, $e );
        }
        $decoded = json_decode( $response, true );
        if ( ! isset( $decoded['query']['results'] ) || count( $decoded['query']['results'] ) === 0 ) {
            throw new ApiException( "Yahoo Finance API did not return a result.", ApiException::EMPTY_RESULT );
        }

        return $decoded;
    }


    /**
     * Create the URL to call
     *
     * @param array $query
     *
     * @return string
     */
    private function createUrl( $query ) {
        $params = array(
            'env'    => "http://datatables.org/alltables.env",
            'format' => "json",
            'q'      => $query,
        );

        return "http://query.yahooapis.com/v1/public/yql?" . http_build_query( $params );
    }

}
