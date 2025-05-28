/*=========================================================
            word and character counter plugin
            Author : Gilles Migliori
            Version : 2.0
=========================================================*/

/*
var wcc = new WordCharCount('%selector%', {'maxAuthorized': %maxAuthorized%});
*/

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory('WordCharCount'));
    } else if (typeof exports === 'object') {
        module.exports = factory('WordCharCount');
    } else {
        root['WordCharCount'] = factory('WordCharCount');
    }
}(this, function () {

    'use strict';

    /**
     * Plugin Object
     * @param {String} selector
     * @param {Object} options
     * @constructor
     */
    function WordCharCount (selector, editor, options) {
        this.selector = selector;
        this.editor = editor; // $(editor.getBody())
        this.element = window.parent.document.querySelector(this.selector);
        this.elementId = this.element.getAttribute('id');
        // options
        let defaultOptions = {
            maxCharacters: 100,
            maxWords: 10,
            characterCount: true,
            wordCount: true,
            characterText: 'character(s)',
            wordText: 'word(s)',
            separator: ' • ',
            wrapperClassName: 'help-block text-left',
            className: 'text-muted',
            errorClassName: 'text-danger'
        };
        this.options = { ...defaultOptions, ...options };
        // convert boolean strings to boolean
        this.options.characterCount = this.options.characterCount === 'true' | this.options.characterCount === true;
        this.options.wordCount = this.options.wordCount === 'true' | this.options.wordCount === true;
        this.init();
    }

    /**
     * WordCharCount prototype
     * @public
     * @constructor
     */
    WordCharCount.prototype = {
        init: function () {
            // find all matching DOM elements.
            let $content = document.createElement('p');
            $content.id = 'p-' + this.elementId;
            this.options.className.split(' ').forEach(cl => {
                $content.classList.add(cl);
            });
            if (this.options.wordCount) {
                let $wcEl = document.createElement('span');
                $wcEl.id = 'word-count' + this.elementId;
                $wcEl.textContent = '0 ' + this.options.wordText + ' / ' + this.options.maxWords;
                $content.appendChild($wcEl);
                if (this.options.characterCount) {
                    $content.append(this.options.separator);
                }
            }
            if (this.options.characterCount) {
                let $ccEl = document.createElement('span');
                $ccEl.id = 'char-count' + this.elementId;
                $ccEl.textContent = '0 ' + this.options.characterText + ' / ' + this.options.maxCharacters;
                $content.appendChild($ccEl);
            }

            let $wrapper = document.createElement('div');
            this.options.wrapperClassName.split(' ').forEach(cl => {
                $wrapper.classList.add(cl);
            });

            $wrapper.appendChild($content);
            this.editor.container.insertAdjacentElement('afterend', $wrapper);

            this.editor.on('keyup', this.doCount.bind(this));
            var event = new Event('keyup');
            this.element.dispatchEvent(event);
        },
        doCount: function () {
            var activeContent = tinymce.activeEditor.getContent({ format: "text" });
            var numberOfCaracters = activeContent.length;
            var numberOfWords = activeContent.trim().split(' ').length;
            if (activeContent === '') {
                numberOfWords = 0;
            }
            if (this.options.wordCount) {
                window.parent.document.getElementById('word-count' + this.elementId).textContent = numberOfWords + ' ' + this.options.wordText + ' / ' + this.options.maxWords;
                if (numberOfWords > this.options.maxWords) {
                    window.parent.document.getElementById('word-count' + this.elementId).classList.add(this.options.errorClassName);
                } else {
                    window.parent.document.getElementById('word-count' + this.elementId).classList.remove(this.options.errorClassName);
                }
            }
            if (this.options.characterCount) {
                window.parent.document.getElementById('char-count' + this.elementId).textContent = numberOfCaracters + ' ' + this.options.characterText + ' / ' + this.options.maxCharacters;
                if (numberOfCaracters > this.options.maxCharacters) {
                    window.parent.document.getElementById('char-count' + this.elementId).classList.add(this.options.errorClassName);
                } else {
                    window.parent.document.getElementById('char-count' + this.elementId).classList.remove(this.options.errorClassName);
                }
            }
        }
    };

    return WordCharCount;
}));
