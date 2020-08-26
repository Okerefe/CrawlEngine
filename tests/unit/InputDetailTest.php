<?php declare(strict_types=1);
# -*- coding: utf-8 -*-
/*
 * This file is part of the CrawlEngine Package
 *
 * (c) DeRavenedWriter
 *
 */

namespace CrawlEngine;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @author  DeRavenedWriter <deravenedwriter@gmail.com>
 * @package CrawlEngine
 * @license http://opensource.org/licenses/MIT MIT
 */
class InputDetailTest extends TestCase
{

    /** @test */
    public function if_constructor_works_with_only_name()
    {
        $inputDetail = $this->getMockBuilder(InputDetail::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setAttributes'])
            ->getMock();

        $err = "InputDetail Constructor Fails when called with Just Name";

        $reflectedClass = new ReflectionClass(InputDetail::class);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($inputDetail, 'howdy');
        $this->assertSame('howdy', $inputDetail->name, $err);
    }

    /** @test */
    public function if_constructor_works_with_additional_input_string()
    {
        $err = "InputDetail Constructor Fails when called with Additional String";

        $inputDetail = $this->getMockBuilder(InputDetail::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setAttributes'])
            ->getMock();

        $inputDetail->expects($this->once())
            ->method('setAttributes');


        $inputString = '<input type="text" id="uid" name=\'hoedy\' class="form-control" placeholder="Some Howdy Stuffs" required=""  />';

        $reflectedClass = new ReflectionClass(InputDetail::class);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($inputDetail, 'howdy', 'values', $inputString);

        $this->assertSame('howdy', $inputDetail->name, $err);
        $this->assertSame('values', $inputDetail->value, $err);
        $this->assertStringContainsString('placeholder="Some Howdy Stuffs"', $inputDetail->inputString, $err);
    }

    /** @test */
    public function if_set_attributes_works_when_called_from_constructor()
    {
        $inputString = "<input type=\n'text' id='uid' name='howdy' class='form-control'\n placeholder='Some Howdy Stuffs' required=''  />";
        $err = "InputDetail Constructor Fails when called From Constructor";

        $inputDetail = new InputDetail('howdy', '', $inputString);
        $this->assertSame('howdy', $inputDetail->name, $err);
        $this->assertStringContainsString("placeholder='Some Howdy Stuffs'", $inputDetail->inputString, $err);
        $this->assertSame('Some Howdy Stuffs', $inputDetail->placeholder, $err);
        $this->assertSame('text', $inputDetail->type, $err);
    }

    /** @test */
    public function if_contains_input_returns_false()
    {
        $inputDetail = new InputDetail('name');
        $inputDetails = [
            new InputDetail('name'),
            new InputDetail('anothername'),
        ];
        $this->assertSame(true, InputDetail::containsInput($inputDetail, $inputDetails));
    }

    /** @test */
    public function if_contains_input_returns_true()
    {
        $inputDetail = new InputDetail('name');
        $inputDetails = [
            new InputDetail('nothere'),
            new InputDetail('anothername'),
        ];
        $this->assertSame(false, InputDetail::containsInput($inputDetail, $inputDetails));
    }
}
