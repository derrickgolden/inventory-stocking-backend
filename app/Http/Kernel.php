<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthorizeMiddleware;
use App\Http\Middleware\FileUploader;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ValidateSignature;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\AuthenticateSession;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];
    protected $routeMiddleware = [
        // Other middleware definitions
        'permission' => AuthorizeMiddleware::class,
        'fileUploader' => FileUploader::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'user' => [
            ThrottleRequests::class . ':user',
            SubstituteBindings::class,

        ],
        'permission' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'role' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'role-permission' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'setting' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'account' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'transaction' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'designation' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'files' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'email-config' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'email' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'dashboard' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'product-category' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'product-sub-category' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'product-vat' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'product-brand' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'customer' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'product' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'counter' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'restock-counter' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'product-color' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'adjust-inventory' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'supplier' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'purchase-invoice' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'payment-purchase-invoice' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'return-purchase-invoice' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'sale-invoice' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'payment-sale-invoice' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'return-sale-invoice' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'product-image' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'report' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'reorder-quantity' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'coupon' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        'purchase-reorder-invoice' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        "page-size" => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        "quote"=>[
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
        "web"=>[
            ThrottleRequests::class,
            SubstituteBindings::class,
        ]

    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'auth.session' => AuthenticateSession::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'precognitive' => HandlePrecognitiveRequests::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
    ];
}
