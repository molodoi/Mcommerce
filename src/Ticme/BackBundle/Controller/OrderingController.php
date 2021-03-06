<?php

namespace Ticme\BackBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Ticme\BackBundle\Entity\Ordering;
use Ticme\BackBundle\Form\OrderingType;
use Symfony\Component\HttpFoundation\Response;

class OrderingController extends Controller
{
    public function activeAction($id){

        $em = $this->getDoctrine()->getManager();
        $order = $em->getRepository('TicmeBackBundle:Ordering')->find($id);

        $order->setValidated(true);
        $order->setUpdatedAt(new \DateTime('NOW'));
        $em->flush();
        return $this->redirectToRoute('ticme_back_order_list');
    }

    public function indexAction(Request $request, $page)
    {
        $repository = $this->getDoctrine()
            ->getManager()
            ->getRepository('TicmeBackBundle:Ordering');

        $allOrderings = $repository->findAll();

        if (null === $allOrderings) {
            throw new NotFoundHttpException("Aucunes commandes.");
        }

        if(empty($page)){
            $page = $request->query->getInt('page', 1);
        }

        $paginator = $this->get('knp_paginator');
        $orderings = $paginator->paginate(
            $allOrderings,
            $page,
            12
        );

        return $this->render('TicmeBackBundle:Ordering:index.html.twig',array(
            'orderings' => $orderings
        ));
    }

    public function listAction(Request $request, $page)
    {
        $repository = $this->getDoctrine()
            ->getManager()
            ->getRepository('TicmeBackBundle:Ordering');

        $allOrderings = $repository->findAll();

        if (null === $allOrderings) {
            throw new NotFoundHttpException("Aucunes commandes.");
        }

        if(empty($page)){
            $page = $request->query->getInt('page', 1);
        }

        $paginator = $this->get('knp_paginator');
        $orderings = $paginator->paginate(
            $allOrderings,
            $page,
            12
        );

        return $this->render('TicmeBackBundle:Ordering:list.html.twig',array(
            'orderings' => $orderings
        ));
    }

    public function createAction(Request $request)
    {
        $ordering = new Ordering();

        $formOrdering = $this->createForm(new OrderingType(), $ordering);

        $formOrdering->handleRequest($request);

        if($formOrdering->isValid()){

            $em = $this->getDoctrine()->getManager();

            $em->persist($ordering);
            $em->flush();

            $session = $request->getSession();

            $session->getFlashBag()->add('info', 'Commande ajouté');

            return $this->redirectToRoute('ticme_back_order_list');
        }

        return $this->render('TicmeBackBundle:Ordering:create.html.twig',
            array(
                'formOrdering' => $formOrdering->createView(),
            )
        );
    }

    public function editAction(Ordering $ordering, Request $request)
    {
        $formOrdering = $this->createForm(new OrderingType(), $ordering);

        $formOrdering->handleRequest($request);

        if($formOrdering->isValid()){

            $em = $this->getDoctrine()->getManager();
            $em->persist($ordering);
            $em->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add('info', $ordering->getReference(). ' modifié');

            return $this->redirectToRoute('ticme_back_ordering_list');

        }

        return $this->render('TicmeBackBundle:Ordering:edit.html.twig',
            array(
                'formOrdering' => $formOrdering->createView(),
                'ordering' => $ordering
            )
        );
    }

    public function showAction(Ordering $ordering, Request $request)
    {
        if (!$ordering) {
            throw $this->createNotFoundException('Unable to find Commande entity.');
        }

        return $this->render('TicmeBackBundle:Ordering:show.html.twig',
            array(
                'ordering' => $ordering
            )
        );
    }

    public function deleteAction(Ordering $ordering, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $reference = $ordering->getReference();
        $em->remove($ordering);
        $em->flush();
        $session = $request->getSession();
        $session->getFlashBag()->add('info', $reference. ' supprimé');

        return $this->redirectToRoute('ticme_back_ordering_list');
    }


    public function invoicesPDFAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $order = $em->getRepository('TicmeBackBundle:Ordering')->findOneBy(
            array(
                'validated' => 1,
                'id' => $id,
            )

        );

        if(!$order){
            $this->get('session')->getFlashBag()->add('error', 'Une erreur est survenue');
            return $this->redirect($this->generateUrl('ticme_back_order_list'));
        }

        //on stocke la vue à convertir en PDF, en n'oubliant pas les paramètres twig si la vue comporte des données dynamiques
        $content = $this->container->get('setNewInvoice')->invoice($order)->Output('facture_'.$order->getReference().'.pdf', true);
        $response = new Response();
        $response->setContent($content);
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-disposition', 'filename=facture_'.$order->getReference().'.pdf');

        return $response;
    }

}
