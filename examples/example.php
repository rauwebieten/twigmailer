<?php

use RauweBieten\TwigMailer\TwigMailer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once '../vendor/autoload.php';

// create a Twig instance
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader);

// create a PHPMailer instance
$phpMailer = new \PHPMailer();
$phpMailer->Mailer = 'mail';
$phpMailer->setFrom('me@example.com', 'Me');

// create the TwigMailer
$mailer = new TwigMailer($phpMailer, $twig);

// specify where the assets (css, images) can be found
$mailer->setAssetFolder(__DIR__ . '/assets');

// create the body from a template with variables
$mailer->create('welcome.html.twig', [
    'name' => 'John Doe'
]);

// send!
$mailer->send('someone@example.com');