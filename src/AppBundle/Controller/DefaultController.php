<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction( Request $request ) {
        return $this->render( '@App/default/index.html.twig' );
    }

    /**
     * @Route("/chart-data", name="chart_shares_ajax", condition="request.isXmlHttpRequest()")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function chartSharesAjaxAction( Request $request ) {
        $api     = $this->get( 'yahoo_finance_csv_api' );
        $symbols = [ ];
        $shares  = $this->getUser()->getShares();
        foreach ( $shares as $share ) {
            $symbols[] = $share->getCode();
        }

        $historicalData = $api->getHistoricalData(
            $symbols,
            new \DateTime( '-2 years' ),
            new \DateTime()
        );

        $chartData = $this->get( 'chart' )->calculateShares( $shares, $historicalData );

        return new JsonResponse( $chartData );

    }
}
