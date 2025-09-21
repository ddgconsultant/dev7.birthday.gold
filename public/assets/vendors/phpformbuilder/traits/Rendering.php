<?php

declare(strict_types=1);

namespace phpformbuilder\traits;

use phpformbuilder\Form;

trait Rendering
{
    /**
     * Renders the HTML code of the form.
     *
     * @param bool $debug   (Optional) True or false.
     *                         If true, the HTML code will be displayed
     * @param bool $display (Optional) True or false.
     *                         If false, the HTML code will be returned but not displayed.
     * @return \phpformbuilder\Form
     *
     */
    public function render(bool $debug = false, bool $display = true)
    {
        // wrapper for popover | modal plugins
        $html = $this->form_start_wrapper;

        if (!empty($_SERVER['QUERY_STRING'])) {
            $get = '?' . $_SERVER['QUERY_STRING'];
        }
        if (empty($this->action)) {
            $this->action = htmlspecialchars($_SERVER["PHP_SELF"]);
        }
        $html .= '<form ';
        if (!empty($this->form_ID)) {
            $html .= 'id="' . $this->form_ID . '" ';
        }
        $html .= 'action="' . $this->action;
        if (isset($get) && $this->add_get_vars) {
            $html .= $get;
        }
        $html .= '" method="' . $this->method . '"';
        if ($this->has_file) {
            $html .= ' enctype="multipart/form-data"';
        }

        /* layout */

        $attr = $this->getAttributes($this->form_attr);

        if (count($this->error_fields) > 0) {
            $attr = $this->addClass('phpfb-has-error', $attr);
        }

        if (strpos($attr, 'data-accordion') !== false) {
            $attr = $this->addClass('js-badger-accordion', $attr);
        }

        if ($this->layout == 'horizontal' && !empty($this->options['formHorizontalClass'])) {
            $attr = $this->addClass($this->options['formHorizontalClass'], $attr);
        } elseif ($this->layout == 'inline' && !empty($this->options['formInlineClass'])) {
            $attr = $this->addClass($this->options['formInlineClass'], $attr);
        } elseif (!empty($this->options['formVerticalClass'])) {
            $attr = $this->addClass($this->options['formVerticalClass'], $attr);
        }

        // validator class to help with plugins
        if (in_array('formvalidation', $this->js_plugins)) {
            $attr = $this->addClass('has-validator', $attr);
        }

        // ajax class to help with plugins
        if ($this->options['ajax']) {
            $attr = $this->addClass('ajax-form', $attr);
        }
        if ($this->framework == 'material') {
            $attr = $this->addClass('material-form', $attr);
            if (!in_array('materialize', $this->js_plugins)) {
                $attr = $this->addClass('materialize-form', $attr);
            }
        } else {
            $attr = $this->addClass($this->framework . '-form', $attr);
            if ($this->framework === 'bulma' && $this->layout === 'horizontal') {
                $attr = $this->addClass('bulma-form-horizontal', $attr);
            }
        }
        if (!empty($attr)) {
            $html .= ' ' . $attr;
        }
        $html .= '>';
        if (!empty($this->error_msg)) { // if iCheck used with material
            $html .= $this->error_msg;
        }
        if (!empty($this->hidden_fields)) {
            $html .= '<div>' . $this->hidden_fields . '</div>';
        }
        $html .= $this->html;
        if (!empty($this->txt)) {
            $html .= $this->txt;
        }
        if (!empty($this->end_fieldset)) {
            $html .= $this->end_fieldset;
        }
        $html .= '</form>';
        $html .= $this->form_end_wrapper;

        // add Recaptcha js callback function
        if (!empty($this->recaptcha_js_callback)) {
            $html .= $this->recaptcha_js_callback;
        }

        if ($debug) {
            $html = $this->cleanHtmlOutput($html); // beautify html
        }

        if ($this->options['ajax'] && $_SERVER['REQUEST_METHOD'] !== 'POST') { // if ajax option enabled
            $cssfiles = explode("\n", $this->printIncludes('css', false, false));
            $jsfiles  = preg_replace("/[\r\n]/", "", $this->printIncludes('js', false, false));
            $html .= $jsfiles;

            $script = '<script>' . "\n";
            // set the 'loadAjaxForm' CustomEvent
            $script .= 'if (typeof(loadAjaxFormEvent) === \'undefined\') {' . "\n";
            $script .= '    var loadAjaxFormEvent = [];' . "\n";
            $script .= '}' . "\n";
            $script .= 'loadAjaxFormEvent[\'' . $this->form_ID . '\'] = new Event(\'loadAjaxForm' . $this->form_ID . '\');' . "\n";
            // script to submit with ajax
            $script .= 'if (typeof(link0) === \'undefined\') {' . "\n";
            $cssfilescount = count($cssfiles);
            for ($i = 0; $i < $cssfilescount; $i++) {
                $cssfile = $cssfiles[$i];
                $link_var = 'link' . $i;
                if (preg_match('`href="([^"]+)"`', $cssfile, $out)) {
                    $script .= '    let ' . $link_var . ' = document.createElement("link");' . "\n";
                    $script .= '    ' . $link_var . '.rel = "stylesheet";' . "\n";
                    $script .= '    ' . $link_var . '.media = "screen";' . "\n";
                    $script .= '    ' . $link_var . '.href = "' . $out[1] . '";' . "\n";
                    $script .= '    document.head.appendChild(' . $link_var . ');' . "\n";
                }
            }
            $script .= '}' . "\n";
            $script .= $this->options['openDomReady'] . "\n";
            $script .= '    var $form = document.getElementById(\'' . $this->form_ID . '\');' . "\n";

            // if formvalidation plugin is enabled,
            // ajax submit is done by the plugin with the 'core.form.valid' event
            $script .= '    if(!$form.classList.contains("has-validator")) {' . "\n";
            $script .= '        $form.addEventListener(\'submit\', function (e) {' . "\n";
            $script .= '            e.preventDefault();' . "\n";
            $script .= '            let data = new FormData($form);' . "\n";
            $script .= '            fetch($form.getAttribute(\'action\'), {' . "\n";
            $script .= '                method: \'post\',' . "\n";
            $script .= '                body: new URLSearchParams(data).toString(),' . "\n";
            $script .= '                headers: {' . "\n";
            $script .= '                    \'Content-type\': \'application/x-www-form-urlencoded\'' . "\n";
            $script .= '                },' . "\n";
            $script .= '                cache: \'no-store\',' . "\n";
            $script .= '                credentials: \'include\'' . "\n";
            $script .= '            }).then(function (response) {' . "\n";
            $script .= '                return response.text()' . "\n";
            $script .= '            }).then(function (data) {' . "\n";
            $script .= '                let $formContainer = document.querySelector(\'*[data-ajax-form-id="' . $this->form_ID . '"]\');' . "\n";
            $script .= '                $formContainer.innerHTML = \'\';' . "\n";
            $script .= '                loadData(data, \'#\' + $formContainer.id).then(() => {' . "\n";
            $script .= '                    window.document.dispatchEvent(loadAjaxFormEvent[\'' . $this->form_ID . '\']);' . "\n";
            $script .= '                });' . "\n";
            $script .= '            }).catch(function (error) {' . "\n";
            $script .= '                console.log(error);' . "\n";
            $script .= '            });' . "\n";
            $script .= '        });' . "\n";
            $script .= '    };' . "\n";
            $script .= $this->options['closeDomReady'] . "\n";
            $script .= '</script>' . "\n";

            $html .= $script;
            $html .= $this->printJsCode(false, false);

            // trigger the 'loadAjaxForm' CustomEvent
            $html .= '<script>window.document.dispatchEvent(loadAjaxFormEvent[\'' . $this->form_ID . '\']);</script>' . "\n";
        }
        if ($debug) {
            echo '<pre class="prettyprint">' . htmlspecialchars($html) . '</pre>';
        } elseif (!$display) {
            return $html;
        } else {
            echo $html;
        }

        return $this;
    }

    /**
     * Prints html code to include css or js dependancies required by plugins.
     * i.e.:
     *     <link rel="stylesheet" ... />
     *     <script src="..."></script>
     *
     * @param string  $type                 value : 'css' or 'js'
     * @param bool $debug                (Optional) True or false.
     *                                      If true, the html code will be displayed
     * @param bool $display              (Optional) True or false.
     *                                      If false, the html code will be returned but not displayed.
     * @return string|object $final_output|$this
     */
    public function printIncludes(string $type, bool $debug = false, bool $display = true)
    {
        $this->getIncludes($type);
        $normal_output        = '';
        $compressed_output    = '';
        if (!empty($this->framework)) {
            $framework = $this->framework;
            if ($this->framework == 'material' && !in_array('materialize', $this->js_plugins)) {
                $framework = 'materialize';
            }
            $compressed_file_url  = $this->plugins_url . 'min/' . $type . '/' . $framework . '-' . $this->form_ID . '.min.' . $type;
            $compressed_file_path = $this->plugins_path . 'min/' . $type . '/' . $framework . '-' . $this->form_ID . '.min.' . $type;
        } else {
            $compressed_file_url  = $this->plugins_url . 'min/' . $type . '/' . $this->form_ID . '.min.' . $type;
            $compressed_file_path = $this->plugins_path . 'min/' . $type . '/' . $this->form_ID . '.min.' . $type;
        }
        $final_output         = '';
        $plugins_files        = [];

        if ($type == 'css') {
            $compressed_output = '<link href="' . $compressed_file_url . '" rel="stylesheet" media="screen">' . "\n";
            foreach ($this->css_includes as $plugin_name) {
                foreach ($plugin_name as $css_file) {
                    if (strlen($css_file) > 0 && !in_array($css_file, Form::$instances['css_files'])) {
                        Form::$instances['css_files'][] = $css_file;
                        if (!preg_match('`^(http(s)?:)?//`', $css_file)) { // if absolute path in XML
                            $css_file = $this->plugins_url . $css_file;
                        }
                        if ($this->mode == 'production') {
                            $plugins_files[] = $css_file;
                        }
                        $normal_output .= '<link href="' . $css_file . '" rel="stylesheet" media="screen">' . "\n";
                    }
                }
            }
        } elseif ($type == 'js') {
            $defer = ' defer';
            if ($this->options['deferScripts'] !== true) {
                $defer = '';
            }
            if ($this->options['useLoadJs'] !== true) {
                $compressed_output = '<script src="' . $compressed_file_url . '"' . $defer . '></script>';
            }
            foreach ($this->js_includes as $plugin_name) {
                foreach ($plugin_name as $js_file) {
                    if (strlen($js_file) > 0 && !in_array($js_file, Form::$instances['js_files'])) {
                        Form::$instances['js_files'][] = $js_file;
                        if (!preg_match('`^(http(s)?:)?//`', $js_file)) { // if relative path in XML
                            $js_file = $this->plugins_url . $js_file;
                        }
                        if ($this->mode == 'production') {
                            $plugins_files[] = $js_file;
                            if (strpos($js_file, 'hcaptcha.com') !== false || strpos($js_file, 'www.google.com/recaptcha') !== false || strpos($js_file, 'tinymce/tinymce.min.js') !== false) {
                                $compressed_output = $compressed_output . '<script src="' . $js_file . '"></script>';
                            }
                        }
                        if ($this->options['useLoadJs'] !== true || strpos($js_file, 'hcaptcha.com') !== false || strpos($js_file, 'www.google.com/recaptcha') !== false || strpos($js_file, 'tinymce/tinymce.min.js') !== false) {
                            $normal_output .= '<script src="' . $js_file . '"></script>' . "\n";
                        }
                    }
                }
            }
        }
        if ($this->mode == 'production') {
            $final_output = $compressed_output;
            $error_msg = '';

            $rewrite_combined_file = $this->checkRewriteCombinedFiles($plugins_files, $compressed_file_path);

            if ($rewrite_combined_file) {
                $error_msg = $this->combinePluginFiles($type, $plugins_files, $compressed_file_path);
                if (!empty($error_msg)) {
                    $this->buildErrorMsg($error_msg);
                    $final_output = $normal_output;
                }
            }
        } else {
            $final_output = $normal_output;
        }
        if ($debug) {
            echo '<pre class="prettyprint">' . htmlspecialchars($final_output) . '</pre>';
        }
        if (!$display) {
            return $final_output;
        } else {
            echo $final_output;
            return $this;
        }
    }

    /**
     * Prints js code generated by plugins.
     * @param bool $debug   (Optional) True or false.
     *                         If true, the html code will be displayed
     * @param bool $display (Optional) True or false.
     *                         If false, the html code will be returned but not displayed.
     * @return \phpformbuilder\Form
     */
    public function printJsCode(bool $debug = false, bool $display = true)
    {
        $this->getJsCode();
        if ($debug) {
            echo '<pre class="prettyprint">' . htmlspecialchars($this->popover_js_code) . '</pre>';
            echo '<pre class="prettyprint">' . htmlspecialchars($this->js_code) . '</pre>';
        }
        if (empty($display)) {
            return $this->popover_js_code . $this->js_code;
        } else {
            echo $this->popover_js_code . $this->js_code;
        }

        return $this;
    }
}
