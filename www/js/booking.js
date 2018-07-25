$(document).ready(function () {
    $("input[type='checkbox'].booking__form-zahlung").on("change", function (evt) {
        $('.booking__zahlung>div[data-id=""]').hide();
        var zid = $(this).data("id");
        var sum = parseFloat($(this).data("value"));

        if ($(".booking__zahlung>div#" + zid).length === 0) {
            $("<div/>").attr("id", "Z" + zid).append(
                $("<span/>").text("Z" + zid),
                $("<span/>").text(sum.toFixed(2)).addClass("money"),
                $("<input/>").attr("type", "hidden").attr("value", zid).attr("name", "zahlung[]")
            ).appendTo($('.booking__zahlung'));
        } else {
            $("#Z" + zid).remove();
            if ($('.booking__zahlung').children("div").length === 1) {
                $('.booking__zahlung>div[data-id=""]').show();
            }
        }
    });

    $("input[type='checkbox'].booking__form-beleg").on("change", function (evt) {
        $('.booking__belege>div[data-id=""]').hide();
        var aid = $(this).data("id");
        var sum = parseFloat($(this).data("value"));

        if ($(".booking__belege>div#" + "A" + aid).length === 0) {
            $("<div/>").attr("id", "A" + aid).append(
                $("<span/>").text("A" + aid),
                $("<span/>").text(sum.toFixed(2)).addClass("money"),
                $("<input/>").attr("type", "hidden").attr("value", aid).attr("name", "beleg[]")
            ).appendTo($('.booking__belege'));
        } else {
            $("#A" + aid).remove();
            if ($('.booking__belege').children("div").length === 1) {
                $('.booking__belege>div[data-id=""]').show();
            }
        }
    });
});