(function ($) {
    var fileUploadCount = 0;

    $.fn.fileUpload = function (options) {
        var settings = $.extend({
            maxFiles: 5,
            allowedFileTypes: ['image/jpeg', 'image/png', 'application/pdf']
        }, options);

        return this.each(function () {
            var fileUploadDiv = $(this);
            var fileUploadId = `fileUpload-${++fileUploadCount}`;
            var uploadedFiles = [];
            fileUploadDiv.data('uploadedFiles', uploadedFiles); // Store it in the jQuery data

            var fileDivContent = `
                <label for="${fileUploadId}" class="file-upload">
                    <div>
                        <i class="material-icons-outlined">cloud_upload</i>
                        <p>Drag & Drop Files Here</p>
                        <span>OR</span>
                        <div>Browse Your Files</div>
                    </div>
                    <input type="file" id="${fileUploadId}" name="files[]" multiple hidden />
                </label>
            `;

            fileUploadDiv.html(fileDivContent).addClass("file-container");

            var table = null;
            var tableBody = null;

            function createTable() {
                table = $(`
                    <table>
                        <thead>
                            <tr>
                                <th></th>
                                <th style="width: 30%;">File Name</th>
                                <th>Preview</th>
                                <th style="width: 20%;">Size</th>
                                <th>Type</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                `);

                tableBody = table.find("tbody");
                fileUploadDiv.append(table);
            }

            function handleFiles(files) {
                if (!table) {
                    createTable();
                }

                if (uploadedFiles.length + files.length > settings.maxFiles) {
                    alert(`You can only upload a maximum of ${settings.maxFiles} files.`);
                    return;
                }

                $.each(files, function (index, file) {
                    // Validate file type
                    if (settings.allowedFileTypes.indexOf(file.type) === -1) {
                        alert(`${file.name} is not a valid file type.`);
                        return;
                    }

                    uploadedFiles.push(file);

                    var fileName = file.name;
                    var fileSize = (file.size / 1024).toFixed(2) + " KB";
                    var fileType = file.type;
                    var preview = fileType.startsWith("image")
                        ? `<img src="${URL.createObjectURL(file)}" alt="${fileName}" height="30">`
                        : `<i class="material-icons-outlined">visibility_off</i>`;

                    tableBody.append(`
                        <tr>
                            <td>${uploadedFiles.length}</td>
                            <td>${fileName}</td>
                            <td>${preview}</td>
                            <td>${fileSize}</td>
                            <td>${fileType}</td>
                            <td><button type="button" class="deleteBtn"><i class="material-icons-outlined">delete</i></button></td>
                        </tr>
                    `);
                });

                if (uploadedFiles.length > 0) {
                    tableBody.find(".deleteBtn").click(function () {
                        var rowIndex = $(this).closest("tr").index();
                        uploadedFiles.splice(rowIndex, 1);
                        $(this).closest("tr").remove();

                        tableBody.find("tr").each(function (index, row) {
                            $(row).find("td:first-child").text(index + 1);
                        });

                        if (tableBody.find("tr").length === 0) {
                            tableBody.append('<tr><td colspan="6" class="no-file">No files selected!</td></tr>');
                        }
                    });
                }
            }

            fileUploadDiv.on({
                dragover: function (e) {
                    e.preventDefault();
                    fileUploadDiv.toggleClass("dragover", e.type === "dragover");
                },
                drop: function (e) {
                    e.preventDefault();
                    fileUploadDiv.removeClass("dragover");
                    handleFiles(e.originalEvent.dataTransfer.files);
                },
            });

            fileUploadDiv.find(`#${fileUploadId}`).change(function () {
                handleFiles(this.files);
            });
        });
    };
})(jQuery);

$('.submitformbtnavatar, .submitformbtnaccount_cover').click(function (e) {
    e.preventDefault(); // Prevent default form submission

    // Disable the button to prevent multiple submissions
    var $btn = $(this);
    $btn.prop('disabled', true);

    // Retrieve the form inside the modal
    var $form = $(this).closest('form');

    // Retrieve the corresponding file upload container
    var fileUploadDiv = $(this).closest('.modal').find('.file-container');
    var uploadedFiles = fileUploadDiv.data('uploadedFiles');

    // Create FormData object
    var formData = new FormData($form[0]); // Collects all form fields

    // Append the uploaded files to the form data
    if (uploadedFiles && uploadedFiles.length > 0) {
        $.each(uploadedFiles, function (index, file) {
            formData.append('files[]', file); // Append files to FormData
        });
    } else {
        alert('Please select at least one file to upload.');
        $btn.prop('disabled', false); // Re-enable the button if no files are uploaded
        return;
    }

    // Submit the form via AJAX to upload-handler.php
    $.ajax({
        url: $form.attr('action'), // Assuming the form action is correct
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            // Parse the response if it comes as a string
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }
            
            if (response.success) {
                // Redirect or refresh the page on successful upload
                window.location.href = response.url || '/myaccount/profile-images';
            } else {
                alert(response.message || 'An error occurred. Please try again.');
                $btn.prop('disabled', false); // Re-enable the button if the upload fails
            }
        },
        error: function () {
            alert('An error occurred while uploading the files. Please try again.');
            $btn.prop('disabled', false); // Re-enable the button on error
        }
    });
    
});

/*
$('.submitformbtnavatar, .submitformbtnaccount_cover').click(function (e) {
    e.preventDefault(); // Prevent default form submission

    // Disable the button to prevent multiple submissions
    var $btn = $(this);
    $btn.prop('disabled', true);

    // Retrieve the form inside the modal
    var $form = $(this).closest('form');

    // Retrieve the corresponding file upload container
    var fileUploadDiv = $(this).closest('.modal').find('.file-container');
    var uploadedFiles = fileUploadDiv.data('uploadedFiles');

    // Check if files are uploaded before submitting the form
    if (uploadedFiles && uploadedFiles.length > 0) {
        // Append the uploaded files to a hidden input element in the form (if needed)

        // Submit the form normally
        $form.submit();
    } else {
        alert('Please select at least one file to upload.');
        $btn.prop('disabled', false); // Re-enable the button if no files are uploaded
    }
});
*/