<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use OpenTelemetry\API\Baggage\Baggage;
use OpenTelemetry\API\Baggage\Propagation\BaggagePropagator;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagator;
use OpenTelemetry\Contrib\Zipkin\Exporter as ZipkinExporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Metrics\MeterProviderFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;


define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

putenv('OTEL_PHP_INTERNAL_METRICS_ENABLED=true');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_SERVICE_NAME=laravel-basic');
putenv('OTEL_TRACES_EXPORTER=zipkin');
putenv('OTEL_METRICS_EXPORTER=otlp');
putenv('OTEL_EXPORTER_ZIPKIN_ENDPOINT=http://192.168.0.18:9411/api/v2/spans');
putenv('OTEL_EXPORTER_OTLP_METRICS_ENDPOINT=http://192.168.0.18:4318/v1/metrics');
putenv('OTEL_PROPAGATORS=baggage,tracecontext');

require __DIR__ . '/../vendor/autoload.php';

    /*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/
    $meterFactory = new MeterProviderFactory();
    $meterProvider = $meterFactory->create();
    $meter = $meterProvider->getMeter('demo_meter');
    $histogram = $meter->createHistogram('roll', 'num', 'The output of roll result');
    $histogram->record(1);
    $meterProvider->shutdown();

    $app = require_once __DIR__ . '/../bootstrap/app.php';

    $kernel = $app->make(Kernel::class);

    $response = $kernel->handle(
        $request = Request::capture()
    )->send();

    $kernel->terminate($request, $response);
