<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

#[AsCommand(name: 'app:seed-users-posts')]
class CreateUserCommand extends Command
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates demo users and posts.')
            ->setHelp('This command seeds the database with users and random posts.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create();
        $users = [];

        // Create users
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setEmail("user$i@example.com");

            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);

            $this->em->persist($user);
            $users[] = $user;
        }

        // Create posts
        for ($i = 0; $i < 10; $i++) {
            $post = new Post();
            $post->setTitle($faker->sentence(6));
            $post->setContent($faker->paragraph(4));

            // Assign to a random user
            $randomUser = $users[array_rand($users)];
            $post->setUser($randomUser); // This assumes you have setUser() method in Post entity

            $this->em->persist($post);
        }

        $this->em->flush();

        $output->writeln('<info>Seeded 5 users and 10 posts.</info>');

        return Command::SUCCESS;
    }
}
