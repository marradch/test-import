<?php

namespace App\Controller;

use App\Service\ImportService;

class ImportController
{
	public function __construct(private ImportService $service) {}

	public function index()
	{
		$this->service->import();
	}
}