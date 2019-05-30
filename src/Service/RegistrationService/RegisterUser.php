<?php


namespace App\Service\RegistrationService;

use App\Entity\User;
use App\Model\AdminModel;
use App\Model\UserRegistrationModel;
use App\Service\MailService\MailSenderInterface;
use App\Service\TokenService\TokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegisterUser implements RegisterUserInterface
{
    private $passwordEncoder;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var MailSenderInterface
     */
    private $mailSender;

    /**
     * RegisterUser constructor.
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EntityManagerInterface $entityManager
     * @param MailSenderInterface $mailSender
     */
    public function __construct(UserPasswordEncoderInterface $passwordEncoder,EntityManagerInterface $entityManager, MailSenderInterface $mailSender)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
        $this->mailSender = $mailSender;
    }

    /**
     * @param UserRegistrationModel $model
     * @return User
     */
    public function registerUser(UserRegistrationModel $model): User
    {
        $user = new User();

        $user->setEmail($model->getEmail())
             ->setPassword($this->passwordEncoder->encodePassword(
                $user,
                $model->getPassword()
                ))
            ->setName($model->getName())
            ->setSurname($model->getSurname())
            ->setAddress($model->getAddress())
            ->setPhone($model->getPhone())
            ->setToken(TokenGenerator::generateToken(
                $this->entityManager->getRepository(User::class)->findTokens(),20
            ))
            ->setRegistrationStatus(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->mailSender->sendMessage($user);

        return $user;

    }

    public function registerAdmin(AdminModel $model): User
    {
        $admin = new User();

        $admin->setName($model->getName())
            ->setEmail($model->getEmail())
            ->setPhone($model->getPhone())
            ->setSurname($model->getSurname())
            ->setPassword(($this->passwordEncoder->encodePassword(
                $admin,
                $model->getPassword())
            ))
            ->setRoles([
                $model->getRole()
            ])
            ->setToken(TokenGenerator::generateToken(
                $this->entityManager->getRepository(User::class)->findTokens(),60
            ))
            ->setRegistrationStatus(false);

            $this->entityManager->persist($admin);
            $this->entityManager->flush();

            $this->mailSender->sendAdminMessage($admin);

            return $admin;
    }

    /**
     * @param User $user
     */
    public function confirmUser(User $user): void
    {
        $user->setToken(null)
            ->setRegistrationStatus(true);

        $this->entityManager->flush();
    }

    public function getRegisterAdminData(AdminModel $model, User $admin): User
    {
        $admin->setEmail($model->getEmail())
             ->setName($model->getName())
             ->setSurname($model->getSurname())
             ->setPhone($model->getPhone())
             ->setPassword($this->passwordEncoder->encodePassword(
                 $admin,
                 $model->getPassword()
             ));

            return $admin;
    }

}