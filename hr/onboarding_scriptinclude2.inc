var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = '/core/applications/phpformbuilder/plugins/formvalidation/js/plugins/Bootstrap5.js';

            document.head.appendChild(script);

            script.onload = function() {
                const intPhoneNumber = function() {
                    return {
                        validate: function(input) {
                            let formId = document.querySelector("#bd-hr_main1").getAttribute('id'),
                                itiInstance = iti['#' + formId + ' ' + '#' + input.element.id];
                            if (input.element.required === false && input.element.value === '') {
                                return {
                                    valid: true
                                };
                            }
                            if (itiInstance.isValidNumber()) {
                                return {
                                    valid: true
                                };
                            }
                            var countryData = itiInstance.getSelectedCountryData();
                            return {
                                valid: false,
                                message: form.fv.localization.phone.country.replace("%s", countryData.name)
                            };
                        },
                    };
                };

                const tinymceNotEmpty = function() {
                    return {
                        validate: function(input) {
                            // Get the plain text without HTML
                            const text = tinyMCE.activeEditor.getContent({
                                format: 'text',
                            });

                            if (text.length > 0) {
                                return {
                                    valid: true
                                };
                            }
                            return {
                                valid: false
                            };
                        }
                    };
                };

                var formId = document.querySelector("#bd-hr_main1").getAttribute('id'),
                    dataAttr = document.querySelector("#bd-hr_main1").dataset,
                    form = forms[formId],
                    frameworkPlugin,
                    isDefaultMessageContainer = true,
                    messagePlugin;

                if (document.querySelector('#bd-hr_main1 button[name="submit"]')) {
                    alert('The Formvalidation plugin does not allow to name the submit button "submit". You have to rename it or the form will not work.');
                }

                if (document.querySelector('#bd-hr_main1 input[type="hidden"][required]')) {
                    alert('The Formvalidation plugin does not allow the "required" attribute on an hidden input. Remove the "required" attribute or the form will not work.');
                }

                let isBulmaHorizontal = document.querySelector("#bd-hr_main1").classList.contains('bulma-form') && document.querySelector("#bd-hr_main1").classList.contains('bulma-form-horizontal'),
                    isBs5 = document.querySelector("#bd-hr_main1").classList.contains('bs5-form'),
                    isBs5Horizontal = document.querySelector("#bd-hr_main1").classList.contains('bs5-form') && document.querySelector("#bd-hr_main1").classList.contains('form-horizontal'),
                    isBs4 = document.querySelector("#bd-hr_main1").classList.contains('bs4-form'),
                    isBs4Horizontal = document.querySelector("#bd-hr_main1").classList.contains('bs4-form') && document.querySelector("#bd-hr_main1").classList.contains('form-horizontal'),
                    isFoundationHorizontal = document.querySelector("#bd-hr_main1").classList.contains('foundation-form') && document.querySelector("#bd-hr_main1").classList.contains('form-horizontal'),
                    isMaterial = document.querySelector("#bd-hr_main1").classList.contains('material-form'),
                    isTailwind = document.querySelector("#bd-hr_main1").classList.contains('tailwind-form'),
                    isUikit = document.querySelector("#bd-hr_main1").classList.contains('uikit-form'),
                    isUikitVertical = document.querySelector("#bd-hr_main1").classList.contains('uk-form-stacked');

                if (isBulmaHorizontal) {
                    isDefaultMessageContainer = false;
                    messagePlugin = new FormValidation.plugins.Message({
                        clazz: 'help is-danger',
                        container: function(field, ele) {
                            return ele.closest('.column');
                        }
                    });
                } else {
                    messagePlugin = new FormValidation.plugins.Message();
                }

                if (isBs5 || isBs4) {
                    if (isBs5Horizontal || isBs4Horizontal) {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer,
                            rowSelector: function(field, ele) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div[class*="col-"]').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                        });
                    } else if (isBs5) {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer,
                            rowSelector: function(field, ele) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div[class*="bs5-form-stacked-element"]').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                        });
                    } else {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer
                        });
                    }
                } else if (isBulmaHorizontal) {
                    frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                        defaultMessageContainer: isDefaultMessageContainer,
                        rowSelector: function(field, ele) {
                            // get the 1st class of closest parent div
                            var classList = ele.closest('div[class*="column"]').getAttribute('class').split(' ').filter(Boolean);
                            return '.' + classList[0];
                        }
                    });
                } else if (isMaterial) {
                    frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                        defaultMessageContainer: isDefaultMessageContainer,
                        rowSelector: function(field, ele) {
                            if (ele.classList.contains('fv-group')) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                            return '.input-field';
                        }
                    });
                } else if (isTailwind) {
                    frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                        defaultMessageContainer: isDefaultMessageContainer,
                        rowSelector: function(field, ele) {
                            // get the 1st class of closest parent div
                            var classList = ele.closest('div[class*="grid-"]').getAttribute('class').split(' ').filter(Boolean);
                            return '.' + classList[0];
                        }
                    });
                } else if (isUikit) {
                    if (isUikitVertical) {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer,
                            rowSelector: function(field, ele) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div[class*="uk-form-stacked-element"]').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                        });
                    } else {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer,
                            rowSelector: function(field, ele) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div[class*="uk-width-"]').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                        });
                    }
                } else {
                    frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                        defaultMessageContainer: isDefaultMessageContainer
                    });
                }
                form.fv = FormValidation.formValidation(
                        document.querySelector("#bd-hr_main1"), {
                            locale: 'en_US',
                            localization: FormValidation.locales.en_US,
                            plugins: {
                                bootstrap: frameworkPlugin,
                                declarative: new FormValidation.plugins.Declarative({
                                    html5Input: true
                                }),
                                aria: new FormValidation.plugins.Aria(),
                                autoFocus: new FormValidation.plugins.AutoFocus(),
                                excluded: new FormValidation.plugins.Excluded({
                                    excluded: function(field, element, elements) {
                                        // return true to exclude the field
                                        var parentHiddenWrapper = element.closest(['.hidden-wrapper:not(.on) *']);

                                        if (parentHiddenWrapper !== null) {
                                            if (dataAttr.fvDebug !== undefined) {
                                                console.log('%c' + field + ': validation skipped', 'color: #666');
                                            }
                                            return true;
                                        }
                                        return false;
                                    }
                                }),
                                icon: new FormValidation.plugins.Icon(),
                                message: messagePlugin,
                                sequence: new FormValidation.plugins.Sequence({
                                    enabled: true,
                                }),
                                submitButton: new FormValidation.plugins.SubmitButton(),
                                trigger: new FormValidation.plugins.Trigger()
                            }
                        }
                    )
                    .registerValidator('intphonenumber', intPhoneNumber)
                    .registerValidator('tinymcenotempty', tinymceNotEmpty)
                    .on('plugins.icon.set', function(e) {
                        if (e.iconElement && document.getElementById(e.element.id).parentNode) {
                            let $rightAddon = document.getElementById(e.element.id).parentNode.querySelector('[class*="phpfb-addon-after"]');
                            if (e.element.closest('.bulma-form') && e.element.closest('.field')) {
                                $rightAddon = e.element.closest('.field').querySelector('[class*="phpfb-addon-after"]:not([class*="addon-control"])');
                            }
                            if ($rightAddon) {
                                if ($rightAddon.tagName === 'I' && (e.element.closest('.bs4-form') || e.element.closest('.bs5-form'))) {
                                    $rightAddon = $rightAddon.parentNode;
                                }
                                if (e.iconElement.tagName === 'I' && e.iconElement.parentNode.classList.contains('is-right')) {
                                    // Bulma icons
                                    e.iconElement.parentNode.style.right = '24px';
                                } else if (e.element.closest('.uikit-form')) {
                                    let style = $rightAddon.currentStyle || window.getComputedStyle($rightAddon),
                                        marginLeft = 0,
                                        paddingLeft = 0;
                                    if (style.marginLeft.match(/px/)) {
                                        marginLeft += parseInt(style.marginLeft.replace('px', ''));
                                    }
                                    if (style.paddingLeft.match(/px/)) {
                                        paddingLeft += parseInt(style.paddingLeft.replace('px', ''));
                                    }

                                    if (e.element.tagName === 'SELECT') {
                                        marginLeft += 20;
                                    }

                                    e.iconElement.style.right = ($rightAddon.offsetWidth + (marginLeft + paddingLeft / 2)) - 15 + 'px';
                                } else if (e.element.closest('.foundation-form')) {
                                    e.iconElement.style.right = $rightAddon.offsetWidth + 'px';
                                } else {
                                    e.iconElement.style.right = $rightAddon.offsetWidth + 15 + 'px';
                                }
                            } else if (e.element.closest('.uikit-form') && e.element.tagName === 'SELECT') {
                                e.iconElement.style.right = '20px';
                            }
                            switch (e.status) {
                                case 'Validating':
                                    e.iconElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg"viewBox="0 0 512.333 512" width="16" height="16"> <path d="M440.935 12.574l3.966 82.766C399.416 41.904 331.674 8 256 8 134.813 8 33.933 94.924 12.296 209.824 10.908 217.193 16.604 224 24.103 224h49.084c5.57 0 10.377-3.842 11.676-9.259C103.407 137.408 172.931 80 256 80c60.893 0 114.512 30.856 146.104 77.801l-101.53-4.865c-6.845-.328-12.574 5.133-12.574 11.986v47.411c0 6.627 5.373 12 12 12h200.333c6.627 0 12-5.373 12-12V12c0-6.627-5.373-12-12-12h-47.411c-6.853 0-12.315 5.729-11.987 12.574zM256 432c-60.895 0-114.517-30.858-146.109-77.805l101.868 4.871c6.845.327 12.573-5.134 12.573-11.986v-47.412c0-6.627-5.373-12-12-12H12c-6.627 0-12 5.373-12 12V500c0 6.627 5.373 12 12 12h47.385c6.863 0 12.328-5.745 11.985-12.599l-4.129-82.575C112.725 470.166 180.405 504 256 504c121.187 0 222.067-86.924 243.704-201.824 1.388-7.369-4.308-14.176-11.807-14.176h-49.084c-5.57 0-10.377 3.842-11.676 9.259C408.593 374.592 339.069 432 256 432z" fill="#FF4136" /> </svg>';
                                    break;

                                case 'Invalid':
                                    e.iconElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg"viewBox="0 0 352 512" width="16" height="16"> <path d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z" fill="#F44336" /> </svg>';
                                    break;

                                case 'Valid':
                                    e.iconElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg"viewBox="0 0 512 512" width="16" height="16"> <path d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z" fill="#4CAF50" /> </svg>';
                                    break;

                                default:
                                    e.iconElement.innerHTML = '';
                                    break;
                            }
                        }
                    });

                // callback if function exists
                if (typeof(fvCallback) !== "undefined") {
                    setTimeout(fvCallback, 0);
                }

                if (dataAttr.fvDebug === undefined && dataAttr.fvNoAutoSubmit === undefined) {
                    form.fv.on('core.form.valid', function() {
                        if (!document.querySelector("#bd-hr_main1").classList.contains('ajax-form')) {
                            if (!document.querySelector("#bd-hr_main1").classList.contains('has-recaptcha-v3')) {
                                document.querySelector("#bd-hr_main1").submit();
                            }
                        } else {
                            var formId = document.querySelector("#bd-hr_main1").getAttribute('id'),
                                $form = document.getElementById(formId);
                            let data = new FormData($form);
                            fetch($form.getAttribute('action'), {
                                method: 'post',
                                body: new URLSearchParams(data).toString(),
                                headers: {
                                    'Content-type': 'application/x-www-form-urlencoded'
                                },
                                cache: 'no-store',
                                credentials: 'include'
                            }).then(function(response) {
                                return response.text()
                            }).then(function(data) {
                                let $formContainer = document.querySelector('*[data-ajax-form-id="' + formId + '"]');
                                $formContainer.innerHTML = '';
                                loadData(data, '#' + $formContainer.id).then(() => {
                                    window.document.dispatchEvent(loadAjaxFormEvent[formId]);
                                });
                            }).catch(function(error) {
                                console.log(error);
                            });
                        }
                    });
                }

                if (document.querySelector('#bd-hr_main1.js-badger-accordion')) {
                    form.fv.on('core.form.invalid', function() {
                        let invalidClasses = ['.is-invalid', '.is-danger', '[aria-invalid="true"]', '.is-invalid-input', '.uk-form-danger'];
                        firstInvalid = null;
                        invalidClasses.forEach(ic => {
                            if (document.querySelector('#bd-hr_main1 ' + ic) !== null) {
                                firstInvalid = document.querySelector('#bd-hr_main1 ' + ic);
                            }
                        });
                        let fieldset = firstInvalid.closest('fieldset');
                        let fieldsetIndex = fieldset.dataset.acIndex;
                        phpfbAccordion["#bd-hr_main1"].closeAll();
                        phpfbAccordion["#bd-hr_main1"].open(fieldsetIndex);
                    });
                }

                if (dataAttr.fvNoIcon !== undefined) {
                    form.fv.deregisterPlugin('icon');
                }

                if (!document.querySelector("#bd-hr_main1").classList.contains('bulma-form') || !document.querySelector("#bd-hr_main1").classList.contains('bulma-form-horizontal')) {
                    form.fv.deregisterPlugin('message');
                }

                if (document.querySelector('#' + formId + ' button[type="reset"]')) {
                    document.querySelector('#' + formId + ' button[type="reset"]').addEventListener('click', () => {
                        form.fv.resetForm(true);
                    });
                }

                if (document.querySelector('#' + formId + ' .litepick')) {
                    Array.from(document.querySelectorAll('#' + formId + ' .litepick')).forEach(element => {
                        element.addEventListener('change', function() {
                            setTimeout(() => {
                                form.fv.validateField(element.getAttribute('name'));
                                if (element.dataset && element.dataset.elementEnd) {
                                    form.fv.validateField(document.getElementById(element.dataset.elementEnd).getAttribute('name'));
                                }
                            }, 400);
                        });
                    });
                }


            };
