<?php

namespace App\Controller;

use App\Entity\Receipt;
use App\Form\OrderType;
use App\Mapper\OrderMapper;
use App\Model\OrderModel;
use App\Service\EntityService\OrderInfoHandler\OrderInfoInterface;
use App\Service\EntityService\UserService\UserServiceInterface;
use App\Service\RegistrationService\RegisterUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /**
     * @Route("/cart/makeOrder")
     *
     * @param Request $request
     * @param OrderInfoInterface $orderInfo
     * @param UserServiceInterface $userService
     * @param RegisterUserInterface $registerUser
     * @return Response
     */
    public function makeOrder(Request $request, OrderInfoInterface $orderInfo, UserServiceInterface $userService, RegisterUserInterface $registerUser): Response
    {
        $user = $this->getUser();
        if ($this->isGranted('IS_AUTHENTICATED_FULLY') && $this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            $orderModel = OrderMapper::entityUserToOrderModel($user);
        else
            $orderModel = new OrderModel();

        $form = $this->createForm(OrderType::class,$orderModel);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            if ($user) {
                $user = $userService->setEmptyPropertiesOfUser($user, $orderModel);
                $orderModel = $orderModel->setUser($user);

            } else {
                $user = $registerUser->registerUnknownUser($orderModel);
                $orderModel = $orderModel->setUser($user);
                $orderInfo->addOrder($orderModel,$request);

                return $this->redirectToRoute('confirmUnknownRegistration',[
                    'email' => $user->getEmail()
                ]);
            }
            $orderInfo->addOrder($orderModel,$request);

            return $this->redirectToRoute('home');
        }

        return $this->render('makeOrder.html.twig',[
            'form' => $form->createView()
        ]);
    }
}