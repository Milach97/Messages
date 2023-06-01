<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Message;
use Symfony\Component\Uid\Uuid;

#[Route('/api/messages', name: 'api_message_')]
class MessageController extends AbstractController
{

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $message = new Message();
         
        //tresc wiadomosci
        if(isset($data['content']))
           $message->setContent($data['content']);
        else
           return $this->json(['message' => 'Wiadomość nie została zapisana. Nie przekazano treści.'], Response::HTTP_BAD_REQUEST);

        //nadawca wiadomosci
        if(isset($data['sender']))
            $message->setSender($data['sender']);

        //data zapisu
        $message->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Warsaw')));
 
        $em = $doctrine->getManager();
        $em->persist($message);
        $em->flush();

        //zapis do pliku
        $this->saveMessageToFile($message);
 
        return $this->json(['message' => 'Wiadomość została zapisana',
                            'uuid' => $message->getId()], Response::HTTP_CREATED);
    }

    #[Route('/read/{id}', name: 'read', methods: ['GET'])]
    public function read(ManagerRegistry $doctrine, $id = null): JsonResponse
    {

        $id = Uuid::fromString($id);
        $message = $doctrine->getRepository(Message::class)->find($id);
        if(!$message){
            return $this->json(['error' => 'Wiadomość nie została znaleziona'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($message->getContent(), Response::HTTP_OK);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request, ManagerRegistry $doctrine, $sortBy = null): JsonResponse
    {
        $sortField = $request->query->get('sort', 'createdAt'); //domyslnie po dacie
        $sortOrder = $request->query->get('order', 'desc'); //domyslnie malejaco

        //wszytkie wiadomosci
        $messages = $doctrine
            ->getRepository(Message::class)
            ->createQueryBuilder('m')
            ->orderBy('m.' . $sortField, $sortOrder)
            ->getQuery()
            ->getResult();

        $response = [];
        foreach ($messages as $message) {
            $response[] = [
                'id' => $message->getId(),
                'sender' => $message->getSender(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse($response);
    }

    private function saveMessageToFile(Message $message)
    {
        $file = 'messages.txt';
        $content = sprintf("ID: %s\nNadawca: %s\nTreść: %s\nData utworzenia: %s\n\n", $message->getId(), $message->getSender(), $message->getContent(), $message->getCreatedAt()->format('Y-m-d H:i:s'));

        file_put_contents($file, $content, FILE_APPEND);
    }
}
