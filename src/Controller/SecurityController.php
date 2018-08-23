<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends Controller
{
    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $countryRepository = $this->getDoctrine()->getRepository('App:Country');
        $countries = $countryRepository->findAll();

        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('security/login.html.twig', [
            'error' => $error,
            'countries' => $countries
        ]);
    }

    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $countryRepository = $this->getDoctrine()->getRepository('App:Country');
        $countries = $countryRepository->findAll();

        $user = new User();

        $form = $this->createFormBuilder($user, [
            'attr' => [
                'class' => 'form-horizontal form-material',
                'id' => 'loginform'
            ]
        ])
        ->setMethod('POST')
        ->add('username', TextType::class,[
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Введите номер телефона'
            ]
        ])
        ->add('promoCode', TextType::class,[
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Введите код приглашения',
            ],
            'data' => $request->query->get('invite'),
        ])
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository = $this->getDoctrine()->getRepository('App:User');
            $user->setUsername($request->request->get('country') . preg_replace("/[^0-9]/", '', $user->getUsername()));
            $existUser = $userRepository->findByUsername($user->getUsername());

            if (!$existUser){
                $parentUser = $userRepository->findOneByPromoCode($user->getPromoCode());

                if (!empty($parentUser)){
                    $plainPassword = $userRepository->generatePassword();
                    $password = $passwordEncoder->encodePassword($user, $plainPassword);
                    $user->setPassword($password)
                        ->setRoles(['ROLE_USER']);
                    $user->setParent($parentUser);
                    $user->setPromoCode($userRepository->generatePromoCode());
                    $user->setShowInvestor(0);

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($user);
                    $entityManager->flush();

                    $userRepository = $this->getDoctrine()->getRepository('App:User');
                    $userRepository->sendPasswordBySMS(
                        preg_replace("/[^0-9]/", '', $user->getUsername()),
                        $plainPassword);

                    return $this->redirectToRoute('login', ['register'=>'success']);

                } else {
                    $this->addFlash(
                        'error',
                        'Пригласительный код не верный!'
                    );
                }
            } else {
                $this->addFlash(
                    'error',
                    'Такой пользователь уже существует!'
                );
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
            'countries' => $countries
        ]);
    }

    public function recoverPassword(Request $request, UserPasswordEncoderInterface $passwordEncoder, string $phone)
    {
        $userRepository = $this->getDoctrine()->getRepository('App:User');

        $user = $userRepository->findOneByUsername(preg_replace(
            "/[^0-9]/",
            '',
            $phone
        ));

        if ($user instanceof User){
            $plainPassword = $userRepository->generatePassword();
            $password = $passwordEncoder->encodePassword($user, $plainPassword);
            $user->setPassword($password);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->merge($user);
            $entityManager->flush();

            if ($userRepository->sendPasswordBySms($user->getUsername(), $plainPassword)){
                return new JsonResponse(['status'=>200, 'text'=>'Ваш новый пароль отправлен на указанный Вами мобильный телефон, теперь вы можете <a href="'.
                    $this->generateUrl('login').'">войти</a> в личный кабинет']);
            } else {
                return new JsonResponse(['status'=>500, 'text'=>'Отправить СМС не удалось']);
            }
        } else {
            return new JsonResponse(['status'=>404, 'text'=>'Номер телефона введен не верно']);
        }
    }
}
