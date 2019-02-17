$(document).ready(function () {

    var frm = $("form.ajax-form");

    frm.each(function () {
        $(this).on('submit', function (e) {
            var data = {};
            $(this).find('input[name],textarea[name]').each(function (i, el) {
                if (el.name === 'nononce')
                    return;
                if (el.name.slice(-2) === "[]") {
                    var name = el.name.slice(0, -2);
                    if (!data.hasOwnProperty(name)) {
                        data[name] = [];
                    }
                    data[name].push($(el).val());
                } else {
                    data[el.name] = $(el).val();
                }
            });
            $.ajax({
                type: $(this).attr('method'),
                url: $(this).attr('action'),
                data: data,
                success: defaultPostModalHandler,
                error: xpAjaxErrorHandler
            });
            e.preventDefault();
        });
    });
});