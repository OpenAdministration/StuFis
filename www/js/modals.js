$(document).ready(function () {

    var frm = $("form.ajax-form");

    frm.on('submit', function (e) {
        console.log(e);
        $.ajax({
            type: frm.attr('method'),
            url: frm.attr('action'),
            data: frm.serialize(),
            success: defaultPostModalHandler,
            error: xpAjaxErrorHandler
        });
        e.preventDefault();
    });
});