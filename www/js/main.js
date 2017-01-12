
$(document).ready(function() {
    $('.selectpicker').selectpicker({
        style: 'btn-default',
        size: false
    });

    $('.dynamic-table').each(function (i, table) {
      var $table = $(table);
      var $tbody = $table.children("tbody");
      var $tfoot = $table.children("tfoot");
      var tableId = $table.attr('id');

      $tfoot.find('.column-sum').each(function (i, e) {
        var $e = $(e);
        var colId = $e.data('col-id');
        $e.addClass(colId);
        $tbody.children('tr').children('.'+colId).find('input').each(function() {
          $(this).on('change.column-sum', null, colId, function (evt) {
            var val = $(this).val();
            val = parseFloat(val);
            if (isNaN(val)) {
              val = 0;
            }
            $(this).val(val.toFixed(2));
            updateColumnSum(evt.data, $(this).parents(".dynamic-table").first());
          });
          $(this).trigger('change');
        });
        updateColumnSum(colId, $table);
      });

     var $tr = $tbody.children('tr.new-table-row').last();
     $tr.find("*[name]").each(function(i,e) {
       var rowCount = 0;
       var $e = $(e);
       var name = $e.attr('name');
       if (name.substr(-2) == '[]') {
         name = name.substr(0, name.length - 2);
       }
       $e.attr('orig-name-'+tableId, name);
       var newName = name+'['+rowCount+']';
       $e.attr('name',newName);
     });
     $tr.find("*[id]").each(function(i,e) {
       var rowCount = 0;
       var $e = $(e);
       var id = $e.attr('id');
       $e.attr('orig-id-'+tableId, id);
       var newId = id+'-'+rowCount;
       $e.attr('id',newId);
       if ("defaultValue" in document.getElementById(newId)) {
         $e.val(document.getElementById(newId).defaultValue);
         $e.trigger("change");
       }
     });
     enableNewRowClick($tr, $tbody, $tfoot, tableId);
     $tr.children("td.delete-row").find('a.delete-row').hide();
   }); /* each table */

  $( "form.ajax" ).submit(function (ev) {
    return handleSubmitForm($(this));
  });
});

function enableNewRowClick($tr, $tbody, $tfoot, tableId) {
  $tr.find("*").off('focus.dynamic-table'+tableId);
  $tr.find("*").on('focus.dynamic-table'+tableId, function (evt) {
    onClickNewRow($tr, $tbody, $tfoot, tableId);
  });
  $tfoot.parent().off('cloned.dynamic-table'+tableId);
  $tfoot.parent().on('cloned.dynamic-table'+tableId, function (evt) {
    var $table = $(this);
    var $tbody = $table.children("tbody");
    var $tfoot = $table.children("tfoot");

    var oldTableId = tableId;
    var newTableId = $table.attr('id');
    $table.off('cloned.dynamic-table'+oldTableId);

    $table.children("tbody").children("tr").find("*[id]").attr('orig-id-'+oldTableId, null);
    $table.children("tbody").children("tr").find("*[name]").attr('orig-name-'+oldTableId, null);
    var rowCount = 0;
    $tbody.children("tr").each(function(i,tr) {
      $(tr).find("*[id]").each(function(i, e) {
        var $e = $(e);
        var id = $e.attr('id');
        $e.attr('orig-id-' + newTableId, id);
        $e.attr('id',id+'-'+rowCount);
      });
      $(tr).find("*[name]").each(function(i,e) {
        var $e = $(e);
        var name = $e.attr('name');
        if (name.substr(-2) == '[]') {
          name = name.substr(0, name.length - 2);
        }
        $e.attr('orig-name-'+newTableId, name);
        var newName = name+'['+rowCount+']';
        $e.attr('name',newName);
      });
      rowCount++;
    });

    $table.children("tbody").children("tr.new-table-row").each(function (i,e) {
      var $ntr = $(e);
      $ntr.find("*").off('focus.dynamic-table'+oldTableId);
      enableNewRowClick($ntr, $tbody, $tfoot, newTableId);
    });
  });
}

function onClickNewRow($tr, $tbody, $tfoot, tableId) {
  $tr.find("*").off('focus.dynamic-table'+tableId);
  var $ntr = $tr.clone(true);
  var rowCount = $tbody.children("tr").length;
  var $table = $tr.parent();

  $tr.removeClass("new-table-row");
  var $adr = $tr.children("td.delete-row").find('a.delete-row');
  $adr.show();
  $adr.on('click', function(evt) {
    evt.stopPropagation();
    $tr.remove();
    var rowCount = 0;
    $tbody.children("tr").each(function(i,tr) {
      var $tr = $(tr);
      $tr.children("td.row-number").text(rowCount+".");
      $tr.find("*[id]").each(function(i, e) {
        var $e = $(e);
        var id = $e.attr('orig-id-' + tableId);
        $e.attr('id',id+'-'+rowCount);
      });
      $tr.find("*[name]").each(function(i, e) {
        var $e = $(e);
        var name = $e.attr('orig-name-' + tableId);
        $e.attr('name',name+'['+rowCount+']');
      });
      rowCount++;
    });
    $tfoot.find('.column-sum').each(function (i, e) {
       var $e = $(e);
       var colId = $e.data('col-id');
       updateColumnSum(colId, $table);
    });
    $tbody.find("*[id]").each(function (i, e) {
      $(e).triggerHandler("cloned");
    });
    return false;
  });

  $ntr.children("td.row-number").text((rowCount+1)+".");
  $ntr.find("*[id]").each(function(i,e) {
    var $e = $(e);
    var id = $e.attr('orig-id-' + tableId);
    $e.attr('id',id+'-'+rowCount);
  }); /* update id attribute of new row */
  $ntr.find("*[name]").each(function(i,e) {
    var $e = $(e);
    var name = $e.attr('orig-name-' + tableId);
    $e.attr('name',name+'['+rowCount+']');
  }); /* update name attribute of new row */
  $ntr.appendTo($tbody);
  enableNewRowClick($ntr, $tbody, $tfoot, tableId);
  $ntr.find("*").each(function (i, e) { $(e).triggerHandler("cloned"); });
}

function updateColumnSum(colId, $table) {
  var $e = $table.children("tfoot").find('.column-sum.'+colId);
  var sum = 0;
  $table.find('.'+colId+' input').each(function() {
    sum += parseFloat($(this).val());
  });
  $e.text(sum.toFixed(2));
}

//moment.locale('de');

function xpAjaxErrorHandler (jqXHR, textStatus, errorThrown) {
      $("#please-wait-dlg").modal("hide");

      $("#server-message-label").text("Es ist ein Server-Fehler aufgetreten");
      var $smc = $("#server-message-content");
      $smc.empty();
      $("#server-message-content").empty();
      var $smcp = $('<pre>').appendTo( $smc ).text(textStatus + "\n" + errorThrown + "\n" + jqXHR.responseText);
      $("#server-message-dlg").modal("show");
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
  $("#please-wait-dlg").modal("show");
  jQuery.ajax({
    url: action,
    data: data,
    cache: false,
    contentType: false,
    processData: false,
    type: "POST"
  })
  .done(function (values, status, req) {
     $("#please-wait-dlg").modal("hide");
     if (typeof(values) == "string") {
       $("#server-message-label").text("Es ist ein Server-Fehler aufgetreten");
       var $smc = $("#server-message-content");
       $smc.empty();
       $("#server-message-content").empty();
       var $smcp = $('<pre>').appendTo( $smc ).text(values);
       $("#server-message-dlg").modal("show");
       return;
     }
     var txt;
     var txtHeadline;
     if (values.ret) {
       txt = "";
       txtHeadline = "Die Daten wurden erfolgreich gespeichert.";
     } else {
       txt = "Die Daten konnten nicht gespeichert werden.";
       txtHeadline = "Die Daten konnten nicht gespeichert werden.";
     }
     if (values.msgs && values.msgs.length > 0) {
         txt = values.msgs.join("\n")+"\n"+txt;
     }
     if (values.ret && txt != "") {
       if (self.opener) {
         self.opener.location.reload();
       }
       $("#server-question-label").text(txtHeadline);
       var $smc = $("#server-question-content");
       $smc.empty();
       $("#server-question-content").empty();
       var $smcu = $('<ul/>').appendTo( $smc );
       for (i = 0; i < values.msgs.length; i++) {
         var msg = (values.msgs[i]);
         $('<li/>').text(msg).appendTo( $smcu );
       }
       $("#server-question-close-window").on("click", function(evt) {
         if (!values.target) {
           if (self.opener) {
             self.opener.focus();
           }
           self.close();
         } else {
           self.location.href = values.target;
         }
       });
       $("#server-question-dlg").on('hidden.bs.modal', function (e) {
         if (values.target) {
           window.open(values.target);
         }
       });
       $("#server-question-dlg").modal("show");

     } else if (values.ret) { // txt is empty
       if (!values.target) {
         if (self.opener) {
           self.opener.focus();
         }
         self.close();
       } else { // values.target
         self.location.href = values.target;
       }
     } else { // !values.ret
      $("#server-message-label").text(txtHeadline);
      var $smc = $("#server-message-content");
      $smc.empty();
      $("#server-message-content").empty();
      var $smcu = $('<ul/>').appendTo( $smc );
      for (i = 0; i < values.msgs.length; i++) {
          var msg = (values.msgs[i]);
          $('<li/>').text(msg).appendTo( $smcu );
      }
      $("#server-message-dlg").modal("show");
     }
   })
  .fail(xpAjaxErrorHandler);
  return false;
}

