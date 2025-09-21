<script>
        if (typeof forms === "undefined") {
            var forms = [];
        }
        forms["bd-hr_main1"] = {};
        document.addEventListener('DOMContentLoaded', function(event) {
            if (top != self && typeof location.ancestorOrigins != "undefined") {
                if (location.ancestorOrigins[0] !== "https://preview.codecanyon.net" && !document.getElementById("drag-and-drop-preview") && document.getElementById("bd-hr_main1")) {
                    document.getElementById("bd-hr_main1").addEventListener("submit", function(e) {
                        e.preventDefault();
                        console.log("not allowed");
                        return false;
                    });
                }
            }

            if (typeof(phpfbDependentFields) == "undefined") {
                window.phpfbDependentFields = [];
            }
            phpfbDependentFields["#bd-hr_main1 .hidden-wrapper"] = new DependentFields("#bd-hr_main1 .hidden-wrapper");
            document.body.classList.add('dependent-fields-loaded');
            var inputName0 = $("#bd-hr_main1 #uploader-i9_passport_full").attr('name'),
                $form = $('input[name="' + inputName0 + '"]').closest('form'),
                $submit = $form.find('button[type="submit"]'),
                form = forms['bd-hr_main1'],
                originalDisabledState = $submit.prop('disabled'),
                debug0 = true;

            if (typeof(validateUpload0) === 'undefined') {
                var validateUpload0 = function() {
                    if (typeof(form.fv) == 'object') {
                        if (inputName0 in form.fv.elements) {
                            form.fv.validateField(inputName0);
                        } else if (inputName0 + '[]' in form.fv.elements) {
                            form.fv.validateField(inputName0 + '[]');
                        }
                    }
                }
            }

            $("#bd-hr_main1 #uploader-i9_passport_full").fileuploader({
                enableApi: true,
                limit: 1,
                extensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                fileMaxSize: 5,
                beforeSelect: function(files, listEl, parentEl, newInputEl, inputEl) {
                    var iName0 = inputName0; // if uploader limit = 1
                    if ($('input[name="' + inputName0 + '[]"]')[0]) { // if several files allowed
                        iName0 += '[]';
                    }
                    if ($('input[name="' + iName0 + '"]').attr('required') && typeof(form.fv) == 'object' && iName0 in form.fv.elements) {
                        // replace the file input value by the hidden input value for the jQuery validation plugin
                        var hiddenInputName = iName0.replace(/^uploader-/, '').replace(/\[\]$/, '');
                        var o = new Object();
                        o[iName0] = {
                            notEmpty: function(field, element, validator) {
                                let value = $('input[name="' + hiddenInputName + '"]').val().replace(/\[\]$/, '');
                                return value;
                            }
                        };
                        form.fv.registerPlugin(
                            'transformer', new FormValidation.plugins.Transformer(o)
                        );
                    }

                    return true;
                },
                upload: {
                    url: '/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_upload_file.php',
                    data: {
                        input_name: inputName0,
                        hash: 'f10f3079af19dcfc3456a3d4544ec06edab68e84',
                        form_id: 'bd-hr_main1'
                    },
                    type: 'POST',
                    enctype: 'multipart/form-data',
                    start: true,
                    synchron: true,
                    onSuccess: function(data, item, listEl, parentEl, newInputEl, inputEl, textStatus, jqXHR) {
                        $submit.prop('disabled', originalDisabledState);

                        try {
                            data = JSON.parse(data);
                            item.name = data.files[0].name;
                            item.html.find('.column-title > div:first-child').text(data.files[0].name).attr('title', data.files[0].name);
                        } catch (e) {
                            if (debug0 === true) {
                                console.log(data);
                                if (data.warnings.length > 0) {
                                    item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-warning has-icon mt-2 mb-0'>" + data.warnings[0] + "</p>");
                                }
                                item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-danger has-icon mt-2 mb-0'><h5>Something went wrong with the uploader.</h5><p>You may have to create the upload folder and/or the thumbnails folders manually, or your upload folder is not writable.</p><p>If you generate thumbnails your upload folder must follow this structure:</p><pre><code>[your-upload-folder] \n    => thumbs \n        => lg \n        => md \n        => sm</code></pre><p class='mb-0'><strong>Open your browser console for more information.</strong></p></div>");
                            }
                        }

                        // make HTML changes
                        item.html.find('.fileuploader-action-remove').addClass('fileuploader-action-success');

                        validateUpload0();

                        setTimeout(function() {
                            item.html.find('.progress-bar2').fadeOut(400);
                        }, 400);
                    },
                    onError: function(item, listEl, parentEl, newInputEl, inputEl, jqXHR, textStatus, errorThrown) {
                        $submit.prop('disabled', originalDisabledState);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.find('span').html(0 + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(0 + "%");
                            item.html.find('.progress-bar2').fadeOut(400);
                        }

                        item.upload.status != 'cancelled' && item.html.find('.fileuploader-action-retry').length == 0 ? item.html.find('.column-actions').prepend(
                            '<a class="fileuploader-action fileuploader-action-retry" title="Retry"><i></i></a>'
                        ) : null;
                    },
                    onProgress: function(data, item) {
                        $submit.prop('disabled', true);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.show();
                            progressBar.find('span').html(data.percentage + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(data.percentage + "%");
                        }
                    },
                    onComplete: null
                },
                onRemove: function(item) {
                    $submit.prop('disabled', originalDisabledState);
                    // send POST request
                    $.post('/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_remove_file.php', {
                        input_name: inputName0,
                        hash: 'f10f3079af19dcfc3456a3d4544ec06edab68e84',
                        form_id: 'bd-hr_main1',
                        filename: item.name,
                        upload_dir: '/hr/file-uploads/'
                    }, function() {
                        validateUpload0();
                    });
                },
                onEmpty: function(listEl, parentEl, newInputEl, inputEl) {
                    validateUpload0();
                },
                // captions
                captions: {
                    button: function(options) {
                        return 'Browse ' + (options.limit == 1 ? 'file' : 'files');
                    },
                    feedback: function(options) {
                        return 'Choose ' + (options.limit == 1 ? 'file' : 'files') + ' to upload';
                    },
                    feedback2: function(options) {
                        return options.length + ' ' + (options.length > 1 ? ' files were' : ' file was') + ' chosen';
                    },
                    confirm: 'Confirm',
                    cancel: 'Cancel',
                    name: 'Name',
                    type: 'Type',
                    size: 'Size',
                    dimensions: 'Dimensions',
                    duration: 'Duration',
                    crop: 'Crop',
                    rotate: 'Rotate',
                    sort: 'Sort',
                    download: 'Download',
                    remove: 'Remove',
                    drop: 'Drop the files here to Upload',
                    paste: '<div class="fileuploader-pending-loader"></div> Pasting a file, click here to cancel.',
                    removeConfirmation: 'Are you sure you want to remove this file?',
                    errors: {
                        filesLimit: 'Only ${limit} files are allowed to be uploaded.',
                        filesType: 'Only ${extensions} files are allowed to be uploaded.',
                        fileSize: '${name} is too large! Please choose a file up to ${fileMaxSize}MB.',
                        filesSizeAll: 'Files that you chose are too large! Please upload files up to ${maxSize} MB.',
                        fileName: 'File with the name ${name} is already selected.',
                        folderUpload: 'You are not allowed to upload folders.'
                    }
                }
            });
            var inputName1 = $("#bd-hr_main1 #uploader-i9_listb_upload").attr('name'),
                $form = $('input[name="' + inputName1 + '"]').closest('form'),
                $submit = $form.find('button[type="submit"]'),
                form = forms['bd-hr_main1'],
                originalDisabledState = $submit.prop('disabled'),
                debug1 = true;

            if (typeof(validateUpload1) === 'undefined') {
                var validateUpload1 = function() {
                    if (typeof(form.fv) == 'object') {
                        if (inputName1 in form.fv.elements) {
                            form.fv.validateField(inputName1);
                        } else if (inputName1 + '[]' in form.fv.elements) {
                            form.fv.validateField(inputName1 + '[]');
                        }
                    }
                }
            }

            $("#bd-hr_main1 #uploader-i9_listb_upload").fileuploader({
                enableApi: true,
                limit: 2,
                extensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                fileMaxSize: 10,
                beforeSelect: function(files, listEl, parentEl, newInputEl, inputEl) {
                    var iName1 = inputName1; // if uploader limit = 1
                    if ($('input[name="' + inputName1 + '[]"]')[0]) { // if several files allowed
                        iName1 += '[]';
                    }
                    if ($('input[name="' + iName1 + '"]').attr('required') && typeof(form.fv) == 'object' && iName1 in form.fv.elements) {
                        // replace the file input value by the hidden input value for the jQuery validation plugin
                        var hiddenInputName = iName1.replace(/^uploader-/, '').replace(/\[\]$/, '');
                        var o = new Object();
                        o[iName1] = {
                            notEmpty: function(field, element, validator) {
                                let value = $('input[name="' + hiddenInputName + '"]').val().replace(/\[\]$/, '');
                                return value;
                            }
                        };
                        form.fv.registerPlugin(
                            'transformer', new FormValidation.plugins.Transformer(o)
                        );
                    }

                    return true;
                },
                upload: {
                    url: '/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_upload_file.php',
                    data: {
                        input_name: inputName1,
                        hash: 'd0512044ad57e63e8b0c858b5db317bcf20d6138',
                        form_id: 'bd-hr_main1'
                    },
                    type: 'POST',
                    enctype: 'multipart/form-data',
                    start: true,
                    synchron: true,
                    onSuccess: function(data, item, listEl, parentEl, newInputEl, inputEl, textStatus, jqXHR) {
                        $submit.prop('disabled', originalDisabledState);

                        try {
                            data = JSON.parse(data);
                            item.name = data.files[0].name;
                            item.html.find('.column-title > div:first-child').text(data.files[0].name).attr('title', data.files[0].name);
                        } catch (e) {
                            if (debug1 === true) {
                                console.log(data);
                                if (data.warnings.length > 0) {
                                    item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-warning has-icon mt-2 mb-0'>" + data.warnings[0] + "</p>");
                                }
                                item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-danger has-icon mt-2 mb-0'><h5>Something went wrong with the uploader.</h5><p>You may have to create the upload folder and/or the thumbnails folders manually, or your upload folder is not writable.</p><p>If you generate thumbnails your upload folder must follow this structure:</p><pre><code>[your-upload-folder] \n    => thumbs \n        => lg \n        => md \n        => sm</code></pre><p class='mb-0'><strong>Open your browser console for more information.</strong></p></div>");
                            }
                        }

                        // make HTML changes
                        item.html.find('.fileuploader-action-remove').addClass('fileuploader-action-success');

                        validateUpload1();

                        setTimeout(function() {
                            item.html.find('.progress-bar2').fadeOut(400);
                        }, 400);
                    },
                    onError: function(item, listEl, parentEl, newInputEl, inputEl, jqXHR, textStatus, errorThrown) {
                        $submit.prop('disabled', originalDisabledState);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.find('span').html(0 + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(0 + "%");
                            item.html.find('.progress-bar2').fadeOut(400);
                        }

                        item.upload.status != 'cancelled' && item.html.find('.fileuploader-action-retry').length == 0 ? item.html.find('.column-actions').prepend(
                            '<a class="fileuploader-action fileuploader-action-retry" title="Retry"><i></i></a>'
                        ) : null;
                    },
                    onProgress: function(data, item) {
                        $submit.prop('disabled', true);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.show();
                            progressBar.find('span').html(data.percentage + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(data.percentage + "%");
                        }
                    },
                    onComplete: null
                },
                onRemove: function(item) {
                    $submit.prop('disabled', originalDisabledState);
                    // send POST request
                    $.post('/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_remove_file.php', {
                        input_name: inputName1,
                        hash: 'd0512044ad57e63e8b0c858b5db317bcf20d6138',
                        form_id: 'bd-hr_main1',
                        filename: item.name,
                        upload_dir: '/hr/file-uploads/'
                    }, function() {
                        validateUpload1();
                    });
                },
                onEmpty: function(listEl, parentEl, newInputEl, inputEl) {
                    validateUpload1();
                },
                // captions
                captions: {
                    button: function(options) {
                        return 'Browse ' + (options.limit == 1 ? 'file' : 'files');
                    },
                    feedback: function(options) {
                        return 'Choose ' + (options.limit == 1 ? 'file' : 'files') + ' to upload';
                    },
                    feedback2: function(options) {
                        return options.length + ' ' + (options.length > 1 ? ' files were' : ' file was') + ' chosen';
                    },
                    confirm: 'Confirm',
                    cancel: 'Cancel',
                    name: 'Name',
                    type: 'Type',
                    size: 'Size',
                    dimensions: 'Dimensions',
                    duration: 'Duration',
                    crop: 'Crop',
                    rotate: 'Rotate',
                    sort: 'Sort',
                    download: 'Download',
                    remove: 'Remove',
                    drop: 'Drop the files here to Upload',
                    paste: '<div class="fileuploader-pending-loader"></div> Pasting a file, click here to cancel.',
                    removeConfirmation: 'Are you sure you want to remove this file?',
                    errors: {
                        filesLimit: 'Only ${limit} files are allowed to be uploaded.',
                        filesType: 'Only ${extensions} files are allowed to be uploaded.',
                        fileSize: '${name} is too large! Please choose a file up to ${fileMaxSize}MB.',
                        filesSizeAll: 'Files that you chose are too large! Please upload files up to ${maxSize} MB.',
                        fileName: 'File with the name ${name} is already selected.',
                        folderUpload: 'You are not allowed to upload folders.'
                    }
                }
            });
            var inputName2 = $("#bd-hr_main1 #uploader-i9_listc_upload").attr('name'),
                $form = $('input[name="' + inputName2 + '"]').closest('form'),
                $submit = $form.find('button[type="submit"]'),
                form = forms['bd-hr_main1'],
                originalDisabledState = $submit.prop('disabled'),
                debug2 = true;

            if (typeof(validateUpload2) === 'undefined') {
                var validateUpload2 = function() {
                    if (typeof(form.fv) == 'object') {
                        if (inputName2 in form.fv.elements) {
                            form.fv.validateField(inputName2);
                        } else if (inputName2 + '[]' in form.fv.elements) {
                            form.fv.validateField(inputName2 + '[]');
                        }
                    }
                }
            }

            $("#bd-hr_main1 #uploader-i9_listc_upload").fileuploader({
                enableApi: true,
                limit: 2,
                extensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                fileMaxSize: 10,
                beforeSelect: function(files, listEl, parentEl, newInputEl, inputEl) {
                    var iName2 = inputName2; // if uploader limit = 1
                    if ($('input[name="' + inputName2 + '[]"]')[0]) { // if several files allowed
                        iName2 += '[]';
                    }
                    if ($('input[name="' + iName2 + '"]').attr('required') && typeof(form.fv) == 'object' && iName2 in form.fv.elements) {
                        // replace the file input value by the hidden input value for the jQuery validation plugin
                        var hiddenInputName = iName2.replace(/^uploader-/, '').replace(/\[\]$/, '');
                        var o = new Object();
                        o[iName2] = {
                            notEmpty: function(field, element, validator) {
                                let value = $('input[name="' + hiddenInputName + '"]').val().replace(/\[\]$/, '');
                                return value;
                            }
                        };
                        form.fv.registerPlugin(
                            'transformer', new FormValidation.plugins.Transformer(o)
                        );
                    }

                    return true;
                },
                upload: {
                    url: '/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_upload_file.php',
                    data: {
                        input_name: inputName2,
                        hash: 'd0512044ad57e63e8b0c858b5db317bcf20d6138',
                        form_id: 'bd-hr_main1'
                    },
                    type: 'POST',
                    enctype: 'multipart/form-data',
                    start: true,
                    synchron: true,
                    onSuccess: function(data, item, listEl, parentEl, newInputEl, inputEl, textStatus, jqXHR) {
                        $submit.prop('disabled', originalDisabledState);

                        try {
                            data = JSON.parse(data);
                            item.name = data.files[0].name;
                            item.html.find('.column-title > div:first-child').text(data.files[0].name).attr('title', data.files[0].name);
                        } catch (e) {
                            if (debug2 === true) {
                                console.log(data);
                                if (data.warnings.length > 0) {
                                    item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-warning has-icon mt-2 mb-0'>" + data.warnings[0] + "</p>");
                                }
                                item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-danger has-icon mt-2 mb-0'><h5>Something went wrong with the uploader.</h5><p>You may have to create the upload folder and/or the thumbnails folders manually, or your upload folder is not writable.</p><p>If you generate thumbnails your upload folder must follow this structure:</p><pre><code>[your-upload-folder] \n    => thumbs \n        => lg \n        => md \n        => sm</code></pre><p class='mb-0'><strong>Open your browser console for more information.</strong></p></div>");
                            }
                        }

                        // make HTML changes
                        item.html.find('.fileuploader-action-remove').addClass('fileuploader-action-success');

                        validateUpload2();

                        setTimeout(function() {
                            item.html.find('.progress-bar2').fadeOut(400);
                        }, 400);
                    },
                    onError: function(item, listEl, parentEl, newInputEl, inputEl, jqXHR, textStatus, errorThrown) {
                        $submit.prop('disabled', originalDisabledState);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.find('span').html(0 + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(0 + "%");
                            item.html.find('.progress-bar2').fadeOut(400);
                        }

                        item.upload.status != 'cancelled' && item.html.find('.fileuploader-action-retry').length == 0 ? item.html.find('.column-actions').prepend(
                            '<a class="fileuploader-action fileuploader-action-retry" title="Retry"><i></i></a>'
                        ) : null;
                    },
                    onProgress: function(data, item) {
                        $submit.prop('disabled', true);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.show();
                            progressBar.find('span').html(data.percentage + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(data.percentage + "%");
                        }
                    },
                    onComplete: null
                },
                onRemove: function(item) {
                    $submit.prop('disabled', originalDisabledState);
                    // send POST request
                    $.post('/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_remove_file.php', {
                        input_name: inputName2,
                        hash: 'd0512044ad57e63e8b0c858b5db317bcf20d6138',
                        form_id: 'bd-hr_main1',
                        filename: item.name,
                        upload_dir: '/hr/file-uploads/'
                    }, function() {
                        validateUpload2();
                    });
                },
                onEmpty: function(listEl, parentEl, newInputEl, inputEl) {
                    validateUpload2();
                },
                // captions
                captions: {
                    button: function(options) {
                        return 'Browse ' + (options.limit == 1 ? 'file' : 'files');
                    },
                    feedback: function(options) {
                        return 'Choose ' + (options.limit == 1 ? 'file' : 'files') + ' to upload';
                    },
                    feedback2: function(options) {
                        return options.length + ' ' + (options.length > 1 ? ' files were' : ' file was') + ' chosen';
                    },
                    confirm: 'Confirm',
                    cancel: 'Cancel',
                    name: 'Name',
                    type: 'Type',
                    size: 'Size',
                    dimensions: 'Dimensions',
                    duration: 'Duration',
                    crop: 'Crop',
                    rotate: 'Rotate',
                    sort: 'Sort',
                    download: 'Download',
                    remove: 'Remove',
                    drop: 'Drop the files here to Upload',
                    paste: '<div class="fileuploader-pending-loader"></div> Pasting a file, click here to cancel.',
                    removeConfirmation: 'Are you sure you want to remove this file?',
                    errors: {
                        filesLimit: 'Only ${limit} files are allowed to be uploaded.',
                        filesType: 'Only ${extensions} files are allowed to be uploaded.',
                        fileSize: '${name} is too large! Please choose a file up to ${fileMaxSize}MB.',
                        filesSizeAll: 'Files that you chose are too large! Please upload files up to ${maxSize} MB.',
                        fileName: 'File with the name ${name} is already selected.',
                        folderUpload: 'You are not allowed to upload folders.'
                    }
                }
            });


//// --- INSERT SCRIPTINCLUDE2 ---- ////


            
        });
    </script>

    
<script>
        if (typeof forms === "undefined") {
            var forms = [];
        }
        forms["bd-hr_main1"] = {};
        document.addEventListener('DOMContentLoaded', function(event) {
            if (top != self && typeof location.ancestorOrigins != "undefined") {
                if (location.ancestorOrigins[0] !== "https://preview.codecanyon.net" && !document.getElementById("drag-and-drop-preview") && document.getElementById("bd-hr_main1")) {
                    document.getElementById("bd-hr_main1").addEventListener("submit", function(e) {
                        e.preventDefault();
                        console.log("not allowed");
                        return false;
                    });
                }
            }

            if (typeof(phpfbSignPads) == "undefined") {
                window.phpfbSignPads = [];
                window.resizeSignatures = function() {
                    let ratio = Math.max(window.devicePixelRatio || 1, 1);
                    let signatures = document.querySelectorAll('.signature-pad-canvas');
                    signatures.forEach(item => {
                        if (item.getAttribute('data-percent-width')) {
                            let percent = item.getAttribute('data-percent-width') / 100;
                            item.style.width = item.parentNode.offsetWidth * percent + 'px';
                        }
                        item.width = item.offsetWidth * ratio;
                        item.height = item.offsetHeight * ratio;
                        item.getContext("2d").scale(ratio, ratio);
                    });
                    window.phpfbSignPads.forEach(function(el) {
                        el.clear();
                    })
                };
                window.addEventListener('resize', resizeSignatures);
            }

            let inputName = document.querySelector("#bd-hr_main1 #user-signature").getAttribute('name'),
                dataAttr = document.querySelector("#bd-hr_main1 #user-signature").dataset,
                dataWidth = dataAttr.width === undefined ? '100%' : dataAttr.width,
                dataHeight = dataAttr.height === undefined ? 200 : dataAttr.height,
                dataBackgroundColor = dataAttr.backgroundColor === undefined ? 'rgba(255, 255, 255, 0)' : dataAttr.backgroundColor,
                dataPenColor = dataAttr.penColor === undefined ? 'rgb(0, 0, 0)' : dataAttr.penColor,
                dataClearButton = dataAttr.clearButton === undefined ? false : dataAttr.clearButton === 'true',
                dataClearButtonClass = dataAttr.clearButtonClass === undefined ? '' : dataAttr.clearButtonClass,
                dataClearButtonText = dataAttr.clearButtonText === undefined ? 'clear' : dataAttr.clearButtonText;

            document.querySelector("#bd-hr_main1 #user-signature-canvas").height = dataHeight;
            document.querySelector("#bd-hr_main1 #user-signature-canvas").style.height = dataHeight + 'px';

            if (!isNaN(dataWidth)) {
                document.querySelector("#bd-hr_main1 #user-signature-canvas").width = dataWidth;
                document.querySelector("#bd-hr_main1 #user-signature-canvas").style.width = dataWidth + 'px';
            } else {
                // if percent
                document.querySelector("#bd-hr_main1 #user-signature-canvas").setAttribute('data-percent-width', dataWidth.replace('%', ''));
            }

            resizeSignatures();

            let signaturePad = new SignaturePad(document.querySelector("#bd-hr_main1 #user-signature-canvas"), {
                backgroundColor: dataBackgroundColor,
                penColor: dataPenColor,
                onEnd: function() {
                    let data = signaturePad.toDataURL('image/png');
                    document.querySelector("#bd-hr_main1 #user-signature").value = data;
                }
            });

            window.phpfbSignPads["#bd-hr_main1 #user-signature"] = signaturePad;

            if (dataClearButton == true) {
                let $clearButton = document.createElement('button');
                dataClearButtonClass.split(' ').forEach(cl => {
                    $clearButton.classList.add(cl);
                });
                $clearButton.classList.add('sign-pad-btn');
                $clearButton.textContent = dataClearButtonText;

                document.querySelector("#bd-hr_main1 #user-signature-canvas").insertAdjacentElement('afterend', $clearButton);

                $clearButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelector("#bd-hr_main1 #user-signature").value = '';
                    window.phpfbSignPads["#bd-hr_main1 #user-signature"].clear();
                    return false;
                });
            }
            grecaptcha.ready(function() {
                var $submitBtn = document.querySelector("#bd-hr_main1 button[type='submit']");
                if ($submitBtn) {
                    document.querySelector('input[name="g-recaptcha-response"]').value = '';
                    if (document.querySelector("#bd-hr_main1").classList.contains('has-validator')) {
                        // if formvalidation enabled

                        var formId = document.querySelector("#bd-hr_main1").getAttribute('id');
                        var form = forms[formId];

                        document.querySelector("#bd-hr_main1").classList.add('has-recaptcha-v3')

                        $submitBtn.addEventListener('click', function(e) {
                            e.preventDefault;
                            form.fv.validate()
                                .then(function(status) {
                                    if (status == 'Valid') {
                                        grecaptcha.execute('6LeNWaQUAAAAAGO_c1ORq2wla-PEFlJruMzyH5L6', {
                                            action: 'default'
                                        }).then(function(token) {
                                            document.querySelector('input[name="g-recaptcha-response"]').value = token;
                                            document.querySelector("#bd-hr_main1").submit();
                                        });
                                    }
                                    return false;
                                });
                            return false;
                        });
                    } else {
                        $submitBtn.addEventListener('click', function(e) {
                            e.preventDefault;
                            grecaptcha.execute('6LeNWaQUAAAAAGO_c1ORq2wla-PEFlJruMzyH5L6', {
                                action: 'default'
                            }).then(function(token) {
                                document.querySelector('input[name="g-recaptcha-response"]').value = token;
                                document.querySelector("#bd-hr_main1").submit();
                            });
                            return false;
                        });
                    }
                } else {
                    const alert = document.createElement("p");
                    alert.classList.add("alert");
                    alert.classList.add("alert-danger");
                    alert.innerHTML = 'Recaptcha V3 - no submit button found';
                    const parent = document.querySelector("#bd-hr_main1").parentNode;
                    parent.insertBefore(alert, document.querySelector("#bd-hr_main1"));
                }
            });
            if (typeof(l) == "undefined") {
                window.l = [];
            }
            var $laddaForm = document.querySelector("#bd-hr_main1 button[name='submit-btn']").closest('form'),
                formId = $laddaForm.getAttribute('id'),
                form = forms[formId];

            if (document.querySelector("#bd-hr_main1 button[name='submit-btn']").getAttribute('data-style') === null) {
                document.querySelector("#bd-hr_main1 button[name='submit-btn']").setAttribute('data-style', 'zoom-in');
            }

            l["#bd-hr_main1 button[name='submit-btn']"] = Ladda.create(document.querySelector("#bd-hr_main1 button[name='submit-btn']"));

            document.querySelector("#bd-hr_main1 button[name='submit-btn']").addEventListener('click', function(e) {
                if (!e.target.closest('button').disabled) {
                    if (!document.querySelector("#bd-hr_main1 button[name='submit-btn']").hasAttribute('data-loading')) {
                        l["#bd-hr_main1 button[name='submit-btn']"].start();

                        // formValidation won't work if submit button is disabled
                        e.target.closest('button').removeAttribute('disabled');
                    } else {
                        l["#bd-hr_main1 button[name='submit-btn']"].stop();
                    }
                }

                // stop if validation fails
                if (typeof(form.fv) == 'object') {
                    form.fv.on('core.form.invalid', function() {
                        l["#bd-hr_main1 button[name='submit-btn']"].stop();
                    });
                }
            });







        });
    </script>
