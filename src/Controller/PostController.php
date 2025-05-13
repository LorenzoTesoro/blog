<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class PostController extends AbstractController
{
    private $em;
    private $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    #[Route('/api/posts', name: 'view_posts', methods: ['GET'])]
    public function list(Security $security): JsonResponse
    {
        $user = $security->getUser(); // Get the currently authenticated user
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }
        $posts = $this->em->getRepository(Post::class)->findBy(
            ["user" => $user]
        );

        return new JsonResponse($this->serializer->serialize($posts, 'json', ['groups' => 'post:read']), 200, [], true);
    }

    #[Route('/api/posts/{id}', name: 'view_post', methods: ['GET'])]
    public function viewPost(?Post $post, Security $security): JsonResponse
    {
        $user = $security->getUser(); // Get the currently authenticated user
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }
        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], 404);
        }
        if ($post->getUser() != $user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        return new JsonResponse($this->serializer->serialize($post, 'json', ['groups' => 'post:read']), 200, [], true);
    }

    #[Route('/api/posts', name: 'create_post', methods: ['POST'])]
    public function createPost(Request $request, Security $security): JsonResponse
    {
        $parameters = $request->request->all();

        if (empty($parameters['title']) || empty($parameters['content'])) {
            return new JsonResponse(['Message' => 'Missing content or title'], 400);
        }

        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        $post = new Post();
        $post->setTitle($parameters['title']);
        $post->setContent($parameters['content']);
        $post->setUser($user);

        $this->em->persist($post);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($post, 'json', ['groups' => 'post:read']), 201, [], true);
    }


    #[Route('/api/posts/{id}', name: 'edit_post', methods: ['PUT'])]
    public function editPost(Request $request, ?Post $post, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }
        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], 404);
        }
        if ($post->getUser() != $user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }
        $parameters = $request->request->all();

        if (empty($parameters['title']) || empty($parameters['content'])) {
            return new JsonResponse(['Message' => 'Missing content or title'], 400);
        }

        $post->setTitle($parameters['title']);
        $post->setContent($parameters['content']);
        $post->setUser($user);

        $this->em->persist($post);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($post, 'json', ['groups' => 'post:read']), 200, [], true);
    }

    #[Route('/api/posts/{id}', name: 'delete_post', methods: ['DELETE'])]
    public function deletePost(?Post $post, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], 404);
        }

        if ($post->getUser() != $user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        $this->em->remove($post);
        $this->em->flush();

        return new JsonResponse(['message' => 'Post deleted'], 200);
    }
}
