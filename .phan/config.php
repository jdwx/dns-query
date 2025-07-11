<?php

declare( strict_types = 1 );


return [

	'minimum_target_php_version' => '8.4',
	'target_php_version' => '8.4',

	'directory_list' => [
		'examples',
		'src',
		'tests',
		'vendor',
	],

	'exclude_analysis_directory_list' => [
		'src/Legacy',
		'tests/Legacy',
		'vendor'
	],

	'suppress_issue_types' => [
		'PhanTypeInvalidThrowsIsInterface',
	],

];

