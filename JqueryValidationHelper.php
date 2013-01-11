<?php

App::uses('AppHelper', 'View/Helper');

class JqueryValidationHelper extends AppHelper {

  public $helpers = array('Form', 'Html');
  #-- Map to replace constants with jquery and class info
  #-- Pass as $options['jquery-validation']
  public $options = array(
	  'map' => array(
		  '__formSelector__' => '.jquery-validation',
		  '__errorElement__' => 'span',
		  '__errorClass__' => 'help-block',
		  '__hilightClass__' => 'form-error',
		  '__closestSelector__' => '.control-group',
		  '__closestErrorClass__' => 'error'
	  )
  );
  private $js = "
    $(document).ready(function(){
      $('__formSelector__').each( function(index) {
        $.metadata.setType('attr','data-validate');
        $(this).validate({
          'errorElement': '__errorElement__',
          'errorClass': '__errorClass__',
          'highlight': function(element,errorClass) {
		    $(element)
            .siblings(':not(label)').remove();
            $(element)
            .addClass('__hilightClass__')
            .closest('__closestSelector__').addClass('__closestErrorClass__');
         },
          'unhighlight': function(element,errorClass) {
			$(element)
            .siblings(':not(label)').remove();
            $(element)
            .removeClass('__hilightClass__')
            .closest('__closestSelector__').removeClass('__closestErrorClass__')
          }
        })
      })
    })";

  /**
   * createHorizontal
   *
   * Routine to mimic form helper with horizontal messages
   *
   * @param type $model
   * @param type $options
   */
  public function createHorizontal($model = null, $options = array()) {
	if (!empty($options['class'])) {
	  $options['class'] .= ' form-horizontal jquery-validation';
	} else {
	  $options['class'] = 'form-horizontal jquery-validation';
	}
	$this->options['map']['__errorClass__'] = 'help-inline';
	return $this->Form->create($model, $options);
  }

  /**
   * createVertical
   *
   * Routine to mimic form helper with vertical messages
   *
   * @param type $model
   * @param type $options
   */
  public function createVertical($model = null, $options = array()) {
	if (!empty($options['class'])) {
	  $options['class'] .= ' form-vertical jquery-validation';
	} else {
	  $options['class'] = 'form-vertical jquery-validation';
	}
	$this->options['map']['__errorClass__'] = 'help-block';
	return $this->Form->create($model, $options);
  }

  /**
   * input
   *
   * Routine to mimic form helper in order to get needed info.
   *
   * @param type $fieldName
   * @param type $options
   */
  public function input($fieldName, $options = array()) {
	$map = $this->options['map'];
	if (isset($options['jquery-validation'])) {
	  if (isset($options['jquery-validation']['map'])) {
		$map = array_merge($map, $options['jquery-validation']['map']);
	  }
	  unset($options['jquery-validation']);
	}
	// Thx2 Olivier Rocques  - Field names can be in the form User.name or User.0.name
	$vals = explode('.', $fieldName);
	if (count($vals) > 1) {
	  $model = $vals[0];
	  $fieldName = $vals[count($vals) - 1];
	} else {
	  $model = $this->Form->defaultModel;
	}
	$meta = $this->meta($model, $fieldName);
	$formatted_js = strtr($this->js, $map);
	$boolean_fix_map = array(
		'"true"' => 'true',
		'"false"' => 'false',
	);
	$options['data-validate'] = strtr(json_encode($meta, JSON_NUMERIC_CHECK), $boolean_fix_map);
	$response = $this->_addJs($map);
	$response .= $this->Form->input($fieldName, $options);
	return $response;
  }

  /**
   * meta
   *
   * Returns a meta string to be added to the class of a dialog input
   *
   * @param type $model
   * @param type $field
   * @return string
   */
  public function meta($model, $field) {
	App::import('Model', $model);
	$model_object = new $model();
	$meta = array();
	foreach ($model_object->validate as $validateField => $validateItem) {
	  //CakeLog::write('debug', 'JqueryValidation->meta: validateField=' . $validateField);
	  if ($field == $validateField) {
		if (is_array($validateItem)) {
		  //CakeLog::write('debug', 'JqueryValidation->meta: validateItem=' . print_r($validateItem, true));
		  foreach ($validateItem as $validateName => $validateParams) {
			if (!empty($validateParams['rule'])) {
			  $rule = $validateParams['rule'];
			  //CakeLog::write('debug', 'JqueryValidation->meta: $rule=' . print_r($rule, true));
			  if (is_array($rule)) {
				$msg = $rule[0];
				$ruleName = $rule[0];
			  } else {
				$msg = $rule;
				$ruleName = $rule;
			  }
			  if (!empty($validateParams['message'])) {
				$msg = $validateParams['message'];
			  }
			  $ruleName = $rule[0];
			  //CakeLog::write('debug', 'JqueryValidation->meta: ruleName=' . $ruleName);
			  $methodName = 'jquery_validate_' . $ruleName;
			  if (method_exists($this, $methodName)) {
				$meta = array_merge($meta, $this->$methodName($model, $field, $rule, $msg));
				//CakeLog::write('debug', 'JqueryValidation->meta: $meta=' . $meta);
			  } else {
				//CakeLog::write('debug', 'JqueryValidation->meta: function $ruleName not found');
			  }
			}
		  }
		}
	  }
	}
	return $meta;
  }

  /**
   *  Various functions to convert a CakePHP validation to a Jquery Validate meta tag
   */
  private function jquery_validate_alphaNumeric($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->date: params=' . print_r($params, true));
	$response['messages']['alphanumeric'] = $msg;
	$response['alphanumeric'] = 'true';
	return $response;
  }

  private function jquery_validate_between($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->between: params=' . print_r($params, true));
	$response['messages']['min'] = $msg;
	$response['messages']['max'] = $msg;
	$response['min'] = $params[1];
	$response['max'] = $params[2];
	return $response;
  }

  private function jquery_validate_blank($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->blank: params=' . print_r($params, true));
	$response['messages']['rangelength'] = $msg;
	$response['rangelength'] = "[0, 0]";
	return $response;
  }

  private function jquery_validate_boolean($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->boolean: params=' . print_r($params, true));
	return '';
	$response['messages']['boolean'] = $msg;
	$response['boolean'] = "true";
	return $response;
  }

  private function jquery_validate_cc($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->cc: params=' . print_r($params, true));
	$response['messages']['creditcard'] = $msg;
	$response['creditcard'] = "true";
	return $response;
  }

  private function jquery_validate_comparison($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->comparison: params=' . print_r($params, true));
	$response = array();
	$op = $params[1];
	$value = $params[2];
	switch ($op) {
	  case '>':
		$value++;
		$response['messages']['min'] = $msg;
		$response['min'] = $value;
		break;
	  case '>=':
		$response['messages']['min'] = $msg;
		$response['min'] = $value;
		break;
	  case '<':
		$value--;
		$response['messages']['max'] = $msg;
		$response['max'] = $value;
		break;
	  case '<=':
		$response['messages']['max'] = $msg;
		$response['max'] = $value;
		break;
	  case '!=':
		$value++;
		$response['messages']['min'] = $msg;
		$response['min'] = $value;
		$value = $value - 2;
		$response['messages']['max'] = $msg;
		$response['max'] = $value;
		break;
	  default:
		return '';
	}
	return $response;
  }

  private function jquery_validate_date($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->date: params=' . print_r($params, true));
	$response['messages']['date'] = $msg;
	$response['date'] = "true";
	return $response;
  }

  private function jquery_validate_datetime($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->datetime: params=' . print_r($params, true));
	return '';
	$response['messages']['datetime'] = $msg;
	$response['datetime'] = "true";
	return $response;
  }

  private function jquery_validate_decimal($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->decimal: params=' . print_r($params, true));
	$response['messages']['decimal'] = $msg;
	$response['number'] = "true";
	return $response;
  }

  private function jquery_validate_email($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->email: params=' . print_r($params, true));
	$response['messages']['email'] = $msg;
	$response['email'] = "true";
	return $response;
  }

  private function jquery_validate_equalTo($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->equalTo: params=' . print_r($params, true));
	$response['messages']['equalTo'] = $msg;
	$response['equalTo'] = "'" . Inflector::camelize($model) . Inflector::camelize($params[1]) . "'";
	return $response;
  }

  private function jquery_validate_extension($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->extension: params=' . print_r($params, true));
	$response['messages']['accept'] = $msg;
	$response['accept'] = "'" . implode($params[1], "|") . "'";
	return $response;
  }

  private function jquery_validate_inList($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->inList: params=' . print_r($params, true));
	return '';
	$response['messages']['inList'] = $msg;
	$response['inList'] = "true";
	return $response;
  }

  private function jquery_validate_ip($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->ip: params=' . print_r($params, true));
	$response['messages']['ipv4'] = $msg;
	$response['ipv4'] = "true";
	return $response;
  }

  private function jquery_validate_luhn($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->luhn: params=' . print_r($params, true));
	return '';
	$response['messages']['luhn'] = $msg;
	$response['luhn'] = "true";
	return $response;
  }

  private function jquery_validate_maxLength($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->maxLength: params=' . print_r($params, true));
	$response['messages']['maxlength'] = $msg;
	$response['maxlength'] = $params[1];
	return $response;
  }

  private function jquery_validate_minLength($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->minLength: params=' . print_r($params, true));
	$response['messages']['minlength'] = $msg;
	$response['minlength'] = $params[1];
	return $response;
  }

  private function jquery_validate_money($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->money: params=' . print_r($params, true));
	return '';
	$response['messages']['money'] = $msg;
	$response['money'] = "true";
	return $response;
  }

  private function jquery_validate_notEmpty($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->notEmpty: params=' . print_r($params, true));
	$response['messages']['required'] = $msg;
	$response['required'] = "true";
	return $response;
  }

  private function jquery_validate_numeric($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->numeric: params=' . print_r($params, true));
	$response['messages']['number'] = $msg;
	$response['number'] = 'true';
	return $response;
  }

  private function jquery_validate_naturalNumber($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->naturalNumber: params=' . print_r($params, true));
	$response['messages']['digits'] = $msg;
	$response['digits'] = "true";
	return $response;
  }

  private function jquery_validate_required($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->required: params=' . print_r($params, true));
	$response['messages']['required'] = $msg;
	$response['required'] = "true";
	return $response;
  }

  private function jquery_validate_phone($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->phone: params=' . print_r($params, true));
	$response['messages']['phoneUS'] = $msg;
	$response['phoneUS'] = "true";
	return $response;
  }

  private function jquery_validate_postal($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->postal: params=' . print_r($params, true));
	$response['messages']['minlength'] = $msg;
	$response['messages']['maxlength'] = $msg;
	$response['minlength'] = 5;
	$response['maxlength'] = 5;
	return $response;
  }

  private function jquery_validate_range($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->range: params=' . print_r($params, true));
	$response['messages']['min'] = $msg;
	$response['messages']['max'] = $msg;
	$response['min'] = $params[1];
	$response['max'] = $params[2];
	return $response;
  }

  private function jquery_validate_ssn($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->ssn: params=' . print_r($params, true));
	$response['messages']['minlength'] = $msg;
	$response['messages']['maxlength'] = $msg;
	$response['minlength'] = 9;
	$response['maxlength'] = 9;
	return $response;
  }

  private function jquery_validate_time($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->time: params=' . print_r($params, true));
	$response['messages']['time'] = $msg;
	$response['time'] = "true";
	return $response;
  }

  private function jquery_validate_url($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->url: params=' . print_r($params, true));
	$response['messages']['url'] = $msg;
	$response['url'] = "true";
	return $response;
  }

  private function jquery_validate_uuid($model, $field, $params, $msg) {
	//CakeLog::write('debug', 'JqueryValidation->uuid: params=' . print_r($params, true));
	return '';
	$response['messages']['uuid'] = $msg;
	$response['uuid'] = "true";
	return $response;
  }

  private function _addJs($map) {
	$response = '';
	#-- Inlcude the js if needed
	if (!empty($this->js)) {
	  $formatted_js = strtr($this->js, $map);
	  $response .= "<script type=\"text/javascript\">" . $formatted_js . "</script>";
	  $this->js = '';
	  //CakeLog::write('debug','JqueryValidation->_addJs: injecting js '.$response);
	}
	return $response;
  }

}

?>