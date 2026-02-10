<?php

declare(strict_types=1);

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\LogRecord as MonologLogRecord;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Logs\LoggerInterface;
use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Logs\Severity;
use Psr\Log\LogLevel;

// create env vars before requiring composer
/**
 * @todo: make these configurable via env vars or config file
 */
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_METRICS_EXPORTER=none');
putenv('OTEL_LOGS_EXPORTER=otlp');
putenv('OTEL_LOGS_PROCESSOR=batch');
putenv('OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf');
putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost');

require __DIR__ . '/vendor.phar';

class OpenTelemetry {

	public static function write(string $message) {
		$name = self::getSystemName();
		putenv("OTEL_SERVICE_NAME=$name");
		//otel handler for Monolog v3
		$otelHandler = new class(LogLevel::INFO) extends AbstractProcessingHandler {

			private LoggerInterface $logger;

			/**
			 * @psalm-suppress ArgumentTypeCoercion
			 */
			public function __construct(string $level, bool $bubble = true, ?LoggerProviderInterface $provider = null) {
				parent::__construct($level, $bubble);
				$provider ??= Globals::loggerProvider();
				$this->logger = $provider->getLogger('monolog-demo', null, null, ['logging.library' => 'monolog']);
			}

			#[\Override]
			protected function write(MonologLogRecord $record): void {
				$this->logger->emit($this->convert($record));
			}

			private function convert(MonologLogRecord $record): LogRecord {
				return (new LogRecord($record['message']))
						->setSeverityText($record->level->toPsrLogLevel())
						->setTimestamp((int) (microtime(true) * (float) LogRecord::NANOS_PER_SECOND))
						->setObservedTimestamp((int) $record->datetime->format('U') * LogRecord::NANOS_PER_SECOND)
						->setSeverityNumber(Severity::fromPsr3($record->level->toPsrLogLevel()))
						->setAttributes($record->context + $record->extra);
			}
		};
		$tracer = Globals::tracerProvider()->getTracer('monolog-demo');
		//start a span so that logs contain span context
		$span = $tracer->spanBuilder('foo')->startSpan();
		$scope = $span->activate();

		$monolog = new Logger('otel-php-monolog', [$otelHandler]);

		$monolog->info($message, ['extra_one' => 'value_one']);

		$scope->detach();
		$span->end();
	}

	private static function getSystemName(): string {
		if (isset($GLOBALS['configDebug']['systemName'])) {
			return $GLOBALS['configDebug']['systemName'];
		}
		if (defined('_SYSNAME')) {
			return _SYSNAME;
		}
		return 'Desconhecido';
	}
}
