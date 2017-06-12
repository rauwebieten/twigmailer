<?php
namespace RauweBieten\TwigMailer;


use Html2Text\Html2Text;
use Pelago\Emogrifier;
use Symfony\Component\DomCrawler\Crawler;
use Wa72\HtmlPageDom\HtmlPage;

class TwigMailer
{
    /**
     * @var \PHPMailer
     */
    private $phpMailer;

    /**
     * @return \PHPMailer
     */
    public function getPhpMailer()
    {
        return $this->phpMailer;
    }

    /**
     * @param \PHPMailer $phpMailer
     */
    public function setPhpMailer(\PHPMailer $phpMailer)
    {
        $this->phpMailer = $phpMailer;
    }

    /**
     * @var \Twig_Environment
     */
    private $twigEnvironment;

    /**
     * @return \Twig_Environment
     */
    public function getTwigEnvironment()
    {
        return $this->twigEnvironment;
    }

    /**
     * @param \Twig_Environment $twigEnvironment
     */
    public function setTwigEnvironment(\Twig_Environment $twigEnvironment)
    {
        $this->twigEnvironment = $twigEnvironment;
    }

    private $assetFolder;

    /**
     * @return mixed
     */
    public function getAssetFolder()
    {
        return $this->assetFolder;
    }

    /**
     * @param mixed $assetFolder
     * @throws \Exception
     */
    public function setAssetFolder($assetFolder)
    {
        if (!is_dir($assetFolder)) {
            throw new \Exception("Cannot set assetFolder: $assetFolder is not a valid directory");
        }
        $this->assetFolder = $assetFolder;
    }

    public function __construct(\PHPMailer $phpMailer, \Twig_Environment $twigEnvironment)
    {
        $this->setPhpMailer($phpMailer);
        $this->setTwigEnvironment($twigEnvironment);
    }

    public function create($template, $variables = [])
    {
        $this->phpMailer->clearAllRecipients();
        $this->phpMailer->clearAttachments();

        // get html from template
        $html = $this->twigEnvironment->render($template, $variables);

        // extract css links / subject
        $css = '';
        $page = new HtmlPage($html);
        $this->phpMailer->Subject = $page->getTitle();
        $me = $this;

        $links = $page->filter('link[rel="stylesheet"]');
        $links->each(function(Crawler $node) use($me, &$css) {
            $path = $me->assetFolder . '/' . $node->attr('href');
            $content = file_get_contents($path);
            $css.= "\n\n" . $content;
        });
        $links->remove();
        $html = $page->save();

        // make css inline
        $emogrifier = new Emogrifier($html, $css);
        $html = $emogrifier->emogrify();

        // set content
        $this->phpMailer->msgHTML($html,$this->assetFolder);

        // set alt content
        $this->phpMailer->AltBody = (new Html2Text($html))->getText();

        return $this->phpMailer;
    }

    public function send($email, $name = null)
    {
        $this->phpMailer->clearAllRecipients();
        $this->phpMailer->addAddress($email, $name);

        $res = $this->phpMailer->send();
        if (!$res) {
            throw new \Exception("PHPMailer::send failed: ".$this->phpMailer->ErrorInfo);
        }
    }

    public function createAndSend($email, $name = null, $template, $variables = [])
    {
        $this->create($template, $variables);
        $this->send($email, $name);
    }
}