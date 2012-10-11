<?php

App::uses('AppHelper', 'View/Helper');

class JqueryValidationHelper extends AppHelper {

  public $helpers = array('Form');

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
  private $validation_messages = array();
  private $js = "
    $(document).ready(function(){
      $('__formSelector__').each( function(index) {
        $(this).validate({
          'errorElement': '__errorElement__',
          'errorClass': '__errorClass__',
          'highlight': function(element,errorClass) {
            $(element)
            .siblings().remove();
            $(element)
            .addClass('__hilightClass__')
            .closest('__closestSelector__').addClass('__closestErrorClass__');
          },
          'unhighlight': function(element,errorClass) {
            $(element)
            .removeClass('__hilightClass__')
            .closest('__closestSelector__').removeClass('__closestErrorClass__')
          },
        });
      });
    });";

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
    $model = $this->Form->defaultModel;
    $meta = $this->meta($model, $fieldName);
    if (!empty($options['class'])) {
      $options['class'] .= ' {' . $meta . '}';
    } else {
      $options['class'] = $meta;
    }
    $response = '';
    #-- Inlcude the js if needed
    if (!empty($this->js)) {
      $formatted_js = strtr($this->js, $map);
      $response .= "<script type=\"text/javascript\">" . $formatted_js . "</script>";
      $this->js = '';
    }
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
    $model_object = new $model();
    foreach ($model_object->validate as $validateField => $validateItem) {
      CakeLog::write('debug', 'JqueryValidation->meta: validateField=' . $validateField);
      if ($field == $validateField) {
        if (is_array($validateItem)) {
          CakeLog::write('debug', 'JqueryValidation->meta: validateItem=' . print_r($validateItem, true));
          foreach ($validateItem as $validateName => $validateParams) {
            if (!empty($validateParams['rule'])) {
              $rule = $validateParams['rule'];
              CakeLog::write('debug', 'JqueryValidation->meta: $rule=' . print_r($rule, true));

              if (is_array($rule)) {
                $msg = $rule[0];
              } else {
                $msg = $rule;
              }
              if (!empty($validateParams['message'])) {
                $msg = $validateParams['message'];
              }
              $ruleName = $rule[0];
              CakeLog::write('debug', 'JqueryValidation->meta: ruleName=' . $ruleName);
              $methodName = 'jquery_validation_' . $ruleName;
              if (method_exists($this, $methodName)) {
                $meta[] = $this->$methodName($model, $field, $rule, $msg);
                CakeLog::write('debug', 'JqueryValidation->meta: $meta=' . $meta);
              } else {
                CakeLog::write('debug', 'JqueryValidation->meta: function $ruleName not found');
              }
            }
          }
        }
      }
    }
    if (is_array($meta)) {
      $messages_str = '';
      if (is_array($this->validation_messages)) {
        $messages_str = implode($this->validation_messages, ', ');
      }
      $meta_str = implode($meta, ', ');
      return "'rules': {" . $meta_str . ", 'messages': { " . $messages_str . "}}";
    } else {
      return '';
    }
  }
/**
 *  Various functions to convert a CakePHP validation to a Jquery Validate meta tag
 */
  private function jquery_validation_alphaNumeric($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->date: params=' . print_r($params, true));
    $this->validation_messages[] = "date: '" . $msg . "'";
    return "'date': true";
  }

  private function jquery_validation_between($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->between: params=' . print_r($params, true));
    $this->validation_messages[] = "between: '" . $msg . "'";
    return "'min': " . $params[1] . ", 'max': " . $params[2];
  }

  private function jquery_validation_blank($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->blank: params=' . print_r($params, true));
    $this->validation_messages[] = "rangelength: '" . $msg . "'";
    return "'rangelength': [0, 0]";
  }

  private function jquery_validation_boolean($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->boolean: params=' . print_r($params, true));
    return '';
    $this->validation_messages[] = "boolean: '" . $msg . "'";
    return "'boolean': true";
  }

  private function jquery_validation_cc($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->cc: params=' . print_r($params, true));
    $this->validation_messages[] = "creditcard: '" . $msg . "'";
    return "'creditcard': true";
  }

  private function jquery_validation_comparison($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->comparison: params=' . print_r($params, true));
    $op = $params[1];
    $value = $params[2];
    switch ($op) {
      case '>':
        $value++;
        $this->validation_messages[] = "'min': '" . $msg . "'";
        return "'min': " . $value;
        break;
      case '>=':
        $this->validation_messages[] = "'min': '" . $msg . "'";
        return "'min': " . $value;
        break;
      case '<':
        $value--;
        $this->validation_messages[] = "'max': '" . $msg . "'";
        return "'max': " . $value;
        break;
      case '<=':
        $this->validation_messages[] = "'max': '" . $msg . "'";
        return "'max': " . $value;
        break;
      case '!=':
        $value++;
        $this->validation_messages[] = "'min': '" . $msg . "'";
        $str = "'min': " . $value;
        $value = $value - 2;
        $this->validation_messages[] = "'max': '" . $msg . "'";
        return $str . ", 'max': " . $value;
        break;
      default:
        return '';
    }
  }

  private function jquery_validation_date($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->date: params=' . print_r($params, true));
    $this->validation_messages[] = "date: '" . $msg . "'";
    return "'date': true";
  }

  private function jquery_validation_datetime($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->datetime: params=' . print_r($params, true));
    return '';
    $this->validation_messages[] = "datetime: '" . $msg . "'";
    return "'datetime': true";
  }

  private function jquery_validation_decimal($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->decimal: params=' . print_r($params, true));
    $this->validation_messages[] = "number: '" . $msg . "'";
    return "'number': true";
  }

  private function jquery_validation_email($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->email: params=' . print_r($params, true));
    $this->validation_messages[] = "email: '" . $msg . "'";
    return "'email': true";
  }

  private function jquery_validation_equalTo($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->equalTo: params=' . print_r($params, true));
    $this->validation_messages[] = "equalTo: '" . $msg . "'";
    return "'equalTo': '" . Inflector::camelize($model) . Inflector::camelize($params[1]) . "'";
  }

  private function jquery_validation_extension($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->extension: params=' . print_r($params, true));
    $this->validation_messages[] = "accept: '" . $msg . "'";
    return "'accept': '" . implode($params[1], "|") . "'";
  }

  private function jquery_validation_inList($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->inList: params=' . print_r($params, true));
    return '';
    $this->validation_messages[] = "inList: '" . $msg . "'";
    return "'inList': true";
  }

  private function jquery_validation_ip($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->ip: params=' . print_r($params, true));
    $this->validation_messages[] = "ipv4: '" . $msg . "'";
    return "'ipv4': true";
  }

  private function jquery_validation_luhn($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->luhn: params=' . print_r($params, true));
    return '';
    $this->validation_messages[] = "luhn: '" . $msg . "'";
    return "'luhn': true";
  }

  private function jquery_validation_maxLength($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->maxLength: params=' . print_r($params, true));
    $this->validation_messages[] = "'maxlength': '" . $msg . "'";
    return "'maxlength': " . $params[1];
  }

  private function jquery_validation_minLength($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->minLength: params=' . print_r($params, true));
    $this->validation_messages[] = "'minlength': '" . $msg . "'";
    return "'minlength': " . $params[1];
  }

  private function jquery_validation_money($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->money: params=' . print_r($params, true));
    return '';
    $this->validation_messages[] = "money: '" . $msg . "'";
    return "'money': true";
  }

  private function jquery_validation_notEmpty($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->notEmpty: params=' . print_r($params, true));
    $this->validation_messages[] = "required: '" . $msg . "'";
    return "'required': true";
  }

  private function jquery_validation_numeric($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->numeric: params=' . print_r($params, true));
    $this->validation_messages[] = "number: '" . $msg . "'";
    return "'number': true";
  }

  private function jquery_validation_naturalNumber($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->naturalNumber: params=' . print_r($params, true));
    $this->validation_messages[] = "digits: '" . $msg . "'";
    return "'digits': true";
  }

  private function jquery_validation_required($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->required: params=' . print_r($params, true));
    $this->validation_messages[] = "required: '" . $msg . "'";
    return "'required': true";
  }

  private function jquery_validation_phone($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->phone: params=' . print_r($params, true));
    $this->validation_messages[] = "phoneUS: '" . $msg . "'";
    return "'phoneUS': true";
  }

  private function jquery_validation_postal($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->postal: params=' . print_r($params, true));
    $this->validation_messages[] = "minlength: '" . $msg . "'";
    $this->validation_messages[] = "maxlength: '" . $msg . "'";
    return "'minlength': 5, 'maxlength': 5";
  }

  private function jquery_validation_range($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->range: params=' . print_r($params, true));
    $this->validation_messages[] = "min: '" . $msg . "'";
    $this->validation_messages[] = "max: '" . $msg . "'";
    return "'min': " . $params[1] . ", 'max': " . $params[2];
  }

  private function jquery_validation_ssn($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->ssn: params=' . print_r($params, true));
    $this->validation_messages[] = "minlength: '" . $msg . "'";
    $this->validation_messages[] = "maxlength: '" . $msg . "'";
    return "'minlength': 9, 'maxlength': 9";
  }

  private function jquery_validation_time($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->time: params=' . print_r($params, true));
    $this->validation_messages[] = "time: '" . $msg . "'";
    return "'time': true";
  }

  private function jquery_validation_url($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->url: params=' . print_r($params, true));
    $this->validation_messages[] = "url: '" . $msg . "'";
    return "'url': true";
  }

  private function jquery_validation_uuid($model, $field, $params, $msg) {
    CakeLog::write('debug', 'JqueryValidation->uuid: params=' . print_r($params, true));
    return '';
    $this->validation_messages[] = "uuid: '" . $msg . "'";
    return "'uuid': true";
  }
}
?>
