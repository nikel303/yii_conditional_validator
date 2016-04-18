<?php

namespace YiiConditionalValidator;

class Validator extends \CValidator {

	/**
	 * The array of rules that should be match before own rule condition
	 * <pre>
	 *    array('attribute','required', 'on'=>'create')
	 *
	 *    or
	 *    array(
	 *        'group'=>array(
	 *            array('attribute','required'),
	 *            array('attribute','email')
	 *        )
	 *    )
	 * </pre>
	 * @var array $conditionalRules
	 *    own rule
	 */
	public $conditionalRules = [];

	/**
	 * @var array $rule the rule
	 */
	public $rule = [];

	/**
	 * @var boolean $skipConditional whether to skip conditional validations
	 */
	public $skipConditional = false;

	/**
	 *
	 * Allows the insertion of the JS code that will be executed for
	 * client validation
	 * @var string $clientValidationJS
	 */
	public $clientValidationJS;

	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * @param CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object, $attribute) {

		$obj = clone $object;

		if (!$this->skipConditional && !$this->validateConditional($obj, $this->conditionalRules))
			return false;

		$validator = \CValidator::createValidator($this->rule[0], $object, $attribute, array_slice($this->rule, 1, null, true));
		$validator->validate($object);

		$obj = null;
	}

	/**
	 *
	 * @param CModel $object the object to be validated
	 * @param mixed $rule the rules to validate the object against
	 * @return boolean false if it has errors, true otherwise
	 */
	protected function validateConditional(&$object, $rule) {
		if (isset($rule['group'])) {
			if (is_array($rule['group'])) {
				foreach ($rule['group'] as $r) {
					if (is_array($r)) {
						$val = $this->validateConditional($object, $r);
						if (!$val)
							return false;
					} else
						continue;
				}
			} else
				throw new \CException(\Yii::t('EConditionalValidator', 'Group must be an array of rules'));
		} else {
			list($attributes, $conditionalValidator) = $rule;

			$parameters = array_splice($rule, 2);

			$validator = \CValidator::createValidator($conditionalValidator, $object, $attributes, $parameters);

			$validator->validate($object);
			if ($object->hasErrors()) {
				$object->clearErrors();

				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the JavaScript needed for performing client-side validation.
	 * Do not override this method if the validator does not support client-side validation.
	 * Two predefined JavaScript variables can be used:
	 * <ul>
	 * <li>value: the value to be validated</li>
	 * <li>messages: an array used to hold the validation error messages for the value</li>
	 * </ul>
	 * @param CModel $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 * @return string the client-side validation script. Null if the validator does not support client-side validation.
	 * @see CActiveForm::enableClientValidation
	 */
	public function clientValidateAttribute($object, $attribute) {
		return $this->clientValidationJS ? $this->clientValidationJS : null;
	}

}