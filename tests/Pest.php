<?php

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

beforeEach(function () {
    Http::preventStrayRequests();
});
