<?php

namespace App\Controller\Cabinet\User;

use App\Entity\User;
use App\Form\Cabinet\User\ProfileType;
use App\Service\Config;
use App\Service\Mailer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ProfileController extends Controller
{
    /**
     * @Route("/", name="cabinet_user_profile_index")
     * @param Request $request
     * @param UserInterface $userInterface
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function index(Request $request, UserInterface $userInterface, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository('App:User');
        $user = $repository->find($userInterface->getId());
        $showInvestor=$user->getShowInvestor();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();
            if ($user->getShowInvestor()!==$showInvestor){
                $recipients=[$this->get(Config::class)->getAdminEmail()];
                if ($user->getShowInvestor()){
                    $subject=$this->get('translator')->trans('profile.showInvestor.on.subject', [], 'email');
                    $text=$this->get('translator')->trans('profile.showInvestor.on.message', [
                        '%fullname%'=>$user->getFullname(),
                        '%phone%'=>$user->getUsername(),
                    ], 'email');
                } else {
                    $subject=$this->get('translator')->trans('profile.showInvestor.off.subject', [], 'email');
                    $text=$this->get('translator')->trans('profile.showInvestor.off.message', [
                        '%fullname%'=>$user->getFullname(),
                        '%phone%'=>$user->getUsername(),
                    ], 'email');
                }
                if (($user->getParent()) && ($user->getParent()->getEmail())){
                    $recipients[]=$user->getParent()->getEmail();
                }
                $this->get(Mailer::class)->send($recipients, $subject, $text);
            }
            if (!empty($user->getPlainPassword())){
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);
            }
            $entityManager->flush();

            return $this->redirectToRoute('cabinet_user_profile_index');
        }
        $inStructure = $repository->getInStructureQty($user);
        $invited = $repository->getInvitedQty($user);
        $notificationRepository = $this->getDoctrine()->getRepository('App:Notification');
        $notifications = $notificationRepository->findByUser($this->getUser());

        return $this->render('cabinet/user/profile/index.html.twig', [
            'form' => $form->createView(),
            'inStructure' => count($inStructure),
            'invited' => count($invited),
            'myNotifications' => $notifications
        ]);
    }
}