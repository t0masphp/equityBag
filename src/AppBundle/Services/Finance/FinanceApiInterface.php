<?php
/**
 * Created by PhpStorm.
 * User: tomov
 * Date: 25.12.16
 * Time: 14:11
 */

namespace AppBundle\Services\Finance;


interface FinanceApiInterface {
    /**
     * Get portfolio costs for time period
     *
     * @param array $symbols
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return array
     */
    public function getHistoricalData( array $symbols, \DateTime $startDate, \DateTime $endDate );

    /**
     * Search exist symbols
     *
     * @param $searchTerm
     *
     * @return array
     */
    public function search( $searchTerm );
}