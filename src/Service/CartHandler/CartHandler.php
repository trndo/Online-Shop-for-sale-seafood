<?php


namespace App\Service\CartHandler;


use App\Repository\ProductRepository;
use App\Repository\ReceiptRepository;
use App\Service\EntityService\SupplyService\SupplyServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartHandler implements CartHandlerInterface
{
    /**
     * @var ReceiptRepository
     */
    private $receiptRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var SupplyServiceInterface
     */
    private $supplyService;

    public function __construct(ReceiptRepository $receiptRepository, ProductRepository $productRepository)
    {
        $this->receiptRepository = $receiptRepository;
        $this->productRepository = $productRepository;
    }


    /**
     * @param string $type
     * @param string $slug
     * @return \App\Entity\Product|\App\Entity\Receipt
     */
    public function getItem(string $type, string $slug)
    {
        if ($type == 'product') {
            return $this->productRepository->findProductBySlug($slug);
        }

        if ($type == 'receipt') {
            return $this->receiptRepository->findReceiptBySlug($slug);
        }
    }

    /**
     * Add receipt or product to cart
     *
     * @param Request $request
     * @param string $key
     * @param array $options
     */
    public function addItemToCart(Request $request,string $key, array $options): void
    {
        $session = $request->getSession();
        $shoppingCart = [];

        if (!$session) {
            $session->set('cart',$shoppingCart);
        } else {
            $shoppingCart = $session->get('cart');
            $shoppingCart[$key] = $options;
            $session->set('cart',$shoppingCart);
        }
        $this->countTotalSum($session);
    }

    public function removeFromCart(Request $request,string $key): void
    {
        $session = $request->getSession();
        $cart = $session->get('cart');

        if (isset($cart[$key])) {
            unset($cart[$key]);
            $session->set('cart',$cart);
        }

        $this->countTotalSum($session);
    }

    /**
     * Add item's quantity by key
     *
     * @param Request $request
     * @param string $key
     * @param float $quantity
     * @return array
     */
    public function changeItemQuantity(Request $request, string $key, float $quantity): array
    {
        $session = $request->getSession();
        $product = $this->productRepository->findProductBySlug($key);
        $productQuantity = $product->getSupply()->getQuantity();
        $cart = $session->get('cart');

        if ($quantity > $productQuantity ) {
            return [
                'status'=> false,
                'message' => 'На данный момент доступно '.$productQuantity.' '.$product->getUnit().' !'
            ];
        }

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = $quantity ;
            $session->set('cart',$cart);
        }
        $this->countTotalSum($session);

        return [
            'status' => true
        ];

    }

    private function countTotalSum(SessionInterface $session)
    {
        $total = 0;
        $cart = $session->get('cart');

        foreach ($cart as $value) {
            $total += $value['item']->getPrice()*$value['quantity'];
        }
        $session->set('totalSum',$total);
    }
}