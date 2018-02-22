var moduleRow = '#module-imagemanager > .row ';
var selectedBlockId = '#module-imagemanager .image-info ';
var itemOverview = '#module-imagemanager .item-overview .item';
var imageManagerModule = {
    //params for input selector
    fieldId: null,
    cropRatio: null,
    cropViewMode: 1,
    defaultImageId: null,
    selectType: null,
    multiple: null,
    //current selected image
    selectedImage: {},
    //language
    message: null,
    //init imageManager
    init: function(){
        //init cropper
        $(moduleRow + '.col-image-editor .image-cropper .image-wrapper img#image-cropper').cropper({
            viewMode: imageManagerModule.cropViewMode
        });

        imageManagerModule.selectDefault();

        //set selected after pjax complete
        $('#pjax-mediamanager').on('pjax:complete', function() {
            imageManagerModule.selectDefault();
        });
    },
    selectDefault: function () {
        if(imageManagerModule.defaultImageId !== ""){
            if (imageManagerModule.multiple) {
                $.each(imageManagerModule.defaultImageId.split(','), function (index, value) {
                    imageManagerModule.selectImage(value);
                });
            } else {
                imageManagerModule.selectImage(imageManagerModule.defaultImageId);
            }
        }
    },
    //filter result
    filterImageResult: function(searchTerm){
        //set new url
        var newUrl = window.queryStringParameter.set(window.location.href, "ImageManagerSearch[globalSearch]", searchTerm);
        //set pjax
        $.pjax({url: newUrl, container: "#pjax-mediamanager", push: false, replace: false, timeout: 5000, scrollTo:false}).done(function () {
            $.pjax({url: newUrl, container: "#pjax-mediamanager-files", push: false, replace: false, timeout: 5000, scrollTo:false});
        });
    },
    //select an image
    selectImage: function(id){
        var $el = $(itemOverview + "[data-key='" + id + "']");

        if (imageManagerModule.multiple) {
            if ($el.hasClass('selected')) {
                $el.removeClass("selected");
                delete imageManagerModule.selectedImage[id];
            } else {
                imageManagerModule.select($el, id);
            }
        } else {
            $(itemOverview).removeClass('selected');
            imageManagerModule.select($el, id);
        }
    },
    select: function ($el, id) {
        $el.addClass("selected");

        imageManagerModule.getDetails(id);
    },
    //pick the selected image
    pickImage: function(){
        switch(imageManagerModule.selectType){
            case "input":
                var sFieldId = imageManagerModule.fieldId;
                var sFieldNameId = sFieldId + "_name";
                var sFieldImageId = sFieldId + "_image";
                var parentDoc = window.parent.document;

                if (imageManagerModule.multiple) {
                    $('.multiple-input-list__item', parentDoc).remove();

                    var i = 1;
                    $.each(imageManagerModule.selectedImage, function (index, value) {
                        $('.js-input-plus', parentDoc).click();

                        var $el = $('.multiple-input-list__item', parentDoc).last();

                        $el.find('.image-id', parentDoc).val(value.id);
                        $el.find('.image-name', parentDoc).text(value.fileName);
                        $el.find('img', parentDoc).attr("src", value.image);
                        $el.find('.image-order', parentDoc).val(i++);
                    });
                } else {
                    $('#' + sFieldId, parentDoc).val(imageManagerModule.selectedImage.id);
                    $('#' + sFieldNameId, parentDoc).val(imageManagerModule.selectedImage.fileName);
                    $('#' + sFieldImageId, parentDoc).attr("src", imageManagerModule.selectedImage.image).parent().removeClass("hide");
                }

                parent.$('#' + sFieldId).trigger('change');

                $(".delete-selected-image[data-input-id='" + sFieldId + "']", parentDoc).removeClass("hide");

                window.parent.imageManagerInput.closeModal();
                break;
            case "ckeditor":
            case "tinymce":
                if (imageManagerModule.selectedImage !== null) {
                    $.ajax({
                        url: imageManagerModule.baseUrl + "/get-original-image",
                        type: "GET",
                        data: {
                            ImageManager_id: imageManagerModule.selectedImage.id
                        },
                        dataType: "json",
                        success: function (responseData) {
                            var sField;
                            if (imageManagerModule.selectType === "ckeditor") {
                                sField = window.queryStringParameter.get(window.location.href, "CKEditorFuncNum");
                                window.top.opener.CKEDITOR.tools.callFunction(sField, responseData);
                                window.self.close();
                            } else if(imageManagerModule.selectType === "tinymce"){
                                sField = window.queryStringParameter.get(window.location.href, "tag_name");
                                window.opener.document.getElementById(sField).value = responseData;
                                window.close();
                                window.opener.focus();
                            }
                        },
                        error: function () {
                            alert("Error: can't get item");
                        }
                    });
                } else {
                    alert("Error: image can't picked");
                }
                break;
        }


    },
    //delete the selected image
    deleteSelectedImage: function (modelId) {
        if (confirm(imageManagerModule.message.deleteMessage)) {
            imageManagerModule.editor.close();

            if (modelId) {
                $.ajax({
                    url: imageManagerModule.baseUrl + "/delete",
                    type: "DELETE",
                    data: {
                        ImageManager_id: modelId
                    },
                    dataType: "json",
                    success: function (responseData) {
                        if(responseData.delete === true){
                            $(itemOverview + '[data-key="' + modelId + '"]').remove();
                            //add hide class to info block
                            $(selectedBlockId).addClass("hide");
                            //set selectedImage to null
                            imageManagerModule.selectedImage = null;
                            //close edit
                        }else{
                            alert("Error: item is not deleted");
                        }
                    },
                    error: function () {
                        alert("Error: can't delete item");
                    }
                });
            }else{
                alert("Error: image can't delete, no image isset set");
            }
        }
    },
    //get image details
    getDetails: function(id, pickAfterGetDetails){
        //set propertie if not set
        pickAfterGetDetails = pickAfterGetDetails !== undefined ? pickAfterGetDetails : false;
        //call action by ajax
        $.ajax({
            url: imageManagerModule.baseUrl+"/view",
            type: "GET",
            data: {
                ImageManager_id: id,
                id: $(selectedBlockId + ".tags").data('id')
            },
            dataType: "json",
            success: function (responseData) {
                if (imageManagerModule.multiple) {
                    imageManagerModule.selectedImage[responseData.id] = responseData;
                } else {
                    imageManagerModule.selectedImage = responseData;
                }

                if (pickAfterGetDetails) {
                    imageManagerModule.pickImage();
                } else {
                    $(selectedBlockId + "#model-id").val(responseData.id);
                    $(selectedBlockId + ".fileName").text(responseData.fileName).attr("title",responseData.fileName);
                    $(selectedBlockId + ".tags").html(responseData.tags);
                    $(selectedBlockId + ".created").text(responseData.created);
                    $(selectedBlockId + ".fileSize").text(responseData.fileSize);
                    $(selectedBlockId + ".image-link").val(responseData.originalLink);

                    var html;
                    if (responseData.image) {
                        $(selectedBlockId + ".dimensions .dimension-width").text(responseData.dimensionWidth);
                        $(selectedBlockId + ".dimensions .dimension-height").text(responseData.dimensionHeight);

                        $(selectedBlockId + ".dimensions").removeClass('hide');
                        $(selectedBlockId + ".crop-image-item").removeClass('hide');

                        html = "<img src='" + responseData.image + "' alt='" + responseData.fileName + "'/>";
                    } else {
                        $(selectedBlockId + ".dimensions").addClass('hide');
                        $(selectedBlockId + ".crop-image-item").addClass('hide');

                        var split = responseData.fileName.split("."),
                            extension = split[split.length - 1];
                        html = '<div class="file-extension">' + extension + '</div>';
                    }

                    $(selectedBlockId + ".thumbnail").html(html);

                    $(selectedBlockId).removeClass("hide");
                }
            },
            error: function (jqXHR) {
                alert("Can't view image. Error: "+jqXHR.responseText);
            }
        });
    },
    updateTags: function (modelId) {
        var tags = [];

        $.each($("#select-tags").find(':selected'), function (key, el) {
            tags[tags.length] = $(el).text();
        });

        $.ajax({
            url: imageManagerModule.baseUrl + "/update-tags",
            type: "POST",
            data: {
                modelId: modelId,
                tags: tags
            },
            dataType: "json",
            success: function (responseData) {
                if (responseData.result) {
                    $('#update-tags').addClass('btn-success');

                    setTimeout(function () {
                        $('#update-tags').removeClass('btn-success');
                    }, 1000);
                } else {
                    alert("Error: tags is not updated");
                }
            },
            error: function () {
                alert("Error: tags is not updated");
            }
        });
    },
    //editor functions
    editor: {
        cropObject: $(moduleRow + '.col-image-editor .image-cropper .image-wrapper img#image-cropper'),
        //open editor block
        open: function(){
            //show editer / hide overview
            $(moduleRow + ".col-image-editor").show();
            $(moduleRow + ".col-overview").hide();
        },
        //close editor block
        close: function(){
            $(moduleRow + ".col-overview").show();
            $(moduleRow + ".col-image-editor").hide();
        },
        //open cropper
        openCropper: function(){
            //check if isset image
            if(imageManagerModule.selectedImage !== null){
                //call action by ajax
                $.ajax({
                    url: imageManagerModule.baseUrl+"/get-original-image",
                    type: "GET",
                    data: {
                        ImageManager_id: imageManagerModule.selectedImage.id
                    },
                    dataType: "json",
                    success: function (responseData) {
                        $(moduleRow + ".col-image-cropper").css("visibility","hidden");

                        imageManagerModule.editor.cropObject.one('built.cropper', function () {
                            $(moduleRow + ".col-image-cropper").css("visibility","visible");
                        })
                            .cropper('reset')
                            .cropper('setAspectRatio', parseFloat(imageManagerModule.cropRatio))
                            .cropper('replace', responseData);

                        imageManagerModule.editor.open();
                    },
                    error: function () {
                        alert("Error: can't get item");
                    }
                });
            }else{
                alert("Error: image can't crop, no image isset set");
            }
        },
        //apply crop
        applyCrop: function(pickAfterCrop){
            pickAfterCrop = pickAfterCrop !== undefined ? pickAfterCrop : false;

            if (imageManagerModule.selectedImage) {
                var oCropData = imageManagerModule.editor.cropObject.cropper("getData");

                $.ajax({
                    url: imageManagerModule.baseUrl + "/crop",
                    type: "POST",
                    data: {
                        ImageManager_id: imageManagerModule.selectedImage.id,
                        CropData: oCropData
                    },
                    dataType: "json",
                    success: function (responseData) {
                        if (responseData) {
                            if (pickAfterCrop) {
                                imageManagerModule.getDetails(responseData, true);
                            } else {
                                imageManagerModule.selectImage(responseData);
                                $.pjax.reload('#pjax-mediamanager', {push: false, replace: false, timeout: 5000, scrollTo: false});
                            }
                        }

                        imageManagerModule.editor.close();
                    },
                    error: function () {
                        alert("Error: item is not cropped");
                    }
                });
            } else {
                alert("Error: image can't crop, no image isset set");
            }
        },
        rotateLeft: function () {
            imageManagerModule.editor.cropObject.cropper('rotate', -90);
        },
        rotateRight: function () {
            imageManagerModule.editor.cropObject.cropper('rotate', 90);
        },
        moveMode: function () {
            imageManagerModule.editor.cropObject.cropper('setDragMode', 'move');
        },
        cropMode: function () {
            imageManagerModule.editor.cropObject.cropper('setDragMode', 'crop');
        },
        zoomIn: function () {
            imageManagerModule.editor.cropObject.cropper('zoom', 0.1);
        },
        zoomOut: function () {
            imageManagerModule.editor.cropObject.cropper('zoom', -0.1);
        },
        reset: function () {
            imageManagerModule.editor.cropObject.cropper('reset');
        }
    }
};

$(document).ready(function () {
    //init Image manage
    imageManagerModule.init();
    //on click select item (open view)
    $(document).on("click", "#module-imagemanager .item-overview .item", function () {
        imageManagerModule.selectImage($(this).data("key"));
    });
    //on click pick image
    $(document).on("click", selectedBlockId + ".pick-image-item", function () {
        imageManagerModule.pickImage();
        return false;
    });
    //on click delete call "delete"
    $(document).on("click", selectedBlockId + ".delete-image-item", function () {
        imageManagerModule.deleteSelectedImage($('#model-id').val());
        return false;
    });
    //on click crop call "crop"
    $(document).on("click", selectedBlockId + ".crop-image-item", function () {
        imageManagerModule.editor.openCropper();
        return false;
    });
    //on click apply crop
    $(document).on("click", "#module-imagemanager .image-cropper .apply-crop", function () {
        imageManagerModule.editor.applyCrop();
        return false;
    });
    //on click apply crop
    $(document).on("click", "#module-imagemanager .image-cropper .apply-crop-select", function () {
        imageManagerModule.editor.applyCrop(true);
        return false;
    });
    //on click cancel crop
    $(document).on("click", "#module-imagemanager .image-cropper .cancel-crop", function () {
        imageManagerModule.editor.close();
        return false;
    });
    //on keyup change set filter
    $( document ).on("keyup change", "#input-mediamanager-search", function() {
        imageManagerModule.filterImageResult($(this).val());
    });

    $(document).on("click", "#rotate-left", function() {
        imageManagerModule.editor.rotateLeft();
    });

    $(document).on("click", "#rotate-right", function() {
        imageManagerModule.editor.rotateRight();
    });

    $(document).on("click", "#mode-move", function() {
        imageManagerModule.editor.moveMode();
    });

    $(document).on("click", "#mode-crop", function() {
        imageManagerModule.editor.cropMode();
    });

    $(document).on("click", "#zoom-in", function() {
        imageManagerModule.editor.zoomIn();
    });

    $(document).on("click", "#zoom-out", function() {
        imageManagerModule.editor.zoomOut();
    });

    $(document).on("click", "#reset-crop", function() {
        imageManagerModule.editor.reset();
    });

    $('.image-info .copy-link').click(function () {
        var $this = $(this);

        copyToClipboard($('.image-info .image-link')[0]);
        $this.text('Copied');

        setTimeout(function () {
            $this.text('Copy');
        }, 1000);
    });

    $('#update-tags').click(function () {
        imageManagerModule.updateTags($('#model-id').val());
    });

    function copyToClipboard(elem) {
        // select the content
        var currentFocus = document.activeElement;
        elem.focus();
        elem.setSelectionRange(0, elem.value.length);

        document.execCommand("copy");

        // restore original focus
        if (currentFocus && typeof currentFocus.focus === "function") {
            currentFocus.focus();
        }
    }
});

/*
 * return new get param to url
 */
window.queryStringParameter = {
    get: function(uri, key){
        var reParam = new RegExp('(?:[\?&]|&amp;)' + key + '=([^&]+)', 'i');
        var match = uri.match(reParam);
        return (match && match.length > 1) ? match[1] : null;
    },
    set: function(uri, key, value){
        //replace brackets
        var keyReplace = key.replace("[]", "").replace(/\[/g, "%5B").replace(/\]/g, "%5D");
        //replace data
        var re = new RegExp("([?&])" + keyReplace + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + keyReplace + "=" + value + '$2');
        }
        else {
            return uri + separator + keyReplace + "=" + value;
        }
    }
};