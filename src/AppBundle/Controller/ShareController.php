<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\View\TwitterBootstrap3View;

use AppBundle\Entity\Share;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Share controller.
 *
 * @Route("/share")
 */
class ShareController extends Controller {
    /**
     * Lists all Share entities.
     *
     * @Route("/", name="share")
     * @Method("GET")
     */
    public function indexAction( Request $request ) {
        $em           = $this->getDoctrine()->getManager();
        $queryBuilder = $em->getRepository( 'AppBundle:Share' )->createQueryBuilder( 'e' );
        list( $filterForm, $queryBuilder ) = $this->filter( $queryBuilder, $request );
        list( $shares, $pagerHtml ) = $this->paginator( $queryBuilder, $request );

        return $this->render( '@App/share/index.html.twig', array(
            'shares'     => $shares,
            'pagerHtml'  => $pagerHtml,
            'filterForm' => $filterForm->createView(),

        ) );
    }


    /**
     * Create filter form and process filter request.
     *
     */
    protected function filter( $queryBuilder, $request ) {
        $queryBuilder->where( 'e.user = :user' )->setParameter( 'user', $this->getUser() );
        $filterForm = $this->createForm( 'AppBundle\Form\ShareFilterType' );

        // Bind values from the request
        $filterForm->handleRequest( $request );

        if ( $filterForm->isValid() ) {
            // Build the query from the given form object
            $this->get( 'petkopara_multi_search.builder' )->searchForm( $queryBuilder, $filterForm->get( 'search' ) );
        }

        return array( $filterForm, $queryBuilder );
    }

    /**
     * Get results from paginator and get paginator view.
     *
     */
    protected function paginator( $queryBuilder, Request $request ) {
        //sorting
        $sortCol = $queryBuilder->getRootAlias() . '.' . $request->get( 'pcg_sort_col', 'id' );
        $queryBuilder->orderBy( $sortCol, $request->get( 'pcg_sort_order', 'desc' ) );
        // Paginator
        $adapter    = new DoctrineORMAdapter( $queryBuilder );
        $pagerfanta = new Pagerfanta( $adapter );
        $pagerfanta->setMaxPerPage( $request->get( 'pcg_show', 10 ) );

        try {
            $pagerfanta->setCurrentPage( $request->get( 'pcg_page', 1 ) );
        } catch ( \Pagerfanta\Exception\OutOfRangeCurrentPageException $ex ) {
            $pagerfanta->setCurrentPage( 1 );
        }

        $entities = $pagerfanta->getCurrentPageResults();

        // Paginator - route generator
        $me             = $this;
        $routeGenerator = function ( $page ) use ( $me, $request ) {
            $requestParams             = $request->query->all();
            $requestParams['pcg_page'] = $page;

            return $me->generateUrl( 'share', $requestParams );
        };

        // Paginator - view
        $view      = new TwitterBootstrap3View();
        $pagerHtml = $view->render( $pagerfanta, $routeGenerator, array(
            'proximity'    => 3,
            'prev_message' => 'previous',
            'next_message' => 'next',
        ) );

        return array( $entities, $pagerHtml );
    }


    /**
     * Displays a form to create a new Share entity.
     *
     * @Route("/new", name="share_new")
     * @Method({"GET", "POST"})
     */
    public function newAction( Request $request ) {

        $share = new Share();
        $share->setUser( $this->getUser() );
        $form = $this->createForm( 'AppBundle\Form\ShareType', $share );
        $form->handleRequest( $request );

        if ( $form->isSubmitted() && $form->isValid() ) {
            $em = $this->getDoctrine()->getManager();
            $em->persist( $share );
            $em->flush();

            $editLink = $this->generateUrl( 'share_edit', array( 'id' => $share->getId() ) );
            $this->get( 'session' )->getFlashBag()->add( 'success', "<a href='$editLink'>New share was created successfully.</a>" );

            $nextAction = $request->get( 'submit' ) == 'save' ? 'share' : 'share_new';

            return $this->redirectToRoute( $nextAction );
        }

        return $this->render( '@App/share/new.html.twig', array(
            'share' => $share,
            'form'  => $form->createView(),
        ) );
    }


    /**
     * Finds and displays a Share entity.
     *
     * @Route("/{id}", name="share_show")
     * @Method("GET")
     */
    public function showAction( Share $share ) {
        if ( $share->getUser() !== $this->getUser() ) {
            throw new NotFoundHttpException();
        }
        $deleteForm = $this->createDeleteForm( $share );

        return $this->render( '@App/share/show.html.twig', array(
            'share'       => $share,
            'delete_form' => $deleteForm->createView(),
        ) );
    }


    /**
     * Displays a form to edit an existing Share entity.
     *
     * @Route("/{id}/edit", name="share_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction( Request $request, Share $share ) {
        if ( $share->getUser() !== $this->getUser() ) {
            throw new NotFoundHttpException();
        }
        $deleteForm = $this->createDeleteForm( $share );
        $editForm   = $this->createForm( 'AppBundle\Form\ShareType', $share );
        $editForm->handleRequest( $request );

        if ( $editForm->isSubmitted() && $editForm->isValid() ) {
            $em = $this->getDoctrine()->getManager();
            $em->persist( $share );
            $em->flush();

            $this->get( 'session' )->getFlashBag()->add( 'success', 'Edited Successfully!' );

            return $this->redirectToRoute( 'share_edit', array( 'id' => $share->getId() ) );
        }

        return $this->render( '@App/share/edit.html.twig', array(
            'share'       => $share,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ) );
    }


    /**
     * Deletes a Share entity.
     *
     * @Route("/{id}", name="share_delete")
     * @Method("DELETE")
     */
    public function deleteAction( Request $request, Share $share ) {
        if ( $share->getUser() !== $this->getUser() ) {
            throw new NotFoundHttpException();
        }
        $form = $this->createDeleteForm( $share );
        $form->handleRequest( $request );

        if ( $form->isSubmitted() && $form->isValid() ) {
            $em = $this->getDoctrine()->getManager();
            $em->remove( $share );
            $em->flush();
            $this->get( 'session' )->getFlashBag()->add( 'success', 'The Share was deleted successfully' );
        } else {
            $this->get( 'session' )->getFlashBag()->add( 'error', 'Problem with deletion of the Share' );
        }

        return $this->redirectToRoute( 'share' );
    }

    /**
     * Creates a form to delete a Share entity.
     *
     * @param Share $share The Share entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm( Share $share ) {
        return $this->createFormBuilder()
                    ->setAction( $this->generateUrl( 'share_delete', array( 'id' => $share->getId() ) ) )
                    ->setMethod( 'DELETE' )
                    ->getForm();
    }

    /**
     * Delete Share by id
     *
     * @Route("/delete/{id}", name="share_by_id_delete")
     * @Method("GET")
     */
    public function deleteByIdAction( Share $share ) {
        if ( $share->getUser() !== $this->getUser() ) {
            throw new NotFoundHttpException();
        }
        $em = $this->getDoctrine()->getManager();
        try {
            $em->remove( $share );
            $em->flush();
            $this->get( 'session' )->getFlashBag()->add( 'success', 'The Share was deleted successfully' );
        } catch ( \Exception $ex ) {
            $this->get( 'session' )->getFlashBag()->add( 'error', 'Problem with deletion of the Share' );
        }

        return $this->redirect( $this->generateUrl( 'share' ) );

    }


    /**
     * Bulk Action
     * @Route("/bulk-action/", name="share_bulk_action")
     * @Method("POST")
     */
    public function bulkAction( Request $request ) {
        $ids    = $request->get( "ids", array() );
        $action = $request->get( "bulk_action", "delete" );

        if ( $action == "delete" ) {
            try {
                $em         = $this->getDoctrine()->getManager();
                $repository = $em->getRepository( 'AppBundle:Share' );

                foreach ( $ids as $id ) {
                    $share = $repository->find( $id );
                    if ( $share->getUser() !== $this->getUser() ) {
                        continue;
                    }
                    $em->remove( $share );
                    $em->flush();
                }

                $this->get( 'session' )->getFlashBag()->add( 'success', 'shares was deleted successfully!' );

            } catch ( \Exception $ex ) {
                $this->get( 'session' )->getFlashBag()->add( 'error', 'Problem with deletion of the shares ' );
            }
        }

        return $this->redirect( $this->generateUrl( 'share' ) );
    }

    /**
     * @Route("/search-code/{query}", name="yahoo_symbol_search", condition="request.isXmlHttpRequest()")
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $query
     *
     * @return JsonResponse
     */
    public function searchCodeAction( Request $request, $query ) {
        $data = $this->get( 'yahoo_finance_json_api' )->search( $query );

        return new JsonResponse( $data );
    }

}
