/*=========================================================
            phpformbuilder pretty-checkbox - vanilla JS plugin
            Author : Gilles Migliori
            Version : 2.0
=========================================================*/

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory('PrettyCheckbox'));
    } else if (typeof exports === 'object') {
        module.exports = factory('PrettyCheckbox');
    } else {
        root['PrettyCheckbox'] = factory('PrettyCheckbox');
    }
}(this, function () {

    'use strict';

    /**
     * Plugin Object
     * @param {String} selector
     * @param {Object} options
     * @constructor
     */
    function PrettyCheckbox (selector, options) {
        this.selector = selector;
        /*
        prettyWrapper  : {
            baseClass      : 'pretty',
            defaultClass   : 'p-default',
            checkboxStyle  : 'p-%checkboxStyle%',
            radioStyle     : 'p-%radioStyle%',
            fill           : 'p-%fill%',
            plain          : '%plain%',
            animations     : 'p-%animations%',
            size           : 'p-%size%',
        },
        labelWrapper   : {
            color          : 'p-%color%',
            icon           : '%icon%',
        }
        toggle         : %toggle%,
        toggleOn: {
            label: '%toggleOnLabel%',
            color: 'p-%toggleOnColor%',
            icon : '%toggleOnIcon%'
        },
        toggleOff: {
            label: '%toggleOffLabel%',
            color: 'p-%toggleOffColor%',
            icon : '%toggleOffIcon%'
        }
        */
        this.options = options;
        this.init();
    }

    /**
     * PrettyCheckbox prototype
     * @public
     * @constructor
     */
    PrettyCheckbox.prototype = {
        init: function () {
            // find all matching DOM elements.
            // makes `.selectors` object available to instance.
            this.elements = document.querySelectorAll(this.selector + ' input[type="checkbox"], ' + this.selector + ' input[type="radio"]');
            for (let $el of this.elements) {
                const $label = this._getLabel($el);
                const prettyWrapperClazz = this._getPrettyWrapperClazz($el);
                const $prettyDiv = document.createElement('div');
                const $tempElement = document.createElement('span');
                $tempElement.setAttribute("id", "temp-element");

                $el.replaceWith($tempElement);

                for (const [key, value] of Object.entries(prettyWrapperClazz)) {
                    if (key !== 'color') {
                        $prettyDiv.classList.add(value);
                    }
                }
                if (prettyWrapperClazz.toggle === undefined) {
                    const $labelWrapper = this._createLabelWrapper({
                        'labelInnerHTML': $label.innerHTML,
                        'color': $el.dataset.color !== undefined ? 'p-' + $el.dataset.color : this.options.labelWrapper.color,
                        'icon': $el.dataset.icon !== undefined ? $el.dataset.icon : this.options.labelWrapper.icon,
                        'wrapperClazz': 'state'
                    });
                    $prettyDiv.appendChild($el);
                    $prettyDiv.appendChild($labelWrapper);
                } else {
                    let onLabelInnerHTML;
                    if ($el.dataset.onLabel !== undefined) {
                        onLabelInnerHTML = $el.dataset.onLabel;
                    } else {
                        onLabelInnerHTML = this.options.toggleOn.label !== '' ? this.options.toggleOn.label : $label.innerHTML;
                    }
                    let onColor;
                    if ($el.dataset.onColor !== undefined) {
                        onColor = 'p-' + $el.dataset.onColor;
                    } else {
                        onColor = this.options.toggleOn.color !== '' ? this.options.toggleOn.color : this.options.labelWrapper.color
                    }
                    let onIcon;
                    if ($el.dataset.onIcon !== undefined) {
                        onIcon = $el.dataset.onIcon;
                    } else {
                        onIcon = this.options.toggleOn.icon !== '' ? this.options.toggleOn.icon : this.options.labelWrapper.icon;
                    }
                    const $labelOnWrapper = this._createLabelWrapper({
                        'labelInnerHTML': onLabelInnerHTML,
                        'color': onColor,
                        'icon': onIcon,
                        'wrapperClazz': 'state p-on'
                    });

                    let offLabelInnerHTML;
                    if ($el.dataset.offLabel !== undefined) {
                        offLabelInnerHTML = $el.dataset.offLabel;
                    } else {
                        offLabelInnerHTML = this.options.toggleOff.label !== '' ? this.options.toggleOff.label : $label.innerHTML;
                    }
                    let offColor;
                    if ($el.dataset.offColor !== undefined) {
                        offColor = 'p-' + $el.dataset.offColor;
                    } else {
                        offColor = this.options.toggleOff.color !== '' ? this.options.toggleOff.color : this.options.labelWrapper.color
                    }
                    let offIcon;
                    if ($el.dataset.offIcon !== undefined) {
                        offIcon = $el.dataset.offIcon;
                    } else {
                        offIcon = this.options.toggleOff.icon !== '' ? this.options.toggleOff.icon : this.options.labelWrapper.icon;
                    }
                    const $labelOffWrapper = this._createLabelWrapper({
                        'labelInnerHTML': offLabelInnerHTML,
                        'color': offColor,
                        'icon': offIcon,
                        'wrapperClazz': 'state p-off'
                    });
                    $prettyDiv.appendChild($el);
                    $prettyDiv.appendChild($labelOnWrapper);
                    $prettyDiv.appendChild($labelOffWrapper);
                }

                // replace the input & label with the new HTML
                $label.parentNode.removeChild($label);
                $tempElement.replaceWith($prettyDiv);
            }
        },

        _createLabelWrapper: function (opt) {
            const $labelWrapper = document.createElement('div');
            opt.wrapperClazz.split(' ').forEach(c => {
                $labelWrapper.classList.add(c);
            });
            if (opt.color !== 'p-') {
                $labelWrapper.classList.add(opt.color);
            }
            if (opt.icon !== '') {
                const $iconElement = document.createElement('i');
                $iconElement.classList.add('icon');
                const clazz = opt.icon.split(' ');
                if (clazz[0] === 'material-icons') {
                    for (let index = 0; index < clazz.length; index++) {
                        if (index === 1) {
                            $iconElement.innerHTML = clazz[index];
                        } else {
                            $iconElement.classList.add(clazz[index]);
                        }
                    }
                } else {
                    clazz.forEach(cl => {
                        $iconElement.classList.add(cl);
                    });
                }
                $labelWrapper.appendChild($iconElement);
            }
            const $labelElement = document.createElement('label');
            $labelElement.innerHTML = opt.labelInnerHTML;
            $labelWrapper.appendChild($labelElement);

            return $labelWrapper;
        },

        _getLabel: function ($el) {
            if ($el.parentNode.nodeName === 'LABEL' && $el.parentNode.querySelector('input')) {
                // if the input is inside the label tag we move it to the parent node
                $el.parentNode.parentNode.insertBefore($el.parentNode.querySelector('input'), $el.parentNode.parentNode.firstChild);
            }
            let $label = $el.parentNode.querySelector('label[for="' + $el.id + '"]');
            if ($label === null) {
                $label = document.createElement('label');
                $el.parentNode.querySelector('input').appendChild($label);
            }
            return $label;
        },

        _getPrettyWrapperClazz: function ($el) {
            let clazz = { ...this.options.prettyWrapper };

            // add clazz from the input data-attr
            for (const [key, value] of Object.entries($el.dataset)) {
                if (clazz[key] !== undefined) {
                    clazz[key] = 'p-' + value;
                }
            }

            // delete empty values
            for (const [key, value] of Object.entries(clazz)) {
                if (value === 'p-' || value === '') {
                    delete clazz[key];
                }
            }

            if ($el.type == 'radio') {
                delete clazz.checkboxStyle;
            } else {
                delete clazz.radioStyle;
            }
            if (this.options.labelWrapper.icon !== '' || $el.dataset.icon !== undefined || $el.dataset.onIcon !== undefined || $el.dataset.onIcon !== undefined) {
                clazz.icon = 'p-icon';
                // remove default class if there's an icon
                for (const [key] of Object.entries(clazz)) {
                    if (clazz[key] === this.options.prettyWrapper.defaultClass) {
                        delete clazz[key];
                    }
                }
            }

            if (this.options.toggle || $el.dataset.toggle === 'true') {
                clazz.toggle = 'p-toggle';
            }

            return clazz;
        },
    };

    return PrettyCheckbox;
}));
