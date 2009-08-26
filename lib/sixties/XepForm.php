<?php
/**
 * This file is part of Sixties, a set of classes extending XMPPHP, the PHP XMPP library from Nathanael C Fritz
 *
 * Copyright (C) 2009  Clochix.net
 *
 * Sixties is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Sixties is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Sixties; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   Library
 * @package    Sixties
 * @subpackage Xep
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * Common classes
 */
require_once dirname(dirname(__FILE__)) . '/bb/BbCommon.php';

/**
 * XepForm : implement client-side XEP 0004 : data forms
 *
 * @category   Library
 * @package    Sixties
 * @subpackage Xep
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class XepForm
{
    /**
     * @var string the form type
     */
    protected $type  = 'submit';
    /**
     * @var string the form title
     */
    protected $title = '';
    /**
     * @var array
     */
    protected $instructions = array();
    /**
     * @var array
     */
    protected $fields = array();
    /**
     * @var string string representation of fields
     */
    protected $fieldsTxt = '';

    const FORM_TYPE_FORM   = 'form';
    const FORM_TYPE_SUBMIT = 'submit';
    const FORM_TYPE_CANCEL = 'cancel';
    const FORM_TYPE_RESULT = 'result';

    /**
     * Create a new form
     *
     * @param string $type  the type
     * @param string $title the title
     *
     * @return void
     */
    public function __construct($type = null, $title = null) {
        $this->type  = ($type ? $type : 'submit');
        $this->title = $title;
    }

    /**
     * Load a Form from its XML representation and return a new form
     *
     * @param XMPPHP_XMLObj $xml the object to load
     *
     * @return XepForm
     *
     * @TODO : manage reported and item tags
     */
    static function load(XMPPHP_XMLObj $xml) {
        if ($xml->name != 'x' && $xml->hasSub('x')) $xml = $xml->sub('x');
        if ($xml->name != 'x') throw new XMPPHP_Exception("Xep_Form::load : wrong message : " . $xml->name);
        $type = $xml->attrs['type'];
        $form = new XepForm($type);
        foreach ($xml->subs as $sub) {
            if ($sub->ns == 'jabber:x:data') {
                switch ($sub->name) {
                case 'title':
                    $form->setTitle($sub->data);
                    break;
                case 'instructions':
                    $form->addInstructions($sub->data);
                    break;
                case 'field':
                    $form->addField(XepFormField::load($sub));
                    break;
                default:
                    BbLogger::get()->log("Unknown form element {$sub->name}", BbLogger::WARNING, 'XepForm');
                }
            }
        }

        return $form;
    }

    /**
     * Return the form as an XML string
     *
     * @return string
     */
    public function __toString() {
        $res = '';
        if ($this->title) $res .= "<title>{$this->title}</title>";
        if (count($this->instructions) > 0) {
            foreach ($this->instructions as $instruction) {
                $res .= "<instruction>$instruction</instruction>";
            }
        }
        $res .= $this->fieldsTxt;
        if (count($this->fields) > 0) {
            foreach ($this->fields as $field) {
                $res .= (string)$field;
            }
        }
        $res = "<x xmlns='jabber:x:data' type=\"{$this->type}\">$res</x>";
        return $res;
    }

    /************************************************************************************
     *
     * Getters and setters
     *
     ************************************************************************************/
    /**
     * Get the title of the form
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }
    /**
     * Set the title of the form
     *
     * @param string $title the title
     *
     * @return Xep_Form $this
     */
    public function setTitle($title) {
        $this->title = (string)$title;
        return $this;
    }
    /**
     * Get the instructions
     *
     * @return array
     */
    public function getInstructions() {
        return $this->instructions;
    }
    /**
     * Set the instructions of the form
     *
     * @param array $instructions the instructions
     *
     * @return Xep_Form $this
     */
    public function setInstructions($instructions) {
        if (is_array($instructions)) {
            $this->instructions = $instructions;
        }
        return $this;
    }
    /**
     * Add an instructions to the form
     *
     * @param string $instructions the instruction
     *
     * @return Xep_Form $this
     */
    public function addInstructions($instructions) {
        $this->instructions[] = (string) $instructions;
        return $this;
    }
    /**
     * Get the fields
     *
     * @return array of XepFormField
     */
    public function getFields() {
        return $this->fields;
    }
    /**
     * Get a field
     *
     * @param string $var the field var
     *
     * @return XepFormField
     */
    public function getField($var) {
        return $this->fields[$var];
    }
    /**
     * Add a field to the form
     *
     * @param XepFormField $field the field
     *
     * @return Xep_Form $this
     */
    public function addField(XepFormField $field) {
        $this->fields[$field->getVar()] = $field;
        return $this;
    }

    /**
     * Add a FORM_TYPE field
     *
     * @param string $type the type of the form
     *
     * @return $this
     */
    public function addFormtype($type) {
        $this->addField(new XepFormField('FORM_TYPE', $type, 'hidden'));
        return $this;
    }
}

/**
 * XepFormField : a form field
 *
 * @category   Library
 * @package    Sixties
 * @subpackage Xep
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class XepFormField
{
    /**
     * @var string
     */
    protected $label    = '';
    /**
     * @var string
     */
    protected $type     = '';
    /**
     * @var string
     */
    protected $var      = '';
    /**
     * @var string
     */
    protected $desc     = '';
    /**
     * @var boolean
     */
    protected $required = false;
    /**
     * @var array
     */
    protected $values   = array();
    /**
     * @var array
     */
    protected $options  = array();

    const FIELD_TYPE_BOOLEAN     = 'boolean';
    const FIELD_TYPE_FIXED       = 'fixed';
    const FIELD_TYPE_HIDDEN      = 'hidden';
    const FIELD_TYPE_JIDMULTI    = 'jid-multi';
    const FIELD_TYPE_JIDSINGLE   = 'jid-single';
    const FIELD_TYPE_LISTMULTI   = 'list-multi';
    const FIELD_TYPE_LISTSINGLE  = 'list-single';
    const FIELD_TYPE_TEXTMULTI   = 'text-multi';
    const FIELD_TYPE_TEXTPRIVATE = 'text-private';
    const FIELD_TYPE_TEXTSINGLE  = 'text-single';

    /**
     * Create a new form field
     *
     * @param string  $var      name of the field
     * @param mixed   $value    value (single value or array)
     * @param string  $type     type
     * @param boolean $required is the field required ?
     * @param string  $label    label for the field
     * @param string  $desc     description for the field
     *
     * @return void
     */
    public function __construct($var, $value = null, $type = null, $required = false, $label = '', $desc = '') {
        $this->var      = $var;
        $this->type     = $type;
        $this->required = $required;
        $this->label    = $label;
        $this->desc     = $desc;
        if ($value !== null) {
            if (is_array($value)) foreach ($value as $val) $this->addValue($val);
            else $this->addValue($value);
        }
    }

    /**
     * Load a form field from its XML representation and return the field
     *
     * @param XMPPHP_XMLObj $xml the object to load
     *
     * @return XepFormField
     */
    static function load(XMPPHP_XMLObj $xml) {
        $field = new XepFormField($xml->attrs['var']);
        $field->setLabel($xml->attrs['label']);
        $field->setType($xml->attrs['type']);
        if ($xml->hasSub('desc')) $field->setDesc($xml->sub('desc')->data);
        if ($xml->hasSub('required')) $field->setRequired();
        foreach ($xml->subs as $sub) {
            if ($sub->ns == 'jabber:x:data') {
                switch ($sub->name) {
                case 'value':
                    $field->addValue($sub->data);
                    break;
                case 'option':
                    $field->addOption(array('label' => $sub->attrs['label'], 'value' => $sub->sub('value')->data));
                    break;
                default:
                    BbLogger::get()->log("Unknown form field element {$sub->name}", BbLogger::WARNING, 'XepForm');
                }
            }
        }

        return $field;
    }
    /**
     * Return the field as an XML string
     *
     * @return string
     */
    public function __toString() {
        $res = '';
        if ($this->desc) $res .= "<desc>{$this->desc}</desc>";
        if ($this->required) $res .= "<required />";
        if (count($this->values) > 0) {
            foreach ($this->values as $value) {
                $res .= "<value>$value</value>";
            }
        }
        if (count($this->options) > 0) {
            foreach ($this->options as $option) {
                $label = (empty($option['label']) ? '' : " label=\"{$option['label']}\"");
                $res .= "<option $label><value>{$option['value']}</value></option>";
            }
        }
        $res = "<field label=\"{$this->label}\" type=\"{$this->type}\" var=\"{$this->var}\">$res</field>";
        return $res;
    }
    /************************************************************************************
     *
     * Getters and setters
     *
     ************************************************************************************/
    /**
     * Get the field label
     *
     * @return string
     */
    public function getLabel() { return $this->label; }
    /**
     * Set the field label
     *
     * @param string $label the label
     *
     * @return XepFormField this
     */
    public function setLabel($label) { $this->label = $label; return $this;}
    /**
     * Get the field type
     *
     * @return string
     */
    public function getType() { return $this->type; }
    /**
     * Set the field type
     *
     * @param string $type the field type
     *
     * @return XepFormField this
     */
    public function setType($type) { $this->type = $type; return $this;}
    /**
     * Get the field name
     *
     * @return string
     */
    public function getVar() { return $this->var; }
    /**
     * Set the field name
     *
     * @param string $var the name
     *
     * @return XepFormField this
     */
    public function setVar($var) { $this->var = $var; return $this;}
    /**
     * Get the field description
     *
     * @return string
     */
    public function getDesc() { return $this->desc; }
    /**
     * Set the field description
     *
     * @param string $desc the description
     *
     * @return XepFormField this
     */
    public function setDesc($desc) { $this->desc = $desc; return $this;}
    /**
     * Is the field required ?
     *
     * @return boolean
     */
    public function getRequired() { return $this->required; }
    /**
     * Set if the field is required
     *
     * @param boolean $required the required value
     *
     * @return XepFormField this
     */
    public function setRequired($required = true) { $this->required = $required; return $this;}
    /**
     * Get the field options
     *
     * @return array
     */
    public function getOptions() { return $this->options; }
    /**
     * Set the options of he field
     *
     * @param array $options array arrays of label and value
     *
     * @return XepFormField this
     */
    public function setOptions($options) {
        if (!is_array($options)) {
            throw new Exception("Wrong options");
        }
        foreach ($options as $option) {
            $this->addOption($option);
        }
        return $this;
    }
    /**
     * Add an option to the field
     *
     * @param array $option array of label and value
     *
     * @return XepFormField this
     */
    public function addOption($option){
        if (!is_array($option) || !isset($option['label']) || !isset($option['value'])) {
            throw new Exception("Wrong option");
        }
        $this->options[] = $option;
        return $this;
    }
    /**
     * Get the field values
     *
     * @return array
     */
    public function getValues() { return $this->values; }
    /**
     * Add a value
     *
     * @param mixed $val the value
     *
     * @return XepFormField this
     */
    public function addValue($val) {
        // cf XEP-0004 3.3 : Data provided for fields of type "text-multi" SHOULD NOT contain any newlines.
        // Instead, the application SHOULD split the data into multiple strings (based on the newlines inserted by the platform),
        // then specify each string as the XML character data of a distinct <value/> element
        if ($this->type == self::FIELD_TYPE_TEXTMULTI || $this->type == '') {
            $vals = explode("\n", $val);
            foreach ($vals as $v) {
                $this->values[] = $v;
            }
        } else {
            $this->values[] = $val;
        }
        return $this;
    }
    /**
     * Get the value of the field
     *
     * @return mixed
     *
     * @throws XMPPHP_Exception if field has no value or more than one value
     */
    public function getValue() {
        if (count($this->values) == 1) return reset($this->values);
        else if (count($this->values) == 0) throw new XMPPHP_Exception("{$this->var} has no value");
        else throw new XMPPHP_Exception("{$this->var} has more than one value");
    }
}