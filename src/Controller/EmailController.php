<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EmailController extends Controller
{
    /**
     * @Route("/send_feedback", name="send_feedback")
     *
     *
     */
    public function send_feedback(Request $request, \Swift_Mailer $mailer)
    {
        $message = (new \Swift_Message('Обратная связь из кабинета'))
            ->setFrom($this->getParameter('send_from'))
            ->setTo($this->getParameter('admin_email'))
            ->setBody(
                $this->renderView('email/feedback.html.twig',[
                    'name' => $request->request->get('name'),
                    'email' => $request->request->get('email'),
                    'text' => $request->request->get('text')
                ]),
                'text/html'
            );

        $mailer->send($message);

        return $this->redirect($request->headers->get('referer'));
    }
}
