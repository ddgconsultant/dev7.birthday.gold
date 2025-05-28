<?php

declare(strict_types=1);

namespace phpformbuilder\traits;

trait Internal
{
    /**
     * Add new class to $attr.(see options).
     *
     * @param  string $newclassname The new class
     * @param  string $attr         The element attributes
     * @return string $attr including new class.
     */
    protected function addClass(string $newclassname, string $attr):string
    {

        /* if $attr already contains a class we keep it and add newclassname */

        if (preg_match('`class="([^"]+)"`', $attr, $out)) {
            $new_class =  'class="' . $out[1] . ' ' . $newclassname . '"';

            return preg_replace('`class="([^"]+)"`', $new_class, $attr, 1);
        } else { // if $attr contains no class we add elementClass
            return $attr . ' class="' . $newclassname . '"';
        }
    }

    /**
     * @param  string $attr_name
     * @param  string $attr_value
     * @param  string $attr_string
     * @return string attributes with the added one
     */
    protected function addAttribute(string $attr_name, string $attr_value, string $attr_string):string
    {
        if (empty($attr_string)) {
            $attr_string = ' ' . $attr_name . '="' . $attr_value . '"';
        } else {
            $attr_string = ' ' . $attr_name . '="' . $attr_value . '" ' . $attr_string;
        }

        return $attr_string;
    }

    /**
     * Add default element class to $attr.(see options).
     *
     * @param  string $element_type The element type: input|textarea|select
     * @param  string $name         The element name
     * @param  string $attr         The element attributes
     * @return string The element class with the one defined in options added.
     */
    protected function addElementClass(string $element_type, string $name, string $attr):string
    {
        $el_class = $this->options['elementsClass'];
        if ($this->framework === 'bs5' && $element_type === 'select' && !strpos($attr, 'selectpicker')) {
            $el_class = 'form-select';
        } elseif ($this->framework === 'bulma') {
            if ($element_type !== 'select') {
                $el_class = $element_type;
            } else {
                $el_class = '';
            }
        } elseif ($this->framework === 'uikit') {
            $el_class = 'uk-' . $element_type;
        }

        /* we retrieve error if any */

        $error_class = '';
        if (in_array(str_replace('[]', '', $name), array_keys($this->error_fields)) && !empty($this->options['elementsErrorClass'])) {
            $error_class = ' ' . $this->options['elementsErrorClass'];
        }

        /* if $attr already contains a class we keep it and add elementClass */

        $output = '';

        if (preg_match('`class="([^"]+)"`', $attr, $out)) {
            $new_class =  'class="' . $out[1] . ' ' . $el_class . $error_class . '"';

            $output = preg_replace('`class="([^"]+)"`', $new_class, $attr);
        } else { /* if $attr contains no class we add elementClass */
            if (empty($el_class)) {
                if (empty($error_class)) {
                    $output = $attr;
                } else {
                    $output = ' class="' . $error_class . '"';
                }
            } else {
                $output = $attr . ' class="' . $el_class . $error_class . '"';
            }
        }

        return $output;
    }

    /**
     * Adds warnings class to elements wrappers
     *
     * @param  string $start_wrapper The html wrapper code
     * @param  string $name          The element name
     * @return string Wrapper Html tag with or without error class
     */
    protected function addErrorWrapper(string $name, string $pos):string
    {
        $error_wrapper = '';
        if (in_array(str_replace('[]', '', $name), array_keys($this->error_fields)) && !empty($this->options['wrapperErrorClass'])) {
            if ($pos == 'start') {
                $error_wrapper = '<div class="' . $this->options['wrapperErrorClass'] . '">';
            } else {
                $error_wrapper = '</div>';
            }
        }

        return $error_wrapper;
    }

    /**
     * convert boolean values to string recursively in an associative array
     *
     * @param  array $array
     * @return array
     */
    protected function booleanToString(array $array):array
    {
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                if (is_bool($value)) {
                    if ($value) {
                        $array[$key] = 'true';
                    } else {
                        $array[$key] = 'false';
                    }
                }
            } else {
                $array[$key] = $this->booleanToString($array[$key]);
            }
        }

        return $array;
    }

    /**
     * display various error messages about server or plugins settings
     *
     * @param string $msg
     * @return void
     */
    protected function buildErrorMsg(string $msg)
    {
        $this->error_msg .= '<div style="line-height:30px;border-radius:5px;border-bottom:1px solid #ac2925;background-color: #c9302c;margin:10px auto;"><p style="color:#fff;text-align:center;font-size:16px;margin:0">' . $msg . '</p></div>';
    }

    /**
     *
     * @param array $plugins_files
     * @param string $compressed_file_path
     * @param bool $debug
     * @return bool
     */
    private function checkRewriteCombinedFiles(array $plugins_files, string $compressed_file_path, bool $debug = false):bool
    {
        $rewrite_combined_file = false;
        if ($this->mode == 'production') {
            if (!file_exists($compressed_file_path)) {
                // if minified combined file doesn't exist
                $rewrite_combined_file = true;
            } else {
                clearstatcache(true, $_SERVER['SCRIPT_FILENAME']);
                clearstatcache(true, $compressed_file_path);
                $parent_file_time   = filemtime($_SERVER['SCRIPT_FILENAME']);
                $combined_file_time = filemtime($compressed_file_path);
                if ($parent_file_time >= $combined_file_time) {
                    $rewrite_combined_file = true;
                }
                if ($debug) {
                    $will_combine = 'false';
                    if ($rewrite_combined_file) {
                        $will_combine = true;
                    }

                    echo "<p>parent_file = " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
                    echo "<p>combined_file = $compressed_file_path</p>";
                    echo "<p>parent_file_time = $parent_file_time</p>";
                    echo "<p>combined_file_time = $combined_file_time</p>";
                    echo "<p>will_combine = $will_combine</p>";
                }
            }
            foreach ($plugins_files as $file) {
                if ($rewrite_combined_file !== true && !preg_match('`www.google.com/recaptcha`', $file)) {
                    // recaptcha is not combined
                    // check if we have to recreate minified combined file
                    $ftime = false;
                    if (function_exists('filemtime')) {
                        $ftime = filemtime(str_replace($this->plugins_url, $this->plugins_path, $file));
                    }
                    if (!$ftime || $ftime > $combined_file_time) {
                        // if file is newer than combined one
                        $rewrite_combined_file = true;
                    }
                }
            }
        }

        return $rewrite_combined_file;
    }

    /**
     * combine css|js files in phpformbuilder/plugins/min/[css|js]
     *
     * @param  string $type                 css|js
     * @param  array  $plugins_files
     * @param  string $compressed_file_path the combined file path with filename = $this->plugins_path . 'min/' . $type . '/' . $this->form_ID . '.min.' . $type
     * @param string  $inline_style
     * @return string $error_msg - empty if all is ok
     */
    private function combinePluginFiles(string $type, array $plugins_files, string $compressed_file_path, string $inline_style = ''):string
    {
        $error_msg = '';
        if (!file_exists($this->plugins_path . '/min') && !mkdir($this->plugins_path . '/min')) {
            $error_msg = 'Unable to create <strong><i>/min</i></strong> folder<br>Try to change permissions (chmod 0755) on your server<br> or set mode to "development": <code>$form->setMode(\'development\')</code>;';
        }
        if (!file_exists($this->plugins_path . '/min/' . $type) && !mkdir($this->plugins_path . '/min/' . $type)) {
            $error_msg = 'Unable to create <strong><i>/min/' . $type . '</i></strong> folder<br>Try to change permissions (chmod 0755) on your server<br> or set mode to "development": <code>$form->setMode(\'development\')</code>;';
        }
        $new_file_content = '';
        foreach ($plugins_files as $file) {
            if (strpos($file, 'www.google.com/recaptcha') === false && strpos($file, 'hcaptcha.com') === false) {
                $current_file_content = php_strip_whitespace(str_replace($this->plugins_url, $this->plugins_path, $file)) . "\n";
                if ($type == 'css') {
                    // workaround to use $this in preg_replace_callback with php < 5.4
                    $self = $this;
                    // convert relative urls to absolute
                    $current_file_content = preg_replace_callback(
                        '`url\(([^\)]+)\)`',
                        function ($matches) use ($self, $file) {
                            return 'url(' . $self->rel2abs($matches[1], $file) . ')';
                        },
                        $current_file_content
                    );
                }
                // remove sourcemaps
                $current_file_content = preg_replace('~//[#@]\s(source(?:Mapping)?URL)=\s*(\S+)~', '', $current_file_content);
                $new_file_content .= $current_file_content;
            }
        }
        if ($type == 'css') {
            $new_file_content .= $inline_style;
        }

        try {
            //open file for writing
            if (!file_put_contents($compressed_file_path, $new_file_content)) {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            $error_msg = 'Unable to open ' . $compressed_file_path . ' for writing.<br>Try to change permissions (chmod 0755) on your server<br> or set mode to "development": <code>$form->setMode(\'development\')</code>;';
        }

        return $error_msg;
    }

    /**
     * Encloses each key of $array with $char
     *
     * @param  string $char  The enclosing character
     * @param  array  $array The target associative array
     * @return array
     */
    protected function encloseArrayKeys(string $char, array $array):array
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            $enclosed_key = $char . $key . $char;
            $newArray[$enclosed_key] = $value;
        }

        return $newArray;
    }

    /**
     * end element Custom Wrapper
     *
     * @return string
     */
    protected function endElementCustomWrapper():string
    {
        return '</div>';
    }

    /**
     * generate token to protect against CSRF
     *
     * @return string $token
     */
    protected function generateToken():string
    {
        $token = uniqid((string) rand(), true);
        $token_name = $this->form_ID;
        $_SESSION[$token_name . '_token'] = $token;
        $_SESSION[$token_name . '_token_time'] = time();

        return $token;
    }

    /**
     * Gets element getAriaLabel.
     *
     * @param  string $label The element label
     * @param  string $attr The element attributes
     * @return string returns the element placeholder in $attr if any,
     *                else returns an empty string
     */
    protected function getAriaLabel(string $label, string $attr):string
    {
        if (empty($label) && preg_match('`placeholder="([^"]+)"`', $attr, $out) && !strpos($attr, 'aria-label')) {
            return ' aria-label="' . $out[1] . '"';
        }

        return '';
    }

    /**
     * find any attribute value in $attr
     *
     * @param  string  $search
     * @param  string $attr   Linearised attributes
     *                        Example: size="30"
     *                        required="required"
     * @return ?string The attribute value or false if not found
     */
    protected function getAttribute(string $search, string $attr):?string
    {
        if (empty($attr)) {
            return false;
        } else {
            // replace protected commas with expression
            $attr = str_replace('\\,', '[comma]', $attr);

            // replace protected equals with expression
            $attr = str_replace('\\=', '[equal]', $attr);

            if (preg_match('`' . $search . '="([^,]+)"`', $attr, $out)) {
                return trim(str_replace(['[comma]', '[equal]'], [',', '='], $out[1]));
            }

            return false;
        }
    }

    /**
     * Returns linearised attributes.
     *
     * @param  string $attr The element attributes
     * @return string Linearised attributes
     *                Example: size=30, required=required => size="30" required="required"
     */
    protected function getAttributes(string $attr):string
    {
        if (empty($attr)) {
            return '';
        } else {
            $clean_attr = '';

            // replace protected commas with expression
            $attr = str_replace('\\,', '[comma]', $attr);

            // replace protected equals with expression
            $attr = str_replace('\\=', '[equal]', $attr);

            // split with commas
            $attr = preg_split('`,`', $attr);
            foreach ($attr as $a) {
                // add quotes
                if (preg_match('`=`', $a)) {
                    $a = preg_replace('`\s*=\s*`', '="', trim($a)) .  '" ';
                } else {
                    // no quote for single attributes
                    $a = trim($a) . ' ';
                }
                $clean_attr .= $a;
            }

            // get back protected commas, equals and trim
            $clean_attr = trim(str_replace(['[comma]', '[equal]'], [',', '='], $clean_attr));

            return $clean_attr;
        }
    }

    /**
     * used for chexkboxes | select options only.
     * adds or remove 'checked' or 'selected' according to default / session values.
     *
     * @param  string $field_name
     * @param  string $value
     * @param  string $attr       ex : checked="checked", class="my-class"
     * @param  string $field_type select | checkbox
     * @return string $attr
     */
    protected function getCheckedOrSelected(string $field_name, string $value, string $attr, string $field_type):string
    {
        $form_ID = $this->form_ID;
        $name_without_hook = preg_replace('`(.*)\[\]`', '$1', $field_name);
        if ($field_type == 'select') {
            $attr_selected = 'selected';
        } else {
            $attr_selected = 'checked';
        }
        if (isset($_SESSION[$form_ID][$name_without_hook])) {
            if (!is_array($_SESSION[$form_ID][$name_without_hook])) {
                if ($_SESSION[$form_ID][$name_without_hook] == $value) {
                    if (!preg_match('`' . $attr_selected . '`', $attr)) {
                        $attr = $this->addAttribute($attr_selected, $attr_selected, $attr);
                    }
                } else { // we remove 'selected' from $checkbox_attr as user has previously selected another, memorized in session.
                    $attr = $this->removeAttribute($attr_selected, $attr);
                }
            } else {
                if (in_array($value, $_SESSION[$form_ID][$name_without_hook])) {
                    if (!preg_match('`' . $attr_selected . '`', $attr)) {
                        $attr = $this->addAttribute($attr_selected, $attr_selected, $attr);
                    }
                } else { // we remove 'selected' from $attr as user has previously selected another, memorized in session.
                    $attr = $this->removeAttribute('selected', $attr);
                }
            }
        }

        return $attr;
    }

    /**
     * Wrapps element with col if needed (see options).
     *
     * @param  string $pos        'start' or 'end'
     * @param  string $label      The element label
     * @param  string $field_type input|textarea|select|radio|checkbox|button|recaptcha
     * @return string The html code of the element wrapper.
     */
    protected function getElementCol(string $pos, string $field_type, string $label = ''):string
    {
        $output = '';

        if ($this->layout == 'horizontal' && !empty($this->options['horizontalElementCol'])) {
            if ($pos == 'start') {
                if (empty($label)) {
                    $output = '<div class="' . trim($this->options['horizontalOffsetCol'] . ' ' . $this->options['horizontalElementCol']) . '">';
                } else {
                    $output = '<div class="' . $this->options['horizontalElementCol'] . '">';
                }
            } else { // end
                $output = '</div>';
            }
        } elseif ($this->framework == 'foundation' && ($field_type == 'radio' || $field_type == 'checkbox' || $field_type == 'button' || $field_type == 'recaptcha')) {
            if ($pos == 'start') {
                // foundation checkboxes, radio & button need column wrapper - in both horizontal & vertical forms
                $output = '<div class="' . $this->options['horizontalElementCol'] . '">';
            } else { // end
                $output = '</div>';
            }
        }

        return $output;
    }

    /**
     * Gets html code to start | end elements wrappers
     *
     * @param  string $html The html wrapper code
     * @param  string $pos  'start' or 'end'
     * @return string Starting or ending html tag
     */
    protected function getElementWrapper(string $html, string $pos):string
    {
        $output = '';

        /* if 2 wrappers */

        $pattern_2_wrappers = '`<([^>]+)><([^>]+)></([^>]+)></([^>]+)>`';
        if (preg_match($pattern_2_wrappers, $html, $out)) {
            if ($pos == 'start') {
                $output = '<' . $out[1] . '>' . '<' . $out[2] . '>';
            } else {
                $output = '</' . $out[3] . '>' . '</' . $out[4] . '>';
            }
        }

        /* if only 1 wrapper */

        $pattern_1_wrapper = '`<([^>]+)></([^>]+)>`';
        if (preg_match($pattern_1_wrapper, $html, $out)) {
            if ($pos == 'start') {
                $output = '<' . $out[1] . '>';
            } else {
                $output = '</' . $out[2] . '>';
            }
        }

        return $output;
    }

    /**
     * Adds warnings if the form was posted with errors
     *
     * Warnings are stored in session, and will be displayed
     * even if your form was called back with header function.
     *
     * @param  string $name The element name
     * @return string The html error
     */
    protected function getError(string $name):string
    {
        $no_hook_name = str_replace('[]', '', $name);
        if (in_array($no_hook_name, array_keys($this->error_fields))) {
            return '<p class="' . $this->options['textErrorClass'] . '">' . $this->error_fields[$no_hook_name] . '</p>';
        }

        // Default
        return '';
    }

    /**
     * wrap element itself with error div if input is grouped or if $label is empty
     *
     * @param  string $name
     * @param  string $label
     * @param  string $pos   'start' | 'end'
     * @return string div start | end
     */
    protected function getErrorInputWrapper(string $name, string $label, string $pos):string
    {
        $isGrouped = $this->isGrouped($name);
        if (($isGrouped || $label == '') && !empty($this->options['wrapperErrorClass']) && in_array(str_replace('[]', '', $name), array_keys($this->error_fields))) {
            if ($pos == 'start') {
                return '<div class="' . $this->options['wrapperErrorClass'] . '">';
            }

            return '</div>';
        }

        return '';
    }

    /**
     * Gets html code to insert just berore or after the element
     *
     * @param  string $name                    The element name
     * @param  string $pos                     'start' or 'end'
     * @param  string $pos_relative_to_wrapper 'inside_wrapper' or 'outside_wrapper' (input groups are inside wrapper, help blocks are outside). Only for inputs.
     * @return string $return                  The html code to insert just before or after the element, inside or outside element wrapper
     */
    protected function getHtmlElementContent(string $name, string $pos, string $pos_relative_to_wrapper = ''):string
    {
        $return = '';
        if (isset($this->html_element_content[$name][$pos])) {
            for ($i = 0; $i < count($this->html_element_content[$name][$pos]); $i++) {
                $html = $this->html_element_content[$name][$pos][$i];
                if (empty($pos_relative_to_wrapper)) {
                    $return .= $html;
                } else {
                    $is_addon = false;
                    $addon_clazz = [
                        'input-group-',
                        'addon-',
                        'uk-form-icon'
                    ];
                    foreach ($addon_clazz as $clz) {
                        if (strpos($html, $clz) !== false) {
                            $is_addon = true;
                        }
                    }
                    if ($pos_relative_to_wrapper == 'outside_wrapper' && !$is_addon) {
                        $return .= $html;
                    } elseif ($pos_relative_to_wrapper == 'inside_wrapper' && $is_addon) {
                        $return .= $html;
                    }
                }
            }

            return $return;
        } else {
            return '';
        }
    }

    /**
     * Gets element ID.
     *
     * @param  string $name The element name
     * @param  string $attr The element attributes
     * @return array returns ID present in $attr if any,
     *                else returns field's name
     */
    protected function getID(string $name, string $attr):array
    {
        if (empty($attr)) {  //
            $array_values['id'] = str_replace('`\[\]`', '', $name); // if $name is an array, we delete '[]'
            $array_values['attributs'] = '';
        } else {
            if (preg_match('`id="([a-zA-Z0-9_-]+)"`', $attr, $out)) {
                $array_values['id'] = $out[1];
                $array_values['attributs'] = preg_replace('`id="([a-zA-Z0-9_-]+)"`', '', $attr);
            } else {
                $array_values['id'] = str_replace('`\[\]`', '', $name);
                $array_values['attributs'] = $attr;
            }
        }

        return $array_values;
    }

    /**
     * Gets css or js files needed for js plugins
     *
     * @param  string $type 'css' or 'js'
     * @return void
     */
    protected function getIncludes(string $type):void
    {
        foreach ($this->js_plugins as $plugin_name) {
            for ($i = 0; $i < count($this->js_content[$plugin_name]); $i++) {
                $js_config      = $this->js_content[$plugin_name][$i]; // default, custom, ...
                $plugin_settings = $this->plugin_settings[$plugin_name][$i];
                if (file_exists(dirname(dirname(__FILE__)) . '/plugins-config-custom/' . $plugin_name . '.xml')) {
                    // if custom config xml file
                    $xml = simplexml_load_file(dirname(dirname(__FILE__)) . '/plugins-config-custom/' . $plugin_name . '.xml');

                    // if node doesn't exist, fallback to default xml
                    if (!isset($xml->{$js_config})) {
                        $xml = simplexml_load_file(dirname(dirname(__FILE__)) . '/plugins-config/' . $plugin_name . '.xml');
                    }
                } else {
                    $xml = simplexml_load_file(dirname(dirname(__FILE__)) . '/plugins-config/' . $plugin_name . '.xml');
                }

                /* if custom include path doesn't exist, we keep default path */

                $path = '/root/' . $js_config . '/includes/' . $type . '/file';
                if (!$xml->xpath($path)) {
                    $path = '/root/default/includes/' . $type . '/file';
                }
                $files = $xml->xpath($path);
                if (!isset($this->css_includes[$plugin_name])) {
                    $this->css_includes[$plugin_name] = [];
                }
                if (!isset($this->js_includes[$plugin_name])) {
                    $this->js_includes[$plugin_name] = [];
                }
                if (!empty($plugin_settings)) {
                    $plugin_settings = $this->encloseArrayKeys('%', $plugin_settings);
                }
                foreach ($files as $file) {
                    $file = (string) $file;
                    if (!empty($plugin_settings) && \strpos($file, '%') !== false) {
                        foreach ($plugin_settings as $key => $value) {
                            if (is_array($value)) {
                                $value =  json_encode($value);
                            }
                            // echo $key . ' - ' . $file . ' => ' . $value . '<br>';
                            $file = str_replace($key, $value, $file);
                        }
                    }
                    if ($type == 'css' && !in_array($file, $this->css_includes[$plugin_name])) {
                        $this->css_includes[$plugin_name][] = (string) $file;
                    } elseif ($type == 'js' && !in_array($file, $this->js_includes[$plugin_name])) {
                        $this->js_includes[$plugin_name][] = (string) $file;

                        /* add framework & language includes for formvalidation plugin */

                        if ($plugin_name == 'formvalidation') {
                            $frameworks = [
                                'bs4'         => 'Bootstrap',
                                'bs5'         => 'Bootstrap5',
                                'bulma'       => 'Bulma',
                                'foundation'  => 'Foundation',
                                'material'    => 'Materialize',
                                'uikit'       => 'Uikit'
                            ];
                            if (array_key_exists($this->framework, $frameworks)) {
                                $f = $this->framework;

                                // add framework to plugin_settings in the xml js_code
                                $this->plugin_settings[$plugin_name][$i]['FRAMEWORK-LOWERCASE'] = strtolower(str_replace('5', '', $frameworks[$f]));
                                $this->plugin_settings[$plugin_name][$i]['FRAMEWORK']   = $frameworks[$f];
                                $this->plugin_settings[$plugin_name][$i]['PLUGINS_URL'] = $this->plugins_url;
                            }
                            $lang = 'en_US'; // default
                            if (array_key_exists('language', $this->plugin_settings[$plugin_name][$i])) {
                                $lang = $this->plugin_settings[$plugin_name][$i]['language'];
                            }
                            $file = 'formvalidation/js/locales/' . $lang . '.min.js';
                            if (!in_array($file, $this->js_includes[$plugin_name])) {
                                $this->js_includes[$plugin_name][] = (string) $file;
                            }

                            // add lang to plugin_settings in the xml js_code
                            $this->plugin_settings[$plugin_name][$i]['language'] = $lang;
                        } elseif ($plugin_name == 'intl-tel-input') {
                            $this->plugin_settings[$plugin_name][$i]['PLUGINS_URL'] = $this->plugins_url;
                        }
                    }
                }
            }
        }
    }

    /**
     * Gets js code generated by js plugins
     * Scroll to user error if any
     * @return void
     */
    protected function getJsCode()
    {
        $nbre_plugins  = count($this->js_plugins);
        $plugins_files = [];
        $plugins_names = [];
        $recaptcha_js  = '';
        $this->js_code  = '<script>' . "\n";
        $this->js_code .= 'if (typeof forms === "undefined") {' . "\n";
        $this->js_code .= '    var forms = [];' . "\n";
        $this->js_code .= '}' . "\n";
        // define the constant for the form
        $this->js_code .= 'forms["' . $this->form_ID . '"] = {};' . "\n";
        // load js files if loadJs enabled
        if ($this->options['useLoadJs']) {
            $this->js_code .= 'if (typeof loadedCssFiles === "undefined") {var loadedCssFiles = [];}' . "\n";
            $this->js_code .= 'if (typeof loadedJsFiles === "undefined") {var loadedJsFiles = [];}' . "\n";
            $this->getIncludes('css');
            $this->getIncludes('js');
            $file_types           = ['css', 'js'];
            $compressed_file_url  = ['css', 'js'];
            $compressed_file_path = ['css', 'js'];
            $includes = [
                'css' => $this->css_includes,
                'js' => $this->js_includes
            ];
            foreach ($file_types as $type) {
                $plugins_files[$type] = [];
                if (!empty($this->framework)) {
                    $framework = $this->framework;
                    if ($this->framework == 'material' && !in_array('materialize', $this->js_plugins)) {
                        $framework = 'materialize';
                    }
                    $compressed_file_url[$type]  = $this->plugins_url . 'min/' . $type . '/' . $framework . '-' . $this->form_ID . '.min.' . $type;
                    $compressed_file_path[$type] = $this->plugins_path . 'min/' . $type . '/' . $framework . '-' . $this->form_ID . '.min.' . $type;
                } else {
                    $compressed_file_url[$type]  = $this->plugins_url . 'min/' . $type . '/' . $this->form_ID . '.min.' . $type;
                    $compressed_file_path[$type] = $this->plugins_path . 'min/' . $type . '/' . $this->form_ID . '.min.' . $type;
                }
                // $this->js_includes[$plugin_name][] = (string) $file;
                foreach ($includes[$type] as $plugin_name => $files_array) {
                    foreach ($files_array as $file) {
                        if (strlen($file) > 0) {
                            if (!preg_match('`^(http(s)?:)?//`', $file)) { // if relative path in XML
                                $file = $this->plugins_url . $file;
                            }
                            $plugins_files[$type][] = $file;
                            $plugins_names[$file]   = $plugin_name;
                        }
                    }
                }
                if ($this->checkRewriteCombinedFiles($plugins_files[$type], $compressed_file_path[$type])) {
                    $this->combinePluginFiles($type, $plugins_files[$type], $compressed_file_path[$type]);
                }
            }

            // load css files
            if (count($plugins_files['css']) > 0) {
                $this->js_code .= '    loadjs([';
                if ($this->mode == 'production') {
                    $this->js_code .= '"' . $compressed_file_url['css'] . '"' . "\n";
                } else {
                    $this->js_code .= '"' . implode('", "', $plugins_files['css']) . '"' . "\n";
                }
                $this->js_code .= '    ], {' . "\n";
                $this->js_code .= '        before: function(path, scriptEl) {' . "\n";
                $this->js_code .= '            if (loadedCssFiles.indexOf(path) !== -1) {' . "\n";
                $this->js_code .= '                /* file already loaded - return `false` to bypass default DOM insertion mechanism */' . "\n";
                $this->js_code .= '                return false;' . "\n";
                $this->js_code .= '            }' . "\n";
                $this->js_code .= '            loadedCssFiles.push(path);' . "\n";
                $this->js_code .= '        }' . "\n";
                $this->js_code .= '    });' . "\n";
            }

            // load js files
            $dom_ready_bundles = [];
            if (!empty($this->options['loadJsBundle'])) {
                if (is_array($this->options['loadJsBundle'])) {
                    $dom_ready_bundles = array_merge($dom_ready_bundles, $this->options['loadJsBundle']);
                } else {
                    $dom_ready_bundles[] = $this->options['loadJsBundle'];
                }
            }
            if ($this->mode == 'production') {
                $this->js_code .= '    loadjs(["' . $compressed_file_url['js'] . '"], "' . $this->form_ID . '", {' . "\n";
                $this->js_code .= '            async: false' . "\n";
                $this->js_code .= '        });' . "\n";
                $dom_ready_bundles[] = $this->form_ID;
            } else {
                foreach ($plugins_files['js'] as $js_file) {
                    $bundle_name = ltrim(str_replace($this->plugins_url, '', $js_file), '/');
                    $dom_ready_bundles[] = $bundle_name;
                    $this->js_code .= '    if (!loadjs.isDefined("' . $bundle_name . '")) {' . "\n";
                    $this->js_code .= '        loadjs(["' . $js_file . '"], "' . $bundle_name . '", {' . "\n";
                    $this->js_code .= '            async: false' . "\n";
                    $this->js_code .= '        });' . "\n";
                    $this->js_code .= '    }' . "\n";
                }
            }
            $this->options['openDomReady'] = '    loadjs.ready(["' . implode('", "', $dom_ready_bundles) . '"], function() {' . "\n";
            $this->options['closeDomReady'] = '});';
            if ($this->has_popover) {
                $this->options['openDomReady'] = 'if (typeof(popoverReady) === "undefined") { window.popoverReady = [];}' . "\n";
                $this->options['openDomReady'] .= '    window.popoverReady["' . $this->form_ID . '"] = function () {' . "\n";
                $this->options['closeDomReady'] = '};' . "\n";
                // $this->options['closeDomReady'] .= '});' . "\n";
            }
        }
        $this->js_code .= $this->options['openDomReady'] . "\n";
        $this->js_code .= '    if(top != self&&typeof location.ancestorOrigins!="undefined"){if(location.ancestorOrigins[0]!=="https://preview.codecanyon.net"&&!document.getElementById("drag-and-drop-preview") && document.getElementById("' . $this->form_ID . '")){document.getElementById("' . $this->form_ID . '").addEventListener("submit",function(e){e.preventDefault();console.log("not allowed");return false;});}}' . "\n";
        for ($i = 0; $i < $nbre_plugins; $i++) {
            $plugin_name = $this->js_plugins[$i]; // ex : colorpicker
            $nbre = count($this->js_fields[$plugin_name]);
            for ($j = 0; $j < $nbre; $j++) {
                $selector        = $this->js_fields[$plugin_name][$j];
                $plugin_settings = $this->plugin_settings[$plugin_name][$j];
                $js_config       = $this->js_content[$plugin_name][$j];
                if (file_exists(dirname(dirname(__FILE__)) . '/plugins-config-custom/' . $plugin_name . '.xml')) {
                    // if custom config xml file
                    $xml = simplexml_load_file(dirname(dirname(__FILE__)) . '/plugins-config-custom/' . $plugin_name . '.xml');

                    // if node doesn't exist, fallback to default xml
                    if (!isset($xml->{$js_config})) {
                        $xml = simplexml_load_file(dirname(dirname(__FILE__)) . '/plugins-config/' . $plugin_name . '.xml');
                    }
                } else {
                    $xml = simplexml_load_file(dirname(dirname(__FILE__)) . '/plugins-config/' . $plugin_name . '.xml');
                }
                if ($plugin_name == 'tooltip' && $js_config === 'popover' && !$this->options['useLoadJs']) { // popover without loadJs
                    $this->popover_js_code = '<script>document.addEventListener(\'DOMContentLoaded\', function(event) {' . str_replace('%formId%', $this->form_ID, rtrim((string) $xml->$js_config->js_code) . '});</script>' . "\n");
                } elseif ($plugin_name == 'tooltip' && $js_config === 'popover' && $this->options['useLoadJs']) { // popover with loadJs
                    $this->popover_js_code = '<script>loadjs.ready(["tippyjs/tippy.min.js"], function() {' . str_replace('%formId%', $this->form_ID, rtrim((string) $xml->$js_config->js_code) . '});</script>' . "\n");
                } elseif (!empty($xml->$js_config->js_code)) { // others
                    $this->js_code .= str_replace('%selector%', $selector, rtrim((string) $xml->$js_config->js_code) . "\n");
                }
                // ensure formValidation has replacements (wont if printIncludes('js') hasn't been called)
                if ($plugin_name == 'formvalidation') {
                    // framework
                    $frameworks = [
                        'bs4'         => 'Bootstrap',
                        'bs5'         => 'Bootstrap5',
                        'bulma'       => 'Bulma',
                        'foundation'  => 'Foundation',
                        'material'    => 'Materialize',
                        'tailwind'    => 'Tailwind',
                        'uikit'       => 'Uikit'
                    ];
                    if (array_key_exists($this->framework, $frameworks)) {
                        $f = $this->framework;
                        $plugin_settings['FRAMEWORK-LOWERCASE'] = strtolower(str_replace('5', '', $frameworks[$f]));
                        $plugin_settings['FRAMEWORK']   = $frameworks[$f];
                        $plugin_settings['PLUGINS_URL'] = $this->plugins_url;
                        $default_replacements = ['language' => 'en_EN'];
                    }
                    foreach ($default_replacements as $key => $value) {
                        if (!isset($plugin_settings[$key])) {
                            $plugin_settings[$key] = $default_replacements[$key];
                        }
                    }
                }
                if (!empty($plugin_settings)) {
                    $plugin_settings = $this->encloseArrayKeys('%', $plugin_settings);
                    foreach ($plugin_settings as $key => $value) {
                        if ($value === null) {
                            $value = '';
                        }
                        if ($plugin_name == 'jquery-fileupload') { // fileupload
                            $this->fileupload_js_code = str_replace($key, (string) $value, $this->fileupload_js_code);
                        } else { // others
                            if (is_array($value)) {
                                $value =  json_encode($value);
                            }
                            $this->js_code = str_replace($key, (string) $value, $this->js_code);
                        }
                    }
                }
            }
        }
        // scroll to user error
        if (!empty($this->options['wrapperErrorClass']) && !in_array('modal', $this->js_plugins) && !$this->has_popover) {
            $this->js_code .= "\n" . '    if (document.querySelector(".' . $this->options['wrapperErrorClass'] . '") !== null) {' . "\n";
            $this->js_code .= '        window.scrollTo(0, document.querySelector(".' . $this->options['wrapperErrorClass'] . '").offsetTop - 400);' . "\n";
            $this->js_code .= '    }' . "\n";
        }
        $this->js_code .= $this->options['closeDomReady'] . "\n";

        // recaptcha callback has to be outside domready
        $this->js_code .= $recaptcha_js;
        $this->js_code .= '</script>' . "\n";
    }

    /**
     * Gets label class. (see options).
     *
     * @param  string $element (Optional) 'standardElement', 'radio' or 'checkbox'
     * @param  bool $inline  True or false
     * @return string The element class defined in form options.
     */
    protected function getLabelClass(string $element = 'standardElement', bool $inline = false)
    {
        $class = '';
        $output = '';
        if ($element == 'standardElement' || $element == 'fileinput') { // input, textarea, select
            if ($this->layout == 'horizontal') {
                if (!$this->options['horizontalLabelWrapper']) {
                    $class = $this->options['horizontalLabelCol'] . ' ' . $this->options['horizontalLabelClass'];
                } else {
                    $class = $this->options['horizontalLabelClass'];
                }
                if ($element == 'fileinput') {
                    $class .= ' fileinput-label';
                }
            } elseif ($this->layout == 'vertical') {
                if (!$this->options['verticalLabelWrapper']) {
                    $class     = $this->options['verticalLabelClass'];
                }
            }
            $class = trim($class);
            if (!empty($class)) {
                $output = ' class="' . $class . '"';
            } else {
                $output = '';
            }
        } elseif ($element == 'radio') {
            if ($inline && !empty($this->options['inlineRadioLabelClass'])) {
                $output = ' class="' . $this->options['inlineRadioLabelClass'] . '"';
            } elseif (!$inline && !empty($this->options['verticalRadioLabelClass'])) {
                $output = ' class="' . $this->options['verticalRadioLabelClass'] . '"';
            } else {
                $output = '';
            }
        } elseif ($element == 'checkbox') {
            if ($inline && !empty($this->options['inlineCheckboxLabelClass'])) {
                $output = ' class="' . $this->options['inlineCheckboxLabelClass'] . '"';
            } elseif (!$inline && !empty($this->options['verticalCheckboxLabelClass'])) {
                $output = ' class="' . $this->options['verticalCheckboxLabelClass'] . '"';
            } else {
                $output = '';
            }
        }

        return $output;
    }

    /**
     * Wrapps label with col if needed (see options).
     *
     * @param  string $pos 'start' or 'end'
     * @return string The html code of the element wrapper.
     */
    protected function getLabelCol(string $pos)
    {
        $output = '';
        if ($this->layout == 'horizontal' && !empty($this->options['horizontalLabelCol'])) {
            if ($pos == 'start') {
                $output = '<div class="' . $this->options['horizontalLabelCol'] . '">';
            } else { // end
                $output = '</div>';
            }
        } elseif ($this->layout == 'vertical' && !empty($this->options['verticalLabelClass'])) {
            if ($pos == 'start') {
                $output = '<div class="' . $this->options['verticalLabelClass'] . '">';
            } else { // end
                $output = '</div>';
            }
        }

        return $output;
    }

    /**
     * Automaticaly adds requiredMark (see options) to labels's required fields
     *
     * @param  string $label The element label
     * @param  string $attr  The element attributes
     * @return string The element label if required html markup if needed
     */
    protected function getRequired(string $label, string $attr)
    {
        if (preg_match('`required`', $attr)) {
            $dom = new \DOMDocument;
            $dom->loadXML('<div>' . $label . '</div>');
            $elements = $dom->documentElement;
            $output = '';
            foreach ($elements->childNodes as $entry) {
                if ($entry->nodeName == '#text') {
                    $output .= $entry->textContent . ' ' . $this->options['requiredMark'];
                } else {
                    $output .= $entry->ownerDocument->saveHTML($entry);
                }
            }

            return $output;
        } else {
            return $label;
        }
    }

    /**
     * Gets element value
     *
     * Returns default value if not empty
     * Else returns session value if it exists
     * Else returns an emplty string
     *
     * @param  string $name  The element name
     * @param  string $value The default value
     * @return string The element value
     */
    protected function getValue(string $name, string $value)
    {
        $output = '';

        $form_ID = $this->form_ID;
        if ((!empty($value) || is_numeric($value)) && !is_array($value)) {
            $output = htmlspecialchars($value);
        } elseif (isset($_SESSION[$form_ID][$name])) {
            $output = htmlspecialchars($_SESSION[$form_ID][$name]);
        } elseif (preg_match('`([^\\[]+)\[([^\\]]+)\]`', $name, $out)) { // arrays
            $array_name = $out[1];
            $array_key = $out[2];
            if (isset($_SESSION[$form_ID][$array_name][$array_key])) {
                $output = htmlspecialchars($_SESSION[$form_ID][$array_name][$array_key]);
            } else {
                $output = htmlspecialchars($_SESSION[$form_ID][$name]);
            }
        }

        return $output;
    }

    /**
     * check the form layout & options to see if labels should or not be wrapped
     *
     * @return boolean
     */
    protected function hasLabelWrapper()
    {
        return $this->layout === 'vertical' && $this->options['verticalLabelWrapper'] || $this->layout === 'horizontal' && $this->options['horizontalLabelWrapper'];
    }

    /**
     * check if name belongs to a group input
     *
     * @param  string $name
     * @return boolean
     */
    protected function isGrouped(string $name)
    {
        foreach ($this->input_grouped as $input_grouped) {
            if (in_array($name, $input_grouped)) {
                return true;
            }
        }
        return false;
    }

    /**
     * output element html code including wrapper, label, element with group, icons, ...
     *
     * @param  string  $start_wrapper   i.e. <div class="row">
     * @param  string  $end_wrapper     i.e. </div>
     * @param  string  $start_label     i.e. <label class="small-3 columns text-right middle main-label">Vertical radio
     * @param  string  $end_label       i.e. </label>
     * @param  string  $start_col       i.e. <div class="small-9 columns">
     * @param  string  $end_col         i.e. </div>
     * @param  string  $element_html    i.e. <fieldset><input type="radio" id="vertical-radio_0" name="vertical-radio" value="1" ><label for="vertical-radio_0" >One</label></fieldset>
     * @param  bool    $wrap_into_label
     * @return string element html code
     */
    protected function outputElement(string $start_wrapper, string $end_wrapper, string $start_label, string $end_label, string $start_col, string $end_col, string $element_html, bool $wrap_into_label)
    {
        $html = $start_wrapper;
        if (!empty($start_label) && $wrap_into_label) {
            $html .= $start_label . $start_col . $element_html . $end_col . $end_label;
        } else {
            $label_col = 0;
            if (preg_match('`(\d+)`', $this->options['horizontalLabelCol'], $out)) {
                $label_col = $out[1];
            }
            if ($this->framework === 'material') {
                if ($label_col < 1 && !strpos($start_label, 'main-label') && !strpos($element_html, 'no-autoinit')) {
                    // label after element if label is into col div except radio & checkbox
                    $html .= $start_col . $element_html . $start_label . $end_label . $end_col;
                } else {
                    // label before element
                    $html .= $start_label . $end_label . $start_col . $element_html . $end_col;
                }
            } else {
                // label before element
                $html .= $start_label . $end_label . $start_col . $element_html . $end_col;
            }
        }
        $html .= $end_wrapper;

        return $html;
    }

    /**
     * Gets errors stored in session
     */
    protected function registerErrors()
    {
        $form_ID = $this->form_ID;
        foreach ($_SESSION['errors'][$form_ID] as $field => $message) {
            /* remove dot syntax (field.index => field */

            $field = preg_replace('`\.[^\s]+`', '', $field);
            $this->error_fields[$field] = $message;
        }

        if (isset($_SESSION['errors'][$form_ID])) {
            $error_keys = array_keys($_SESSION['errors'][$form_ID]);
            // register hcaptcha error
            if (in_array('h-captcha-response', $error_keys)) {
                $this->has_hcaptcha_error  = true;
                $this->hcaptcha_error_text = $_SESSION['errors'][$form_ID]['h-captcha-response'];
            }
            // register recaptcha error
            if (in_array('g-recaptcha-response', $error_keys)) {
                $this->has_recaptcha_error  = true;
                $this->recaptcha_error_text = $_SESSION['errors'][$form_ID]['g-recaptcha-response'];
            }
        }
    }

    /**
     * When the form is posted, values are passed in session
     * to be keeped and displayed again if posted values aren't correct.
     * @param string $name The name of the field
     * @param string $attr The attributes of the field
     */
    protected function registerField(string $name, string $attr)
    {
        $form_ID = $this->form_ID;
        if (!isset($_SESSION[$form_ID])) {
            $_SESSION[$form_ID]           = [];
        }
        if (!isset($_SESSION[$form_ID]['fields'])) {
            $_SESSION[$form_ID]['fields'] = [];
        }
        if (!isset($_SESSION[$form_ID]['required_fields'])) {
            $_SESSION[$form_ID]['required_fields'] = [];
        }
        $name = preg_replace('`(.*)\[\]`', '$1', $name); // if $name is an array, we register without hooks ([])
        if (!in_array($name, $_SESSION[$form_ID]['fields'])) {
            $_SESSION[$form_ID]['fields'][] = $name;
        }
        if (!isset($_SESSION[$form_ID]['required_fields_conditions'])) {
            $_SESSION[$form_ID]['required_fields_conditions'] = [];
        } else {
            // reset dependent field condition
            $_SESSION[$form_ID]['required_fields_conditions'][$name] = '';
        }

        // register required fields
        if (preg_match('`required`', $attr) && !in_array($name, $_SESSION[$form_ID]['required_fields'])) {
            $_SESSION[$form_ID]['required_fields'][] = $name;
        }

        // register required conditions if we're into dependent fields
        if (!empty($this->current_dependent_data)) {
            $last_element = $this->current_dependent_data[array_key_last($this->current_dependent_data)];
            $_SESSION[$form_ID]['required_fields_conditions'][$name] = $last_element;
        }
    }

    /**
     * convert relative url to absolute
     *
     * @param string $rel  the url to convert
     * @param string $base the url of the origin file
     *
     * @return the absolute url
     */
    private function rel2abs(string $rel, string $base)
    {
        $output = '';

        // remove beginning & ending quotes
        $rel = preg_replace('`^([\'"]?)([^\'"]+)([\'"]?)$`', '$2', $rel);

        // parse base URL  and convert to local variables: $scheme, $host,  $path
        extract(parse_url($base));

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');

        if (strpos($rel, "//") === 0) {
            $output = $scheme . ':' . $rel;
        } elseif (parse_url($rel, PHP_URL_SCHEME) != '') {
            // return if already absolute URL
            $output = $rel;
        } elseif ($rel[0] == '#' || $rel[0] == '?') {
            // queries and anchors
            $output = $base . $rel;
        } else {
            // remove non-directory element from path
            $path = preg_replace('#/[^/]*$#', '', $path);

            // destroy path if relative url points to root
            if ($rel[0] ==  '/') {
                $path = '';
            }

            // dirty absolute URL
            $abs = $host . $path . "/" . $rel;

            // replace '//' or  '/./' or '/foo/../' with '/'
            $abs = preg_replace("/(\/\.?\/)/", "/", $abs);
            $abs = preg_replace("/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs);

            $output = $scheme . '://' . $abs;
        }

        // absolute URL is ready!
        return $output;
    }

    /**
     * removes specific attribute from list (ex : removes 'checked="checked"' from radio in other than default has been stored in session)
     *
     * @param  string $attr_to_remove ex : checked
     * @param  string $attr_string    ex : checked="checked", required
     * @return string attributes without the removed one
     */
    protected function removeAttribute(string $attr_to_remove, string $attr_string)
    {
        if (preg_match('`,(\s)?' . $attr_to_remove . '((\s)?=(\s)?([\'|"])?' . $attr_to_remove . '([\'|"])?)?`', $attr_string)) { // beginning comma
            $attr_string = preg_replace('`,(\s)?' . $attr_to_remove . '((\s)?=(\s)?([\'|"])?' . $attr_to_remove . '([\'|"])?)?`', '', $attr_string);
        } elseif (preg_match('`' . $attr_to_remove . '((\s)?=(\s)?([\'|"])?' . $attr_to_remove . '([\'|"])?(\s)?)?,`', $attr_string)) { // ending comma
            $attr_string = preg_replace('`' . $attr_to_remove . '((\s)?=(\s)?([\'|"])?' . $attr_to_remove . '([\'|"])?(\s)?)?,`', '', $attr_string);
        } elseif (preg_match('`\s?' . $attr_to_remove . '((\s)?=(\s)?([\'|"])?' . $attr_to_remove . '([\'|"])?(\s)?)?`', $attr_string)) { // no comma
            $attr_string = preg_replace('`\s?' . $attr_to_remove . '((\s)?=(\s)?([\'|"])?' . $attr_to_remove . '([\'|"])?(\s)?)?`', '', $attr_string);
        }

        return $attr_string;
    }

    /**
     * Allows to group inputs in the same wrapper (12 inputs max.)
     *
     * @param string $name        The input name
     * @param string $wrapper_pos start | end
     * @param string $wrapper     | end     elementsWrapper | checkboxWrapper | radioWrapper | buttonWrapper
     * @return string html of the wrapper
     */
    protected function setInputGroup(string $name, string $wrapper_pos, string $wrapper)
    {
        $output = '';

        if (!empty($this->options[$wrapper])) {
            $grouped = false;
            $input_pos = ''; // start | middle | end
            $pattern_2_wrappers = '`<([^>]+)><([^>]+)></([^>]+)></([^>]+)>`';
            if ($wrapper == 'elementsWrapper') {
                $start_wrapper = $this->elements_start_wrapper;
                $end_wrapper   = $this->elements_end_wrapper;
            } elseif ($wrapper == 'checkboxWrapper') {
                $start_wrapper = $this->checkbox_start_wrapper;
                $end_wrapper   = $this->checkbox_end_wrapper;
            } elseif ($wrapper == 'radioWrapper') {
                $start_wrapper = $this->radio_start_wrapper;
                $end_wrapper   = $this->radio_end_wrapper;
            } elseif ($wrapper == 'buttonWrapper') {
                $start_wrapper = $this->button_start_wrapper;
                $end_wrapper   = $this->button_end_wrapper;
            }
            if ($wrapper_pos == 'start') {
                if ($this->options['centerContent']['center']) {
                    $centered_class = ' phpfb-centered';
                    if ($this->options['centerContent']['stack']) {
                        $centered_class .= ' phpfb-centered-stacked';
                    }
                    $start_wrapper = preg_replace('`class="([^"]+)"`', 'class="$1' . $centered_class . '"', $start_wrapper);
                }
                foreach ($this->input_grouped as $input_grouped) {
                    for ($i = 1; $i < 12; $i++) {
                        $input = 'input_' . ($i + 1);
                        $next_input = 'input_' . ($i + 2);
                        if (isset($input_grouped[$input]) && $name == $input_grouped[$input]) {
                            $grouped = true;
                            if (isset($input_grouped[$next_input])) {
                                $input_pos = 'middle';
                            } else {
                                $input_pos = 'end';
                            }
                        }
                    }
                }
                if ($grouped && $input_pos == 'middle' || $input_pos == 'end') {
                    if (preg_match($pattern_2_wrappers, $this->options[$wrapper], $out)) {
                        $output = '<' . $out[2] . '>';
                    } else {
                        $output = '';
                    }
                } else {
                    $output = $start_wrapper;
                }
            } elseif ($wrapper_pos == 'end') {
                foreach ($this->input_grouped as $input_grouped) {
                    for ($i = 0; $i < 12; $i++) {
                        $input = 'input_' . ($i + 1);
                        $next_input = 'input_' . ($i + 2);
                        if ($i == 0 && $name == $input_grouped[$input]) {
                            $grouped = true;
                            $input_pos = 'start';
                        } elseif (isset($input_grouped[$input]) && $name == $input_grouped[$input] && isset($input_grouped[$next_input])) {
                            $input_pos = 'middle';
                        }
                    }
                }
                if ($grouped && $input_pos == 'start' || $input_pos == 'middle') {
                    if (preg_match($pattern_2_wrappers, $this->options[$wrapper], $out)) {
                        $output = '</' . $out[3] . '>';
                    } else {
                        $output = '';
                    }
                } else {
                    $output = $end_wrapper;
                }
            }
        }

        return $output;
    }

    /**
     * start custom wrapper div according to $conf_name
     *
     * @param string $name      the element name
     * @param string $conf_name specific configuration name: bulma_control|bulma_select|bulma_select_multiple|tailwind_horizontal_radio_checkbox_inline|tailwind_vertical_radio_checkbox_inline
     *
     * @return string $output       the html start div
     */
    protected function startElementCustomWrapper(string $name, string $conf_name)
    {
        $output = '';
        if ($conf_name === 'bulma_control') {
            $class = 'control is-expanded';
            if (\array_key_exists($name, $this->fields_with_icons)) {
                $icon_pos = 'left';
                if ($this->fields_with_icons[$name] === 'after') {
                    $icon_pos = 'right';
                }
                $class .= ' has-icons-' . $icon_pos;
            }
            $output = '<div class="' . $class . '">';
        } elseif ($conf_name === 'bulma_select') {
            $output .= '<div class="select is-fullwidth">';
        } elseif ($conf_name === 'bulma_select_multiple') {
            $output .= '<div class="select is-multiple is-fullwidth">';
        } elseif ($conf_name === 'bulma_field' || $conf_name === 'bulma_field_multiline') {
            $class = 'field';
            if ($conf_name === 'bulma_field_multiline') {
                $class .= ' is-grouped is-grouped-multiline';
            }
            $output = '<div class="' . $class . '">';
        } elseif ($conf_name === 'tailwind_horizontal_radio_checkbox_inline' || $conf_name === 'tailwind_vertical_radio_checkbox_inline') {
            $output = '<div class="flex-auto">';
        }

        return $output;
    }
}
