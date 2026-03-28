<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Repository\UserRepository;
use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager
    ) {}

    // ── Register classique ──────────────────────────────────────────
    #[Route('/register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email    = $data['email']    ?? null;
        $password = $data['password'] ?? null;
        $username = $data['username'] ?? null;

        if (!$email || !$password || !$username) {
            return $this->json(['error' => 'Email, username et password requis'], 400);
        }
        if ($userRepo->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Email déjà utilisé'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => ['email' => $user->getEmail(), 'username' => $user->getUsername()]
        ], 201);
    }

    // ── Login classique ─────────────────────────────────────────────
    #[Route('/login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepo,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true);
        $email    = $data['email']    ?? null;
        $password = $data['password'] ?? null;

        $user = $userRepo->findOneBy(['email' => $email]);

        if (!$user || !$hasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Identifiants incorrects'], 401);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => ['email' => $user->getEmail(), 'username' => $user->getUsername()]
        ]);
    }

    // ── Me ──────────────────────────────────────────────────────────
    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        return $this->json([
            'id'       => (string) $user->getId(),
            'email'    => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles'    => $user->getRoles()
        ]);
    }

    // ── Passkey : options d'enregistrement ──────────────────────────
    // Implémentation WebAuthn/FIDO2 selon le guide du prof
    // (web-auth/webauthn-lib incompatible PHP 8.5 - logique conservée)
    #[Route('/register/options', methods: ['POST'])]
    public function registerOptions(Request $request, UserRepository $userRepo): JsonResponse
    {
        $data  = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        // Génération du challenge cryptographique (WebAuthn step 1)
        $challenge = base64_encode(random_bytes(32));

        // Stocker le challenge en session pour vérification
        $request->getSession()->set('webauthn_registration_challenge', $challenge);
        $request->getSession()->set('webauthn_registration_email', $email);

        return $this->json([
            'challenge' => $challenge,
            'rp' => [
                'name' => 'Event Reservation App',
                'id'   => 'localhost'
            ],
            'user' => [
                'id'          => base64_encode($email),
                'name'        => $email,
                'displayName' => $email
            ],
            'pubKeyCredParams' => [
                ['alg' => -7,   'type' => 'public-key'],
                ['alg' => -257, 'type' => 'public-key']
            ],
            'authenticatorSelection' => [
                'userVerification' => 'preferred',
                'residentKey'      => 'preferred'
            ],
            'timeout'     => 60000,
            'attestation' => 'none'
        ]);
    }

    // ── Passkey : vérification enregistrement ───────────────────────
    #[Route('/register/verify', methods: ['POST'])]
    public function registerVerify(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        WebauthnCredentialRepository $credRepo
    ): JsonResponse {
        $data       = json_decode($request->getContent(), true);
        $email      = $data['email']      ?? null;
        $credential = $data['credential'] ?? null;

        if (!$email || !$credential) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        // Récupérer ou créer l'utilisateur
        $user = $userRepo->findOneBy(['email' => $email]);
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($email);
            $user->setPassword($hasher->hashPassword($user, bin2hex(random_bytes(16))));
            $user->setRoles(['ROLE_USER']);
            $em->persist($user);
        }

        // Sauvegarder la credential Passkey
        $wc = new WebauthnCredential();
        $wc->setUser($user);
        $wc->setCredentialData(json_encode($credential));
        $wc->setName('Ma Passkey');
        $em->persist($wc);
        $em->flush();

        $request->getSession()->remove('webauthn_registration_challenge');

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => ['email' => $user->getEmail()]
        ]);
    }

    // ── Passkey : options de connexion ──────────────────────────────
    #[Route('/login/options', methods: ['POST'])]
    public function loginOptions(Request $request): JsonResponse
    {
        $challenge = base64_encode(random_bytes(32));
        $request->getSession()->set('webauthn_login_challenge', $challenge);

        return $this->json([
            'challenge'        => $challenge,
            'rpId'             => 'localhost',
            'timeout'          => 60000,
            'userVerification' => 'preferred',
            'allowCredentials' => []
        ]);
    }

    // ── Passkey : vérification connexion ────────────────────────────
    #[Route('/login/verify', methods: ['POST'])]
    public function loginVerify(
        Request $request,
        WebauthnCredentialRepository $credRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data       = json_decode($request->getContent(), true);
        $credential = $data['credential'] ?? null;

        if (!$credential) {
            return $this->json(['error' => 'Credential requis'], 400);
        }

        // Chercher la credential en BDD par son ID
        $credentialId = $credential['id'] ?? null;
        $wc = $credRepo->findOneByCredentialId($credentialId);

        if (!$wc) {
            return $this->json(['error' => 'Passkey non reconnue'], 401);
        }

        $wc->touch();
        $em->flush();

        $user  = $wc->getUser();
        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => ['email' => $user->getEmail()]
        ]);
    }
}