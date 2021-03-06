<?php


namespace App\Service\PaymentService;


use App\Entity\OrderInfo;
use App\Service\EntityService\OrderInfoHandler\OrderInfoInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentHandler
{
    /**
     * @var OrderInfoInterface
     */
    private $orderInfo;

    private $publicKey;

    private $privateKey;
    /**
     * @var UrlGeneratorInterface
     */
    private $generator;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * PaymentHandler constructor.
     * @param OrderInfoInterface $orderInfo
     * @param UrlGeneratorInterface $generator
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(OrderInfoInterface $orderInfo, UrlGeneratorInterface $generator, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->orderInfo = $orderInfo;
        $this->publicKey = getenv('PUBLIC_KEY');
        $this->privateKey = getenv('PRIVATE_KEY');
        $this->generator = $generator;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @param OrderInfo $orderInfo
     * @return string
     */
    public function doPayment(OrderInfo $orderInfo): ?string
    {
        if ($orderInfo && ($orderInfo->getStatus() == 'confirmed' || $orderInfo->getStatus() == 'failed')) {

            $liqpay = new \LiqPay($this->publicKey, $this->privateKey);
            $form = $liqpay->cnb_form([
                'version' => 3,
                'action' => 'pay',
                'currency' => 'UAH',
                'amount' => $orderInfo->getTotalPrice(),
                'description' => 'Оплата заказа № ' . $orderInfo->getOrderUniqueId(),
                'result_url' => $this->generator->generate(
                    'home', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'language' => 'ru',
                'order_id' => $this->generateLiqPayId(7),
                'server_url' => $this->generator->generate(
                    'confirmPay', [
                    'orderUniqueId' => $orderInfo->getOrderUniqueId()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $form;
        }

        return null;
    }

    public function confirmPayment(OrderInfo $orderInfo, string $res): ?bool
    {
        if ($orderInfo && $orderInfo->getStatus() == 'confirmed') {
            $data = json_decode(base64_decode($res, true));

            switch ($data->status) {
                case 'success':
                case 'sandbox':
                    return $this->handleConfirmation($orderInfo, $data) == true ?: null;
                case 'failure':
                case 'error':
                case 'reversed':
                    $orderInfo->setStatus('failed');
                    $this->entityManager->flush();
                    return $this->generator->generate('user_orders', [
                        'uniqueId' => $orderInfo->getUser()->getUniqueId(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);
                case '3ds_verify':
                case 'wait_secure':
                case 'wait_accept':
                case 'processing':
                    break;
            }
            return true;
        }

        return false;
    }

    private function handleConfirmation(OrderInfo $orderInfo, object $res): bool
    {
        if ($orderInfo->getTotalPrice() == $res->amount) {
            $orderDetails = $orderInfo->getOrderDetails();
            $user = $orderInfo->getUser();
            $userBonuses = $user->getBonuses();

            foreach ($orderDetails as $orderDetail) {
                $receipt = $orderDetail->getReceipt();
                $product = $orderDetail->getProduct();
                $quantity = $orderDetail->getQuantity();
                $productSupply = $orderDetail->getProduct()->getSupply();

                if ($orderDetail->getReceipt() !== null) {
                    $userBonuses = ($receipt->getPrice() * ceil($quantity) + $product->getPrice() * $quantity) * 0.1 + $userBonuses;
                }
                $productSupply->setQuantity($productSupply->getQuantity() - $quantity);
            }

            $orderInfo->setStatus('payed');
            $user->setBonuses($userBonuses);

            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    private function generateLiqPayId(int $length): string
    {
        $str = (new \DateTime())->getTimestamp();

        $binHash = md5($str, true);
        $numHash = unpack('N2', $binHash);
        $hash = $numHash[1] . $numHash[2];
        if ($length && is_int($length)) {
            $hash = substr($hash, 0, $length);
        }
        return $hash;
    }
}

