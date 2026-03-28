<?php
namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    public function saveCredential(User $user, string $credentialData, string $name = 'Ma clé'): WebauthnCredential
    {
        $credential = new WebauthnCredential();
        $credential->setUser($user);
        $credential->setCredentialData($credentialData);
        $credential->setName($name);

        $this->getEntityManager()->persist($credential);
        $this->getEntityManager()->flush();

        return $credential;
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function findOneByCredentialId(string $credentialId): ?WebauthnCredential
{
    $all = $this->findAll();
    foreach ($all as $cred) {
        $data = json_decode($cred->getCredentialData(), true);
        if (($data['id'] ?? null) === $credentialId) {
            return $cred;
        }
    }
    return null;
}
}