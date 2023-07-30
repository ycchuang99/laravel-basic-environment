<?php

namespace App\Log;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\API\Trace\Span;

class OpenTelemetryLogProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record)
    {
        $spanContext = Span::getCurrent()->getContext();

        $context = array_merge($record->context, [
            'trace_id' => $spanContext->getTraceId(), 'span_id' => $spanContext->getSpanId()
        ]);

        return $record->with(context: $context);
    }
}
