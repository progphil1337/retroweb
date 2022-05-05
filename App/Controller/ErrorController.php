<?php

namespace App\Controller;

use App\Model\Error\ErrorFactory;
use App\Model\News\NewsFactory;
use App\Widget\News\SliderWidget;
use FastRoute\Dispatcher;
use System\App\View\Template;
use System\ErrorTypes;
use System\HTTP\IRoutable;
use System\HTTP\Request\Request;
use System\HTTP\Request\RequestType;
use System\HTTP\Route;
use System\Navigation\Point;

class ErrorController extends WebsiteController implements IRoutable
{

    /**
     * @var $_communityTab Point
     */
    private $_communityTab;

    public function onRegistration(): void
    {

        parent::onRegistration(); // TODO: Change the autogenerated stub

        $this->_communityTab = $this->getNavigation()->getById("Community");
    }

    /**
     * Returns the routes of the Controller
     * @return array
     */
    public function getRoutes(): array
    {

        return [
            new Route(RequestType::GET, '/error', '404'),
            new Route(RequestType::GET, '/error/500', '500'),
            new Route(RequestType::GET, '/error/id/{id:\d+}', '500-with-id')
        ];
    }

    /**
     * @param string $title
     * @param array $box
     * @param bool $showNews
     */
    private function displayAction(string $title, array $box, bool $showNews = true): void
    {

        $this->setPageTitle($title);

        /**
         * @var $template Template
         */
        $template = $this->getView()->createTemplate("error/Error.tpl.php");

        if ($showNews) {
            /**
             * @var $newsSliderWidget SliderWidget
             */
            $newsSliderWidget = $this->getWidget(SliderWidget::class);

            /**
             * @var $newsFactory NewsFactory
             */
            $newsFactory = $this->getFactoryManager()->get(NewsFactory::class);

            $newsSliderWidget->setNewsFactory($newsFactory);
            $newsSliderWidget->setGrid(6);

            $template->newsSliderWidget = $newsSliderWidget;
        }
        $template->title = $title;

        $image = [
            'float' => isset($box['image']['float']) ? $box['image']['float'] : 'right',
            'src' => isset($box['image']['src']) ? $box['image']['src'] : $this->getApp()
                    ->getConfig()
                    ->get("site", "url") . 'public/images/frank_lost_connection.png',
            'hide' => isset($box['image']['hide']) ? $box['image']['hide'] : false
        ];

        $button = [
            'color' => isset($box['button']['color']) ? $box['button']['color'] : 'green',
            'url' => isset($box['button']['url']) ? $box['button']['url'] : $this->getApp()
                    ->getConfig()
                    ->get("site", "url") . "community",
            'text' => isset($box['button']['text']) ? $box['button']['text'] : 'In die Community',
            'hide' => isset($box['button']['hide']) ? $box['button']['hide'] : false
        ];

        $template->box = [
            'title' => isset($box['title']) ? $box['title'] : $title,
            'text' => isset($box['text']) ? $box['text'] : "Da ist wohl etwas schiefgelaufen!",
            'image' => $image,
            'button' => $button
        ];

        $this->display($template);
    }

    public function methodNotAllowedAction(): void
    {

        $this->displayAction("Zugriff verweigert!", [
            "title" => "Oops!",
            "text" => "Du hast keinen Zugriff auf diese Seite.<br /><br />Dies kann daran liegen, dass dein Web-Browser diese Seite falsch angefragt hat.",
            "button" => [
                "text" => "Zur&uuml;ck in die Community"
            ]
        ]);
    }

    private function notFoundAction(): void
    {

        $this->displayAction("404 - Seite nicht gefunden", [
            "title" => "Oops!",
            "text" => "Die von dir aufgerufene Seite konnte nicht gefunden werden.<br />Stelle sicher, dass du dich bei der eingegebenen Url nicht verschrieben hast.",
            "button" => [
                "text" => "Zur&uuml;ck in die Community"
            ]
        ]);
    }

    private function error500Action(): void
    {

        $this->displayAction("500 - Irgendetwas ist schiefgelaufen!", [
            "title" => "Oops!",
            "text" => "W&auml;hrend der Verarbeitung deiner Anfrage ist etwas schiefgelaufen.<br />Informiere die Administration &uuml;ber diesen Unfall, sofern sie noch nicht dar&uuml;ber Informiert ist.",
            "button" => [
                "text" => "Zur&uuml;ck in die Community"
            ]
        ]);
    }

    private function error500ActionWithId(int $id): void
    {

        $errorFactory = $this->getFactoryManager()->get(ErrorFactory::class);
        $error = $errorFactory->getById($id);

        if ($error == null) {
            $this->redirect("error/500");
        } else {

            $info = $error->get("type") == ErrorTypes::ERROR ? json_encode(json_decode($error->get("info")), JSON_PRETTY_PRINT) : $error->get("info");

            $this->displayAction("500 - Irgendetwas ist schiefgelaufen!", [
                "title" => "Oops!",
                "text" => "<b>Fehler:</b> " . $error->get("message") . "<br /><b>Datei: </b> " . $error->get("file") . "<br /><b>Zeile:</b> " . $error->get("line") . "<br /><b>URL:</b> " . $error->get("url") . "<br /><b>Uhrzeit: </b>" . date('d.m.Y H:i:s', $error->get("timestamp")) . " Uhr" . "<br /><b>Info:</b> <pre>" . $info . "</pre>",
                "button" => [
                    "text" => "Zur&uuml;ck zu " . $error->get("url"),
                    "color" => "red",
                    "url" => substr($this->getApp()->getConfig()->get("site", "url"), 0, -1) . $error->get("url")
                ]
            ], false);
        }
    }

    public function onRequest(Request $request, Route $route, array $vars): void
    {

        $this->_communityTab->setActive(true);

        if ($route->getHandler() == '500') {
            $this->error500Action();
        } else {
            if ($route->getHandler() == '500-with-id') {
                $this->error500ActionWithId($vars["id"]);
            } else {
                switch ($vars['type']) {
                    case Dispatcher::NOT_FOUND:
                        $this->notFoundAction();
                        break;

                    case Dispatcher::METHOD_NOT_ALLOWED:
                        $this->methodNotAllowedAction();
                        break;

                }
            }
        }
    }
}