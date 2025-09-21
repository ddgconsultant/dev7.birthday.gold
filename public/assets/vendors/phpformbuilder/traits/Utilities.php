<?php

declare(strict_types=1);

namespace phpformbuilder\traits;

use phpformbuilder\FormatHtml;

trait Utilities
{

    /**
     * shortcut to prepend or append any adon to an input
     * @param string $input_name the name of target input
     * @param string $addon_html  addon html code
     * @param string $pos        before | after
     * @return \phpformbuilder\Form
     */
    public function addAddon(string $input_name, string $addon_html, string $pos): \phpformbuilder\Form
    {
        $has_button = preg_match('`<button`i', $addon_html);

        if ($this->framework == 'bs5' || $this->framework == 'bs4' || $this->framework == 'foundation') {
            $this->addInputWrapper('<div class="input-group has-addon-' . $pos . '"></div>', $input_name);
        } elseif ($this->framework == 'bulma') {
            $this->addInputWrapper('<div class="field has-addons"></div>', $input_name);
        } elseif ($this->framework == 'tailwind') {
            $this->addInputWrapper('<div class="flex addon-' . $pos . '"></div>', $input_name);
        } elseif ($this->framework == 'uikit') {
            if (!$has_button) {
                $this->addInputWrapper('<div class="uk-flex uk-flex-wrap uk-position-relative uk-width-1-1"></div>', $input_name);
            } else {
                $this->addInputWrapper('<div class="uk-flex uk-position-relative"></div>', $input_name);
            }
        }

        if ($this->framework == 'bs4') {
            if ($pos == 'before') {
                $input_group_addon_class = 'input-group-prepend';
            } else {
                $input_group_addon_class = 'input-group-append';
            }
            $input_group_addon_class .= ' phpfb-addon-' . $pos;
            if (!$has_button) {
                $addon_html = '<span class="input-group-text">' . $addon_html . '</span>';
            }
            $this->addHtml('<div class="' . $input_group_addon_class . '">' . $addon_html . '</div>', $input_name, $pos);
        } elseif ($this->framework == 'bs5') {
            if (!$has_button) {
                $this->addHtml('<div class="input-group-text phpfb-addon-' . $pos . '">' . $addon_html . '</div>', $input_name, $pos);
            } else {
                $addon_html = $this->addClass('input-group-btn phpfb-addon-' . $pos, $addon_html);
                $this->addHtml($addon_html, $input_name, $pos);
            }
        } elseif ($this->framework == 'bulma') {
            if (!$has_button) {
                $this->addHtml('<p class="control addon-control phpfb-addon-' . $pos . '"><a class="button is-static">' . $addon_html . '</a></p>', $input_name, $pos);
            } else {
                $this->addHtml('<p class="control addon-control phpfb-addon-' . $pos . '">' . $addon_html . '</p>', $input_name, $pos);
            }
        } elseif ($this->framework == 'foundation') {
            if (!$has_button) {
                $this->addHtml('<span class="input-group-label phpfb-addon-' . $pos . '">' . $addon_html . '</span>', $input_name, $pos);
            } else {
                $this->addHtml('<div class="input-group-button phpfb-addon-' . $pos . '">' . $addon_html . '</div>', $input_name, $pos);
            }
        } elseif ($this->framework == 'material') {
            $clazz = 'addon-' . $pos . ' phpfb-addon-' . $pos;
            if (!$has_button) {
                $this->addHtml('<span class="input-group-text ' . $clazz . '">' . $addon_html . '</span>', $input_name, $pos);
            } else {
                $addon_html = $this->addClass($clazz, $addon_html);
                $this->addHtml($addon_html, $input_name, $pos);
            }
        } elseif ($this->framework == 'tailwind') {
            $rounded = 'l';
            $border = 'r';
            if ($pos === 'after') {
                $rounded = 'r';
                $border = 'l';
            }
            $clazz = 'phpfb-addon-' . $pos;
            if (!$has_button) {
                $this->addHtml('<span class="phpfb-addon-' . $pos . ' inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 rounded-' . $rounded . '-md border border-' . $border . '-0 border-gray-300 dark:bg-gray-600 dark:text-gray-400 dark:border-gray-600">' . $addon_html . '</span>', $input_name, $pos);
            } else {
                $addon_html = $this->addClass($clazz, $addon_html);
                $this->addHtml($addon_html, $input_name, $pos);
            }
        } elseif ($this->framework == 'uikit') {
            $clazz = 'uk-text-nowrap addon-' . $pos;
            if ($pos === 'after') {
                $clazz .= ' uk-form-icon-flip';
            }
            $clazz .= ' phpfb-addon-' . $pos;
            if (!$has_button) {
                $this->addHtml('<span class="uk-form-icon ' . $clazz . '">' . $addon_html . '</span>', $input_name, $pos);
            } else {
                $addon_html = $this->addClass($clazz, $addon_html);
                $this->addHtml($addon_html, $input_name, $pos);
            }
        } else {
            $addon_html = $this->addClass('phpfb-addon-' . $pos, $addon_html);
            $this->addHtml($addon_html, $input_name, $pos);
        }
        $this->fields_with_addons[$input_name] = $pos;

        return $this;
    }

    /**
     * add a HTML heading
     *
     * @param  string $html         the heading content text or HTML
     * @param  string $tag_name     (Optional) the heading tag name (h1, h2, ...)
     * @param  string $attr         (Optional) the heading attributes
     * @return void
     */
    public function addHeading(string $html, string $tag_name = 'h4', string $attr = ''): void
    {
        $heading_attr = '';
        if (!empty($attr)) {
            $heading_attr = ' ' . $this->getAttributes($attr);
        }
        if (!$this->hasAccordion) {
            $this->html .= '<' . $tag_name . $heading_attr . '>' . $html . '</' . $tag_name . '>';
        } else {
            $this->html .= '<' . $tag_name . $heading_attr . '><span class="js-badger-accordion-header">' . $html . '</span></' . $tag_name . '>';
        }
    }

    /**
     * Shortcut to add element helper text
     *
     * @param string $helper_text    The helper text or HTML to add.
     * @param string $element_name   the helper text will be inserted just after the element.
     * @return \phpformbuilder\Form
     */
    public function addHelper(string $helper_text, string $element_name): \phpformbuilder\Form
    {
        if (!isset($this->html_element_content[$element_name])) {
            $this->html_element_content[$element_name] = ['before', 'after'];
        }
        // add an id to be refered by the element 'aria-describedby' attribute
        $hsw = preg_replace('`>$`', ' id="' . $element_name . '-helper">', $this->helper_start_wrapper);
        $this->html_element_content[$element_name]['after'][] = $hsw . $helper_text . $this->helper_end_wrapper;

        $this->fields_with_helpers[] = $element_name;

        return $this;
    }

    /**
     * Adds HTML code at any place of the form
     *
     * @param string $html         The html code to add.
     * @param string $element_name (Optional) If not empty, the html code will be inserted
     *                             just before or after the element.
     * @param string $pos          (Optional) If $element_name is not empty, defines the position
     *                             of the inserted html code.
     *                             Values can be 'before' or 'after'.
     * @return \phpformbuilder\Form
     */
    public function addHtml(string $html, string $element_name = '', string $pos = 'after'): \phpformbuilder\Form
    {
        if (!empty($element_name)) {
            if (!isset($this->html_element_content[$element_name])) {
                $this->html_element_content[$element_name] = ['before', 'after'];
            }
            $this->html_element_content[$element_name][$pos][] = $html;
        } else {
            $this->html .= $html;
        }

        return $this;
    }

    /**
     * shortcut to prepend or append icon to an input
     *
     * @param string $input_name the name of target input
     * @param string $icon_html  icon html code
     * @param string $pos        before | after
     * @return \phpformbuilder\Form
     */
    public function addIcon(string $input_name, string $icon_html, string $pos): \phpformbuilder\Form
    {
        $icon_html = $this->addClass('phpfb-addon-' . $pos, $icon_html);
        if ($this->framework == 'bs5' || $this->framework == 'bs4' || $this->framework == 'foundation') {
            $this->addInputWrapper('<div class="input-group has-addon-' . $pos . '"></div>', $input_name);
        } elseif ($this->framework == 'material') {
            $class = 'prefix' . ' icon-' . $pos;
            $icon_html = $this->addClass($class, $icon_html);
        } elseif ($this->framework == 'uikit') {
            $this->addInputWrapper('<div class="uk-flex uk-flex-wrap uk-position-relative uk-width-1-1"></div>', $input_name);
            if ($pos === 'after') {
                $icon_html = $this->addClass('uk-form-icon-flip', $icon_html);
            }
        }
        $start_wrapper = $this->icon_before_start_wrapper;
        $end_wrapper = $this->icon_before_end_wrapper;
        if ($pos === 'after') {
            $start_wrapper = $this->icon_after_start_wrapper;
            $end_wrapper = $this->icon_after_end_wrapper;
        }
        $this->addHtml($start_wrapper . $icon_html . $end_wrapper, $input_name, $pos);

        $this->fields_with_icons[$input_name] = $pos;

        return $this;
    }

    /**
     * Wraps the element with HTML code.
     *
     * @param string $html         The HTML code to wrap the element with.
     *                             The HTML tag must be opened and closed.
     *                             Example : <div class="my-class"></div>
     * @param string $element_name The form element to wrap.
     * @return \phpformbuilder\Form
     */
    public function addInputWrapper(string $html, string $element_name): \phpformbuilder\Form
    {
        $this->input_wrapper[$element_name] = $html;

        return $this;
    }

    /**
     * @param bool $center
     * @param bool $stack
     * @return \phpformbuilder\Form
     */
    public function centerContent(bool $center = true, bool $stack = false): \phpformbuilder\Form
    {
        $this->setOptions(['centerContent' => ['center' => $center, 'stack' => $stack]]);

        return $this;
    }

    /**
     * set html output linebreaks and indent
     * @param  string $html
     * @return string clean html
     */
    public function cleanHtmlOutput(string $html): string
    {
        include_once dirname(dirname(__FILE__)) . '/FormatHtml.php';
        $cleaning_object = new FormatHtml();

        // set linebreaks & indent
        $html = $cleaning_object->html($html);

        // remove empty lines
        // $html = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $html);

        return $html;
    }

    /**
     * End a HTML column
     * @return \phpformbuilder\Form
     */
    public function endCol(): \phpformbuilder\Form
    {
        return $this->endDiv();
    }

    /**
     * End a HTML div element
     *
     * @return \phpformbuilder\Form
     */
    public function endDiv(): \phpformbuilder\Form
    {
        $this->addHtml('</div>');

        return $this;
    }

    /**
     * End a HTML row
     * @return mixed
     */
    public function endRow(): \phpformbuilder\Form
    {
        return $this->endDiv();
    }

    /**
     * Ends a fieldset tag.
     * @return \phpformbuilder\Form
     */
    public function endFieldset(): \phpformbuilder\Form
    {
        if ($this->hasAccordion) {
            $this->html .= '</div>';
        }
        $this->html .= '</fieldset>';

        return $this;
    }

    /**
     * Allows to group inputs in the same wrapper
     *
     * Arguments can be :
     *     -    a single array with fieldnames to group
     *     OR
     *     -    fieldnames given as strings
     *
     * @param string|array $input1 The name of the first input of the group
     *                             OR
     *                             array including all fieldnames
     *
     * @param string $input2 The name of the second input of the group
     * @param string $input3 [optional] The name of the third input of the group
     * @param string $input4 [optional] The name of the fourth input of the group
     * @param string ...etc.
     * @return \phpformbuilder\Form
     */
    public function groupElements(string $input1, string $input2 = '', string $input3 = '', string $input4 = '', string $input5 = '', string $input6 = '', string $input7 = '', string $input8 = '', string $input9 = '', string $input10 = '', string $input11 = '', string $input12 = ''): \phpformbuilder\Form
    {
        $group = [];

        if (is_array($input1)) {
            // if array given
            for ($i = 1; $i <= count($input1); $i++) {
                $group['input_' . $i] = $input1[$i - 1];
            }
        } else {
            $args = func_get_args();
            // if strings given
            for ($i = 0; $i < func_num_args(); $i++) {
                $input = $args[$i];
                if (!empty($input)) {
                    $group['input_' . ($i + 1)] = $input;
                }
            }
        }
        $this->input_grouped[] = $group;

        return $this;
    }

    /**
     * Shortcut for labels & cols options
     *
     * @param int $label_col_number number of columns for label
     * @param int $field_col_number number of columns for fields
     * @param string $breakpoint Bootstrap's breakpoints : xs | sm | md |lg
     * @return \phpformbuilder\Form
     */
    public function setCols(int $label_col_number, int $field_col_number, string $breakpoint = 'sm'): \phpformbuilder\Form
    {
        $options = [];
        if ($this->framework == 'bs5' || $this->framework == 'bs4' || $this->framework == 'material' && $this->layout == 'horizontal') {
            $label_col          = '';
            $label_offset_col   = '';
            $element_col        = '';
            if (!empty($field_col_number) && $field_col_number > 0) {
                $element_col = 'col-' . $breakpoint . '-' . $field_col_number;
            }
            if ($this->framework == 'bs5' || $this->framework == 'bs4') {
                // Bootstrap
                if (!empty($label_col_number) && $label_col_number > 0) {
                    $label_col        = 'col-' . $breakpoint . '-' . $label_col_number;
                    // $label_offset_col = 'offset-' . $breakpoint . '-' . $label_col_number;
                }

                // if no breakpoint (ie: col-6)
                $label_col = str_replace('--', '-', $label_col);
                // $label_offset_col = str_replace('--', '-', $label_offset_col);
                $element_col = str_replace('--', '-', $element_col);

                // if negative, => "col" without number (auto-width)
                if ($label_col_number < 0) {
                    $label_col = 'col';
                    if (!empty($breakpoint)) {
                        $label_col .= '-' . $breakpoint;
                    }
                }
                if ($field_col_number < 0) {
                    $element_col = 'col';
                    if (!empty($breakpoint)) {
                        $element_col .= '-' . $breakpoint;
                    }
                }
                $options = [
                    'horizontalLabelCol'   => $label_col,
                    'horizontalOffsetCol'  => $label_offset_col,
                    'horizontalElementCol' => $element_col
                ];
            } elseif ($this->framework == 'material') {
                // Material Design

                // translate from bs4 breakpoints to material
                $md_breakpoints = [
                    'xs' => 's',
                    'sm' => 'm',
                    'md' => 'l',
                    'lg' => 'xl'
                ];
                $breakpoint = $md_breakpoints[$breakpoint];
                $element_col = 'col ' . $breakpoint . $field_col_number;

                // set full with to the lower breakpoint
                $lower_col = '';
                if ($breakpoint == 'xl') {
                    $lower_col = ' l12';
                } elseif ($breakpoint == 'l') {
                    $lower_col = ' m12';
                } elseif ($breakpoint == 'm') {
                    $lower_col = ' s12';
                }
                $element_col .= $lower_col;
                if (!empty($label_col_number) && $label_col_number > 0) {
                    // Normal input with label in front
                    // elementsWrapper with 2 col divs inside for label & field

                    $label_col = 'col ' . $breakpoint . $label_col_number . $lower_col;
                    $label_offset_col = 'offset-' . $breakpoint . $label_col_number;
                    $options = [
                        'horizontalLabelCol'   => $label_col,
                        'horizontalOffsetCol'  => $label_offset_col,
                        'horizontalElementCol' => 'input-field ' . $element_col
                    ];
                } else {
                    // Material input-field with label inside
                    // elementsWrapper with row + col class, label & field directly inside
                    $options = [
                        'horizontalLabelCol'   => '',
                        'horizontalOffsetCol'  => '',
                        'horizontalElementCol' => 'input-field ' . $element_col
                    ];
                }
            }
        } elseif ($this->framework == 'bulma' && $this->layout == 'horizontal') {
            $label_col         = 'column';
            $label_offset_col  = '';
            $element_col       = 'column';
            $bulma_breakpoints = [
                'xs' => ' is-mobile',
                'sm' => ' is-tablet',
                'md' => ' is-desktop',
                'lg' => ' is-widescreen'
            ];
            $breakpoint = $bulma_breakpoints[$breakpoint];
            if (!empty($label_col_number) && $label_col_number > 0) {
                $label_col .= ' is-' . $label_col_number . $breakpoint;
                $label_offset_col = 'is-offset-' . $label_col_number;
            }
            if (!empty($field_col_number) && $field_col_number > 0) {
                $element_col .= ' is-' . $field_col_number . $breakpoint;
            }
            $options = [
                'horizontalLabelCol'   => $label_col,
                'horizontalOffsetCol'  => $label_offset_col,
                'horizontalElementCol' => $element_col
            ];
        } elseif ($this->framework == 'foundation') {
            $label_col   = '';
            $label_offset_col  = '';
            $element_col = '';
            $foundation_breakpoints = [
                'xs' => 'small',
                'sm' => 'medium',
                'md' => 'medium',
                'lg' => 'large'
            ];
            $breakpoint = $foundation_breakpoints[$breakpoint];
            if ($this->layout == 'horizontal') {
                if (!empty($label_col_number) && $label_col_number > 0) {
                    if ($breakpoint !== 'small') {
                        $label_col = 'small-12 ';
                    }
                    $label_col        .= $breakpoint . '-' . $label_col_number . ' cell';
                    $label_offset_col = $breakpoint . '-offset-' . $label_col_number;
                }
                if (!empty($field_col_number) && $field_col_number > 0) {
                    if ($breakpoint !== 'small') {
                        $element_col = 'small-12 ';
                    }
                    $element_col .= $breakpoint . '-' . $field_col_number . ' cell';
                }
                $options = [
                    'horizontalLabelCol'   => $label_col,
                    'horizontalOffsetCol'  => $label_offset_col,
                    'horizontalElementCol' => $element_col
                ];
            } elseif ($this->layout == 'vertical') {
                if (!empty($label_col_number) && $label_col_number > 0) {
                    $label_col        = $breakpoint . '-' . $label_col_number . ' cell';
                }
                $options = ['verticalLabelClass' => $label_col];
            }
        } elseif ($this->framework == 'tailwind' && $this->layout == 'horizontal') {
            $label_col          = '';
            $label_offset_col  = '';
            $element_col        = '';
            if (!empty($label_col_number) && $label_col_number > 0) {
                $label_col        = 'col-span-' . $label_col_number;
                $label_offset_col = 'col-start-' . ($label_col_number + 1);
            }
            if (!empty($field_col_number) && $field_col_number > 0) {
                $element_col = 'col-span-' . $field_col_number;
            }

            if (!empty($breakpoint)) {
                $tailwind_breakpoints = [
                    'xs' => '',
                    'sm' => 'sm',
                    'md' => 'md',
                    'lg' => 'lg'
                ];
                $breakpoint = $tailwind_breakpoints[$breakpoint];
                $label_col = 'col-span-12 ' . $breakpoint . ':' . $label_col;
                if (!empty($label_col_number) && $label_col_number > 0) {
                    $label_offset_col = $breakpoint . ':' . $label_offset_col;
                }
                $element_col = 'col-span-12 ' . $breakpoint . ':' . $element_col;
            }
            $options = [
                'horizontalLabelCol'   => $label_col,
                'horizontalOffsetCol'  => $label_offset_col,
                'horizontalElementCol' => $element_col . ' relative'
            ];
        } elseif ($this->framework == 'uikit' && $this->layout == 'horizontal') {
            $label_col          = '';
            $element_col        = '';
            $uikit_cols = [
                1 => 'n/a',
                2 => '1-6',
                3 => '1-4',
                4 => '1-3',
                5 => 'n/a',
                6 => '1-2',
                7 => 'n/a',
                8 => '2-3',
                9 => '3-4',
                10 => '5-6',
                11 => 'n/a',
                12 => '1-1'
            ];
            if (!empty($label_col_number) && $label_col_number > 0) {
                if ($uikit_cols[$label_col_number] === 'n/a') {
                    $this->buildErrorMsg('<code>$form->setCols(' . $label_col_number . ', ' . $field_col_number . ');</code><br>The UIkit grid is designed from simple fractions and cannot create columns of ' . $label_col_number . '/12.<br>');
                }
                $label_col        = 'uk-width-' . $uikit_cols[$label_col_number];
            }
            if (!empty($field_col_number) && $field_col_number > 0) {
                if ($uikit_cols[$field_col_number] === 'n/a') {
                    $this->buildErrorMsg('<code>$form->setCols(' . $label_col_number . ', ' . $field_col_number . ');</code><br>The UIkit grid is designed from simple fractions and cannot create columns of ' . $field_col_number . '/12.<br>');
                }
                $element_col = 'uk-width-' . $uikit_cols[$field_col_number];
            }

            if (!empty($breakpoint)) {
                $uikit_breakpoints = [
                    'xs' => '',
                    'sm' => 's',
                    'md' => 'm',
                    'lg' => 'l'
                ];
                $breakpoint = $uikit_breakpoints[$breakpoint];
                $label_col = 'uk-width-1-1 ' . $label_col . '@' . $breakpoint;
                $element_col = 'uk-width-1-1 ' . $element_col . '@' . $breakpoint;
            }
            $options = [
                'horizontalLabelCol'   => $label_col,
                'horizontalOffsetCol'  => '',
                'horizontalElementCol' => $element_col . ' relative'
            ];
        }
        $this->setOptions($options);
        $this->elements_start_wrapper = $this->getElementWrapper($this->options['elementsWrapper'], 'start');
        $this->elements_end_wrapper   = $this->getElementWrapper($this->options['elementsWrapper'], 'end');

        return $this;
    }

    /**
     * Start a column HTML div
     *
     * @param int $col_number - the number of columns between 1 and 12
     * @param string $breakpoint - xs, sm, md or lg
     * @param string $additionalClass
     * @param string $id
     * @return \phpformbuilder\Form
     */
    public function startCol(int $col_number, string $breakpoint = 'sm', string $additionalClass = '', string $id = ''): \phpformbuilder\Form
    {
        $col_clazz = '';
        if ($this->framework === 'bs4' || $this->framework === 'bs5') {
            $col_clazz = str_replace('--', '-', 'col-' . $breakpoint . '-' . $col_number);
        } elseif ($this->framework === 'bulma') {
            $col_clazz = 'column';
            $bulma_breakpoints = [
                'xs' => ' is-mobile',
                'sm' => ' is-tablet',
                'md' => ' is-desktop',
                'lg' => ' is-widescreen'
            ];
            $breakpoint = $bulma_breakpoints[$breakpoint];
            $col_clazz .= ' is-' . $col_number . $breakpoint;
        } elseif ($this->framework === 'foundation') {
            $foundation_breakpoints = [
                'xs' => 'small',
                'sm' => 'medium',
                'md' => 'medium',
                'lg' => 'large'
            ];
            $breakpoint = $foundation_breakpoints[$breakpoint];
            if ($breakpoint !== 'small') {
                $col_clazz = 'small-12 ';
            }
            $col_clazz        .= $breakpoint . '-' . $col_number . ' cell';
        } elseif ($this->framework === 'material') {
            $md_breakpoints = [
                'xs' => 's',
                'sm' => 'm',
                'md' => 'l',
                'lg' => 'xl'
            ];
            $breakpoint = $md_breakpoints[$breakpoint];
            $col_clazz = 'col ' . $breakpoint . $col_number;

            // set full with to the lower breakpoint
            $lower_col = '';
            if ($breakpoint == 'xl') {
                $lower_col = ' l12';
            } elseif ($breakpoint == 'l') {
                $lower_col = ' m12';
            } elseif ($breakpoint == 'm') {
                $lower_col = ' s12';
            }
            $col_clazz .= $lower_col;
        } elseif ($this->framework === 'tailwind') {
            $col_clazz = 'col-span-' . $col_number;
            $tailwind_breakpoints = [
                'xs' => '',
                'sm' => 'sm',
                'md' => 'md',
                'lg' => 'lg'
            ];
            $breakpoint = $tailwind_breakpoints[$breakpoint];
            $col_clazz = 'col-span-12 ' . $breakpoint . ':' . $col_clazz;
        } elseif ($this->framework === 'uikit') {
            $uikit_cols = [
                1 => 'n/a',
                2 => '1-6',
                3 => '1-4',
                4 => '1-3',
                5 => 'n/a',
                6 => '1-2',
                7 => 'n/a',
                8 => '2-3',
                9 => '3-4',
                10 => '5-6',
                11 => 'n/a',
                12 => '1-1'
            ];
            if ($uikit_cols[$col_number] === 'n/a') {
                $this->buildErrorMsg('<code>$form->startCol(' . $col_number . ');</code><br>The UIkit grid is designed from simple fractions and cannot create columns of ' . $col_number . '/12.<br>');
            }
            $col_clazz = 'uk-width-' . $uikit_cols[$col_number];
            if (!empty($breakpoint)) {
                $uikit_breakpoints = [
                    'xs' => '',
                    'sm' => 's',
                    'md' => 'm',
                    'lg' => 'l'
                ];
                $breakpoint = $uikit_breakpoints[$breakpoint];
                $col_clazz = 'uk-width-1-1 ' . $col_clazz . '@' . $breakpoint;
            }
        }

        if (!empty($additionalClass)) {
            $col_clazz .= ' ' . $additionalClass;
        }
        $id_html = '';
        if (!empty($id)) {
            $id_html = ' id="' . $id . '"';
        }
        $this->addHtml('<div class="' . \trim($col_clazz) . '"' . $id_html . '>');

        return $this;
    }

    /**
     *
     * Start a HTML div element
     * @param string $class
     * @param string $id
     * @return \phpformbuilder\Form
     */
    public function startDiv(string $class = '', string $id = ''): \phpformbuilder\Form
    {
        if (!empty($class)) {
            $class = ' class="' . $class . '"';
        }
        if (!empty($id)) {
            $id = ' id="' . $id . '"';
        }
        $this->addHtml('<div' . $id . $class . '>');

        return $this;
    }

    /**
     * Starts a fieldset tag.
     *
     * @param string $legend (Optional) Legend of the fieldset.
     * @param string $fieldset_attr (Optional) Fieldset attributes.
     * @param string $legend_attr (Optional) Legend attributes.
     * @return \phpformbuilder\Form
     */
    public function startFieldset(string $legend = '', string $fieldset_attr = '', string $legend_attr = ''): \phpformbuilder\Form
    {
        $fieldset_attr = $this->getAttributes($fieldset_attr);
        if ($this->hasAccordion) {
            $fieldset_attr = $this->addClass('badger-accordion__panel js-badger-accordion-panel -ba-is-hidden', $fieldset_attr);
        }
        if (!empty($fieldset_attr)) {
            $fieldset_attr = ' ' . $fieldset_attr;
        }
        if (!empty($legend_attr)) {
            $legend_attr = ' ' . $this->getAttributes($legend_attr);
        }
        $this->html .= '<fieldset' . $fieldset_attr . '>';
        if (!empty($legend)) {
            $this->html .= '<legend' . $legend_attr . '>' . $legend . '</legend>';
        }
        if ($this->hasAccordion) {
            $this->html .= '<div class="js-badger-accordion-panel-inner">';
        }

        return $this;
    }

    /**
     * Start a HTML row
     *
     * @param string $additionalClass
     * @param string $id
     * @return \phpformbuilder\Form
     */
    public function startRow(string $additional_class = '', string $id = ''): \phpformbuilder\Form
    {
        $clazz = '';
        $fw_clazz = [
            'bs4'         => 'row',
            'bs5'         => 'row',
            'bulma'       => 'columns',
            'foundation'  => 'grid-x grid-margin-x',
            'material'    => 'row',
            'tailwind'    => 'grid grid-cols-12 gap-4',
            'uikit'       => 'uk-grid'
        ];
        if (\array_key_exists($this->framework, $fw_clazz)) {
            $clazz = $fw_clazz[$this->framework];
        }
        if (!empty($additional_class)) {
            $clazz .= ' ' . $additional_class;
        }
        $id_html = '';
        if (!empty($id)) {
            $id_html = ' id="' . $id . '"';
        }
        $this->addHtml('<div class="' . \trim($clazz) . '"' . $id_html . '>');

        return $this;
    }
}
