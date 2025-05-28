<?php

declare(strict_types=1);

namespace phpformbuilder\traits;

trait Elements
{
    /**
     * Adds an input element to the form.
     *
     * @param string $type  The type of the input element.
     *                      Accepts all input html5 types except checkbox and radio :
     *                      button, color, date, datetime, datetime-local,
     *                      email, file, hidden, image, month, number, password,
     *                      range, reset, search, submit, tel, text, time, url, week
     * @param string $name  The name of the input element.
     * @param string $value (Optional) The default value of the input element.
     * @param string $label (Optional) The label of the input element.
     * @param string $attr  (Optional) Any additional attributes for the input element.
     *                      Can be any HTML input attribute or js event.
     *                      attributes must be listed separated with commas.
     *                      If you don't specify any ID as attr, the ID will be the input's name.
     *                      Example : class=my-class,placeholder=My Text,onclick=alert(\'clicked\');
     * @return \phpformbuilder\Form
     */
    public function addInput(string $type, string $name, string $value = '', string $label = '', string $attr = ''): \phpformbuilder\Form
    {
        if ($type == 'file') {
            $this->has_file = true;
        }
        $attr          = $this->getAttributes($attr); // returns linearised attributes (with ID)
        $array_values  = $this->getID($name, $attr); // if $attr contains no ID, field ID will be $name.
        $id            = $array_values['id'];
        $attr          = $array_values['attributs']; // if $attr contains an ID, we remove it.
        if ($type != 'hidden') {
            $attr = $this->addElementClass('input', $name, $attr);
        }
        if ($this->isGrouped($name)) {
            $attr = $this->addClass('fv-group', $attr);
        }
        if (in_array($name, $this->fields_with_helpers)) {
            $attr = $this->addAttribute('aria-describedby', $name . '-helper', $attr);
        }
        $value         = $this->getValue($name, $value);
        $start_wrapper = '';
        $end_wrapper   = '';
        $start_label   = '';
        $end_label     = '';
        $start_col     = '';
        $end_col       = '';
        $element       = '';
        $has_addon     = \array_key_exists($name, $this->fields_with_addons);
        $has_icon      = \array_key_exists($name, $this->fields_with_icons);
        // auto-add date/time pickers in material forms
        if ($this->framework == 'material') {
            if (strpos($attr, 'datepicker') !== false) {
                $this->addPlugin('material-datepicker', '#' . $id);
            } elseif (strpos($attr, 'timepicker') !== false) {
                $this->addPlugin('material-timepicker', '#' . $id);
            }
        }
        if ($type == 'hidden' && strpos($attr, 'data-signature-pad') === false) {
            $this->hidden_fields .= '<input name="' . $name . '" type="hidden" value="' . $value . '" ' . $attr . '>';
        } else {
            // form-group wrapper
            $start_wrapper = $this->setInputGroup($name, 'start', 'elementsWrapper');
            $start_wrapper .= $this->addErrorWrapper($name, 'start');

            // label
            if (!empty($label)) {
                if ($this->hasLabelWrapper()) {
                    $start_label  .= $this->getLabelCol('start');
                }
                $label_class = $this->getLabelClass();
                if (strpos($attr, 'form-control-sm')) {
                    $label_class = $this->addClass('col-form-label-sm', $label_class);
                } elseif (strpos($attr, 'form-control-lg')) {
                    $label_class = $this->addClass('col-form-label-lg', $label_class);
                }
                $start_label .= '<label for="' . $id . '"' . $label_class . '>' . $this->getRequired($label, $attr);
                $end_label = '</label>';
                if ($this->hasLabelWrapper()) {
                    $end_label   .= $this->getLabelCol('end');
                }
            }

            // daterange picker
            if (strpos($attr, 'data-litepick') !== false) {
                $this->addPlugin('litepicker', 'input[data-litepick=\'true\']', 'default');
            }

            $start_col .= $this->getElementCol('start', 'input', $label); // col-sm-8
            $element .= $this->getErrorInputWrapper($name, $label, 'start'); // has-error
            if ($this->framework === 'bulma') {
                $element .= $this->startElementCustomWrapper($name, 'bulma_control');
            }
            $element .= $this->getHtmlElementContent($name, 'before', 'outside_wrapper');
            if (isset($this->input_wrapper[$name])) {
                $element .= $this->getElementWrapper($this->input_wrapper[$name], 'start'); // input-group (icons, addons)
            }
            // icons, addons, custom html with addHtml()
            $html_before = $this->getHtmlElementContent($name, 'before', 'inside_wrapper');
            $html_after = $this->getHtmlElementContent($name, 'after', 'inside_wrapper');
            if ($this->framework == 'material') {
                if ($has_addon) {
                    $attr = $this->addClass('has-addon-' . $this->fields_with_addons[$name], $attr);
                } elseif ($has_icon) {
                    $attr = $this->addClass('has-icon-' . $this->fields_with_icons[$name], $attr);
                }
            } elseif ($this->framework == 'tailwind') {
                if ($has_addon) {
                    $clazz = 'pl-10';
                    $replace = 'rounded-r-';
                    if ($this->fields_with_addons[$name] == 'after') {
                        $clazz = 'pr-10';
                        $replace = 'rounded-l-';
                    }
                    $attr = \str_replace('rounded-', $replace, $attr);
                    $attr = $this->addClass($clazz, $attr);
                }
                if ($has_icon) {
                    $clazz = 'pl-10';
                    if ($this->fields_with_icons[$name] == 'after') {
                        $clazz = 'pr-10';
                    }
                    $attr = $this->addClass($clazz, $attr);
                }
            }
            $element .= $html_before;
            $aria_label = $this->getAriaLabel($label, $attr);
            if ($this->framework === 'foundation' && ($has_addon || $has_icon)) {
                $attr = $this->addClass('input-group-field', $attr);
            } elseif ($this->framework == 'bulma' && $has_addon) {
                $element .= '<div class="control is-expanded">';
            }
            $element .= '<input id="' . $id . '" name="' . $name . '" type="' . $type . '" value="' . $value . '" ' . $attr . $aria_label . '>';
            if ($type === 'hidden' && strpos($attr, 'data-signature-pad') !== false) {
                $element .= '<canvas id="' . $id . '-canvas" class="signature-pad-canvas"></canvas>';
                $this->addPlugin('signature-pad', '#' . $id, 'default');
            }
            if ($this->framework == 'bulma' && $has_addon) {
                $element .= '</div>';
            }
            $element .= $html_after;
            if (isset($this->input_wrapper[$name])) {
                $element .= $this->getElementWrapper($this->input_wrapper[$name], 'end'); // end input-group
            }
            $element .= $this->getHtmlElementContent($name, 'after', 'outside_wrapper'); // -------------------Desired Username
            if ($this->framework === 'bulma') {
                $element .= $this->endElementCustomWrapper();
            }
            $element .= $this->getErrorInputWrapper($name, $label, 'end'); // end has-error
            $element .= $this->getError($name); // -------------------Error txt
            $end_col .= $this->getElementCol('end', 'input', $label); // end col-sm-8
            $end_wrapper .= $this->addErrorWrapper($name, 'end');
            $end_wrapper .= $this->setInputGroup($name, 'end', 'elementsWrapper'); // end form-group
            // output
            $this->html .= $this->outputElement($start_wrapper, $end_wrapper, $start_label, $end_label, $start_col, $end_col, $element, $this->options['wrapElementsIntoLabels']);

            //  if bootstrap-input-spinner enabled
            if (strpos($attr, 'data-input-spinner') !== false) {
                $xml_node = 'default';
                if ($this->framework == 'bs4') {
                    $xml_node = 'bs4';
                }
                $this->addPlugin('bootstrap-input-spinner', '#' . $id, $xml_node);
            }

            //  if intl-tel-input enabled
            if (strpos($attr, 'data-intphone') !== false) {
                $this->addPlugin('intl-tel-input', '#' . $id);

                // add the intl-tel-input hidden field
                $this->addInput('hidden', $id . '-full-phone');
            }

            //  if colorpicker enabled
            if (strpos($attr, 'colorpicker') !== false) {
                $theme = 'classic';
                if (preg_match('`data-theme="(monolith|nano)"`', $attr, $out)) {
                    $theme = $out[1];
                }
                $this->addPlugin('colorpicker', '#' . $id, 'default', ['theme' => $theme]);
            }

            //  if js-captcha enabled
            if (strpos($attr, 'jCaptcha') !== false) {
                $this->addInput('hidden', 'jcaptcha-server-side-verification', '', '');
                $this->addPlugin('js-captcha', '.jCaptcha', 'default', ['formId' => $this->form_ID, 'errorClass' => $this->options['elementsErrorClass'], 'helperStart' => $this->helper_start_wrapper, 'helperEnd' => $this->helper_end_wrapper]);
            }
        }
        $this->registerField($name, $attr);

        return $this;
    }

    /**
     * Creates an input with fileuploader plugin.
     *
     * The fileuploader plugin generates complete html, js and css code.
     * You'll just have to call printIncludes('css') and printIncludes('js')
     * where you wants to put your css/js codes (generaly in <head> and just before </body>).
     *
     * @param string $type              The type of the input, usualy 'file'
     * @param string $name              The upload field name.
     *                                  Use an array (ex : name[]) to allow multiple files upload
     * @param string $value             (Optional) The input default value
     * @param string $label             (Optional) The input label
     * @param string $attr              (Optional) Can be any HTML input attribute or js event.
     *                                  attributes must be listed separated with commas.
     *                                  If you don't specify any ID as attr, the ID will be the input's name.
     *                                  Example : class=my-class,placeholder=My Text,onclick=alert(\'clicked\');.
     * @param array  $fileUpload_config An associative array containing :
     *                                  'xml'           [string]       => (Optional) The xml node where your plugin code is
     *                                                                    in plugins-config/fileuploader.xml
     *                                                                    Default: 'default'
     *                                  'uploader'      [string]       => (Optional) The PHP uploader file in phpformbuilder/plugins/fileuploader/[xml-node-name]/php/
     *                                                                    Default: 'ajax_upload_file.php'
     *                                  'upload_dir'    [string]       => (Optional) the directory to upload the files.
     *                                                                    Relative to phpformbuilder/plugins/fileuploader/default/php/ajax_upload_file.php
     *                                                                    Default: '../../../../../file-uploads/' ( = [project root]/file-uploads)
     *                                  'limit'         [null|Number]  => (Optional) The max number of files to upload
     *                                                                    Default: 1
     *                                  'extensions'    [null|array]   => (Optional) Allowed extensions or file types
     *                                                                    example: ['jpg', 'jpeg', 'png', 'audio/mp3', 'text/plain']
     *                                                                    Default: ['jpg', 'jpeg', 'png', 'gif']
     *                                  'fileMaxSize'   [null|Number]  => (Optional) Each file's maximal size in MB,
     *                                                                    Default: 5
     *                                  'thumbnails'    [Boolean]      => (Optional) Defines Wether if the uploader creates thumbnails or not.
     *                                                                    Thumbnails paths and sizing is done in the plugin php uploader.
     *                                                                    Default: false
     *                                  'editor'        [Boolean]      => (Optional)  Allows the user to crop/rotate the uploaded image
     *                                                                    Default: false
     *                                  'width'         [null|Number]  => (Optional) The uploaded image maximum width in px
     *                                                                    Default: null
     *                                  'height'        [null|Number]  => (Optional) The uploaded image maximum height in px
     *                                                                    Default: null
     *                                  'crop'          [Boolean]      => (Optional) Defines Wether if the uploader crops the uploaded image or not.
     *                                                                    Default: false
     *                                  'debug'         [Boolean]      => (Optional) log the result in the browser's console
     *                                                                    and shows an error text on the page if the uploader fails to parse the json result.
     *                                                                    Default: false
     * @return \phpformbuilder\Form
     *
     */
    public function addFileUpload(string $name, string $value = '', string $label = '', string $attr = '', array  $fileUpload_config = [], array  $current_file = []): \phpformbuilder\Form
    {
        $this->has_file = true;
        $attr           = $this->getAttributes($attr); // returns linearised attributes (with ID)
        $array_values   = $this->getID($name, $attr); // if $attr contains no ID, field ID will be $name.
        $attr           = $array_values['attributs']; // if $attr contains an ID, we remove it.
        $attr           = $this->addElementClass('input', $name, $attr);
        $value          = $this->getValue($name, $value);
        $start_wrapper  = '';
        $end_wrapper    = '';
        $start_label    = '';
        $end_label      = '';
        $start_col      = '';
        $end_col        = '';
        $element        = '';

        /* hidden field which will be posted in JSON with the uploaded file names. */
        $attr .= ' data-fileuploader-listInput="' . $name . '"';

        /* adding plugin */

        $default_config = [
            'xml'           => 'default',
            'uploader'      => 'ajax_upload_file.php',
            'upload_dir'    => '../../../../../file-uploads/',
            'limit'         => 1,
            'extensions'    => ['jpg', 'jpeg', 'png', 'gif'],
            'file_max_size' => 5,
            'thumbnails'    => false,
            'editor'        => false,
            'width'         => null,
            'height'        => null,
            'crop'          => false,
            'debug'         => false
        ];

        $fileUpload_config = array_merge($default_config, $fileUpload_config);

        // replace boolean values for javascript
        $bool = ['thumbnails', 'editor', 'crop', 'debug'];
        foreach ($bool as $b) {
            if ($fileUpload_config[$b]) {
                $fileUpload_config[$b] = 'true';
            } else {
                $fileUpload_config[$b] = 'false';
            }
        }

        if (is_array($fileUpload_config['extensions'])) {
            $fileUpload_config['extensions'] = "['" . implode("', '", $fileUpload_config['extensions']) . "']";
        }

        // set session vars which will be controlled & added to the PHP uploader
        // (plugins/fileuploader/[uploader]/php/ajax_upload_file.php)
        $form_id = $this->form_ID;
        $_SESSION[$form_id]['upload_config']['uploader-' . $name] = [
            'limit'         => $fileUpload_config['limit'],
            'file_max_size' => $fileUpload_config['file_max_size'],
            'extensions'    => $fileUpload_config['extensions'],
            'upload_dir'    => $fileUpload_config['upload_dir']
        ];

        $hash = sha1($fileUpload_config['limit'] . $fileUpload_config['file_max_size'] . $fileUpload_config['extensions'] . $fileUpload_config['upload_dir']);

        $xml_replacements = [
            'limit'       => $fileUpload_config['limit'],
            'uploader'    => $fileUpload_config['uploader'],
            'uploadDir'   => $fileUpload_config['upload_dir'],
            'extensions'  => $fileUpload_config['extensions'],
            'fileMaxSize' => $fileUpload_config['file_max_size'],
            'thumbnails'  => $fileUpload_config['thumbnails'],
            'editor'      => $fileUpload_config['editor'],
            'debug'       => $fileUpload_config['debug'],
            'width'       => $fileUpload_config['width'],
            'height'      => $fileUpload_config['height'],
            'crop'        => $fileUpload_config['crop'],
            'index'       => $this->fileuploader_count,
            'hash'        => $hash,
            'formId'      => $this->form_ID,
            'PLUGINS_URL' => $this->plugins_url
        ];
        $this->addPlugin('fileuploader', '#uploader-' . $name, $fileUpload_config['xml'], $xml_replacements);

        // increment index
        $this->fileuploader_count++;

        // form-group wrapper
        $start_wrapper = $this->setInputGroup($name, 'start', 'elementsWrapper');
        $start_wrapper .= $this->addErrorWrapper($name, 'start');

        // label
        if (!empty($label)) {
            if ($this->hasLabelWrapper()) {
                $start_label  .= $this->getLabelCol('start');
            }
            $start_label .= '<label for="uploader-' . $name . '"' . $this->getLabelClass('fileinput') . '>';
            if (in_array(str_replace('[]', '', $name), array_keys($this->error_fields))) {
                $start_label .= '<span class="' . $this->options['textErrorClass'] . '">' . $this->getRequired($label, $attr) . '</span>';
            } else {
                $start_label .= $this->getRequired($label, $attr);
            }
            $end_label = '</label>';
            if ($this->hasLabelWrapper()) {
                $end_label   .= $this->getLabelCol('end');
            }
        }

        // input
        $start_col .= $this->getElementCol('start', 'input', $label); // col-sm-8
        $element .= $this->getErrorInputWrapper($name, $label, 'start'); // has-error
        if ($this->framework === 'bulma') {
            $element .= $this->startElementCustomWrapper($name, 'bulma_control');
        }
        $element .= $this->getHtmlElementContent($name, 'before', 'outside_wrapper');
        if (isset($this->input_wrapper[$name])) {
            $element .= $this->getElementWrapper($this->input_wrapper[$name], 'start'); // input-group
        }
        $element .= $this->getHtmlElementContent($name, 'before', 'inside_wrapper');
        $current_file_json_data = '';
        if (!empty($current_file) && is_array($current_file)) {
            if (isset($current_file[0])) {
                // if several files passed as array
                $json = '';
                foreach ($current_file as $cf) {
                    $json .= json_encode($cf) . ',';
                }
                $current_file = rtrim($json, ',');
            } else {
                $current_file = json_encode($current_file);
            }
            $current_file_json_data = ' data-fileuploader-files=\'[' . $current_file . ']\'';
        }
        $element .= '<input type="file" name="uploader-' . $name . '" id="uploader-' . $name . '"' . $attr . $current_file_json_data . '>';
        $element .= $this->getHtmlElementContent($name, 'after', 'inside_wrapper');
        if (isset($this->input_wrapper[$name])) {
            $element .= $this->getError($name, true);
            $element .= $this->getElementWrapper($this->input_wrapper[$name], 'end'); // end input-group
        }
        $element .= $this->getHtmlElementContent($name, 'after', 'outside_wrapper');
        if ($this->framework === 'bulma') {
            $element .= $this->endElementCustomWrapper();
        }
        $element .= $this->getErrorInputWrapper($name, $label, 'end'); // end has-error
        $element .= $this->getError($name);
        $end_col .= $this->getElementCol('end', 'input', $label); // end col-sm-8
        $end_wrapper .= $this->addErrorWrapper($name, 'end');
        $end_wrapper .= $this->setInputGroup($name, 'end', 'elementsWrapper'); // end form-group
        // output
        $this->html .= $this->outputElement($start_wrapper, $end_wrapper, $start_label, $end_label, $start_col, $end_col, $element, $this->options['wrapElementsIntoLabels']);
        $this->registerField($name, $attr);

        return $this;
    }

    /**
     * Adds textarea to the form
     * @param string $name  The textarea name
     * @param string $value (Optional) The textarea default value
     * @param string $label (Optional) The textarea label
     * @param string $attr  (Optional) Can be any HTML input attribute or js event.
     *                      attributes must be listed separated with commas.
     *                      If you don't specify any ID as attr, the ID will be the name of the textarea.
     *                      Example : cols=30, rows=4;
     * @return \phpformbuilder\Form
     */
    public function addTextarea(string $name, string $value = '', string $label = '', string $attr = ''): \phpformbuilder\Form
    {
        $attr          = $this->getAttributes($attr); // returns linearised attributes (with ID)
        $array_values  = $this->getID($name, $attr); // if $attr contains no ID, field ID will be $name.
        $id            = $array_values['id'];
        $attr          = $array_values['attributs']; // if $attr contains an ID, we remove it.
        $attr          = $this->addElementClass('textarea', $name, $attr);
        $start_wrapper = '';
        $end_wrapper   = '';
        $start_label   = '';
        $end_label     = '';
        $start_col     = '';
        $end_col       = '';
        $element       = '';
        if ($this->framework == 'material') {
            $attr         = $this->addClass("materialize-textarea", $attr);
        }
        $value        = $this->getValue($name, $value);
        // form-group wrapper
        $start_wrapper = $this->setInputGroup($name, 'start', 'elementsWrapper');
        $start_wrapper .= $this->addErrorWrapper($name, 'start');
        // label
        if (!empty($label)) {
            if ($this->hasLabelWrapper()) {
                $start_label  .= $this->getLabelCol('start');
            }
            $start_label .= '<label for="' . $id . '"' . $this->getLabelClass() . '>' . $this->getRequired($label, $attr);
            $end_label = '</label>';
            if ($this->hasLabelWrapper()) {
                $end_label   .= $this->getLabelCol('end');
            }
        }
        $start_col .= $this->getElementCol('start', 'textarea', $label);
        $element .= $this->getErrorInputWrapper($name, $label, 'start');
        if ($this->framework === 'bulma') {
            $element .= $this->startElementCustomWrapper($name, 'bulma_control');
        }
        $element .= $this->getHtmlElementContent($name, 'before');
        $aria_label = $this->getAriaLabel($label, $attr);
        $element .= '<textarea id="' . $id . '" name="' . $name . '" ' . $attr . $aria_label . '>' . $value . '</textarea>';
        $element .= $this->getHtmlElementContent($name, 'after');
        $element .= $this->getError($name);
        if ($this->framework === 'bulma') {
            $element .= $this->endElementCustomWrapper();
        }
        $element .= $this->getErrorInputWrapper($name, $label, 'end');
        $end_col .= $this->getElementCol('end', 'textarea', $label);
        $end_wrapper = $this->addErrorWrapper($name, 'end');
        $end_wrapper .= $this->setInputGroup($name, 'end', 'elementsWrapper'); // end form-group
        $this->html .= $this->outputElement($start_wrapper, $end_wrapper, $start_label, $end_label, $start_col, $end_col, $element, $this->options['wrapElementsIntoLabels']);
        $this->registerField($name, $attr);

        return $this;
    }

    /**
     * Adds option to the $select_name element
     *
     * IMPORTANT : Always add your options BEFORE creating the select element
     *
     * @param string $select_name The name of the select element
     * @param string $value       The option value
     * @param string $txt         The text that will be displayed
     * @param string $group_name  (Optional) the optgroup name
     * @param string $attr        (Optional) Can be any HTML input attribute or js event.
     *                            attributes must be listed separated with commas.
     *                            If you don't specify any ID as attr, the ID will be the name of the option.
     *                            Example : class=my-class
     * @return \phpformbuilder\Form
     */
    public function addOption(string $select_name, string $value, string $txt, string $group_name = '', string $attr = ''): \phpformbuilder\Form
    {
        $optionValues = ['value' => $value, 'txt' => $txt, 'attributs' => $attr];
        if (!empty($group_name)) {
            $this->option[$select_name][$group_name][] = $optionValues;
            if (!isset($this->group_name[$select_name])) {
                $this->group_name[$select_name] = [];
            }
            if (!in_array($group_name, $this->group_name[$select_name])) {
                $this->group_name[$select_name][] = $group_name;
            }
        } else {
            $this->option[$select_name][] = $optionValues;
        }

        return $this;
    }

    /**
     * Adds a select element
     *
     * IMPORTANT : Always add your options BEFORE creating the select element
     *
     * @param string $select_name        The name of the select element
     * @param string $label              (Optional) The select label
     * @param string $attr               (Optional)  Can be any HTML input attribute or js event.
     *                                   attributes must be listed separated with commas.
     *                                   If you don't specify any ID as attr, the ID will be the input's name.
     *                                   Example : class=my-class
     * @param bool   $displayGroupLabels (Optional) True or false.
     *                                   Default is true.
     * @return \phpformbuilder\Form
     */
    public function addSelect(string $select_name, string $label = '', string $attr = '', bool $displayGroupLabels = true): \phpformbuilder\Form
    {
        $attr          = $this->getAttributes($attr); // returns linearised attributes (with ID)
        $array_values  = $this->getID($select_name, $attr); // if $attr contains no ID, field ID will be $select_name.
        $id            = $array_values['id'];
        $attr          = $array_values['attributs']; // if $attr contains an ID, we remove it.
        $is_uikit_slimselect = $this->framework === 'uikit' && strpos($attr, 'data-slimselect') !== false;
        if ($this->framework !== 'material' && !$is_uikit_slimselect) { // don't add select class if material
            $attr          = $this->addElementClass('select', $select_name, $attr);
        }
        $start_wrapper = '';
        $end_wrapper   = '';
        $start_label   = '';
        $end_label     = '';
        $start_col     = '';
        $end_col       = '';
        $element       = '';

        // form-group wrapper
        $start_wrapper = $this->setInputGroup($select_name, 'start', 'elementsWrapper');
        $start_wrapper .= $this->addErrorWrapper($select_name, 'start');
        // label
        if (!empty($label)) {
            if ($this->hasLabelWrapper()) {
                $start_label  .= $this->getLabelCol('start');
            }
            $label_class = $this->getLabelClass();
            if (strpos($attr, 'form-control-sm')) {
                $label_class = $this->addClass('col-form-label-sm', $label_class);
            } elseif (strpos($attr, 'form-control-lg')) {
                $label_class = $this->addClass('col-form-label-lg', $label_class);
            }
            $start_label .= '<label for="' . $id . '"' . $label_class . '>' . $this->getRequired($label, $attr);
            $end_label = '</label>';
            if ($this->hasLabelWrapper()) {
                $end_label   .= $this->getLabelCol('end');
            }
        }
        $start_col .= $this->getElementCol('start', 'select', $label);
        $element .= $this->getErrorInputWrapper($select_name, $label, 'start');
        if ($this->framework === 'bulma') {
            $element .= $this->startElementCustomWrapper($select_name, 'bulma_control');
        }
        $element .= $this->getHtmlElementContent($select_name, 'before', 'outside_wrapper');
        // icons, addons, custom html with addHtml()
        $html_before = $this->getHtmlElementContent($select_name, 'before', 'inside_wrapper');
        $html_after = $this->getHtmlElementContent($select_name, 'after', 'inside_wrapper');
        if (isset($this->input_wrapper[$select_name])) {
            $element .= $this->getElementWrapper($this->input_wrapper[$select_name], 'start'); // input-group (icons, addons)
        }
        if ($this->framework == 'material') {
            if (strpos($html_before, 'icon-before')) {
                $attr = $this->addClass('has-icon-before', $attr);
            }
            if (strpos($html_after, 'icon-after')) {
                $attr = $this->addClass('has-icon-after', $attr);
            }
            if (strpos($html_before, 'addon-before')) {
                $attr = $this->addClass('has-addon-before', $attr);
            }
            if (strpos($html_after, 'addon-after')) {
                $attr = $this->addClass('has-addon-after', $attr);
            }
            if (strpos($attr, 'selectpicker') !== false || strpos($attr, 'data-slimselect') !== false || strpos($attr, 'select2') !== false) {
                $attr = $this->addClass('browser-default no-autoinit', $attr);
            }
        }
        if (strpos($attr, 'selectpicker') !== false) {
            if (!strpos('data-icon-base', $attr)) {
                $attr .= ' data-icon-base="fa"';
            }
            if (!strpos('data-tick-icon', $attr)) {
                $attr .= ' data-tick-icon="fa-check"';
            }
            if (!strpos('data-show-tick', $attr)) {
                $attr .= ' data-show-tick=true';
            }
        }
        $element .= $html_before;
        if ($this->framework === 'bulma') {
            $element .= $this->startElementCustomWrapper($select_name, 'bulma_control');
            $custom_wrapper_conf = 'bulma_select';
            if (strpos($attr, 'multiple')) {
                $custom_wrapper_conf = 'bulma_select_multiple';
            }
            $element .= $this->startElementCustomWrapper($select_name, $custom_wrapper_conf);
        }
        $aria_label = $this->getAriaLabel($label, $attr);
        $element .= '<select id="' . $id . '" name="' . $select_name . '" ' . $attr . $aria_label . '>';
        $nbreOptions = 0;
        if (isset($this->option[$select_name])) {
            $nbreOptions = count($this->option[$select_name]);
        }
        for ($i = 0; $i < $nbreOptions; $i++) {
            if (isset($this->option[$select_name][$i])) {
                $txt         = $this->option[$select_name][$i]['txt'];
                $value       = $this->option[$select_name][$i]['value'];
                $option_attr = $this->option[$select_name][$i]['attributs'];
                $option_attr = $this->getAttributes($option_attr);
                $element     .= '<option value="' . $value . '"';
                $option_attr = $this->getCheckedOrSelected($select_name, $value, $option_attr, 'select');
                $element     .= ' ' . $option_attr . '>' . $txt . '</option>';
            }
        }
        if (isset($this->group_name[$select_name])) {
            foreach ($this->group_name[$select_name] as $group_name) {
                $nbreOptions = count($this->option[$select_name][$group_name]);
                $groupLabel = '';
                if ($displayGroupLabels) {
                    $groupLabel = ' label="' . $group_name . '"';
                }
                $element .= '<optgroup' . $groupLabel . '>';
                for ($i = 0; $i < $nbreOptions; $i++) {
                    $txt         = $this->option[$select_name][$group_name][$i]['txt'];
                    $value       = $this->option[$select_name][$group_name][$i]['value'];
                    $option_attr = $this->option[$select_name][$group_name][$i]['attributs'];
                    $option_attr = $this->getAttributes($option_attr);
                    $element     .= '<option value="' . $value . '"';
                    $option_attr = $this->getCheckedOrSelected($select_name, $value, $option_attr, 'select');
                    $element     .= ' ' . $option_attr . '>' . $txt . '</option>';
                }
                $element .= '</optgroup>';
            }
        }
        $element .= '</select>';

        if ($this->framework === 'bulma') {
            $element .= $this->endElementCustomWrapper();
            $element .= $this->endElementCustomWrapper();
        }
        $element .= $html_after;
        if ($this->framework === 'bulma') {
            $element .= $this->endElementCustomWrapper();
        }
        if (isset($this->input_wrapper[$select_name])) {
            $element .= $this->getElementWrapper($this->input_wrapper[$select_name], 'end');
        }
        $element .= $this->getHtmlElementContent($select_name, 'after', 'outside_wrapper');
        $element .= $this->getErrorInputWrapper($select_name, $label, 'end');
        $element .= $this->getError($select_name);
        $end_col .= $this->getElementCol('end', 'select', $label);
        $end_wrapper = $this->addErrorWrapper($select_name, 'end');
        $end_wrapper .= $this->setInputGroup($select_name, 'end', 'elementsWrapper'); // end form-group
        if (strpos($attr, 'selectpicker') !== false) {
            $bootstrap_version = 5;
            if ($this->framework == 'bs4') {
                $bootstrap_version = 4;
            }
            $this->addPlugin('bootstrap-select', '#' . $this->form_ID . ' select[name=\'' . $select_name . '\']', 'default', ['bootstrapversion' => $bootstrap_version]);
        } elseif (strpos($attr, 'data-slimselect') !== false) {
            $this->addPlugin('slimselect', '#' . $this->form_ID . ' select[data-slimselect=true]');
        } elseif (strpos($attr, 'select2') !== false) {
            $theme = 'clean';
            $language = 'en';
            if ($this->framework == 'material') {
                $theme = 'material';
            }
            if (preg_match('`data-language="([^"]+)`', $attr, $out)) {
                $language = $out[1];
            }
            $this->addPlugin('select2', '.select2', 'default', ['theme' => $theme, 'language' => $language]);
        }

        // output
        $this->html .= $this->outputElement($start_wrapper, $end_wrapper, $start_label, $end_label, $start_col, $end_col, $element, $this->options['wrapElementsIntoLabels']);
        $this->registerField($select_name, $attr);

        return $this;
    }

    /**
     * adds a country select list with flags
     * @param string  $select_name
     * @param string $label        (Optional) The select label
     * @param string $attr         (Optional)  Can be any HTML input attribute or js event.
     *                             attributes must be listed separated with commas.
     *                             If you don't specify any ID as attr, the ID will be the name of the select.
     *                             Example : class=my-class
     * @param array  $user_options (Optional) :
     *                             plugin          : slimselect | select2 | bootstrap-select    Default: 'slimselect'
     *                             lang            : MUST correspond to one subfolder of [$this->plugins_url]countries/country-list/country/cldr/
     *                             *** for example 'en', or 'fr_FR'                 Default : 'en'
     *                             flags           : true or false.                 Default : true
     *                             *** displays flags into option list
     *                             flag_size       : 16 or 32                       Default : 32
     *                             return_value    : 'name' or 'code'               Default : 'name'
     *                             *** type of the value that will be returned
     * @return \phpformbuilder\Form
     */
    public function addCountrySelect(string $select_name, string $label = '', string $attr = '', array $user_options = []): \phpformbuilder\Form
    {

        /* define options*/

        $options = [
            'plugin'       => 'slimselect',
            'lang'         => 'en',
            'flags'        => true,
            'flag_size'    => 32,
            'return_value' => 'name',
        ];
        $options = \array_merge($options, $user_options);
        $class = '';
        if (preg_match('`class(\s)?=(\s)?([^,]+)`', $attr, $out)) {
            $class = $out[3] . ' ';
        }
        if ($options['plugin'] == 'select2') {
            $class .= 'select2country ' . $this->options['elementsClass'];
        } elseif ($options['plugin'] == 'bootstrap-select') {
            $class .= 'selectpicker ' . $this->options['elementsClass'];
        }
        if ($this->framework == 'material') {
            $class .= ' no-autoinit';
        }
        $xml_node = 'default';
        if ($options['flags']) {
            $class .= ' f' . $options['flag_size'];
            $xml_node = 'countries-flags-' . $options['flag_size'];
        }
        $data_attr = '';
        if ($options['plugin'] == 'bootstrap-select') {
            $data_attr .= ' data-live-search="true" data-show-tick="true"';
        } elseif ($options['plugin'] == 'slimselect') {
            $data_attr .= ' data-slimselect=true';
            if ($options['flags']) {
                $data_attr .= ' data-flag-size=' . $options['flag_size'];
            }
        }
        $countries    = include_once $this->plugins_path . 'countries/country-list/country/cldr/' . $options['lang'] . '/country.php';
        $attr         = $this->getAttributes($attr); // returns linearised attributes (with ID)
        $array_values = $this->getID($select_name, $attr); // if $attr contains no ID, field ID will be $select_name.
        $id           = $array_values['id'];
        $attr         = $array_values['attributs']; // if $attr contains an ID, we remove it.
        $attr         = $this->removeAttribute('class', $attr);
        $start_wrapper = '';
        $end_wrapper   = '';
        $start_label   = '';
        $end_label     = '';
        $start_col     = '';
        $end_col       = '';
        $element       = '';
        $start_wrapper = $this->setInputGroup($select_name, 'start', 'elementsWrapper');
        $start_wrapper .= $this->addErrorWrapper($select_name, 'start');
        if ($options['plugin'] == 'slimselect') {
            $this->addPlugin('slimselect', '#' . $id, 'default', ['pluginsUrl' => $this->plugins_url]);
        } elseif ($options['plugin'] == 'select2') {
            $theme = 'clean';
            if ($this->framework == 'material') {
                $theme = 'material';
            }
            $this->addPlugin('select2', '#' . str_replace('[]', '', $select_name), $xml_node, ['theme' => $theme]);
        } else {
            $this->addPlugin('bootstrap-select', '.selectpicker', $xml_node);
        }
        // label
        if (!empty($label)) {
            if ($this->hasLabelWrapper()) {
                $start_label  .= $this->getLabelCol('start');
            }
            $start_label .= '<label for="' . $id . '"' . $this->getLabelClass() . '>' . $this->getRequired($label, $attr);
            $end_label = '</label>';
            if ($this->hasLabelWrapper()) {
                $end_label   .= $this->getLabelCol('end');
            }
        }
        $start_col .= $this->getElementCol('start', 'select', $label);
        $element .= $this->getErrorInputWrapper($select_name, $label, 'start');
        if ($this->framework === 'bulma') {
            $element .= $this->startElementCustomWrapper($select_name, 'bulma_select');
        }
        $element .= $this->getHtmlElementContent($select_name, 'before');
        $aria_label = $this->getAriaLabel($label, $attr);
        $element .= '<select name="' . $select_name . '" id="' . $id . '" class="' . $class . '"' . $data_attr . ' ' . $attr . $aria_label . '>';
        $option_list = '';
        if ($options['return_value'] == 'name') {
            foreach ($countries as $country_code => $country_name) {
                $option_list .= '<option value="' . $country_name . '" class="flag ' . mb_strtolower($country_code) . '"';
                $option_attr = $this->getCheckedOrSelected(mb_strtolower($select_name), $country_name, '', 'select');
                $option_list .= ' ' . $option_attr . '>' . $country_name . '</option>';
            }
        } else {
            foreach ($countries as $country_code => $country_name) {
                $option_list .= '<option value="' . $country_code . '" class="flag ' . mb_strtolower($country_code) . '"';
                $option_attr = $this->getCheckedOrSelected(mb_strtolower($select_name), $country_code, '', 'select');
                $option_list .= ' ' . $option_attr . '>' . $country_name . '</option>';
            }
        }
        $element .= $option_list;
        $element .= '</select>';
        $element .= $this->getHtmlElementContent($select_name, 'after');
        if ($this->framework === 'bulma') {
            $element .= $this->endElementCustomWrapper();
        }
        $element .= $this->getError($select_name);
        $element .= $this->getErrorInputWrapper($select_name, $label, 'end');
        $end_col .= $this->getElementCol('end', 'select', $label);
        $end_wrapper = $this->addErrorWrapper($select_name, 'end');
        $end_wrapper .= $this->setInputGroup($select_name, 'end', 'elementsWrapper'); // end form-group

        // output
        $this->html .= $this->outputElement($start_wrapper, $end_wrapper, $start_label, $end_label, $start_col, $end_col, $element, $this->options['wrapElementsIntoLabels']);
        $this->registerField($select_name, $attr);

        return $this;
    }

    /**
     * adds an hours:minutes select dropdown
     * @param  string $select_name
     * @param  string $label       (Optional) The select label
     * @param  string (Optional)   Can be any HTML input attribute or js event.
     *                             attributes must be listed separated with commas.
     *                             If you don't specify any ID as attr, the ID will be the name of the select.
     *                             Example : class=my-class
     * @param array  $user_options (Optional) :
     *                             min       : the minimum time in hour:minutes                   Default: '00:00'
     *                             max       : the maximum time in hour:minutes                   Default: '23:59'
     *                             step      : the step interval in minutes between each option   Default: 1
     *                             format    : '12hours' or '24hours'                             Default : '24hours'
     *                             display_separator : the displayed separator character between hours and minutes  Default : 'h'
     *                             value_separator   : the value separator character between hours and minutes  Default : ':'
     * @return \phpformbuilder\Form
     */
    public function addTimeSelect(string $select_name, string $label = '', string $attr = '', array $user_options = []): \phpformbuilder\Form
    {
        /* define options*/
        $options = [
            'min'                => '00:00',
            'max'                => '23:59',
            'step'               => 1,
            'format'             => '24hours',
            'display_separator'  => 'h',
            'value_separator'    => ':'
        ];
        $options = \array_merge($options, $user_options);
        $min_xp = \explode(':', $options['min']);
        $min = [
            'hour'   => (int) $min_xp[0],
            'minute' => (int) $min_xp[1]
        ];
        $max_xp = \explode(':', $options['max']);
        $max = [
            'hour'   => (int) $max_xp[0],
            'minute' => (int) $max_xp[1]
        ];

        $min_minutes = $min['hour'] * 60 + $min['minute'];
        $max_minutes = $max['hour'] * 60 + $max['minute'];

        for ($i = $min_minutes; $i <= $max_minutes; $i += $options['step']) {
            $hours = 0;
            $current_minutes = $i;
            while ($current_minutes >= 60) {
                $hours += 1;
                $current_minutes -= 60;
            }
        #    $current_minutes_pad = str_pad($current_minutes, 2, '0', STR_PAD_LEFT);
            $current_minutes_pad = str_pad((string)$current_minutes, 2, '0', STR_PAD_LEFT);

            if ($options['format'] === '12hours') {
                if ($hours < 13) {
                    $opt_value = str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . $options['value_separator'] . $current_minutes_pad . ' am';
                } else {
                    $opt_value = str_pad((string) ($hours - 12), 2, '0', STR_PAD_LEFT) . $options['value_separator'] . $current_minutes_pad . ' pm';
                }
            } else {
                $opt_value = str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . $options['value_separator'] . $current_minutes_pad;
            }
            $this->addOption($select_name, $opt_value, str_replace($options['value_separator'], $options['display_separator'], $opt_value));
        }

        $this->addSelect($select_name, $label, $attr);

        return $this;
    }

    /**
     * Adds checkbox to $group_name
     *
     * @param string $group_name The checkbox button groupname
     * @param string $label      The checkbox label
     * @param string $value      The checkbox value
     * @param string $attr       The checkbox attributes
     * @return \phpformbuilder\Form
     */
    public function addCheckbox(string $group_name, string $label, string $value, string $attr = ''): \phpformbuilder\Form
    {
        if ($this->framework == 'material') {
            $this->checkbox[$group_name]['label'][] = '<span>' . $label . '</span>';
        } else {
            $this->checkbox[$group_name]['label'][] = $label;
        }
        $this->checkbox[$group_name]['value'][]     = $value;
        $this->checkbox[$group_name]['attr'][]      = $attr;

        return $this;
    }

    /**
     * Prints checkbox group.
     *
     * @param string $group_name The checkbox group name (will be converted to an array of indexed value)
     * @param string $label      (Optional) The checkbox group label
     * @param bool   $inline     (Optional) True or false.
     *                           Default is true.
     * @param string $attr       (Optional) Can be any HTML input attribute or js event.
     *                           attributes must be listed separated with commas.
     *                           Example : class=my-class
     * @return \phpformbuilder\Form
     */
    public function printCheckboxGroup(string $group_name, string $label = '', bool $inline = true, string $attr = ''): \phpformbuilder\Form
    {
        $start_wrapper = '';
        $end_wrapper   = '';
        $start_label   = '';
        $end_label     = '';
        $start_col     = '';
        $end_col       = '';
        $element       = '';
        $start_wrapper = $this->setInputGroup($group_name, 'start', 'elementsWrapper');
        if ($this->framework === 'tailwind' && $this->layout === 'vertical' && $inline) {
            $start_wrapper .= $this->startElementCustomWrapper($group_name, 'tailwind_vertical_radio_checkbox_inline');
        }
        $start_wrapper .= $this->addErrorWrapper($group_name, 'start');
        $has_switch    = false;
        $attr          = $this->getAttributes($attr); // returns linearised attributes (with ID)
        if (strpos($attr, 'data-lcswitch') !== false) {
            $has_switch = true;
            $lcswitch_global = [];
            $lcswitch_attributes = ['data-ontext', 'data-offtext', 'data-oncolor', 'data-oncss'];
            foreach ($lcswitch_attributes as $lcswitch_attr) {
                if (strpos($attr, $lcswitch_attr) !== false) {
                    $lcswitch_global[$lcswitch_attr] = $this->getAttribute($lcswitch_attr, $attr);
                }
            }
        }
        if (!empty($label)) {
            $attr = $this->addClass('main-label', $attr);
            if ($inline) {
                $attr = $this->addClass('main-label-inline', $attr);
            }
            if ($has_switch) {
                $attr = str_replace('data-lcswitch', 'data-has-switch', $attr);
            }
            if ($this->layout == 'horizontal') {
                if (!$this->options['horizontalLabelWrapper']) {
                    $class     = $this->options['horizontalLabelCol'] . ' ' . $this->options['horizontalLabelClass'];
                    $attr      = $this->addClass($class, $attr);
                    $start_label = '<label ' . $attr . '>' . $this->getRequired($label, $attr);
                    $end_label = '</label>';
                } else {
                    // wrap label into div with horizontalLabelClass
                    $class        = $this->options['horizontalLabelClass'];
                    $attr         = $this->addClass($class, $attr);
                    $start_label  = $this->getLabelCol('start');
                    $start_label .= '<label ' . $attr . '>' . $this->getRequired($label, $attr);
                    $end_label    = '</label>';
                    $end_label   .= $this->getLabelCol('end');
                }
            } else {
                if (!$this->options['verticalLabelWrapper']) {
                    $class     = $this->options['verticalLabelClass'];
                    $attr      = $this->addClass($class, $attr);
                    $start_label = '<label ' . $attr . '>' . $this->getRequired($label, $attr);
                    $end_label = '</label>';
                } else {
                    // wrap label into div with verticalLabelClass
                    $start_label  = $this->getLabelCol('start');
                    $start_label .= '<label ' . $attr . '>' . $this->getRequired($label, $attr);
                    $end_label    = '</label>';
                    $end_label   .= $this->getLabelCol('end');
                }
            }
        }
        $start_col .= $this->getElementCol('start', 'checkbox', $label);
        if ($this->framework === 'tailwind' && $this->layout === 'horizontal' && $inline) {
            $start_col .= $this->startElementCustomWrapper($group_name, 'tailwind_horizontal_radio_checkbox_inline');
        }
        $element .= $this->getErrorInputWrapper($group_name, $label, 'start');
        if ($this->framework === 'bulma') {
            if ($inline) {
                $element .= $this->startElementCustomWrapper($group_name, 'bulma_field_multiline');
            } else {
                $element .= $this->startElementCustomWrapper($group_name, 'bulma_field');
            }
        }
        $element .= $this->getHtmlElementContent($group_name, 'before');
        for ($i = 0; $i < count($this->checkbox[$group_name]['label']); $i++) {
            $checkbox_start_label   = '';
            $checkbox_end_label     = '';
            $checkbox_input         = '';
            if (!empty($this->options['checkboxWrapper']) && $inline !== true) {
                $element .= $this->checkbox_start_wrapper;
            } elseif (!empty($this->options['inlineCheckboxWrapper']) && $inline) {
                $element .= $this->inline_checkbox_start_wrapper;
            }
            $checkbox_label = $this->checkbox[$group_name]['label'][$i];
            $checkbox_value = $this->checkbox[$group_name]['value'][$i];
            $checkbox_attr = $this->getAttributes($this->checkbox[$group_name]['attr'][$i]);
            if ($this->framework == 'bs4' || $this->framework == 'bs5') {
                $checkbox_attr = $this->addClass('form-check-input', $checkbox_attr);
            }
            // lcswitch plugin
            if ($has_switch !== false) {
                // add global lcswitch attributes if no individuals found
                foreach ($lcswitch_attributes as $lcswitch_attr) {
                    if (!strpos($checkbox_attr, $lcswitch_attr) && isset($lcswitch_global[$lcswitch_attr])) {
                        $checkbox_attr = $this->addAttribute($lcswitch_attr, $lcswitch_global[$lcswitch_attr], $checkbox_attr);
                    }
                }
                $this->addPlugin('lcswitch', '#' . $group_name . '_' . $i);
            }
            $checkbox_start_label = '<label for="' . $group_name . '_' . $i . '"' . $this->getLabelClass('checkbox', $inline) . '>';
            $checkbox_input = '<input type="checkbox" id="' . $group_name . '_' . $i . '" name="' . $group_name . '[]" value="' . $checkbox_value . '"';
            $checkbox_attr = $this->getCheckedOrSelected($group_name, $checkbox_value, $checkbox_attr, 'checkbox');
            $checkbox_input .= ' ' . $checkbox_attr . '>';
            $checkbox_end_label = $checkbox_label . '</label>';

            if ($this->options['wrapCheckboxesIntoLabels']) {
                $element .= $checkbox_start_label . $checkbox_input . $checkbox_end_label;
            } else {
                $element .= $checkbox_input . $checkbox_start_label . $checkbox_end_label;
            }
            if (!empty($this->options['checkboxWrapper']) && !$inline) {
                $element .= $this->checkbox_end_wrapper;
            } elseif (!empty($this->options['inlineCheckboxWrapper']) && $inline) {
                $element .= $this->inline_checkbox_end_wrapper;
            }
        }
        $element .= $this->getHtmlElementContent($group_name, 'after');
        if ($this->framework === 'bulma') {
            $element .= $this->endElementCustomWrapper();
        }
        $element .= $this->getError($group_name);
        $element .= $this->getErrorInputWrapper($group_name, $label, 'end');

        if ($this->framework === 'tailwind' && $this->layout === 'horizontal' && $inline) {
            $end_col .= $this->endElementCustomWrapper();
        }

        $end_col .= $this->getElementCol('end', 'checkbox', $label);
        $end_wrapper = $this->addErrorWrapper($group_name, 'end');
        if ($this->framework === 'tailwind' && $this->layout === 'vertical' && $inline) {
            $end_wrapper .= $this->endElementCustomWrapper();
        }
        $end_wrapper .= $this->setInputGroup($group_name, 'end', 'elementsWrapper'); // end form-group
        $this->html .= $this->outputElement($start_wrapper, $end_wrapper, $start_label, $end_label, $start_col, $end_col, $element, false);

        $this->registerField($group_name, $attr);

        return $this;
    }

    /**
     * Adds radio button to $group_name element
     *
     * @param string $group_name The radio button groupname
     * @param string $label      The radio button label
     * @param string $value      The radio button value
     * @param string $attr       (Optional) Can be any HTML input attribute or js event.
     *                           attributes must be listed separated with commas.
     *                           Example : checked=checked
     * @return \phpformbuilder\Form
     */
    public function addRadio(string $group_name, string $label, string $value, string $attr = ''): \phpformbuilder\Form
    {
        if ($this->framework == 'material') {
            $this->radio[$group_name]['label'][]  = '<span>' . $label . '</span>';
        } else {
            $this->radio[$group_name]['label'][]  = $label;
        }
        $this->radio[$group_name]['value'][]  = $value;
        $this->radio[$group_name]['attr'][]  = $attr;

        return $this;
    }

    /**
     * Prints radio buttons group.
     *
     * @param string $group_name The radio button group name
     * @param string $label      (Optional) The radio buttons group label
     * @param bool   $inline     (Optional) True or false.
     *                           Default is true.
     * @param string $attr       (Optional) Can be any HTML input attribute or js event.
     *                           attributes must be listed separated with commas.
     *                           Example : class=my-class
     * @return \phpformbuilder\Form
     */
    public function printRadioGroup(string $group_name, string $label = '', bool $inline = true, string $attr = ''): \phpformbuilder\Form
    {
        $form_ID       = $this->form_ID;
        $start_wrapper = '';
        $end_wrapper   = '';
        $start_label   = '';
        $end_label     = '';
        $start_col     = '';
        $end_col       = '';
        $element       = '';
        $start_wrapper = $this->setInputGroup($group_name, 'start', 'elementsWrapper');
        if ($this->framework === 'tailwind' && $this->layout === 'vertical' && $inline) {
            $start_wrapper .= $this->startElementCustomWrapper($group_name, 'tailwind_vertical_radio_checkbox_inline');
        }
        $start_wrapper .= $this->addErrorWrapper($group_name, 'start');
        $has_switch    = false;
        $attr          = $this->getAttributes($attr); // returns linearised attributes (with ID)
        if (strpos($attr, 'data-lcswitch') !== false) {
            $has_switch = true;
            $lcswitch_global = [];
            $lcswitch_attributes = ['data-ontext', 'data-offtext', 'data-oncolor', 'data-oncss'];
            foreach ($lcswitch_attributes as $lcswitch_attr) {
                if (strpos($attr, $lcswitch_attr) !== false) {
                    $lcswitch_global[$lcswitch_attr] = $this->getAttribute($lcswitch_attr, $attr);
                }
            }
        }
        if (!empty($label)) {
            $attr = $this->addClass('main-label', $attr);
            if ($inline) {
                $attr = $this->addClass('main-label-inline', $attr);
            }
            if ($has_switch) {
                $attr = str_replace('data-lcswitch', 'data-has-switch', $attr);
            }
            if ($this->layout == 'horizontal') {
                if (!$this->options['horizontalLabelWrapper']) {
                    $class     = $this->options['horizontalLabelCol'] . ' ' . $this->options['horizontalLabelClass'];
                    $attr      = $this->addClass($class, $attr);
                    $start_label = '<label ' . $attr . '>' . $this->getRequired($label, $attr);
                    $end_label = '</label>';
                } else {
                    // wrap label into div with horizontalLabelClass
                    $class        = $this->options['horizontalLabelClass'];
                    $attr         = $this->addClass($class, $attr);
                    $start_label  = $this->getLabelCol('start');
                    $start_label .= '<label ' . $attr . '>' . $this->getRequired($label, $attr);
                    $end_label    = '</label>';
                    $end_label   .= $this->getLabelCol('end');
                }
            } else {
                if (!$this->options['verticalLabelWrapper']) {
                    $class     = $this->options['verticalLabelClass'];
                    $attr      = $this->addClass($class, $attr);
                    $start_label = '<label ' . $attr . '>' . $this->getRequired($label, $attr);
                    $end_label = '</label>';
                } else {
                    // wrap label into div with verticalLabelClass
                    $start_label  = $this->getLabelCol('start');
                    $start_label .= '<label ' . $attr . '>' . $this->getRequired($label, $attr);
                    $end_label    = '</label>';
                    $end_label   .= $this->getLabelCol('end');
                }
            }
        }
        $required = '';
        if (preg_match('`required`', $attr)) {
            $required = ' required';
        }
        $start_col .= $this->getElementCol('start', 'radio', $label);
        if ($this->framework === 'tailwind' && $this->layout === 'horizontal' && $inline) {
            $start_col .= $this->startElementCustomWrapper($group_name, 'tailwind_horizontal_radio_checkbox_inline');
        }
        $element .= $this->getErrorInputWrapper($group_name, $label, 'start');
        if ($this->framework === 'bulma') {
            if ($inline) {
                $element .= $this->startElementCustomWrapper($group_name, 'bulma_field_multiline');
            } else {
                $element .= $this->startElementCustomWrapper($group_name, 'bulma_field');
            }
        }
        $element .= $this->getHtmlElementContent($group_name, 'before');
        if (isset($this->input_wrapper[$group_name])) {
            $element .= $this->getElementWrapper($this->input_wrapper[$group_name], 'start'); // input-group
        }
        for ($i = 0; $i < count($this->radio[$group_name]['label']); $i++) {
            $radio_start_label   = '';
            $radio_end_label     = '';
            $radio_input         = '';
            if (!empty($this->options['radioWrapper']) && $inline !== true) {
                $element .= $this->radio_start_wrapper;
            } elseif (!empty($this->options['inlineRadioWrapper']) && $inline) {
                $element .= $this->inline_radio_start_wrapper;
            }
            $radio_label  = $this->radio[$group_name]['label'][$i];
            $radio_value  = $this->radio[$group_name]['value'][$i];
            $radio_attr   = $this->getAttributes($this->radio[$group_name]['attr'][$i]); // returns linearised attributes (with ID)
            if ($this->framework == 'material') {
                $radio_attr = $this->addClass('with-gap', $radio_attr);
            } elseif ($this->framework == 'bs4' || $this->framework == 'bs5') {
                $radio_attr = $this->addClass('form-check-input', $radio_attr);
            }
            // lcswitch plugin
            if ($has_switch !== false) {
                // add global lcswitch attributes if no individuals found
                foreach ($lcswitch_attributes as $lcswitch_attr) {
                    if (!strpos($radio_attr, $lcswitch_attr) && isset($lcswitch_global[$lcswitch_attr])) {
                        $radio_attr = $this->addAttribute($lcswitch_attr, $lcswitch_global[$lcswitch_attr], $attr);
                    }
                }
                $this->addPlugin('lcswitch', '#' . $group_name . '_' . $i);
            }
            $radio_start_label .= '<label for="' . $group_name . '_' . $i . '" ' . $this->getLabelClass('radio', $inline) . '>';
            $radio_input .= '<input type="radio" id="' . $group_name . '_' . $i . '" name="' . $group_name . '" value="' . $radio_value . '"';
            if (isset($_SESSION[$form_ID][$group_name])) {
                if ($_SESSION[$form_ID][$group_name] == $radio_value) {
                    if (!preg_match('`checked`', $radio_attr)) {
                        $radio_input .= ' checked="checked"';
                    }
                } else { // we remove 'checked' from $radio_attr as user has previously checked another, memorized in session.
                    $radio_attr = $this->removeAttribute('checked', $radio_attr);
                }
            }
            $radio_input .= $required . ' ' . $radio_attr . '>';

            $radio_end_label = $radio_label . '</label>';
            if ($this->options['wrapRadiobtnsIntoLabels']) {
                $element .= $radio_start_label . $radio_input . $radio_end_label;
            } else {
                $element .= $radio_input . $radio_start_label . $radio_end_label;
            }
            if ($inline !== true) {
                if (!empty($this->options['radioWrapper'])) {
                    $element .= $this->radio_end_wrapper;
                } else {
                    $element .= '<br>';
                }
            } elseif (!empty($this->options['inlineRadioWrapper'])) {
                $element .= $this->inline_radio_end_wrapper;
            }
        }
        if (isset($this->input_wrapper[$group_name])) {
            $element .= $this->getError($group_name, true);
            $element .= $this->getElementWrapper($this->input_wrapper[$group_name], 'end'); // end input-group
        }
        $element .= $this->getHtmlElementContent($group_name, 'after');
        if ($this->framework === 'bulma') {
            $element .= $this->endElementCustomWrapper();
        }
        $element .= $this->getError($group_name);
        $element .= $this->getErrorInputWrapper($group_name, $label, 'end');
        if ($this->framework === 'tailwind' && $this->layout === 'horizontal' && $inline) {
            $end_col .= $this->endElementCustomWrapper();
        }
        $end_col .= $this->getElementCol('end', 'radio', $label);
        $end_wrapper = $this->addErrorWrapper($group_name, 'end');
        if ($this->framework === 'tailwind' && $this->layout === 'vertical' && $inline) {
            $end_wrapper .= $this->endElementCustomWrapper();
        }
        $end_wrapper .= $this->setInputGroup($group_name, 'end', 'elementsWrapper'); // end form-group
        $this->html .= $this->outputElement($start_wrapper, $end_wrapper, $start_label, $end_label, $start_col, $end_col, $element, false);

        $this->registerField($group_name, $attr);

        return $this;
    }

    /**
     * Adds a button to the form
     *
     * If $btnGroupName is empty, the button will be automatically displayed.
     * Otherwise, you'll have to call printBtnGroup to display your btnGroup.
     *
     * @param string $type         The html button type
     * @param string $name         The button name
     * @param string $value        The button value
     * @param string $text         The button text
     * @param string $attr         (Optional) Can be any HTML input attribute or js event.
     *                             attributes must be listed separated with commas.
     *                             If you don't specify any ID as attr, the ID will be the input's name.
     *                             Example : class=my-class,onclick=alert(\'clicked\');
     * @param string $btnGroupName (Optional) If you wants to group several buttons, group them then call printBtnGroup.
     * @return \phpformbuilder\Form
     */
    public function addBtn(string $type, string $name, string $value, string $text, string $attr = '', string $btnGroupName = ''): \phpformbuilder\Form
    {

        /*  if $btnGroupName isn't empty, we just store values
        *   witch will be called back by printBtnGroup($btnGroupName)
        *   else we store the values in a new array, then call immediately printBtnGroup($btnGroupName)
        */

        if (empty($btnGroupName)) {
            $btnGroupName = 'btn-alone';
            $this->btn[$btnGroupName] = [];
        }

        /* Automagically add Ladda plugin */

        if (preg_match('`data-ladda-button`', $attr)) {
            $this->addPlugin('ladda', 'button[name=\'' . $name . '\']');
        }

        $this->btn[$btnGroupName]['type'][] = $type;
        $this->btn[$btnGroupName]['name'][] = $name;
        $this->btn[$btnGroupName]['value'][] = $value;
        $this->btn[$btnGroupName]['text'][] = $text;
        $this->btn[$btnGroupName]['attr'][] = $attr;

        /*  if $btnGroupName was empty the button is displayed. */

        if ($btnGroupName == 'btn-alone') {
            $this->printBtnGroup($btnGroupName);
        }

        return $this;
    }

    /**
     * Prints buttons group.
     *
     * @param string $btnGroupName The buttons' group name
     * @param string $label        (Optional) The buttons group label
     * @return \phpformbuilder\Form
     */
    public function printBtnGroup(string $btnGroupName, string $label = ''): \phpformbuilder\Form
    {
        $btn_alone = false;
        $btn_name  = '';
        $start_wrapper = '';
        $end_wrapper   = '';
        $start_label   = '';
        $end_label     = '';
        $start_col     = '';
        $end_col       = '';
        $element       = '';
        if ($btnGroupName == 'btn-alone') {
            $btn_alone = true;
            $btn_name  = $this->btn[$btnGroupName]['name'][0];
        }
        $start_wrapper = $this->setInputGroup($btn_name, 'start', 'buttonWrapper');

        // label
        if (!empty($label)) {
            if ($this->hasLabelWrapper()) {
                $start_label  .= $this->getLabelCol('start');
            }
            $start_label .= '<label>' . $label;
            $end_label = '</label>';
            if ($this->hasLabelWrapper()) {
                $end_label   .= $this->getLabelCol('end');
            }
        }
        $start_col .= $this->getElementCol('start', 'button', $label);
        if (!empty($this->options['btnGroupClass']) && !$btn_alone) {
            $element .= '<div class="' . $this->options['btnGroupClass'] . '">';
        }
        $element .= $this->getHtmlElementContent($btnGroupName, 'before');
        if ($btn_alone && isset($this->input_wrapper[$btn_name])) {
            $element .= $this->getElementWrapper($this->input_wrapper[$btn_name], 'start'); // input-group-btn
        }
        for ($i = 0; $i < count($this->btn[$btnGroupName]['type']); $i++) {
            $btn_type     = $this->btn[$btnGroupName]['type'][$i];
            $btn_name     = $this->btn[$btnGroupName]['name'][$i];
            $btn_value    = $this->btn[$btnGroupName]['value'][$i];
            $btn_text     = $this->btn[$btnGroupName]['text'][$i];
            $btn_attr     = $this->btn[$btnGroupName]['attr'][$i];
            $btn_attr     = $this->getAttributes($btn_attr); // returns linearised attributes (with ID)
            $btn_value    = $this->getValue($btn_name, $btn_value);
            if ($this->framework === 'bulma') {
                $element .= '<p class="control">';
            }
            $element .= '<button type="' . $btn_type . '" name="' . $btn_name . '" value="' . $btn_value . '" ' . $btn_attr . '>' . $btn_text . '</button>';
            if ($this->framework === 'bulma') {
                $element .= '</p>';
            }
        }
        if (isset($this->input_wrapper[$btn_name])) {
            $element .= $this->getError($btn_name, true);
            $element .= $this->getElementWrapper($this->input_wrapper[$btn_name], 'end'); // end input-group-btn
        }
        $element .= $this->getHtmlElementContent($btnGroupName, 'after');
        if (!empty($this->options['btnGroupClass']) && !$btn_alone) {
            $element .= '</div>';
        }
        $end_col .= $this->getElementCol('end', 'button', $label);
        $end_wrapper .= $this->setInputGroup($btn_name, 'end', 'buttonWrapper');
        $this->html .= $this->outputElement($start_wrapper, $end_wrapper, $start_label, $end_label, $start_col, $end_col, $element, false);

        return $this;
    }
}
