/*=========================================================
            nice checkboxes and radio buttons plugin
            Author : Gilles Migliori
            Version : 2.0
=========================================================*/

/*
NiceCheck(document.querySelector('#form'));
*/

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory('NiceCheck'));
    } else if (typeof exports === 'object') {
        module.exports = factory('NiceCheck');
    } else {
        root['NiceCheck'] = factory('NiceCheck');
    }
}(this, function () {

    'use strict';

    /**
     * Plugin Object
     * @param {String} selector
     * @constructor
     */
    function NiceCheck (selector) {
        this.selector = selector;
        this.element = document.querySelector(this.selector);
        this.options = {
            checkClassName: 'check'
        };
        this.init();
    }

    /**
     * NiceCheck prototype
     * @public
     * @constructor
     */
    NiceCheck.prototype = {
        init: function () {
            // find all matching DOM elements.
            var $inputTargets = this.element.querySelectorAll('input[type="checkbox"]:not(.lcswitch), input[type="radio"]:not(.lcswitch)');
            $inputTargets.forEach(item => {
                item.classList.forEach(cl => {
                    if (cl.match(/lc-/)) {
                        $inputTargets.remove(item);
                    }
                })
            })

            for (let $el of $inputTargets) {
                let $checkElement = document.createElement('span');
                $checkElement.classList.add(this.options.checkClassName);
                let label = document.querySelector('label[for="' + $el.getAttribute('id') + '"]');
                label.classList.add('nice-check');
                label.appendChild($checkElement);
            }
        }
    };

    return NiceCheck;
}));
