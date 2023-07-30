<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenTelemetry\Contrib\Zipkin\Exporter as ZipkinExporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\API\Metrics\ObserverInterface;

class OpenTelemetry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tracer = (new TracerProvider(
            [
                new SimpleSpanProcessor(
                    new ZipkinExporter(
                        PsrTransportFactory::discover()->create('http://192.168.0.14:9411/api/v2/spans', 'application/json')
                    ),
                ),
            ],
            new AlwaysOnSampler(),
        ))->getTracer('io.opentelemetry.contrib.php');


        $reader = new ExportingReader(new ConsoleMetricExporter(Temporality::DELTA));

        $meter = MeterProvider::builder()
            ->addReader($reader)
            ->build();

        $meter
            ->getMeter('demo_meter')
            ->createObservableGauge('number', 'items', 'Random number')
            ->observe(static function (ObserverInterface $observer): void {
                $observer->observe(random_int(0, 256));
            });

        //metrics are collected every time `collect()` is called
        $reader->collect();

        $span = $tracer->spanBuilder('root')->startSpan();
        $spanScope = $span->activate();

        $span->setAttribute('http.method', $request->method());
        $span->setAttribute('http.path', $request->getPathInfo());

        $response = $next($request);

        $span->setAttribute('http.status', $response->getStatusCode());

        $spanScope->detach();
        $span->end();

        return $response;
    }
}
