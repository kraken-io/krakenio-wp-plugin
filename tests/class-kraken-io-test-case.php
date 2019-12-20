<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class Kraken_IO_Test_Case extends TestCase {

	protected function setUp() {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown() {
		Monkey\tearDown();
		parent::tearDown();
	}
}