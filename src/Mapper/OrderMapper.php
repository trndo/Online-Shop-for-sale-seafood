<?php


namespace App\Mapper;


use App\Entity\OrderInfo;
use App\Entity\User;
use App\Model\OrderModel;

final class OrderMapper
{
    public static function entityUserToOrderModel(User $user): OrderModel
    {
        $model = new OrderModel();

        $model->setName($user->getName())
            ->setSurname($user->getSurname())
            ->setEmail($user->getEmail())
            ->setPhoneNumber($user->getPhone())
            ->setDeliveryType($user->getAddress())
            ->setUser($user);

        return $model;
    }

    public static function entityToModel(OrderInfo $info): OrderModel
    {
        $model = new OrderModel();

        return $model->setOrderDate($info->getOrderDate())
                ->setOrderTime($info->getOrderTime())
                ->setTotalPrice($info->getTotalPrice())
                ->setAdminMessage($info->getAdminMessage());
    }

    public static function modelToEntity(OrderModel $model, OrderInfo $info): OrderInfo
    {
        $info->setOrderDate($model->getOrderDate())
            ->setOrderTime($model->getOrderTime())
            ->setTotalPrice($model->getTotalPrice())
            ->setAdminMessage($model->getAdminMessage());

        return $info;
    }

    public static function orderModelToEntity(OrderModel $model): OrderInfo
    {
        $entity = new OrderInfo();

        $entity->setOrderDate($model->getOrderDate())
            ->setOrderTime($model->getOrderTime())
            ->setCreatedAt(new \DateTime())
            ->setUser($model->getUser())
            ->setStatus('new')
            ->setTotalPrice($model->getTotalPrice())
            ->setOrderPhone($model->getPhoneNumber())
            ->setOrderEmail($model->getEmail())
            ->setAddress($model->getDeliveryType())
            ->setCoordinates($model->getCoordinates());

        return $entity;
    }

}