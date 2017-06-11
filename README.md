# TwigMailer

Send mails with PHPMailer and a Twig template.

- Uses a PHPMailer instance for sending the mail
- Uses a Twig Environment to render templates
- Creates a text version from the HTML version
- If linked CSS files are found in the HTML, they are loaded and converted to inline styles
- Sets the subject from the HTML title tag

# Installation

Install with composer/packagist

`
composer require rauwebieten/twigmailer
`

## Basic usage

Make sure you have a configured PHPMailer instance. 
Check the PHPMailer documentation for details.

`
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');  
$twig = new \Twig\Environment($loader);
`

Make sure you have a configured Twig Environment instance.
Check the Twig documentation for details.

`
$phpMailer = new \PHPMailer();  
$phpMailer->Mailer = 'mail';  
$phpMailer->setFrom('me@example.com', 'Me');
`

Create a TwigMailer instance

`
$mailer = new \RauweBieten\TwigMailer\TwigMailer($phpMailer, $twig);
`

Create content from the template

`
$mailer->create('some-template.html.twig', [  
    'some-variable' => 'Some value'  
]);
`

And send the mail

`
$mailer->send('someone@example.com', 'Someone');
`



