<?php

namespace TwitterBundle\Entity;

class Tweet
{
    protected $text;

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

}