<?php
/**
 * Created by PhpStorm.
 * User: tomov
 * Date: 25.12.16
 * Time: 16:05
 */

namespace AppBundle\Services;


use AppBundle\Entity\Share;

class ChartService {

    public function calculateShares( $shares, array $historyData ) {
        $result   = [ ];
        $shareMap = [ ];
        /**
         * @var Share $share
         */
        foreach ( $shares as $share ) {
            $shareMap[ $share->getCode() ] = $share->getCount();
        }

        foreach ( $historyData as $date => $codes ) {
            if ( ! array_key_exists( $date, $result ) ) {
                $result[ $date ] = [ 'date' => $date, 'cost' => 0 ];
            }
            foreach ( $codes as $code => $values ) {
                $result[ $date ]['cost'] += $shareMap[ $code ] * $values[1];
            }
        }

        return array_values( $result );
    }
}