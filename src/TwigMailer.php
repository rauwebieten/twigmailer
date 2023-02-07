<?php

namespace RauweBieten\TwigMailer;


use Html2Text\Html2Text;
use Pelago\Emogrifier\CssInliner;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\CssSelector\Exception\ParseException;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Wa72\HtmlPageDom\HtmlPage;

class TwigMailer
{
    /**
     * @var PHPMailer
     */
    private $phpMailer;
    /**
     * @var Environment
     */
    private $twigEnvironment;
    private $assetFolder;

    public function __construct(PHPMailer $phpMailer, Environment $twigEnvironment)
    {
        $this->setPhpMailer($phpMailer);
        $this->setTwigEnvironment($twigEnvironment);
    }

    /**
     * @return PHPMailer
     */
    public function getPhpMailer(): PHPMailer
    {
        return $this->phpMailer;
    }

    /**
     * @param PHPMailer $phpMailer
     */
    public function setPhpMailer(PHPMailer $phpMailer)
    {
        $this->phpMailer = $phpMailer;
    }

    /**
     * @return Environment
     */
    public function getTwigEnvironment(): Environment
    {
        return $this->twigEnvironment;
    }

    /**
     * @param Environment $twigEnvironment
     */
    public function setTwigEnvironment(Environment $twigEnvironment)
    {
        $this->twigEnvironment = $twigEnvironment;
    }

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

    /**
     * @throws Exception
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws ParseException
     */
    public function create($template, $variables = []): PHPMailer
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
        $links->each(function (Crawler $node) use ($me, &$css) {
            $path = $me->assetFolder . '/' . $node->attr('href');
            $content = file_get_contents($path);
            $css .= "\n\n" . $content;
        });
        $links->remove();
        $html = $page->save();

        // make css inline
        $html = CssInliner::fromHtml($html)->inlineCss($css)->render();

        // set content
        $this->phpMailer->msgHTML($html, $this->assetFolder);

        // set alt content
        $this->phpMailer->AltBody = (new Html2Text($html))->getText();

        return $this->phpMailer;
    }

    /**
     * @throws Exception
     */
    public function send()
    {
        $res = $this->phpMailer->send();
        if (!$res) {
            throw new \Exception("PHPMailer::send failed: " . $this->phpMailer->ErrorInfo);
        }
    }
}