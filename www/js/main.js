
$(document).ready(function() {
    $('.selectpicker').selectpicker({
        style: 'btn-default',
        size: false
    });

    $('.dynamic-table').each(function (i, table) {
      var $table = $(table);
      var $tbody = $table.children("tbody");
      var $tfoot = $table.children("tfoot");

      $tfoot.find('.column-sum').each(function (i, e) {
        var $e = $(e);
        var colId = $e.data('col-id');
        $e.addClass(colId);
        $tbody.children('tr').children('.'+colId).find('input').each(function() {
          $(this).on('change.column-sum', null, colId, function (evt) {
            var val= parseFloat($(this).val());
            $(this).val(val.toFixed(2));
            updateColumnSum(evt.data);
          });
        });
        updateColumnSum(colId);
      });

     var $tr = $tbody.find('tr.new-table-row').last();
     $tr.find("*").each(function(i,e) {
       var rowCount = 0;
       var $e = $(e);
       var id = $e.attr('id');
       if (!id) { return; }
       $e.data('orig-id', id);
       $e.attr('id',id+'-'+rowCount);
     });
     enableNewRowClock($tr, $tbody, $tfoot);
     $tr.find('a.delete-row').hide();
   }); /* each table */

  $( "form.ajax" ).submit(function (ev) {
    handleSubmitForm($(this));
    return false;
  });
});

function enableNewRowClock($tr, $tbody, $tfoot) {
  $tr.find("*").on('focus.dynamic-table', function (evt) {
    onClickNewRow($tr, $tbody, $tfoot);
  });
}

function onClickNewRow($tr, $tbody, $tfoot) {
  $tr.find("*").off('focus.dynamic-table');
  var $ntr = $tr.clone(true);
  var rowCount = $tbody.children("tr").length;

  var $adr = $tr.find('a.delete-row');
  $adr.show();
  $adr.on('click', function(evt) {
    evt.stopPropagation();
    $tr.remove();
    var rowCount = 0;
    $tbody.find("tr").each(function(i,e) {
      rowCount++;
      $(e).find(".row-number").text(rowCount+".");
    });
    $tfoot.find('.column-sum').each(function (i, e) {
       var $e = $(e);
       var colId = $e.data('col-id');
       updateColumnSum(colId);
    });
    return false;
  });

  $ntr.find(".row-number").text((rowCount+1)+".");
  $ntr.find("*").each(function(i,e) {
    var $e = $(e);
    var id = $e.data('orig-id');
    if (!id) { return; }
    $e.attr('id',id+'-'+rowCount);
  }); /* update id attribute of new row */
  $ntr.appendTo($tbody);
  enableNewRowClock($ntr, $tbody, $tfoot);
  $ntr.trigger("cloned");
}

function updateColumnSum(colId) {
  var $e = $('.column-sum.'+colId);
  var sum = 0;
  $('.'+colId+' input').each(function() {
    sum += parseFloat($(this).val());
  });
  $e.text("Σ " + sum.toFixed(2) + " €");
}

//moment.locale('de');

function xpAjaxErrorHandler (jqXHR, textStatus, errorThrown) {
      $("#waitDialog").modal("hide");
      alert(textStatus + "\n" + errorThrown + "\n" + jqXHR.responseText);
};

function doSubmitForm(formid) {
  handleSubmitForm($("#"+formid));
  return false;
}

function handleSubmitForm($form) {
  var action = $form.attr("action");
  if ($form.find("input[name=action]").length + $form.find("select[name=action]").length == 0) { return true; }
  var data = new FormData($form[0]);
  data.append("ajax", 1);
  $("#waitDialog").modal("show");
  $.ajax({
    url: action,
    data: data,
    cache: false,
    contentType: false,
    processData: false,
    type: "POST"
  })
  .success(function (values, status, req) {
     $("#waitDialog").modal("hide");
     if (typeof(values) == "string") {
       alert(values);
       return;
     }
     var txt;
     if (values.ret) {
       txt = "";
     } else {
       txt = "Die Daten konnten nicht gespeichert werden.";
     }
     if (values.msgs && values.msgs.length > 0) {
         txt = values.msgs.join("\n")+"\n"+txt;
     }
     if (values.ret) {
      if (txt != "") {
        txt = txt + "\n\nFenster schließen?";
        cls = confirm(txt);
        if (self.opener) {
         self.opener.location.reload();
        }
       } else {
         cls = true;
       }
     } else {
      alert(txt);
     }
     if (values.ret && !values.target && cls) {
      if (self.opener) {
       self.opener.focus();
      }
      self.close();
     }
     if (values.ret && values.target) {
      if (cls) {
       self.location.href = values.target;
      } else {
       window.open(values.target);
      }
     }
   })
  .error(xpAjaxErrorHandler);
}

