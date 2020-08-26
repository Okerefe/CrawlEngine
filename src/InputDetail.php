<?php declare(strict_types=1);
# -*- coding: utf-8 -*-
/*
 * This file is part of the CrawlEngine Package
 *
 * (c) DeRavenedWriter
 *
 */

namespace CrawlEngine;


/**
 * This Class an Input Tag of a form Element
 *
 * @author  DeRavenedWriter <deravenedwriter@gmail.com>
 * @package CrawlEngine
 * @license http://opensource.org/licenses/MIT MIT
 */
class InputDetail
{

    /**
     * @var string Name value of Input Tag
     */
    public $name;

    /**
     * @var string Type value of Input Tag
     */
    public $type;


    /**
     * @var string Placeholder of InputTag
     */
    public $placeholder;

    /**
     * @var string Value of Input Tag
     */
    public $value;

    /**
     * @var string Entire Input String of InputTag
     */
    public $inputString;

    /**
     * @var string Regex used for finding Detecting InputTag
     */
    const REG_STRING = '/<input.+?name.*?=.*?["|\'](.*?)["|\'].+?>/smi';

    /**
     * Constructs the InputDetail Object
     *
     * @param string $name                 Name of the input tag
     * @param string $value                Value of the Input Tag
     * @param string $inputString          Entire Input String of the input tag
     *
     * @return void
     */
    public function __construct(string $name, string $value="", string $inputString ="")
    {
        $this->name = $name;
        $this->value = $value;

        //If inputString is set, we call the setAttribute method to populate other fields
        if($inputString != "") {
            $this->inputString = $inputString;
            $this->setAttributes();
        }
    }


    /**
     * Sets all needed attributes that are present.
     *
     * @return void
     */
    public function setAttributes()
    {
        $attrs = [
            'value',
            'type',
            'placeholder',
        ];

        foreach ($attrs as $attr) {
            //We Check to see if Attribute is present in inputString
            \preg_match("/{$attr}.*?=.*?[\"|'](.*?)[\"|']/smi", $this->inputString, $match);
            if(\count($match) > 1) {
                $this->$attr = $match[1];
            }
        }
    }

    /**
     * Checks to see if a given Input Detail is contained in an array of InputDetails
     *
     * @param InputDetail $needle          Input Detail
     * @param InputDetail[] $haystack      Array of Input Detail
     *
     * @return bool
     */
    public static function containsInput(InputDetail $needle, array $haystack) : bool
    {
        foreach ($haystack as $item) {
            if($needle->name == $item->name) {
                return true;
            }
        }
        return false;
    }


    /**
     * Returns a string representation of an InputDetail Instance
     *
     * @return string
     */
    public function __toString() : string
    {
        return "\n\nInput Detail:"
            ."\nName: " . $this->name
            ."\nValue: " . (\is_null($this->value) ? '' : $this->value)
            ."\nPlaceholder: " . (\is_null($this->placeholder) ? '' : $this->placeholder)
            ."\nType: " . (\is_null($this->type) ? '' : $this->type);
    }
}
