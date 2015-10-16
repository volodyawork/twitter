<?php

namespace VG\UserBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContextInterface;
use VG\UserBundle\Entity\User;


class SecurityController extends Controller{

    public function loginAction(Request $request)
    {
        //$em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(
                SecurityContextInterface::AUTHENTICATION_ERROR
            );
        } else {
            $error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR);
            $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
        }

        return $this->render(
            'VGUserBundle:Security:login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $session->get(SecurityContextInterface::LAST_USERNAME),
                'error'         => $error
            )
        );
    }

    public function registrationAction(Request $request)
    {


        $error_message="";
        $roles = array('ROLE_USER');
        $user = new User($roles);
        $form = $this->createFormBuilder($user)
            ->add('password', 'password')
            ->add('email', 'email')
            ->add('register', 'submit')
            ->getForm();
        $form->handleRequest($request);


        // TODO if password empty - generate password
        $em = $this->getDoctrine()->getManager();
        if($form->isValid()){ // TODO валидация на уникальность емейла
            $redis = $this->container->get('pdl.phpredis.twitter');

            $email = $form['email']->getData();

            $salt = md5(time());
            $encoder = new MessageDigestPasswordEncoder('sha512', true, 10);
            $password = $encoder->encodePassword($form['password']->getData(), $user->getSalt());
            $data = [
                'salt' => $salt,
                'password' => $password,
                'roles' => $user->getRoles(),
            ];
            $redis->hMset('user:'.$email, $data);


/*            $user->setEmail($form['email']->getData());
            $user->setSalt(md5(time()));
            $encoder = new MessageDigestPasswordEncoder('sha512', true, 10);
            $password = $encoder->encodePassword($form['password']->getData(), $user->getSalt());
            $user->setPassword($password);

            $em->persist($user);
            $em->flush();*/


            // send email with login-password
/*            $site_mail = $this->container->getParameter('admin_email');
            $message = \Swift_Message::newInstance()
                ->setSubject('Регистрация')
                ->setFrom($site_mail)
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        'VGUserBundle:Security:register.email.txt.twig',
                        array('login' => $user->getEmail(), 'password'=>$form['password']->getData())
                    )
                )
            ;
            $this->get('mailer')->send($message);*/



            return $this->redirect($this->generateUrl('login_path'));
        }

        return $this->render(
            'VGUserBundle:Security:registration.html.twig',array(
                'form'=>$form->createView(),
                'error_message'=>$error_message
            )

        );
    }

    public function securityCheckAction()
    {
        // The security layer will intercept this request
    }

    public function logoutAction()
    {
        // The security layer will intercept this request
    }
}