<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Receipt;
use App\Service\EntityService\ProductService\ProductServiceInterface;
use App\Service\EntityService\ReceiptService\ReceiptServiceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{

    /**
     * @Route("/category-{category_slug}/{slug}", name="showItem")
     * @ParamConverter("category", options={"mapping": {"category_slug": "slug"}})
     *
     * @param Category $category
     * @param string $slug
     * @param ProductServiceInterface $service
     * @param ReceiptServiceInterface $receiptService
     * @param SessionInterface $session
     * @return Response
     */
    public function item(Category $category, ?string $slug, ProductServiceInterface $service, ReceiptServiceInterface $receiptService, SessionInterface $session): Response
    {
        if ($category->getType() == 'products')
            $item = $service->getProduct($slug);
        else
            $item = $receiptService->getReceipt($slug);

        $this->checkIsValidProduct($category, $item);

        $orderType = $session->get('chooseOrder');
        $response =  $item->getType() == 'product'
            ? $this->render('product.html.twig', [
                'item' => $item,
                'active' => $item->getCategory()->getSlug()
            ])
            : $this->render('receipt.html.twig', [
                'item' => $item,
                'sizes' => $orderType ? $item->getProducts()->filter(function (Product $product){
                    return $product->getSupply()->getQuantity() > 0 && $product->getStatus();
                }) : $item->getProducts(),
                'active' => $item->getCategory()->getSlug()
            ]);

        $response->setPrivate();
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-store', true);

        return $response;
    }

    /**
     * @Route("/api/getSizes")
     *
     * @param Request $request
     * @param ProductServiceInterface $productService
     * @return Response
     */
    public function getSizes(Request $request, ProductServiceInterface $productService): Response
    {
        $id = (int) $request->request->get('receipt');
        $orderType = $request->request->get('orderType');
        $sizes = $productService->getSizes($id, $orderType);

        return $this->render('elements/sizes.html.twig', [
            'products' => $sizes, 'id' => $id
        ]);
    }

    private function checkIsValidProduct(Category $category, $item): void
    {
         if($item->getType() == 'product' && !$category->getProducts()->contains($item))
               throw $this->createNotFoundException('404');

         if($item->getType() == 'receipt' && !$category->getReceipts()->contains($item))
             throw $this->createNotFoundException('404');

    }

}