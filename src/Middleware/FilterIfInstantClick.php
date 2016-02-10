<?php

namespace DiaaFares\InstantClick\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\DomCrawler\Crawler;

class FilterIfInstantClick
{
    /**
     * The DomCrawler instance.
     *
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$this->IsInstantClickRequest($request) || $response->isRedirection()) {
            return $response;
        }

        $this->filterResponse($response, $container = 'body');

//        $this->filterResponse($response, $request->header('X-InstantClick-Container'))
//            ->setUriHeader($response, $request)
//            ->setVersionHeader($response, $request);

        return $response;
    }

    /**
     * @param \Illuminate\Http\Response $response
     * @param string                    $container
     *
     * @return $this
     */
    protected function filterResponse(Response $response, $container)
    {
        $crawler = $this->getCrawler($response);

        $response->setContent(
            $this->makeTitle($crawler) .
            $this->fetchContainer($crawler, $container)
        );

        return $this;
    }

    /**
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     *
     * @return null|string
     */
    protected function makeTitle(Crawler $crawler)
    {
        $pageTitle = $crawler->filter('head > title');

        if (!$pageTitle->count()) {
            return;
        }

        return "<title>{$pageTitle->html()}</title>";
    }

    /**
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     * @param string                                $container
     *
     * @return string
     */
    protected function fetchContainer(Crawler $crawler, $container)
    {
        $content = $crawler->filter($container);

        if (!$content->count()) {
            abort(422);
        }

        return $content->html();
    }

    /**
     * Get the DomCrawler instance.
     *
     * @param \Illuminate\Http\Response $response
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getCrawler(Response $response)
    {
        if ($this->crawler) {
            return $this->crawler;
        }

        return $this->crawler = new Crawler($response->getContent());
    }


    /**
     * Determine if the request is the result of an PJAX call.
     *
     * @return bool
     */
    public function IsInstantClickRequest(Request $request)
    {
        return $request->headers->get('X-INSTANTCLICK') == true;
    }

//    /**
//     * @param \Illuminate\Http\Response $response
//     * @param \Illuminate\Http\Request  $request
//     *
//     * @return $this
//     */
//    protected function setUriHeader(Response $response, Request $request)
//    {
//        $response->header('X-PJAX-URL', $request->getRequestUri());
//
//        return $this;
//    }

//    /**
//     * @param \Illuminate\Http\Response $response
//     * @param \Illuminate\Http\Request  $request
//     *
//     * @return $this
//     */
//    protected function setVersionHeader(Response $response, Request $request)
//    {
//        $crawler = $this->getCrawler($response);
//        $node = $crawler->filter('head > meta[http-equiv]');
//
//        if ($node->count()) {
//            $response->header('X-PJAX-Version', $node->attr('content'));
//        }
//
//        return $this;
//    }


}