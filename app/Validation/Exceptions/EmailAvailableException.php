<?php

namespace App\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class EmailAvailableException extends ValidationException {
	public static $defaultTemplates = [
		self::MODE_DEFAULT => [
			self::STANDARD => 'La dirección de correo se encuentra en uso.',
		],
	];
}