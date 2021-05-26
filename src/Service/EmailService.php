<?php

namespace App\Service;

use App\Entity\LienMail;
use App\Entity\Mail;
use App\Entity\PersonneLien;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EmailService
{
    public array $typeMapping = [
        '-1' => 'OUTPUT',
        '0' => 'NPAI',
        '1' => 'E-Mail',
    ];

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Créé un Mail depuis une adresse email.
     *
     * @param $email
     *
     * @throws \Exception
     */
    private function createEmail($email): Mail
    {
        $mail = new Mail();
        $mail->setUuid(Uuid::uuid4());
        $mail->setValeur($email);
        $mail->setDateCreation(new DateTime());
        $mail->setUserCreation($this->tokenStorage->getToken()->getUser());
        $mail->setDateModification(new DateTime());
        $mail->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($mail);

        return $mail;
    }

    /**
     * Ajoute un email en relation avec une personne lien.
     *
     * @param $uuid
     * @param $email
     *
     * @throws \Exception
     */
    public function add($uuid, $email, $type = '1', $principal = false)
    {
        $personneLien = $this->em->getRepository(PersonneLien::class)
            ->findBy(['uuid' => $uuid]);
        $lien = new LienMail();
        $lien->setUuid(Uuid::uuid4());
        $lien->setMail($this->createEmail($email));
        $lien->setLien($personneLien[0]);
        $lien->setPrincipal($principal);
        $lien->setType((int) $type);
        $this->em->persist($lien);
        $this->em->flush();
    }

    /**
     * Renvoie la liste des emails liés à une PersonneLien.
     */
    public function getPersonneEmails(PersonneLien $lien): array
    {
        $emails = [];
        /** @var LienMail $lienEmail */
        foreach ($lien->getMails() as $lienEmail) {
            $data = [];
            $data['uuid'] = $lienEmail->getUuid()->toString();
            $data['principal'] = $lienEmail->getPrincipal();
            $data['type'] = $this->typeMapping[$lienEmail->getType()];
            $data['type_id'] = $lienEmail->getType();
            $data['valeur'] = $lienEmail->getMail()->getValeur();
            $emails[] = $data;
        }

        return $emails;
    }

    /**
     * Met à jour la liste des liens E-Mail d'une personne (et leurs valeurs).
     *
     * @param PersonneLien $lien
     * @param array        $data
     *
     * @throws \Exception
     */
    public function updatePersonneEmails($lien, $data)
    {
        $aSupprimer = [];
        $lienEmails = $this->em->getRepository(LienMail::class)->findBy(['lien' => $lien]);
        /** @var LienMail $lienEmail */
        foreach ($lienEmails as $lienEmail) {
            $existe = false;
            foreach ($data as $id => $item) {
                if ($item['uuid'] === $lienEmail->getUuid()->toString()) {
                    $existe = true;
                    $lienEmail->setPrincipal((bool) $item['principal']);
                    $lienEmail->setType($this->getTypeId($item['type']));
                    if ($lienEmail->getMail()->getvaleur() !== $item['valeur']) {
                        $email = $this->em->getRepository(Mail::class)
                            ->findBy(['valeur' => $item['valeur']]);
                        if (isset($email[0])) {
                            $lienEmail->setMail($email[0]);
                        } else {
                            $lienEmail->setMail($this->createEmail($item['valeur']));
                        }
                    }
                    $this->em->persist($lienEmail);
                    unset($data[$id]);
                }
            }
            if (!$existe) {
                $aSupprimer[] = $lienEmail;
            }
        }
        foreach ($data as $item) {
            $this->add(
                $lien->getUuid()->toString(),
                $item['valeur'],
                $this->getTypeId($item['type']),
                $item['principal']
            );
        }
        foreach ($aSupprimer as $item) {
            $this->em->remove($item);
        }
    }

    /**
     * Retourne l'id d'un type d'E-Mail depuis son libellé.
     *
     * @param $type
     */
    private function getTypeId($type): string
    {
        foreach ($this->typeMapping as $id => $texte) {
            if ($texte === $type) {
                return $id;
            }
        }
        // Par défaut, on renvoie le type "E-Mail"
        return '1';
    }
}
