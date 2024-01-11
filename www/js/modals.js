$(document).ready(function () {

    const frms = $("form.ajax-form");

    frms.each(function () {
        const frm = $(this);
        frm.on('submit', function (e) {
            var data = {};
            $(this).find('input[name],textarea[name],select[name]').each(function (i, el) {
                if (el.name === 'nononce')
                    return;
                if (el.name.slice(-2) === "[]") { // array input
                    var name = el.name.slice(0, -2);
                    if (!data.hasOwnProperty(name)) {
                        data[name] = [];
                    }
                    if(el.type === "checkbox"){
                        if($(el).is(":checked")){
                            data[name].push($(el).val());
                        }
                    }else{
                        data[name].push($(el).val());
                    }
                } else { // no array input
                    if(el.type === "checkbox"){ // single checkbox
                        if($(el).is(":checked")){
                            data[el.name] = $(el).val();
                        }
                    }else if(el.type === "radio"){ // radio buttons
                        if($(el).is(":checked")){
                            data[el.name] = $(el).val();
                        }
                    }else {
                        data[el.name] = $(el).val();
                    }
                }
            });

            let action;
            action = frm.attr('action');

            $.ajax({
                type: $(this).attr('method'),
                url: action,
                data: data,
                success: defaultPostModalHandler,
                error: xpAjaxErrorHandler
            });
            e.preventDefault();
        });

        frm.find('button[formaction]').each(function (){
            const btn = $(this);
            btn.on('click', function (){
                frm.attr('action', btn.attr('formaction'))
                frm.submit();
            })
        });
    });
});