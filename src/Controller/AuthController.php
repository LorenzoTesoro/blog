<?php

// src/Controller/PostController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;


class AuthController extends AbstractController
{
    private $em;
    private $jwtManager;

    public function __construct(
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->em = $em;
        $this->jwtManager = $jwtManager;
    }

    #[Route('/api/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $credentials = json_decode($request->getContent(), true);

        if (empty($credentials['username']) || empty($credentials['password'])) {
            return new JsonResponse(['message' => 'Missing username or password'], 400);
        }

        // Find user by username
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);

        if (!$user || !password_verify($credentials['password'], $user->getPassword())) {
            return new JsonResponse(['message' => 'Invalid credentials'], 401);
        }

        // Generate the JWT token for the authenticated user
        $token = $this->jwtManager->create($user);

        return new JsonResponse(['token' => $token], 200);
    }
}
