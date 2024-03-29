$(document).ready(function () {
    $("input[type='checkbox'].booking__form-zahlung").on("change", function (evt) {
        $('.booking__zahlung>div#booking__zahlung-not-selected').hide();
        var zid = $(this).data("id");
        var type = $(this).data("type");
        var newVal = parseFloat($(this).data("value"));

        if ($(".booking__zahlung>div#Z" + zid).length === 0) {
            $('.booking__zahlung>div:last-child').before(
                $("<div/>").attr("id", "Z" + zid).append(
                    $("<span/>").text("Z" + zid),
                    $("<span/>").text(newVal.toFixed(2)).addClass("money"),
                    $("<input/>").attr("type", "hidden").attr("value", zid).attr("name", "zahlung[]"),
                    $("<input/>").attr("type", "hidden").attr("value", type).attr("name", "zahlung-type[]")
                )
            );
        } else {
            $("#Z" + zid).remove();
            if ($('.booking__zahlung>div[id]').length === 1) {
                $('.booking__zahlung>div#booking__zahlung-not-selected').show();
            }
        }
        //update sum
        var sum = 0.0;
        $(".booking__zahlung>div")
            .not(".booking__zahlung-sum")
            .children("span.money")
            .each(function () {
                sum += parseFloat($(this).text());
            });
        $("div.booking__zahlung-sum>span.money").text(sum.toFixed(2));
        toggleButton(sum, parseFloat($("div.booking__belege-sum>span.money").text()));
    });

    $("input[type='checkbox'].booking__form-beleg").on("change", function (evt) {
        $('.booking__belege>div#booking__belege-not-selected').hide();
        var newVal = parseFloat($(this).data("value"));
        if ($(this).data("type") === "extern") {
            var eId = $(this).data("e-id");
            var vId = $(this).data("v-id");
            var name = "E" + eId + "-V" + vId;
            var id = $(this).data('id');
            if ($(".booking__belege>div#" + name).length === 0) {
                $('.booking__belege>div:last-child').before(
                    $("<div/>").attr("id", name).append(
                        $("<span/>").text(name),
                        $("<span/>").text(newVal.toFixed(2)).addClass("money"),
                        $("<input/>").attr("type", "hidden").attr("value", id).attr("name", "extern[]")
                    )
                );
            } else {
                $("#" + name).remove();
                if ($('.booking__belege>div[id]').length === 1) {
                    $('.booking__belege>div#booking__belege-not-selected').show();
                }
            }
        } else if ($(this).data("type") === "auslage") {
            var aid = $(this).data("id");

            if ($(".booking__belege>div#" + "A" + aid).length === 0) {
                $('.booking__belege>div:last-child').before(
                    $("<div/>").attr("id", "A" + aid).append(
                        $("<span/>").text("A" + aid),
                        $("<span/>").text(newVal.toFixed(2)).addClass("money"),
                        $("<input/>").attr("type", "hidden").attr("value", aid).attr("name", "auslage[]")
                    )
                );
            } else {
                $("#A" + aid).remove();
                if ($('.booking__belege>div[id]').length === 1) {
                    $('.booking__belege>div#booking__belege-not-selected').show();
                }
            }
        }
        //update sum
        var sum = 0.0;
        $(".booking__belege>div")
            .not(".booking__belege-sum")
            .children("span.money")
            .each(function () {
                sum += parseFloat($(this).text());
            });
        $("div.booking__belege-sum>span.money").text(sum.toFixed(2));
        toggleButton(sum, parseFloat($("div.booking__zahlung-sum>span.money").text()));
    });
    toggleButton(0, 0);
});

function toggleButton(sum_belege, sum_zahlung) {
    if ($("#booking__check-button").hasClass("user-is-not-hv")) {
        $("#booking__check-button").prop('disabled', true).attr("title", "Nur Haushaltsverantwortliche können Buchungen anweisen!");
        return;
    }
    if (sum_belege.toFixed(2) !== sum_zahlung.toFixed(2)) {
        $("#booking__check-button").prop('disabled', true).attr("title", "Summen stimmen nicht überein");
    } else {
        $("#booking__check-button").prop('disabled', false).attr("title", "");
    }
    if ($('.booking__belege>div').length === 1 && $('.booking__zahlung>div').length === 1) {
        $("#booking__check-button").prop('disabled', true).attr("title", "nichts ausgewählt");
    }
}
