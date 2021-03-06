<?php

/*
	Copyright (c) 2012 Colin Rotherham, http://colinr.com
	https://github.com/colinrotherham
*/

	namespace CRD\Form;

	class Helper
	{
		public $type;
		public $validator;
		public $helper;

		// Callbacks
		public $onPreSubmit; // Before validation
		public $onSubmit; // After validation (any)
		public $onSuccess; // After validation (successful only)

		// Status
		public $hasSubmit = false;
		public $hasSuccess = false;

		public function __construct($type, $model, $onSuccess = null, $onSubmit = null, $onPreSubmit = null)
		{
			// Set properties
			$this->type = $type;
			$this->onSuccess = $onSuccess;
			$this->onSubmit = $onSubmit;
			$this->onPreSubmit = $onPreSubmit;

			// Create validator/helper objects
			$this->validator = new Validator($model);
			$this->helper = new HTML($this->validator);
		}

		public function validate()
		{
			// Validate? Runs once per submission
			if (!$this->hasSubmit && $this->isSubmitted())
			{
				$onSuccess = $this->onSuccess;
				$onSubmit = $this->onSubmit;
				$onPreSubmit = $this->onPreSubmit;

				// Run pre-submit callback before validation
				if (is_callable($onPreSubmit))
					$onPreSubmit($this);

				// Validate
				$this->validator->validate();

				// Run submit callback after validation
				if (is_callable($onSubmit))
					$onSubmit($this);

				// Check status and run callback
				if ($this->isSuccess() && is_callable($onSuccess))
					$onSuccess($this);
			}
		}

		public function toEmail($to, $from, $subject, $message = '', $name = '')
		{
			$fields = array();

			// Pad if message provided
			if (!empty($message))
				$message .= "\n\n";

			// Build message
			foreach ($this->validator->fields as $field => $value)
			{
				$field = $this->validator->model->$field;

				// Array of values
				if (is_array($value)) foreach ($value as $part)
					$message .= $field->name . ': ' . trim($part) . "\n";

				// Single field
				else $message .= $field->name . ': ' . trim($value) . "\n";
			}

			// Additional headers
			$headers = array();
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'Content-Type: text/plain; charset="UTF-8"';
			$headers[] = !empty($name)? "From: {$name} <{$from}>" : "From: {$from}";

			// Send email
			mail($to, $subject, $message, implode("\r\n", $headers), "-f {$from}");
		}

		// Has form been submitted?
		public function isSubmitted()
		{
			// Check for request and form type matches
			if (!empty($_REQUEST['type']))
				$this->hasSubmit = $_REQUEST['type'] === $this->type;

			// Check for posted type
			return $this->hasSubmit;
		}

		// Wrapper for compatibility
		public function isPosted()
		{
			return $this->isSubmitted();
		}

		// Has form been submitted with no errors?
		public function isSuccess()
		{
			// Check form is submitted
			if ($this->isSubmitted())
				$this->hasSuccess = count((array) $this->getErrors()) === 0;

			// Check for posted type
			return $this->hasSuccess;
		}

		public function getErrors()
		{
			return $this->validator->errors;
		}

		public function hasErrors()
		{
			return count((array) $this->getErrors()) > 0;
		}
	}