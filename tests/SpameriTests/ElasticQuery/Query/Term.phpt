<?php declare(strict_types = 1);

namespace SpameriTests\ElasticQuery\Query;

require_once __DIR__ . '/../../bootstrap.php';


class Term extends \Tester\TestCase
{

	private const SPAMERI_VIDEO = 'spameri_test_video';


	public function testCreate() : void
	{
		$term = new \Spameri\ElasticQuery\Query\Term(
			'name',
			'Avengers',
			1.0
		);

		$array = $term->toArray();

		\Tester\Assert::true(isset($array['term']['name']['value']));
		\Tester\Assert::same('Avengers', $array['term']['name']['value']);
		\Tester\Assert::same(1.0, $array['term']['name']['boost']);

		$document = new \Spameri\ElasticQuery\Document(
			self::SPAMERI_VIDEO,
			new \Spameri\ElasticQuery\Document\Body\Plain(
				(
				new \Spameri\ElasticQuery\ElasticQuery(
					new \Spameri\ElasticQuery\Query\QueryCollection(
						new \Spameri\ElasticQuery\Query\MustCollection(
							$term
						)
					)
				)
				)->toArray()
			),
			self::SPAMERI_VIDEO
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'localhost:9200/' . $document->index() . '_search');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt(
			$ch, CURLOPT_POSTFIELDS,
			\json_encode($document->toArray()['body'])
		);

		\Tester\Assert::noError(static function () use ($ch) {
			$response = curl_exec($ch);
			$resultMapper = new \Spameri\ElasticQuery\Response\ResultMapper();
			/** @var \Spameri\ElasticQuery\Response\ResultSearch $result */
			$result = $resultMapper->map(\json_decode($response, TRUE));
			\Tester\Assert::type('int', $result->stats()->total());
		});

		curl_close($ch);
	}

}

(new Term())->run();
