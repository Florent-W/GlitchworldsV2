<?php

namespace App\Tests\Controller;

use App\Entity\Conversation;
use App\Entity\Utilisateur;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MessagerieControllerTest extends WebTestCase
{
    public function testLaMessagerieDemandeUneConnexion(): void
    {
        $client = self::createClient();
        $client->request('GET', '/messages');
        self::assertResponseRedirects('/connexion');
    }

    public function testDeuxMembresPeuventEchangerDesMessages(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $alice = (new Utilisateur())->setPseudo('Alice'.$suffixe)->setEmail('alice-'.$suffixe.'@test.local');
        $bob = (new Utilisateur())->setPseudo('Bob'.$suffixe)->setEmail('bob-'.$suffixe.'@test.local');
        $entityManager->persist($alice); $entityManager->persist($bob); $entityManager->flush();
        $aliceId = $alice->getId(); $bobId = $bob->getId();

        $client->loginUser($alice);
        $crawler = $client->request('GET', '/messages/nouveau');
        $client->submit($crawler->selectButton('Envoyer')->form([
            'nouvelle_conversation[destinataire]' => $bobId,
            'nouvelle_conversation[contenu]' => 'Bonjour Bob '.$suffixe,
        ]));
        $conversation = $entityManager->getRepository(Conversation::class)->findOneBy([]);
        self::assertInstanceOf(Conversation::class, $conversation);
        self::assertResponseRedirects('/messages/'.$conversation->getId());

        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('body', 'Bonjour Bob '.$suffixe);
        $client->submit($crawler->filter('form[name="message"]')->form([
            'message[contenu]' => 'Deuxième message '.$suffixe,
        ]));
        self::assertResponseRedirects('/messages/'.$conversation->getId());

        $entityManager->clear();
        $conversation = $entityManager->find(Conversation::class, $conversation->getId());
        self::assertCount(2, $conversation?->getMessages());
        $bob = $entityManager->find(Utilisateur::class, $bobId);
        $messageRepository = self::getContainer()->get(MessageRepository::class);
        self::assertSame(2, $messageRepository->compterNonLus($bob, $conversation));

        $client->loginUser($bob);
        $client->request('GET', '/messages/'.$conversation->getId());
        self::assertResponseIsSuccessful();
        self::assertSame(0, $messageRepository->compterNonLus($bob, $conversation));
        $crawler = $client->getCrawler();
        $client->submit($crawler->selectButton('Archiver')->form());
        self::assertResponseRedirects('/messages?archivees=1');
        $client->request('GET', '/messages?archivees=1&recherche=Alice'.$suffixe);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Alice'.$suffixe);

        $entityManager->clear();
        $conversation = $entityManager->find(Conversation::class, $conversation->getId());
        $entityManager->remove($conversation);
        $entityManager->remove($entityManager->find(Utilisateur::class, $aliceId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $bobId));
        $entityManager->flush();
    }
}
