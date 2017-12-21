var modalManagerId = '#modal-imagemanager ';
var imageManagerInput = {
	baseUrl: null,
	//language
	message: null,
	//init imageManagerInput
	init: function(){
		//create modal
		imageManagerInput.initModal();
	},
	//creat image Manager modal
	initModal: function(){
		if (!$("#modal-imagemanager").length) {
			var sModalHtml = '<div tabindex="-1" role="dialog" class="fade modal" id="modal-imagemanager">';
				sModalHtml += '<div class="modal-dialog modal-lg">';
					sModalHtml += '<div class="modal-content">';
						sModalHtml += '<div class="modal-header">';
							sModalHtml += '<button aria-hidden="true" data-dismiss="modal" class="close" type="button">&times;</button>';
							sModalHtml += '<h4>Image manager</h4>';
						sModalHtml += '</div>';
						sModalHtml += '<div class="modal-body">';
							sModalHtml += '<iframe src="#"></iframe>';
						sModalHtml += '</div>';
					sModalHtml += '</div>';
				sModalHtml += '</div>';
			sModalHtml += '</div>';

			$('body').prepend(sModalHtml);
		}
	},
	//open media manager modal
	openModal: function(inputId, aspectRatio, cropViewMode, multiple){
		//get selected item
		var iImageId = $("#" + inputId).val();
		if (multiple) {
			var ids = [];
            $('.multiple-input-list__item').each(function () {
				ids[ids.length] = $(this).find('input[type="hidden"]').val();
            });

            iImageId = ids.join(',');
		}

        var srcImageIdQueryString = iImageId !== "" ? "&image-id="+iImageId : "";

		//create iframe url
		var queryStringStartCharacter = ((imageManagerInput.baseUrl).indexOf('?') == -1) ? '?' : '&';
		var imageManagerUrl = imageManagerInput.baseUrl+queryStringStartCharacter+"view-mode=iframe&input-id="+inputId+"&aspect-ratio="+aspectRatio+"&multiple="+multiple+"&crop-view-mode="+cropViewMode+srcImageIdQueryString;
		//set iframe path
		$(modalManagerId + "iframe").attr("src",imageManagerUrl);
                //set translation title for modal header
                $(modalManagerId + ".modal-dialog .modal-header h4").text(imageManagerInput.message.imageManager);
		//open modal
		$(modalManagerId).modal("show");
	},
	//close media manager modal
	closeModal: function(){
		$(modalManagerId).modal("hide");
	},
	//delete picked image
	deletePickedImage: function(inputId){
		var sFieldId = inputId;
		var sFieldNameId = sFieldId + "_name";
		var sImagePreviewId = sFieldId + "_image";
		var $fieldId = $('#' + sFieldId);
		var $deleteSelectedImage = $(".delete-selected-image[data-input-id='" + inputId + "']");
		var bShowConfirm = JSON.parse($deleteSelectedImage.data("show-delete-confirm"));

		if (bShowConfirm) {
			if (confirm(imageManagerInput.message.detachWarningMessage) === false) {
				return false;
			}
		}

		$fieldId.val("");
		$('#' + sFieldNameId).val("");

		$fieldId.trigger("change");

		$('#' + sImagePreviewId).attr("src","").parent().addClass("hide");

		$deleteSelectedImage.addClass("hide");
	}
};

$(document).ready(function () {
	//init Image manage
	imageManagerInput.init();
	
	//open media manager modal
	$(document).on("click", ".open-modal-imagemanager", function () {
		var aspectRatio = $(this).data("aspect-ratio");
		var cropViewMode = $(this).data("crop-view-mode");
		var inputId = $(this).data("input-id");
		var multiple = $(this).data("multiple");
		//open selector id
		imageManagerInput.openModal(inputId, aspectRatio, cropViewMode, multiple);
	});	
	
	//delete picked image
	$(document).on("click", ".delete-selected-image", function () {
		var inputId = $(this).data("input-id");
		//open selector id
		imageManagerInput.deletePickedImage(inputId);
	});
});