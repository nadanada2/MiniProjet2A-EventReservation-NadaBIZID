<?php
namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ── Créer un admin ──────────────────────────────────────────
        $admin = new User();
        $admin->setEmail('admin@eventapp.com');
        $admin->setUsername('admin');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // ── Créer un utilisateur normal ─────────────────────────────
        $user = new User();
        $user->setEmail('user@eventapp.com');
        $user->setUsername('utilisateur');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, 'user123'));
        $manager->persist($user);

        // ── Créer 5 événements ──────────────────────────────────────
        $eventsData = [
            [
                'title'       => 'Conférence Tech Sousse 2026',
                'description' => 'Une journée dédiée aux dernières innovations technologiques.',
                'date'        => '+3 days',
                'location'    => 'ISSAT Sousse, Tunisie',
                'seats'       => 100,
                'image'       => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800',
            ],
            [
                'title'       => 'Workshop Symfony & Docker',
                'description' => 'Atelier pratique sur Symfony 7 et Docker Compose.',
                'date'        => '+7 days',
                'location'    => 'Faculté des Sciences, Sousse',
                'seats'       => 30,
                'image'       => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800',
            ],
            [
                'title'       => 'Hackathon IA 2026',
                'description' => '48h pour développer des solutions innovantes avec l\'IA.',
                'date'        => '+14 days',
                'location'    => 'Technoparc Sousse',
                'seats'       => 50,
                'image'       => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800',
            ],
            [
                'title'       => 'Journée Portes Ouvertes ISSAT',
                'description' => 'Venez découvrir les formations et les laboratoires de l\'ISSAT.',
                'date'        => '+21 days',
                'location'    => 'ISSAT Sousse',
                'seats'       => 200,
                'image'       => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800',
            ],
            [
                'title'       => 'Meetup Développeurs Tunisie',
                'description' => 'Rencontre mensuelle de la communauté des développeurs tunisiens.',
                'date'        => '+30 days',
                'location'    => 'Espace Startup Sousse',
                'seats'       => 80,
                'image'       => 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=800',
            ],
        ];

        $createdEvents = [];
        foreach ($eventsData as $data) {
            $event = new Event();
            $event->setTitle($data['title']);
            $event->setDescription($data['description']);
            $event->setDate(new \DateTimeImmutable($data['date']));
            $event->setLocation($data['location']);
            $event->setSeats($data['seats']);
            $event->setImage($data['image']);
            $manager->persist($event);
            $createdEvents[] = $event;
        }

        // ── Créer quelques réservations de test ─────────────────────
        $reservationsData = [
            ['name' => 'Ahmed Ben Ali',    'email' => 'ahmed@test.com',   'phone' => '+216 20 000 001'],
            ['name' => 'Sarra Trabelsi',  'email' => 'sarra@test.com',   'phone' => '+216 20 000 002'],
            ['name' => 'Mohamed Gharbi',  'email' => 'mohamed@test.com', 'phone' => null],
        ];

        foreach ($reservationsData as $i => $data) {
            $resa = new Reservation();
            $resa->setName($data['name']);
            $resa->setEmail($data['email']);
            $resa->setPhone($data['phone']);
            $resa->setCreateAt(new \DateTimeImmutable());
            $resa->setEvent($createdEvents[$i % count($createdEvents)]);
            $manager->persist($resa);
        }

        $manager->flush();
    }
}