<?php

namespace AppBundle\Services\Finance;

use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpClient {
    /**
     * @var string $url
     */
    private $url;
    /**
     * @var int $timeout
     */
    private $timeout;

    /**
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl( $url ) {
        $this->url = $url;
    }

    /**
     * @return int
     */
    public function getTimeout() {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout( $timeout ) {
        $this->timeout = $timeout;
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    public function execute( $type = 'json' ) {
        $options = [
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $this->url,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_USERAGENT      => 'YahooFinanceApiWebKit',
        ];

        $ch = curl_init();
        curl_setopt_array( $ch, $options );
        $response    = curl_exec( $ch );
        $httpStatus  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $contentType = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
        curl_close( $ch );

        if ( $httpStatus !== 200 || strpos( $contentType, $type ) === false ) {
            throw new HttpException( $httpStatus, "HTTP call failed with error " . $httpStatus . "." );
        } elseif ( $response === false ) {
            throw new HttpException( 0, "HTTP call failed empty response." );
        }

        return $response;
    }
}