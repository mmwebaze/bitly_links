<?php

namespace Drupal\bitly_links\Service;


interface BitlyLinksServiceInterface
{
    public function getAccessToken($code);
    public function shorten($longUrl);
}