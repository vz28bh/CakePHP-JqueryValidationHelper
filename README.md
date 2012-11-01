CakePHP-JqueryValidationHelper
==============================

I was implementing JQuery Validate plugin for the first time and quickly realized that it required adding all the same validation rules that are in the Cake models. Since that seemed to violate DRY, I came up with a helper that scans the model validation rules and creates the equivalent validation rules for the JQuery plugin and stores them as a string in the dialog class item.

First, include JQuery and the Validate plugin in your layout. There is also an 'additional-methods' file that defines a few extra validation rules. You also need the metadata plugin to decode the string with the validation rules.

```
echo $this->Html->script('jquery-1.7.2.min');
echo $this->Html->script('jquery.validate.min');
echo $this->Html->script('additional-methods.js');
echo $this->Html->script('jquery.metadata');
```

Then add the helper in your controller

```
public $helpers = array( 'Html','Form', 'Js' => array('Jquery'), 'JqueryValidation' );
```

When you create a form you need to provide a special class that is used in the javascript to detect which forms to validate:

```
echo $this->Form->create('Order', array('class' => 'jquery-validation'));
```

Or better yet, use the two methods to create Bootstrap formatted forms

```
echo $this->JqueryValidation->createVertical('MyModel');
```

or

```
echo $this->JqueryValidation->createHorizontal('MyModel');
```

and then the error messages should display either below or to the right of the input fields.  Remember to download Twitter Bootstrap (css and js) separately and add it to your layou!

Then for each element that you want to validate, use the helper function for the input:

```
echo $this->JqueryValidation->input('serial', $options);
```

This function just calls the Form->input after modifying the options array.

Here is the code for the helper. This is still a work in progress so there are a couple of Cake validation rules that I don't have working, so feel free to update or add new rules. The good news is the basic validation rules are covered with very little additional work. Note that it only works for multiple rules per field format.