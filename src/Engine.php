<?php declare(strict_types=1);
# -*- coding: utf-8 -*-
/*
 * This file is part of the CrawlEngine Package
 *
 * (c) DeRavenedWriter
 *
 */

namespace CrawlEngine;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * This Class Is the Core Package Class that Handles Majority Of tasks
 *
 * @author  DeRavenedWriter <deravenedwriter@gmail.com>
 * @package CrawlEngine
 * @license http://opensource.org/licenses/MIT MIT
 */
class Engine
{
    /**
     * @var int Default request timeout
     */
    public $timeOut;



    /**
     * Sets the Default Timeout of the Engine if given
     *
     * @param int $timeOut
     *
     * @return void
     */
    public function __construct(int $timeOut = 10)
    {
        $this->timeOut = $timeOut;
    }

    /**
     * Returns an Instance of the InputDetail Class
     *
     * @param string $name                 Name of the input tag
     * @param string $value                Value of the Input Tag
     * @param string $inputString          Entire Input String of the input tag
     *
     * @return InputDetail
     */
    public function inputDetail(string $name, string $value ="", string $inputString ="") : InputDetail
    {
        return new InputDetail($name,  $value, $inputString);
    }

    /**
     * Extract all Login Fields of a page from the page Data
     *
     * Accepts The String content of the page
     * And an index of which form to extract
     * The default index is 1
     * If there are more than one form on the page,
     * The Index is used to determine which form details to fetch,
     *
     * If there are no form tags, It throws an exception.
     * If there are form tags but no input tag contained in it,
     * It returns an empty array..
     *
     * @param string $pageContent          String containing page content
     * @param int $index                   Index of the form on the page to be fetched
     *
     * @throws CrawlEngineException
     * @return InputDetail[]
     */
    public function formInputs(string $pageContent, $index = 1 ) : array
    {
        $fields = [];
        \preg_match_all("/<form.+?>(.+?)<\/form>/smi", $pageContent, $formMatch);
        if(count($formMatch) < 1) {
            throw new CrawlEngineException('No Form found in Given Page');
        }
        if(\is_null($formMatch[0][(--$index)])){
            throw new CrawlEngineException('Form Index Not Found');
        }

        //\var_dump($match);
        //die();
        \preg_match_all(InputDetail::REG_STRING, $formMatch[0][($index)], $matches);

        for($i=0; $i < \count($matches[0]); $i++) {
            $fields[] = $this->inputDetail($matches[1][$i],"", $matches[0][$i]);
        }
        return $fields;
    }

    /**
     * Returns Headers to be used for HTTP Requests.
     *
     * When no parameter is set, it adds a default referer and
     * leaves the content length blank
     * But When they are set it assigns them both.
     *
     * @param int $contentLength   The value being encoded
     * @param string $referer      JSON encode option bitmask
     *
     * @return array
     */
    public function headers( int $contentLength = 0, string $referer = "http://www.google.com/bot.html") : array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.8) Gecko/20100101 Firefox/78.0',
            'Origin' => 'http://www.google.com/bot.html',
            'Referer' => $referer,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ] + (($contentLength > 0) ? ['Content-Length' => "{$contentLength}"] : []);
    }



    /**
     * Returns Guzzle Client to be used for HTTP Requests.
     *
     * @return Client
     */
    public function client() : Client
    {
        return new Client([
            'timeout'  => $this->timeOut,
            'cookies' => true
        ]);

    }

    /**
     * Returns HTML Content of a Page given the uri..
     *
     * @param string|UriInterface $uri     URI object or string.
     *
     * @throws CrawlEngineException
     * @return string
     */
    public function pageContent($uri) : string
    {
        try {
            return (string)($this->client()->request(
                'GET',
                $uri,
                [
                    'headers' => $this->headers(),
                ]
            ))->getBody();
        } catch (GuzzleException $e) {
            //Some Pages Do not return a 200 Level Error but yet displays the requested page
            //So If there is an error, we check if the page has a body and use that body as our response
            //If not, we throw an Error..
            if(!\method_exists($e, 'getResponse') || \is_null($e->getResponse())) {
                throw new CrawlEngineException($e->getMessage());
            }
            return $e->getResponse()->getBody()->getContents();
        }
    }


    /**
     * Returns Login Fields of a page Containing a form
     *
     * Accepts a Uri and the index of the form..
     * The default index is 1
     * If there are more than one form on the page,
     * The Index is used to determine which form details to fetch,
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param int $index                   Index of the form on the page to be fetched
     *
     * @throws CrawlEngineException
     * @return InputDetail[]
     */
    public function getLoginFields($uri, $index = 1) : array
    {
        return $this->formInputs($this->pageContent($uri), $index);
    }

    /**
     * Checks if an Input Detail with a particular name is contained in a list of InputDetails
     *
     * @param InputDetail $needle     Needle to be searched for
     * @param array $haystack         Haystack in whic to fetch from
     *
     * @return bool
     */
    public function containsInput(InputDetail $needle, array $haystack) :bool
    {
        return InputDetail::containsInput($needle,$haystack);
    }

    /**
     * Returns Input to be used to make request
     *
     *
     * Analyses User Defined Inputs and prefilled inputs that have been found
     * In the Page
     * And determines which should be used
     * User defined Inputs will override Pre-filled Inputs found on page
     *
     * @param InputDetail[] $prefilledInputs           Inputs pre-filled in form page
     * @param InputDetail[] $userDefinedInputs         Inputs Defined by User
     *
     * @return InputDetail[]
     */
    public function getQualifiedInputs(array $prefilledInputs, array $userDefinedInputs) : array
    {
        $qualifiedPreInputs = [];

        foreach ($prefilledInputs as $prefilled) {
            if(!$this->containsInput($prefilled, $userDefinedInputs)) {
                $qualifiedPreInputs[] = $prefilled;
            }
        }
        return array_merge($qualifiedPreInputs, $userDefinedInputs);
    }


    /**
     * Changes array of Input Details to assoc array
     *
     * @param InputDetail[] $inputs      Array of Inputs to convert to assoc array
     *
     * @return array
     */
    public function inputsToAssocArray(array $inputs) : array
    {
        $assoc = [];
        foreach ($inputs as $input) {
            $assoc[$input->name] = $input->value;
        }
        return $assoc;
    }

    /**
     * Extract Inputs with pre-filled value from a HTML page
     *
     * Accepts the HTML content and an index to specify which form on the page to access
     *
     * @param string $pageContent      Contains HTML of page content
     * @param int $index               Index of form from which to extract Input
     *
     * @throws CrawlEngineException     When no form is found on page
     * @return InputDetail[]
     */
    public function preFilledInputs(string $pageContent, int $index = 1) : array
    {
        $allInputs = $this->formInputs($pageContent, $index);
        $preFilledInputs = [];
        foreach ($allInputs as $input){
            if($input->value != ""){
                $preFilledInputs[] = $input;
            }
        }
        return $preFilledInputs;
    }



    /**
     * Returns an assoc array of form data to be submitted
     *
     * Given the Uri of the Form and user defined inputs
     * It extracts pre-filled inputs and merges with user defined inputs
     *
     * @param Client $client                   Guzzle client to be used for request
     * @param string|UriInterface $formUri     Uri of Form Data
     * @param InputDetail[] $userDefinedInputs         Inputs already defined by User
     * @param int $index                       Index of Form to be parsed
     *
     * @throws CrawlEngineException            When no form is found on page or HTTP Error in making Guzzle request
     * @return Crawler[]
     */
    public function resolveFormData(Client &$client, $formUri, array $userDefinedInputs, int $index=1) : array
    {
        try {
            $formPage = (string)($client->request(
                'GET',
                $formUri,
                [
                    'headers' => $this->headers(),
                ]
            ))->getBody();
        } catch (GuzzleException $e) {

            //Some Pages Do not return a 200 Level Error but yet displays the requested page
            //So If there is an error, we check if the page has a body and use that body as our response
            //If not, we throw an Error
            if(!\method_exists($e, 'getResponse') || \is_null($e->getResponse())) {
                throw new CrawlEngineException("Request Failed in Getting Form Page as Follows:"
                    . $e->getMessage());
            }

            $formPage = $e->getResponse()->getBody()->getContents();
        }

        $preFilledInputs =$this->preFilledInputs($formPage, $index);
        return $this->inputsToAssocArray($this->getQualifiedInputs($preFilledInputs, $userDefinedInputs));
    }


    /**
     * Returns the form Length of a given form array Data
     *
     * Given an assoc array, it Builds it into a query string and returns it's size
     *
     * @param array $formData                   Array of Form Data
     * @return int
     */
    public function formLength(array $formData) : int
    {
        return (int)\strlen(\http_build_query($formData));
    }



    /**
     * Returns the form Length of a given form array Data
     *
     * Given an assoc array, it Builds it into a query string and returns it's size
     * @param string|UriInterface $formPageUri              Uri of Form Page
     * @param string|UriInterface $submitUri                Uri to Submit Form Data
     * @param InputDetail[] $formDetails                    Array of Pre-filled Input Details
     * @param string[]|UriInterface[] $contentPagesUri      Array containing different urls which pages should be gotten
     * @param int $index                                    Index of Form to be parsed on formPage (default = 1)
     *
     * @throws CrawlEngineException                 Thrown When anything goes wrong in the chain of Events..
     * @return Crawler[]
     */
    public function resolveRequest($formPageUri, $submitUri, array $formDetails, array $contentPagesUri, int $index=1) : array
    {
        try {
            $client = $this->client();
            $formData = $this->resolveFormData($client, $formPageUri, $formDetails, $index);
            $client->request(
                'POST',
                $submitUri,
                [
                    'headers' => $this->headers(),
                    'form_params' => $formData,
                ]
            );
            return $this->getAllCrawlers($client, $submitUri, $contentPagesUri);
        } catch (CrawlEngineException $e){
            throw $e;
        } catch (GuzzleException $e){
            throw new CrawlEngineException("Request Failed in Submitting Form as Follows:"
                . $e->getMessage());
        }
    }


    /**
     * Returns Crawler Object of page content given
     *
     * @param string           Content of Web Page
     *
     * @return Crawler
     */
    public function crawler($pageContent) : Crawler
    {
        return new Crawler($pageContent);
    }

    /**
     * Returns an array of crawlers for each given page
     *
     * Given an array of Pages Url and a Guzzle client
     * it returns crawler object of the given object
     *
     * @param Client $client                               Array of Pre-filled Input Details
     * @param string[]|UriInterface[] $pagesUrl           Array of Uri to Submit Form Data
     *
     * @throws CrawlEngineException               Thrown When Request to given page is not successful
     * @return Crawler[]
     */
    public function getAllCrawlers(Client &$client, $submitUri, array $pagesUrl) : array
    {
        $crawlers = [];
        foreach ($pagesUrl as $pageUrl) {
            try {
                $pageContent = (string)($client->request(
                    'GET',
                    $pageUrl,
                    [
                        'headers' => $this->headers(0, $submitUri),
                    ]
                ))->getBody();
                $crawlers[] = $this->crawler($pageContent);

            } catch (GuzzleException $e)  {
                throw new CrawlEngineException("Request Failed in Getting Details From {$pageUrl} as Follows:"
                    . $e->getMessage());
            }
        }
        return $crawlers;
    }
}
