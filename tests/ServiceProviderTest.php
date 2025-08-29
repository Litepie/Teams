<?php

declare(strict_types=1);

use Litepie\Teams\TeamsServiceProvider;

it('can instantiate the service provider', function () {
    $app = new \Illuminate\Foundation\Application();
    $provider = new TeamsServiceProvider($app);
    
    expect($provider)->toBeInstanceOf(TeamsServiceProvider::class);
});

it('can register the service provider', function () {
    $app = new \Illuminate\Foundation\Application();
    $provider = new TeamsServiceProvider($app);
    
    // This should not throw an exception
    expect(fn() => $provider->register())->not->toThrow(Exception::class);
});
