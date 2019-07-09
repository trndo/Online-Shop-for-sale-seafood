<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /**
     * @Route("/makeOrder")
     *
     * @return Response
     */
    public function order(): Response
    {
        return $this->render('makeOrder.html.twig');
    }
}