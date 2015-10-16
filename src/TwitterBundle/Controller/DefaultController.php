<?php

namespace TwitterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use TwitterBundle\Entity\Tweet;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('login_path');
        }

        /*$redis = $this->container->get('pdl.phpredis.service_name');
        $redis->set('key', 'value');
        echo $redis->get('key');*/

        // все твиты




        $user = $this->getUser();
        return array('user' => $user);
    }

    /**
     * @Route("/add")
     * @Template()
     */
    public function addAction(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('login_path');
        }

        $tweet = new Tweet();
        $tweet->setText('');

        $form = $this->createFormBuilder($tweet)
            ->add('text', 'text')
            ->add('save', 'submit', array('label' => 'Save'))
            ->getForm();

        $form->handleRequest($request);


        $key = 0;

        if ($form->isValid()) {


            $redis = $this->container->get('pdl.phpredis.twitter');

            //counter
            $lastId = $redis->hGet('counters', 'message');
            if (false == $lastId) {
                $lastId = 0;
            }

            $key = $lastId + 1;
            // запишем в Редис твит

            $user = $this->getUser();

            $data = $form->getData();
            $values = [
                'id' => $key,
                'text' => $data->getText(),
                'username' => $user->getUsername(),
            ];
            $redis->hMset('message:'.$key, $values);

            // обновим счетчик
            $redis->hSet('counters', 'message', $key);

            // поиск хештегов
            $regex = "/#+([a-zA-Z0-9_]+)/";
            preg_match_all($regex, $data->getText(), $matches);
            $hashTags = ($matches[1]);
            foreach ($hashTags as $hashTag) {
                // запишем в Редис єтот твит к хештегу
                $redis->sAdd('hashtag:'.$hashTag.':messages', $key);
            }

            return $this->redirectToRoute('homepage');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/hashtag/{hashtag}")
     * @Template()
     */
    public function hashtagAction($hashtag)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('login_path');
        }
        $redis = $this->container->get('pdl.phpredis.twitter');
        $res = $redis->sMembers('hashtag:'.$hashtag.':messages');
        $messages = [];

        $regex = "/#+([a-zA-Z0-9_]+)/";
        foreach ($res as $mesId) {
            $mesData = $redis->hMget('message:' . $mesId, ['id', 'text', 'username']);
            $mesData['text'] = preg_replace($regex, '<a href="/hashtag/$1">$0</a>', $mesData['text']);
            $messages[] = $mesData;
        }

        //smembers twitter.dev:hashtag:dfdf:messages
        return [
            'hashtag' => $hashtag,
            'messages' => $messages,
        ];
    }
}
