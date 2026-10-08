<?php

namespace App\Service;

use Aspera\Spreadsheet\XLSX\Reader;

class ImportService
{
	private string $filePath;
	public function __construct(private \PDO $pdo) {
		$this->filePath = dirname(__DIR__, 2) . '/import/import.xlsx';
	}

	public function import(): void
	{
		echo "Start import service"."<br/>";

		$startTime = microtime(true);

		$this->loadDictionaries();
		$this->loadLeads();

		$executionTime = microtime(true) - $startTime;

		echo '<br/>' . "=== Final pass ===" . '<br/>';
		echo 'Execution time: ' . round($executionTime, 3) . ' sec' . '<br/>';
	}


	private function loadLeads(): void
	{
		echo '<br/>' . "=== Second pass: leads ===" . '<br/>';

		$totalStart = microtime(true);

		$cityMap = $this->getDictionaryMap('cities');
		$sourceMap = $this->getDictionaryMap('sources');
		$productMap = $this->getDictionaryMap('products');
		$statusMap = $this->getDictionaryMap('statuses');
		$managerMap = $this->getDictionaryMap('users');

		$start = microtime(true);

		$reader = new Reader();
		$reader->open($this->filePath);

		echo 'Reader open: '
			. round(microtime(true) - $start, 3)
			. " sec" . '<br/>';

		$batchSize = 1000;
		$rows = [];

		$rowNumber = 0;

		$start = microtime(true);

		foreach ($reader as $row) {
			$rowNumber++;

			if ($rowNumber === 1) {
				continue;
			}

			$source = trim(
				(string) ($row[7] ?? '')
			);

			$product = trim(
				(string) ($row[9] ?? '')
			);

			$status = trim(
				(string) ($row[11] ?? '')
			);

			$manager = trim(
				(string) ($row[12] ?? '')
			);

			$rows[] = [
				'external_id' => trim(
					(string) ($row[0] ?? '')
				),
				'email' => trim((string) ($row[5] ?? '')),
				'first_name' => trim((string) ($row[2] ?? '')),
				'last_name' => trim((string) ($row[3] ?? '')),
				'phone' => trim((string) ($row[4] ?? '')),
				'city_id' => $cityMap[trim((string) ($row[6] ?? ''))] ?? null,
				'created_at' => $this->parseDate(
					$row[1] ?? null
				),
				'source_id' => $sourceMap[$source] ?? null,
				'product_id' => $productMap[$product] ?? null,
				'status_id' => $statusMap[$status] ?? null,
				'manager_id' => $managerMap[$manager] ?? null,
				'utm_campaign' => trim(
					(string) ($row[8] ?? '')
				) ?: null,
				'budget_uah' => $this->parseBudget(
					$row[10] ?? null
				),
				'next_contact_at' => $this->parseDate(
					$row[14] ?? null
				),
				'comment' => trim(
					(string) ($row[13] ?? '')
				) ?: null,
			];

			if (count($rows) >= $batchSize) {
				$this->insertLeads($rows);

				$rows = [];
			}
		}

		if (!empty($rows)) {
			$this->insertLeads($rows);
		}

		$reader->close();

		echo 'Excel processing: '
			. round(microtime(true) - $start, 3)
			. " sec" . '<br/>';

		echo 'Rows: ' . ($rowNumber - 1) . '<br/>';

		echo 'Total second pass: '
			. round(microtime(true) - $totalStart, 3)
			. " sec" . '<br/>';

		echo 'Peak memory: '
			. round(memory_get_peak_usage(true) / 1024 / 1024, 2)
			. " MB" . '<br/>';
	}

	private function insertLeads(array $leads): void
	{
		if (empty($leads)) {
			return;
		}

		$values = [];
		$params = [];
		$index = 0;

		foreach ($leads as $lead) {
			$values[] = "(
			   :external_id_$index,
			   :created_at_$index,
			   :first_name_$index,
			   :last_name_$index,
			   :phone_$index,
			   :email_$index,
			   :city_id_$index,
			   :source_id_$index,
			   :product_id_$index,
			   :status_id_$index,
			   :manager_id_$index,
			   :utm_campaign_$index,
			   :budget_uah_$index,
			   :comment_$index,
			   :next_contact_at_$index
			)";

			$params["external_id_$index"] = $lead['external_id'];
			$params["first_name_$index"] = $lead['first_name'];
			$params["last_name_$index"] = $lead['last_name'];
			$params["phone_$index"] = $lead['phone'];
			$params["email_$index"] = $lead['email'];
			$params["city_id_$index"] = $lead['city_id'];
			$params["created_at_$index"] = $lead['created_at'];
			$params["source_id_$index"] = $lead['source_id'];
			$params["product_id_$index"] = $lead['product_id'];
			$params["status_id_$index"] = $lead['status_id'];
			$params["manager_id_$index"] = $lead['manager_id'];
			$params["utm_campaign_$index"] = $lead['utm_campaign'];
			$params["budget_uah_$index"] = $lead['budget_uah'];
			$params["next_contact_at_$index"] = $lead['next_contact_at'];
			$params["comment_$index"] = $lead['comment'];

			$index++;
		}

		$sql = '
		   INSERT INTO leads (
			  external_id,
			  created_at,
			  first_name,
			  last_name,
			  phone,
			  email,
			  city_id,
			  source_id,
			  product_id,
			  status_id,
			  manager_id,
			  utm_campaign,
			  budget_uah,
			  comment,
			  next_contact_at
		   )
		   VALUES ' . implode(', ', $values);

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
	}

	private function loadDictionaries(): void
	{
		echo "=== First pass: dictionaries ===" . '<br />';

		$totalStart = microtime(true);

		// -----------------------------
		// Open reader
		// -----------------------------

		$start = microtime(true);

		$reader = new Reader();
		$reader->open($this->filePath);

		echo 'Reader open: '
			. round(microtime(true) - $start, 3)
			. " sec" . '<br />';


		// -----------------------------
		// Prepare dictionaries
		// -----------------------------

		$cities = [];
		$sources = [];
		$products = [];
		$statuses = [];
		$managers = [];


		// -----------------------------
		// Read Excel
		// -----------------------------

		$start = microtime(true);

		$rowNumber = 0;

		foreach ($reader as $row) {

			$rowNumber++;

			// Skip header
			if ($rowNumber === 1) {
				continue;
			}

			$city = trim((string) ($row[6] ?? ''));
			$source = trim((string) ($row[7] ?? ''));
			$product = trim((string) ($row[9] ?? ''));
			$status = trim((string) ($row[11] ?? ''));
			$manager = trim((string) ($row[12] ?? ''));

			if ($city !== '') {
				$cities[$city] = true;
			}

			if ($source !== '') {
				$sources[$source] = true;
			}

			if ($product !== '') {
				$products[$product] = true;
			}

			if ($status !== '') {
				$statuses[$status] = true;
			}

			if ($manager !== '') {
				$managers[$manager] = true;
			}
		}

		$reader->close();

		echo 'Excel reading: '
			. round(microtime(true) - $start, 3)
			. " sec" . '<br />';


		// -----------------------------
		// Database
		// -----------------------------

		$start = microtime(true);

		$this->insertDictionary(
			'cities',
			array_keys($cities)
		);

		$this->insertDictionary(
			'sources',
			array_keys($sources)
		);

		$this->insertDictionary(
			'products',
			array_keys($products)
		);

		$this->insertDictionary(
			'statuses',
			array_keys($statuses)
		);

		$this->insertDictionary(
			'users',
			array_keys($managers)
		);

		echo 'DB inserts: '
			. round(microtime(true) - $start, 3)
			. " sec" . '<br />';


		// -----------------------------
		// Statistics
		// -----------------------------

		echo 'Rows: ' . ($rowNumber - 1) . '<br />';
		echo 'Cities: ' . count($cities) . '<br />';
		echo 'Sources: ' . count($sources) . '<br />';
		echo 'Products: ' . count($products) . '<br />';
		echo 'Statuses: ' . count($statuses) . '<br />';
		echo 'Managers: ' . count($managers) . '<br />';

		echo 'Total first pass: '
			. round(microtime(true) - $totalStart, 3)
			. " sec" . '<br />';

		echo 'Peak memory: '
			. round(memory_get_peak_usage(true) / 1024 / 1024, 2)
			. " MB" . '<br />';
	}

	private function insertDictionary(string $table, array $values): void {
		if (empty($values)) {
			return;
		}

		$values = array_values(array_unique(array_filter(
			array_map('trim', $values),
			fn ($value) => $value !== ''
		)));

		if (empty($values)) {
			return;
		}

		$placeholders = [];
		$params = [];

		foreach ($values as $index => $value) {
			$placeholder = ':value_' . $index;

			$placeholders[] = "($placeholder)";
			$params[$placeholder] = $value;
		}

		$sql = sprintf(
			'INSERT INTO %s (name)
			 VALUES %s
			 ON CONFLICT (name) DO NOTHING',
			$table,
			implode(', ', $placeholders)
		);

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
	}

	private function getDictionaryMap(string $table): array
	{
		$stmt = $this->pdo->query("SELECT id, name FROM {$table}");

		$map = [];

		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$map[$row['name']] = (int) $row['id'];
		}

		return $map;
	}

	private function parseDate(mixed $value): ?string
	{
		if ($value === null || $value === '') {
			return null;
		}

		if ($value instanceof \DateTimeInterface) {
			return $value->format('Y-m-d H:i:s');
		}

		$value = trim((string) $value);

		if ($value === '') {
			return null;
		}

		$timestamp = strtotime($value);

		if ($timestamp === false) {
			return null;
		}

		return date('Y-m-d H:i:s', $timestamp);
	}

	private function parseBudget(mixed $value): ?float
	{
		if ($value === null || $value === '') {
			return null;
		}

		if (is_numeric($value)) {
			return (float) $value;
		}

		$value = trim((string) $value);

		$value = str_replace(
			["\xc2\xa0", ' '],
			'',
			$value
		);

		$value = str_replace(',', '.', $value);

		$value = preg_replace('/[^0-9.\-]/', '', $value);

		if ($value === '' || !is_numeric($value)) {
			return null;
		}

		return (float) $value;
	}

}