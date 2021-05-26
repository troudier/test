<?php

namespace App\Controller;

// ...
use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function login(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $this->getUser();
        if (!$user) {
            $credentials = json_decode($request->getContent(), true);
            $userRepository = $this->getDoctrine()->getRepository(User::class);
            /**
             * @var $dbUser array[User]
             * */
            $dbUser = $userRepository->findBy(['username' => $credentials['username']]);
            $password = $passwordEncoder->isPasswordValid(
                $dbUser[0],
                $credentials['password']
            );
            if ($password) {
                $user = $dbUser[0];
            } else {
                return $this->json(
                    [
                        'error' => 'Invalid credentials',
                    ],
                    401
                );
            }
        }

        return $this->json([
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
        ]);
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $data = json_decode($request->getContent(), true);
        $user = new User();
        $user->setUsername($data['username']);
        $user->setRoles($data['roles']);
        $user->setPassword($passwordEncoder->encodePassword(
            $user,
            $data['password']
        ));
        try {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $error) {
            return $this->json(
                ['error' => 'User exists'],
                400
            );
        }

        return $this->json(
            ['content' => 'User Created'],
            201
        );
    }
}
