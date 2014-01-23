<?php

/*
	Copyright (c) 2012 Colin Rotherham, http://colinr.com
	https://github.com/colinrotherham
*/

	namespace CRD\Form;

	class Validator
	{
		public $model = array();
		public $fields = array();
		public $errors = array();

		public $validated = false;

		public function __construct($model, $resources = null)
		{
			$this->model = $model;
			$this->errors = (object) array();

			$this->resources = !empty($resources)? $resources : array
			(
				'required'		=> '%1$s is a required field',
				'fixedLength'	=> '%1$s must be %2$s characters',
				'minLength'		=> '%1$s must be %2$s or more characters',
				'maxLength'		=> '%1$s must be %2$s or fewer characters',
				'invalid'		=> '%1$s is not valid',
				'invalidDate'	=> '%1$s is not a valid date',
				'groupCheckbox'	=> 'Please tick at least one %1$s',
				'groupText'		=> '%1$s must match'
			);
		}

		public function validate()
		{
			$this->validated = false;

			// Loop fields
			foreach ($this->model as $field => $validation)
			{
				$is_empty = false;

				// Missing value becomes empty string
				$value = (!empty($_POST[$field]))? (get_magic_quotes_gpc()? stripcslashes($_POST[$field]) : $_POST[$field]) : '';
				$is_empty = ($value === '')? true : false;

				// Is this a field group, not a field?
				if (!empty($validation->group) && !empty($validation->groupType))
				{
					// Date groups
					if ($validation->groupType == 'date')
					{
						$date_day = $_POST[$validation->group[0]];
						$date_month = $_POST[$validation->group[1]];
						$date_year = $_POST[$validation->group[2]];

						// All date fields must be populated
						$is_empty = (!empty($date_day) && !empty($date_month) && !empty($date_year))? false : true;

						// Save combined date group
						if (!$is_empty)
						{
							$date_combined = "{$date_year}-{$date_month}-{$date_day}";
							$this->fields[$field] = $date_combined;
						}

						// Convert to ints
						$date_day = intval($date_day);
						$date_month = intval($date_month);
						$date_year = intval($date_year);

						// Valid date?
						if (!checkdate($date_month, $date_day, $date_year))
						{
							$this->errorAdd($field, $this->resources['invalidDate'], $validation->name);
							$date_combined = '';
						}
					}

					// Checkbox groups
					else if ($validation->groupType == 'checkbox')
					{
						$group_fields = array();
						$group_empty = true; // Assume all checkboxes are still unticked

						foreach ($validation->group as $group_field)
						{
							// Is this checkbox empty?
							if (empty($_POST[$group_field]))
								continue;

							$group_empty = false;
							$group_fields[$group_field] = $_POST[$group_field];
						}

						// Are all checkboxes unticked?
						if ($group_empty)
							$this->errorAdd($field, $this->resources['groupCheckbox'], strtolower($validation->name));
					}

					// Text groups
					else if ($validation->groupType == 'text')
					{
						$group_fields = array();
						$group_matches = false; // Assume emails don't match

						$input1 = (!empty($_POST[$validation->group[0]]))? $_POST[$validation->group[0]] : '';
						$input2 = (!empty($_POST[$validation->group[1]]))? $_POST[$validation->group[1]] : '';

						// Emails aren't empty but don't match
						if (!empty($input1) && !empty($input2) && $input1 !== $input2)
							$this->errorAdd($field, $this->resources['groupText'], $validation->name);
					}
				}

				// This is a field, not a group
				else if (empty($validation->group))
				{
					if (is_object($validation) && (!isset($validation->required) || $validation->required === true))
					{
						// Add validation message
						if ($is_empty)
							$this->errorAdd($field, $this->resources['required'], $validation->name);

						// Check regex
						else if (!empty($validation->regEx) && is_string($validation->regEx))
						{
							// Ignore spaces?
							if (!empty($validation->stripSpaces) && $validation->stripSpaces)
								$value = str_replace(' ', '', $value);

							preg_match($validation->regEx, $value, $regex_matches);

							if (count($regex_matches) != 1)
								$this->errorAdd($field, $this->resources['invalid'], $validation->name);
						}

						// Check fixed length
						if (!empty($validation->fixedLength) && is_numeric($validation->fixedLength) && strlen($value) != $validation->fixedLength)
						{
							$this->errorAdd($field, $this->resources['fixedLength'],
								array($validation->name, $validation->fixedLength));
						}

						// Check min length
						else if (!empty($validation->minLength) && is_numeric($validation->minLength) && strlen($value) < $validation->minLength)
						{
							$this->errorAdd($field, $this->resources['minLength'],
								array($validation->name, $validation->minLength));
						}

						// Check max length
						else if (!empty($validation->maxLength) && is_numeric($validation->maxLength) && strlen($value) > $validation->maxLength)
						{
							$this->errorAdd($field, $this->resources['maxLength'],
								array($validation->name, $validation->maxLength));
						}
					}

					// Save value for later
					$this->fields[$field] = $value;
				}
			}

			if (count((array) $this->errors) === 0)
			{
				$this->validated = true;
			}
		}

		public function errorAdd($field, $resource, $arguments)
		{
			if (empty($this->errors->$field))
				$this->errors->$field = array();

			// Ensure arguments is an array
			if (is_string($arguments)) $arguments = array($arguments);

			// Add error message
			array_push($this->errors->$field, vsprintf($resource, $arguments));
		}
	}
?>