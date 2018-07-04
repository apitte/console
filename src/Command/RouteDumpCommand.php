<?php declare(strict_types = 1);

namespace Apitte\Console\Command;

use Apitte\Core\Schema\Endpoint;
use Apitte\Core\Schema\EndpointParameter;
use Apitte\Core\Schema\Schema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class RouteDumpCommand extends Command
{

	public const TABLE_HEADER = ['Method', 'Path', 'Handler', 'Parameters'];

	/** @var Schema */
	private $schema;

	public function __construct(Schema $schema)
	{
		parent::__construct();

		$this->schema = $schema;
	}

	protected function configure(): void
	{
		$this->setName('apitte:route:dump');
		$this->setDescription('Lists all endpoints registered in application');
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		$endpoints = $this->schema->getEndpoints();

		if ($endpoints === []) {
			$output->writeln('No endpoints found');

			return;
		}

		$io = new SymfonyStyle($input, $output);

		$io->title('All registered endpoints');

		$table = new Table($output);
		$table->setHeaders(self::TABLE_HEADER);

		/** @var Endpoint[][] $endpointsByHandler */
		$endpointsByHandler = [];
		foreach ($endpoints as $endpoint) {
			$endpointsByHandler[$endpoint->getHandler()->getClass()][] = $endpoint;
		}

		foreach ($endpointsByHandler as $handler) {

			usort($handler, function (Endpoint $first, Endpoint $second) {
				return strlen((string) $first->getMask()) - strlen((string) $second->getMask());
			});

			foreach ($handler as $endpoint) {
				$table->addRow([
					implode('|', $endpoint->getMethods()),
					$endpoint->getMask(),
					sprintf(
						'%s::%s()',
						$endpoint->getHandler()->getClass(),
						$endpoint->getHandler()->getMethod()
					),
					$this->formatParameters($endpoint->getParameters()),
				]);
			}

			if ($handler !== end($endpointsByHandler)) {
				$table->addRow(new TableSeparator());
			}
		}

		$table->render();
	}

	/**
	 * @param EndpointParameter[] $parameters
	 */
	private function formatParameters(array $parameters): string
	{
		$params = array_map(function (EndpointParameter $parameter) {
			return sprintf('%s (%s)', $parameter->getName(), $parameter->getType());
		}, $parameters);

		return implode(', ', $params);
	}

}
