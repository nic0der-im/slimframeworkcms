<?php

namespace App\Validation;


use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;

class Validator
{
	protected $errors;
	
	public function validate($request, array $rules)
	{
		foreach($rules as $field=>$rule){
			try {
				$rule->setName(ucfirst($field))->assert($request->getParam($field)); 	
			} catch(NestedValidationException $e) {
				$this->errors[$field] = array_filter($e->findMessages([
				    'alnum' => 'Solo esta permitido numeros y letras.',
				    'numeric' => 'Debe ser numerico.',
				    'noWhitespace' => 'No tiene que contener espacios en blanco.',
				    'notEmpty' => 'No puede estar vacio.',
				    'alpha' => 'Debe ser alfabetico.',
				]));
			}			
		}
		$_SESSION['errors'] = $this->errors;

		return $this;
	}

	public function failed()
	{
		return !empty($this->errors);
	}
}