<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\User;
use App\Form\ChatType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ChatController extends Controller
{
    /**
     * @Route("/{chatUser}", name="chat_index")
     * @param Request $request
     * @param AuthorizationCheckerInterface $authChecker
     * @param User $chatUser
     * @ParamConverter("chatUser", class="App\Entity\User")
     * @return Response
     */
    public function index(Request $request, AuthorizationCheckerInterface $authChecker, User $chatUser = null): Response
    {
        $chat = new Chat();
        $form = $this->createForm(ChatType::class, $chat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $chat->setRecipient($chatUser);
            $chat->setSender($this->getUser());
            $this->getDoctrine()->getManager()->persist($chat);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('chat_index', ['chatUser' => $chatUser->getId()]);
        }
        if ($chatUser) {
            $messages = $this->getDoctrine()->getManager()->getRepository(Chat::class)->getMessages($chatUser, $this->getUser());
            $messagesForMe=$this->getDoctrine()->getManager()->getRepository(Chat::class)->getMessagesForMe($this->getUser(), $chatUser);
            /** @var Chat $messageForMe */
            foreach ($messagesForMe as $messageForMe) {
                $messageForMe->setReaded(true);
                $this->getDoctrine()->getManager()->persist($messageForMe);
            }
            $this->getDoctrine()->getManager()->flush();
        } else {
            $messages = null;
        }
        $users = $this->getDoctrine()->getRepository(User::class)->getAllByRole($authChecker->isGranted('ROLE_ADMIN'), $this->getUser());

        return $this->render('chat/index.html.twig', [
            'users' => $users,
            'chatUser' => $chatUser,
            'messages' => $messages,
            'form' => $form->createView()
        ]);
    }
}