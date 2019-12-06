$(document).ready(function () {

    const frm = $("form.ajax-form");

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
                    if(el.type === "checkbox"){
                        if($(el).is(":checked")){
                            data[name].push($(el).val());
                        }
                    }else{
                        data[name].push($(el).val());
                    }
                } else {
                    if(el.type === "checkbox"){
                        if($(el).is(":checked")){
                            data[el.name] = $(el).val();
                        }
                    }else{
                        data[el.name] = $(el).val();
                    }
                }
            });
            const submittingButton = $(e.originalEvent.explicitOriginalTarget).closest("input[type=button],button");
            let action;
            if(submittingButton.length > 0 && submittingButton.attr("formaction") !== undefined){
                console.log(submittingButton.attr("formaction"));
                action = submittingButton.attr("formaction");
                const buttonData = submittingButton.data();
                for(const key in buttonData){
                    // skip loop if the property is from prototype
                    if (!buttonData.hasOwnProperty(key)) continue;
                    // add
                    data["data-" + key] = buttonData[key];
                }
            }else{
                action = $(this).attr('action');
            }
            $.ajax({
                type: $(this).attr('method'),
                url: action,
                data: data,
                success: defaultPostModalHandler,
                error: xpAjaxErrorHandler
            });
            e.preventDefault();
        });
    });
});