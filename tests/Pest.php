<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Magicoli\TwoWayTicket\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
