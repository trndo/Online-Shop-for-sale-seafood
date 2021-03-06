<?php


namespace App\Service\EntityService\ReservationHandler;


use App\Collection\ReservationCollection;
use App\Entity\Product;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\CartHandler\Item;
use App\Service\EntityService\ProductService\ProductServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ReservationHandler implements ReservationInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ReservationRepository
     */
    private $reservationRepository;
    /**
     * @var ProductServiceInterface
     */
    private $productService;
    /**
     * @var SessionInterface
     */
    private $session;

    private $reservationId;

    /**
     * ReservationHandler constructor.
     * @param EntityManagerInterface $entityManager
     * @param ReservationRepository $reservationRepository
     * @param ProductServiceInterface $productService
     * @param SessionInterface $session
     */
    public function __construct(EntityManagerInterface $entityManager, ReservationRepository $reservationRepository, ProductServiceInterface $productService, SessionInterface $session)
    {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
        $this->productService = $productService;
        $this->session = $session;
        $this->reservationId = $session->get('reservationId');
    }


    public function reserve(Product $product, Item $item): void
    {
        $reservation = $this->getReservation($item->getUniqueIndex());
        $supply = $product->getSupply();
        $quantity = $item->getQuantity();

        if ($reservation) {
            $reservationQuantity = $reservation->getReservationQuantity();
            $diff = $this->recognizeDiff($quantity, $reservationQuantity);
            $supply->setReservationQuantity($supply->getReservationQuantity() + $diff);
            $reservation->setReservationQuantity($quantity);
            $this->addToSessionReserve($item);
        } elseif ($this->session->get('chooseOrder', true)) {
            $reservation = new Reservation();
            $reservation->setReservationQuantity($quantity)
                ->setProduct($product)
                ->setReservationTime(new \DateTime())
                ->setPositionKey($item->getUniqueIndex());

            if (!$this->reservationId) {
                $reservation->setUniqId(
                    $this->generateUniqueReservation(
                        $reservation->getReservationTime()->format('Y-m-d H:i'), 5
                    ));
                $this->session->set('reservationId', $reservation->getUniqId());
            } else {
                $reservation->setUniqId($this->reservationId);
            }

            $this->entityManager->persist($reservation);
            $supply->setReservationQuantity($supply->getReservationQuantity() - $quantity);
            $this->addToSessionReserve($item);
        }
        $this->entityManager->flush();
    }

    /**
     * @param string $key
     * @return Reservation
     */
    public function getReservation(string $key): ?Reservation
    {
        if (!$this->reservationId) {
            return null;
        }
        $reservation = $this->reservationRepository->findOneBy([
            'uniqId' => $this->reservationId,
            'positionKey' => $key
        ]);
        return $reservation instanceof Reservation ? $reservation : null;
    }

    /**
     *
     * @param Item $item
     * @return void
     */
    public function deleteReservation(Item $item): void
    {
        if ($item->getItemType() == 'product') {
            $reservation = $this->getReservation($item->getUniqueIndex());
        } elseif ($item->getItemType() == 'receipt') {
            $reservation = $this->getReservation($item->getUniqueIndex());
        } else
            $reservation = null;
        if ($reservation instanceof Reservation) {
            $supply = $reservation->getProduct()->getSupply();
            $supply->setReservationQuantity($supply->getReservationQuantity() + $reservation->getReservationQuantity());
            $this->entityManager->remove($reservation);
            $this->entityManager->flush();
        }
    }

    private function explodeProductId(string $productId)
    {
        return (int)explode('-', $productId)[1];
    }

    private function recognizeDiff(float $newQuantity, float $oldQuantity): float
    {
        $difference = 0;
        if ($newQuantity > $oldQuantity) {
            $difference = ($newQuantity - $oldQuantity) * -1;
        }

        if ($newQuantity < $oldQuantity) {
            $difference = $oldQuantity - $newQuantity;
        }

        return $difference;
    }

    private function generateUniqueReservation(string $reservationTime, int $length): string
    {
        return \substr(\md5(\uniqid($reservationTime, true)), 0, $length);
    }

    public function getReservations(): ReservationCollection
    {
        return new ReservationCollection($this->reservationRepository->findAll());
    }

    private function addToSessionReserve(Item $item): void
    {
        $reserve = $this->session->get('reservation', []);
        $reserve[$item->getUniqueIndex()] = $item->getQuantity();
        $this->session->set('reservation', $reserve);
    }

    public function deleteReservationsById(?int $id): void
    {
        $reservations = $this->reservationRepository->getReservationsById($id);
        if ($reservations) {
            foreach ($reservations as $reservation) {
                $this->entityManager->remove($reservation);
            }
        }
    }
}