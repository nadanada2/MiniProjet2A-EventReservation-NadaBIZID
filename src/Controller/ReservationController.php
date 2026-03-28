<?php
namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Event;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/events/{id}/reserve', name: 'event_reserve', methods: ['GET', 'POST'])]
    public function reserve(
        Event $event,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $reservation = new Reservation();
        $reservation->setEvent($event);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setCreatedAt(new \DateTimeImmutable());
            $em->persist($reservation);
            $em->flush();

            // Envoi du mail de confirmation
            $email = (new TemplatedEmail())
                ->from('noreply@eventapp.com')
                ->to($reservation->getEmail())
                ->subject('Confirmation de réservation : ' . $event->getTitle())
                ->htmlTemplate('emails/confirmation.html.twig')
                ->context([
                    'reservation' => $reservation,
                    'event' => $event,
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Réservation confirmée ! Un email de confirmation vous a été envoyé.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }
}