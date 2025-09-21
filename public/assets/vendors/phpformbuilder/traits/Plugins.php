<?php

declare(strict_types=1);

namespace phpformbuilder\traits;

trait Plugins
{
    /**
     * Adds a hcaptcha div
     *
     * @param  string $sitekey hcaptcha key
     * @param  string $attr    (Optional) Can be any HTML input attribute
     * @return \phpformbuilder\Form
     */
    public function addHcaptcha(string $sitekey, string $attr = ''): \phpformbuilder\Form
    {
        if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1') {
            // localhost settings
            $sitekey = '10000000-ffff-ffff-ffff-000000000001';
        }
        $attr          = $this->getAttributes($attr);
        $attr          = $this->addClass('h-captcha', $attr);
        $start_wrapper = $this->setInputGroup('', 'start', 'elementsWrapper');
        $start_col     = $this->getElementCol('start', 'recaptcha');
        $end_col       = $this->getElementCol('end', 'recaptcha');
        $end_wrapper   = $this->setInputGroup('', 'end', 'elementsWrapper');
        $this->addHtml($start_wrapper);
        $this->addHtml($start_col);
        $this->addHtml('<div id="hcaptcha-' . $this->form_ID . '" data-sitekey="' . $sitekey . '" ' . $attr . '></div>');

        if ($this->has_hcaptcha_error) {
            $this->addHtml('<p class="has-error ' . $this->options['textErrorClass'] . '" style="display:block;">' . $this->hcaptcha_error_text . '</p>');
        }

        $this->addHtml('<br>');
        $this->addHtml($end_col);
        $this->addHtml($end_wrapper);
        $this->addPlugin('hcaptcha', '#' . $this->form_ID, 'default', ['sitekey' => $sitekey, 'formId' => $this->form_ID]);

        return $this;
    }

    /**
     * Gets and tests plugins url ($this->plugins_url).
     * Adds a javascript plugin to the selected field(s)
     *
     * @param  string $plugin_name     The name of the plugin,
     *                                 must be the name of the
     *                                 xml file in
     *                                 plugins-config dir
     *                                 without extension.
     *                                 Example : colorpicker
     * @param  string $selector        The jQuery style selector.
     *                                 Examples : #colorpicker
     *                                 .colorpicker
     * @param  string $js_config       (Optional)         The xml node where your plugin code is
     *                                 in plugins-config/[your-plugin.xml] file
     * @param  array  $plugin_settings (Optional)   An associative array containing
     *                                 the strings to search as keys
     *                                 and replacement values as data.
     *                                 Strings will be replaced with data
     *                                 in js_code xml node of your
     *                                 plugins-config/[your-plugin.xml] file.
     * @return \phpformbuilder\Form
     */
    public function addPlugin(string $plugin_name, string $selector, string $js_config = 'default', array $plugin_settings = []): \phpformbuilder\Form
    {
        $keep_original_selector_plugins = ['modal', 'popover'];

        // standardizes boolean values by converting them to strings
        $plugin_settings = $this->booleanToString($plugin_settings);

        if (!in_array($plugin_name, $keep_original_selector_plugins) && !preg_match('`' . $this->form_ID . '`', $selector) && !preg_match('`form`', $selector)) {
            // add the form id to selector
            $selector = '#' . $this->form_ID . ' ' . $selector;
        }
        if ($plugin_name == 'nice-check' && $this->framework == 'material') {
            $this->buildErrorMsg('NICE-CHECK PLUGIN + MATERIAL<br>nice-check plugin cannot be used with Material plugin.');
        } elseif ($plugin_name == 'bootstrap-select' && $this->framework == 'foundation') {
            $this->buildErrorMsg('BOOTSTRAP SELECT PLUGIN + FOUNDATION<br>Bootstrap Select plugin cannot be used with FOUNDATION.<br>Use <em>select2</em> instead.');
        }

        // custom PHPCG settings
        if (defined('DATETIMEPICKERS_STYLE') && \DATETIMEPICKERS_STYLE == 'material' && $plugin_name == 'pickadate') {
            if ($js_config == 'pickatime') {
                $plugin_name = 'material-timepicker';
                $js_config = 'default';
            } else {
                $plugin_name = 'material-datepicker';
            }
        }

        if ($plugin_name == 'materialize') {
            $_SESSION['phpfb_framework'] = 'material-bootstrap';
        }
        if ($plugin_name == 'material-datepicker' || $plugin_name == 'material-timepicker') {
            // add Material base css & js if needed
            // if form is loaded with Ajax,
            // material-pickers-base css & js have to be loaded in the main page manually
            if (!in_array('material-pickers-base', $this->js_plugins)) {
                if ($this->framework == 'material') {
                    $this->addPlugin('material-pickers-base', '#', 'materialize', ['pluginsUrl' => $this->plugins_url]);
                } else {
                    $this->addPlugin('material-pickers-base', '#', 'default');
                }
            }
            // set pickers default language
            if (($plugin_name == 'material-datepicker' || $plugin_name == 'material-timepicker') && !array_key_exists('language', $plugin_settings)) {
                if (defined('DATETIMEPICKERS_LANG')) {
                    $plugin_settings['language'] = \DATETIMEPICKERS_LANG;
                } else {
                    $plugin_settings['language'] = 'en_EN';
                }
            }
        } elseif ($plugin_name == 'pickadate' && !array_key_exists('language', $plugin_settings)) {
            // set pickers default language
            if (defined('DATETIMEPICKERS_LANG')) {
                $plugin_settings['language'] = \DATETIMEPICKERS_LANG;
            } else {
                $plugin_settings['language'] = 'en_EN';
            }
        } elseif ($plugin_name == 'nice-check' && !array_key_exists('skin', $plugin_settings)) {
            // set pickers default language
            $plugin_settings['skin'] = 'green';
        } elseif ($plugin_name == 'pretty-checkbox' && !array_key_exists('class', $plugin_settings)) {
            // set pretty-checkbox defaults
            $defaults = [
                'checkboxStyle'  => 'default',
                'radioStyle'     => 'round',
                'color'          => '',
                'fill'           => '',
                'icon'           => '',
                'plain'          => '',
                'animations'     => '',
                'size'           => '',
                'toggle'         => 'false',
                'toggleOnLabel'  => '',
                'toggleOnColor'  => '',
                'toggleOnIcon'   => '',
                'toggleOffLabel' => '',
                'toggleOffColor' => '',
                'toggleOffIcon'  => ''
            ];
            $plugin_settings = \array_merge($defaults, $plugin_settings);
        } elseif ($plugin_name == 'select2' && !array_key_exists('language', $plugin_settings)) {
            // set pickers default language
            $plugin_settings['language'] = 'en';
        } elseif ($plugin_name == 'intl-tel-input' || $plugin_name == 'slimselect') {
            // set slimselect theme
            $find = ['bs4', 'bs5'];
            $replace = ['bootstrap4', 'bootstrap5'];
            $plugin_settings['FRAMEWORK'] = str_replace($find, $replace, $this->framework);
        } elseif ($plugin_name == 'word-character-count' || ($plugin_name == 'tinymce' && $js_config == 'word-char-count')) {
            // pass the $plugin_settings array as a Js object
            if (!array_key_exists('wrapperClassName', $plugin_settings)) {
                $wrapperClassName = [
                    'bs4'         => 'form-text',
                    'bs5'         => 'form-text',
                    'bulma'       => 'help',
                    'foundation'  => 'help-text',
                    'material'    => 'form-text',
                    'tailwind'    => 'mt-2 text-sm',
                    'uikit'       => 'uk-text-small'
                ];
                $className = [
                    'bs4'         => 'text-muted mb-0',
                    'bs5'         => 'text-muted mb-0',
                    'bulma'       => 'has-text-grey',
                    'foundation'  => 'text-muted',
                    'material'    => 'text-muted',
                    'tailwind'    => 'text-gray-500 dark:text-gray-400',
                    'uikit'       => 'uk-text-muted uk-margin-remove-bottom'
                ];
                $errorClassName = [
                    'bs4'         => 'text-danger mb-0',
                    'bs5'         => 'text-danger mb-0',
                    'bulma'       => 'has-text-danger',
                    'foundation'  => 'text-danger',
                    'material'    => 'text-danger',
                    'tailwind'    => 'text-danger-500 dark:text-danger-400',
                    'uikit'       => 'uk-text-danger uk-margin-remove-bottom'
                ];
                $framework = $this->framework;
                if (array_key_exists($this->framework, $wrapperClassName)) {
                    $plugin_settings['wrapperClassName'] = $wrapperClassName[$framework];
                }
                if (array_key_exists($this->framework, $className)) {
                    $plugin_settings['className'] = $className[$framework];
                }
                if (array_key_exists($this->framework, $errorClassName)) {
                    $plugin_settings['errorClassName'] = $errorClassName[$framework];
                }
            }
            $plugin_settings['options'] = json_encode($plugin_settings);
        }
        if (!in_array($plugin_name, $this->js_plugins)) {
            $this->js_plugins[] = $plugin_name;
        }
        if (!isset($this->js_fields[$plugin_name]) || !in_array($selector, $this->js_fields[$plugin_name]) || !in_array($js_config, $this->js_content[$plugin_name])) {
            $this->js_fields[$plugin_name][]       = $selector;
            $this->js_content[$plugin_name][]      = $js_config;
            $this->plugin_settings[$plugin_name][] = $plugin_settings;
        }

        return $this;
    }

    /**
     * Adds a Google Invisible Recaptcha field
     *
     * @param string $sitekey Google recaptcha key
     * @param string $action Google recaptcha action
     * @param string $response_fieldname Google recaptcha response fieldname
     * @param string $xml_config The xml configuration of the plugin
     * @return \phpformbuilder\Form
     */
    public function addRecaptchaV3($sitekey, $action = 'default', $response_fieldname = 'g-recaptcha-response', $xml_config = 'default'): \phpformbuilder\Form
    {
        $action = str_replace('-', '_', $action);
        if (!preg_match('`^[a-zA-Z_0-9]+$`', $action)) {
            $this->buildErrorMsg('Recaptcha V3 Action contains invalid characters. Allowed characters: lowercase, uppercase, underscore');
        } else {
            $this->addInput('hidden', $response_fieldname);
            $this->addPlugin('recaptcha-v3', '#' . $this->form_ID, $xml_config, ['sitekey' => $sitekey, 'action' => $action, 'response_fieldname' => $response_fieldname]);

            if ($this->has_recaptcha_error) {
                $this->addHtml('<p class="has-error ' . $this->options['textErrorClass'] . '" style="display:block">' . $this->recaptcha_error_text . '</p>');
            }
        }

        return $this;
    }

    /**
     * Ends a dependent field block
     *
     * @return \phpformbuilder\Form
     */
    public function endDependentFields(): \phpformbuilder\Form
    {
        $this->addHtml('</div>');

        // remove the last current_dependent_data
        \array_pop($this->current_dependent_data);

        return $this;
    }

    /**
     * wrap form in a modal
     *
     * @param  array $options
     *               $modal_options = [
     * string title       The modal title
     * string title-class The modal title classname(s)
     * string title-tag   The modal title HTML tag
     * string animation   E.g.: 'slide-in-top',
     * bool   blurred     Choose whether the modal backdrop is blurred or not
     * @return \phpformbuilder\Form
     */
    public function modal(array $options = []): \phpformbuilder\Form
    {
        $modal_id = 'modal-' . $this->form_ID;
        $modal_options = [
            'title'       => '',
            'title-class' => '',
            'title-tag'   => 'h2',
            'animation'   => 'fade-in',
            'blur'        => true
        ];
        $modal_options = array_merge($modal_options, $options);
        $this->addPlugin('modal', $modal_id, 'default', ['formId' => $this->form_ID]);

        // set the title
        $modal_title_html = '';
        $modal_title_class = '';

        if (!empty($modal_options['title'])) {
            if (!empty($modal_options['title-class'])) {
                $modal_title_class .= ' class="' . $modal_options['title-class'] . '"';
            }
            $modal_title_html = '<' . $modal_options['title-tag'] . ' id="' . $modal_id . '-title"' . $modal_title_class . '>' . $modal_options['title'] . '</' . $modal_options['title-tag'] . '>';
        }

        $blur_class = '';
        if ($modal_options['blur'] === true) {
            $blur_class = ' modal-overlay-blurred';
        }

        $this->form_start_wrapper = '<div class="micromodal micromodal-' . $modal_options['animation'] . '" id="' . $modal_id . '" aria-hidden="true">';
        $this->form_start_wrapper .= '<div tabindex="-1" class="modal-overlay' . $blur_class . '" data-micromodal-close>';

        $aria_label_title = '';
        if (!empty($modal_options['title'])) {
            $aria_label_title = ' aria-labelledby="' . $modal_id . '-title"';
        }
        $this->form_start_wrapper .= '<div role="dialog" class="modal-container" aria-modal="true"' . $aria_label_title . '">';
        $this->form_start_wrapper .= '<button class="modal-close" aria-label="Close modal" data-micromodal-close></button>';
        $this->form_start_wrapper .= '<header class="modal-header">';
        $this->form_start_wrapper .= $modal_title_html;
        $this->form_start_wrapper .= '</header>';
        $this->form_start_wrapper .= '<div id="' . $modal_id . '-content">';

        $this->form_end_wrapper = '</div></div></div></div>';

        return $this;
    }

    /**
     * wrap form in a popover
     *
     * @return \phpformbuilder\Form
     */
    public function popover(): \phpformbuilder\Form
    {
        $this->has_popover = true;
        $popover_id = 'popover-' . $this->form_ID;
        $this->addPlugin('tooltip', $popover_id, 'popover', ['formId' => $this->form_ID]);

        $this->form_start_wrapper = '<template id="' . $popover_id . '" aria-hidden="true">';
        $this->form_end_wrapper = '</template>';

        $this->options['openDomReady'] = '    if (typeof(popoverReady) === "undefined") { window.popoverReady = [];}' . "\n";
        $this->options['openDomReady'] .= 'popoverReady["' . $this->form_ID . '"] = function() {' . "\n";

        $this->options['closeDomReady'] = '}' . "\n";

        // show popover if the form is posted with some errors
        if (count($this->error_fields) > 0) {
            $this->options['closeDomReady'] .= 'setTimeout(() => {' . "\n";
            $this->options['closeDomReady'] .= 'document.querySelector(\'[data-popover-trigger="' . $this->form_ID . '"]\').dispatchEvent(new Event(\'click\', { bubbles: true }));' . "\n";
            $this->options['closeDomReady'] .= '}, 1000);' . "\n";
        }

        return $this;
    }

    /**
     * Start a hidden block
     * which can contain any element and html
     * Hiden block will be shown on $parent_field change
     * if $parent_field value matches one of $show_values
     *
     * @param  string  $parent_field name of the field which will trigger show/hide
     * @param  string  $show_values  single value or comma separated values which will trigger show.
     * @param  boolean $inverse      if true, dependent fields will be shown if any other value than $show_values is selected.
     * @return \phpformbuilder\Form
     */
    public function startDependentFields($parent_field, $show_values, $inverse = false): \phpformbuilder\Form
    {
        $this->addHtml('<div class="hidden-wrapper off" data-parent="' . $parent_field . '" data-show-values="' . $show_values . '" data-inverse="' . $inverse . '">');
        if (!in_array('dependent-fields', $this->js_plugins)) {
            $this->addPlugin('dependent-fields', '.hidden-wrapper');
        }

        // register data to transmit to dependent fields. Data will be used to know if dependent fields are required or not, depending on parent posted value.
        $this->current_dependent_data[] = [
            'parent_field' => $parent_field,
            'show_values'  => $show_values,
            'inverse'      => $inverse
        ];

        return $this;
    }
}
