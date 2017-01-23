String.prototype.replaceAll = function(target, replacement) {
  return this.split(target).join(replacement);
};

$(document).ready(function() {
  $('.selectpicker').each(function (i,e) {
    var $e = $(e);
    if ($e.closest(".select-picker-container").length > 0) { return; }
    $e.selectpicker({
      style: 'btn-default',
      size: false
    });
  });

  $(".select-picker-container").on("clone-post.selectpicker cloned.selectpicker", function(evt) {
    var cfg = {
      style: 'btn-default',
      size: false
    };
    var $fselect = $(this).find(".selectpicker");
    $fselect.selectpicker(cfg);
    $fselect.on("show.bs.select",function (e) {
      $fselect.addClass("select-picker-open");
      setTimeout(function() { $fselect.triggerHandler("focus"); }, 1);
    });
    $fselect.on("hidden.bs.select",function (e) {
      $fselect.removeClass("select-picker-open");
    });
    if ($fselect.is("[data-references]")) {

      $fselect.parents("tr").off("pre-row-delete.ref-field-inv");
      $fselect.parents("tr").on("pre-row-delete.ref-field-inv", function(evt) {
        $(this).find("select.selectpicker[data-references]").each(function (i, sel) {
          var $sel = $(sel);
          var targetTableId = $sel.attr("data-references");
          updateInvRef(targetTableId, $sel, false);
        });
      });

      $fselect.off("change.ref-field-inv");
      $fselect.on("change.ref-field-inv",function (e) {
        var targetTableId = $fselect.attr("data-references");
        updateInvRef(targetTableId, $fselect, $(this).selectpicker('val'));
      });

      $fselect.parents("tr").off("row-changed.ref-field-cascade row-number-changed.ref-field-cascade");
      $fselect.parents("tr").on("row-changed.ref-field-cascade row-number-changed.ref-field-cascade", function(evt) {
        var $tr = $(this);
        $tr.find("tr").each(function (i, tr) {
          $(tr).triggerHandler("row-changed");
        });
       });

      $fselect.parents("tr").first().off("row-changed.ref-field-inv");
      $fselect.parents("tr").first().on("row-changed.ref-field-inv", function(evt) {
        var $tr = $(this);
        $tr.find("select.selectpicker[data-references]").each(function (i, sel) {
          var $sel = $(sel);
          var targetTableId = $sel.attr("data-references");
          updateInvRef(targetTableId, $sel, $(this).selectpicker('val'));
        });
      });
    }
    var isOpen = $fselect.is(".select-picker-open");
    if (isOpen) {
      $fselect.selectpicker('toggle');
    }
  });
  $(".select-picker-container").on("clone-pre.selectpicker", function(evt) {
    var $fselect = $(this).find(".selectpicker");
    $fselect.selectpicker('destroy');
    $fselect.addClass("selectpicker"); // plugin removes this on destroy, though makes no sense at all
  });
  $(".select-picker-container").each(function(i,e) { $(e).triggerHandler("clone-post"); });

  $(".single-file-container").on("clone-post.single-file cloned.file", function(evt) {
    var cfg = {
      'fileActionSettings': {
        'showUpload':false,
        'showZoom': false
      },
     'showPreview':false,
     'language': 'de',
     'theme': 'gly',
    };
    var $finput = $(this).find(".single-file");
    $finput.fileinput(cfg);
    $(this).attr("name", $finput.attr("name"));
    $finput.on("fileloaded.multi-file fileremoved.multi-file filecleared.multi-file init-display-text.multi-file", function() {
      var files = $(this).fileinput('getFileStack');
      var txt = [];
      for(i = 0; i < files.length; i++) {
        txt.push(files[i].name);
      }
      var $td = $(this).closest("td");
      $td.data("display-text", txt.join(", "));
      $td.closest("tr").triggerHandler("row-changed");
    }).triggerHandler("init-display-text");
  });
  $(".multi-file-container-with-destination").on("clone-post.multi-file cloned.file", function(evt) {
    var cfg = {
     'showPreview':false,
     'allowedPreviewTypes': false,
     'language': 'de',
     'theme': 'gly',
     'uploadUrl' : 'stuffme',
     'uploadExtraData': {
     },
    };
    var $finput = $(this).find(".multi-file");
    $finput.fileinput(cfg);
    $(this).closest("td").data("display-text", "[file-selector]");

    $finput.on("fileloaded.multi-file", function(evt, file, previewId, index, reader) {
      console.log("fileloaded");

      //setTimeout(function() { $finput.fileinput("clear"); }, 1000);
      var $mfinput = $(this);
      var $container = $mfinput.closest(".multi-file-container");
      var isUpdateRef = $container.is(".multi-file-container-update-ref");
      var destination = $container.data("destination");
      // check for dynamic row
      var $destination = null;
      $container.parents().each(function(i,tr) {
        tables = [];
        $(tr).find("[orig-id="+destination+"]").each(function (i, e) {
          var $table = $(e).closest(".dynamic-table");
          tables.push($table[0]);
        });
        if (tables.length == 0) {
          return;
        }
        tables = $.uniqueSort(tables);
        if (tables.length != 1) {
          return;
        }
        $destination = $(tables[0]);
        return false; // break loop
      });
      if ($destination == null) {
        console.log("fileloaded - destination not found");
        console.log(destination);
        return;
      }
      // create new table row and replace file element
      var $table = $destination;
      var tableId = $table.attr('orig-id');
      var $tbody = $table.children("tbody");
      var $tr = $tbody.children('tr.new-table-row').last();
      if ($tr.length != 1 || $table.length != 1) {
        console.log("dynamic table has no new-table-row");
        console.log(tableId);
        console.log($tr);
        console.log($table);
        alert('error dynamic row handling');
      }
      onClickNewRow($tr, $table, tableId);

      var $sfc = $tr.find("[orig-id="+destination+"]").closest(".single-file-container");
      var $sfcinput = $sfc.find(".multi-file");
      $sfc.empty();
      $sfc.addClass("form-files");
      $("<span/>").text(file.name).appendTo($sfc);
      $("<span>&nbsp;</span>").appendTo($sfc);
      $("<small/>").append($("<nobr/>").text(getSizeText(file.size))).appendTo($sfc);
      $sfc.data("file", file);
      $sfc.closest("td").data("display-text", file.name);
      $tr.on("pre-row-delete.multi-file-with-destination", function (evt) {
        $mfinput.fileinput('clear');
      });
      $tr.triggerHandler("row-changed");

      $mfinput.parents().each(function (i, p) {
        var $ref = $(p).find('select[data-references='+tableId+']');
        if ($ref.length == 0) {
          return;
        }

        refTables = []
        $ref.each(function(i, sel) {
          var $sel = $(sel);
          var $refTable = $sel.closest(".dynamic-table");
          refTables.push($refTable[0]);
        });
        refTables = $.uniqueSort(refTables);

        for(i=0; i < refTables.length && isUpdateRef; i++) {
          var $refTable = $(refTables[i]);
          var refTableId = $refTable.attr('orig-id');
          var $refTBody = $refTable.children("tbody");
          var $refTr = $refTBody.children('tr.new-table-row').last();
          var selValue = extractFieldName($table.attr("name") + "["+$tr.attr('dynamic-table-row-number')+"]");
          onClickNewRow($refTr, $refTable, refTableId);
          $refTr.find('select[data-references='+tableId+']').each(function (i, sel) {
            var $sel = $(sel);
            if ($sel.is(".selectpicker")) {
              $sel.selectpicker("val", selValue);
              $sel.triggerHandler("change");
            } else { /* not selectpicker */
              $sel.val(selValue);
            }
          });
        }

        return false; // completed, no not traverse parents() further
      });

    });
  });
  $(".multi-file-container-without-destination").on("clone-post.multi-file cloned.file", function(evt) {
    var cfg = {
      'fileActionSettings': {
        'showUpload':false,
        'showZoom': false
      },
     'showPreview':true,
     'allowedPreviewTypes': false,
     'language': 'de',
     'theme': 'gly',
     'uploadUrl' : 'stuffme',
     'uploadExtraData': {
     },
    };

    var $finput = $(this).find(".multi-file");
    $finput.fileinput(cfg);
    $finput.on("fileloaded.multi-file fileremoved.multi-file filecleared.multi-file init-display-text.multi-file", function() {
      var files = $(this).fileinput('getFileStack');
      var txt = [];
      for(i = 0; files.length; i++) {
        txt.push(files[i].name);
      }
      var $td = $(this).closest("td");
      $td.data("display-text", txt.join(", "));
      $td.closest("tr").triggerHandler("row-changed");
    }).triggerHandler("init-display-text");
  });
  $(".single-file-container,.multi-file-container").on("clone-pre.file", function(evt) {
    var $finputs = $(this).find(".single-file,.multi-file");
    $finputs.fileinput('destroy');
    $finputs.off("fileloaded.multi-file fileremoved.multi-file filecleared.multi-file init-display-text.multi-file");
  });
  $(".single-file-container,.multi-file-container").each(function(i,e) { $(e).triggerHandler("clone-post"); });
  $(".dynamic-table .single-file,.dynamic-table .multi-file").on("name-changed.file", function(evt) {
    var d = $(this).data('fileinput');
    if (!d) return;
    d.uploadFileAttr = $(this).attr("name");
  });
  $("*[data-addToSum]").on("change.compute", function(evt) {
    var $i = $(this);
    if ($i.is(":input")) {
      var val = $i.val();
      val = parseFloat(val);
      $i.val(val.toFixed(2));
    }
		var ids = $(this).attr("data-addToSum").split(" ");
    for (j = 0; j < ids.length; j++) {
      $("*[data-printSum~=\""+ ids[j] + "\"]").each(function (i, e) {
        $(e).triggerHandler("update-print-sum");
      });
    }
  });
  $("*[data-printSum]").on("update-print-sum.compute", function(evt) {
    var $out = $(this);

    $region = $out.data("print-sum-region");
    if (!$region)
      return;

    var sum = 0.00;
		var printId = $out.attr("data-printSum");
    $region.find("*[data-addToSum~=\""+printId+"\"]").each(function (k, e) {
      var val;
      var $e = $(this);
      if ($e.is(":input")) {
        val = $e.val();
      } else {
        val = $e.text();
      }
      val = parseFloat(val);
      if (isNaN(val)) {
        val = 0;
      }
      sum += val;
    });
    $out.text(sum.toFixed(2));
    $out.triggerHandler("change");
  });
  $("table.summing-table").off("update-summing-table.sum");
  $("table.summing-table").on("update-summing-table.sum", function(evt) {
    var $table = $(this);
    var $tbody = $table.children("tbody");
    var $tfoot = $table.children("tfoot");
    if ($table.children("tbody").children("tr:not(.summing-skip)").length == 0) {
      $tfoot.hide();
      return;
    } else {
      $tfoot.show();
    }
    $tfoot.children("tr").children(".cell-has-printSum").find("*[data-printSum]").each(function (i, e) {
      var $e = $(e);
      $e.data("print-sum-region", $table);
      $e.triggerHandler("update-print-sum");
    });
  });
  $("table.summing-table").trigger("update-summing-table");

  $(".dynamic-table *[name],.dynamic-table").each(function(i,e) {
    var $e = $(e);
    var name = $e.attr('name');
    $e.attr('orig-name', name);
  });
  $(".dynamic-table").on("name-changed.ref-field", function(evt) {
    var $tbody = $(this).children("tbody");
    $tbody.children("td").each(function(i, td) {
      var $td = $(td);
      $td.triggerHandler("name-changed");
    });
  });
  $(".dynamic-table *[name][name^=formdata],.dynamic-table").on("name-suffix-changed.dynamic-table", function(evt) {
    var $e = $(this);
    var name = $e.attr('orig-name').split("[]");
    var suffix = "";
    var $ns = $e.parents("*[name-suffix]");
    $ns.each(function (i,p) {
      name[$ns.length - 1 - i] += $(p).attr('name-suffix');
    });
    for (i = $ns.length; i < name.length - 1; i++)
      name[i] += "[]";

    $e.attr('name',name.join(""));
    $e.triggerHandler("name-changed");
  });
  $(".dynamic-table > tbody > tr").on("row-number-changed.dynamic-table", function (evt) {
    var $tr = $(this);
    var rowNumber = $tr.attr('dynamic-table-row-number');
    $tr.attr('name-suffix','['+rowNumber+']');

    $tr.find("*[name]").each(function(i, e) {
       $(e).triggerHandler("name-suffix-changed");
    });

    rowNumber++;
    $tr.children("td.row-number").text(rowNumber+".");
  });

  $("*[id]").each(function(i,e) {
    var $e = $(e);
    var id = $e.attr('id');
    $e.attr('orig-id', id);
  });
  $(".dynamic-table *[id]").on("id-suffix-changed.dynamic-table", function(evt) {
    var $e = $(this);
    var id = $e.attr('orig-id');
    var suffix = "";
    $e.parents("*[id-suffix]").each(function (i,p) {
      suffix = $(p).attr('id-suffix') + suffix;
    });

    $e.attr('id',id + suffix);
  });
  $(".dynamic-table tr.new-table-row *").off('focus.dynamic-table mousedown.dynamic-table');
  $(".dynamic-table tr.new-table-row *").on('focus.dynamic-table mousedown.dynamic-table', function (evt) {
    $($(this).parents("tr.new-table-row").get().reverse()).each(function (i, tr) {
      var $tr = $(tr);
      var tableId = $tr.attr('dynamic-table-id');
      var $table = $tr.closest("table");
      if ($tr.length != 1 || $table.length != 1) {
        console.log("parent not unique");
        console.log(tableId);
        console.log(this);
        console.log($tr);
        console.log($table);
        alert('error dynamic row handling');
      }
      if ($table.attr("orig-id") != tableId) {
        console.log("bad parent table");
        console.log("tableId="+tableId);
        console.log("actual orig-id="+$table.attr("orig-id"));
        alert('error dynamic row handling');
      }
      onClickNewRow($tr, $table, tableId);
    });
  });
  $('.dynamic-table').each(function (i, table) {
    var $table = $(table);
    var $tbody = $table.children("tbody");
    var $tfoot = $table.children("tfoot");
    var tableId = $table.attr('orig-id');
    $table.attr('dynamic-table-id-ctr', 0);

    var $tr = $tbody.children('tr.new-table-row').last();

    $tr.attr('dynamic-table-id', tableId);

    $tr.attr('dynamic-table-row-number', 0);
    $tr.triggerHandler("row-number-changed");
    $table.children(".store-row-count").val($tbody.children("tr:not(.new-table-row)").length);

    $tr.attr('id-suffix', '-0');
    $tr.find("*[id]").each(function(i,e) {
      $(this).triggerHandler("id-suffix-changed");
    });
    $tr.find("*[id]").each(function(i,e) {
      if ("defaultValue" in e) {
        var $e = $(e);
        var type = $(e).attr("type");
        if (type == "file") { return; }
        $e.val(e.defaultValue);
        $e.trigger("change");
      }
    });
    $tr.children("td.delete-row").find('a.delete-row')
      .on('click', function(evt) {
        evt.stopPropagation();
        var $tr = $(this).closest("tr");
        var $tbody = $tr.closest("tbody");
        $tr.triggerHandler("pre-row-delete");
        $tr.remove();
        $tbody.children("tr").each(function(rowNumber,tr) {
          var $tr = $(tr);
          $tr.attr('dynamic-table-row-number', rowNumber);
          $tr.triggerHandler("row-number-changed");
        });
        $table.children(".store-row-count").val($tbody.children("tr:not(.new-table-row)").length);
        $table.triggerHandler("update-summing-table.sum");
        return false;
      });
    /*  */
  }); /* each table */

  $( "form.ajax" ).validator().on("submit", function(e) {
    if (e.isDefaultPrevented()) return; // validator said no
    return handleSubmitForm($(this), e);
  });

});

function onClickNewRow($tr, $table, tableId) {

  if (!$tr.is(".new-table-row")) return;
  $tr.find("*").each(function (i, e) { $(e).triggerHandler("clone-pre"); });

  var $ntr = $tr.clone(true);
  var $tbody = $table.children("tbody");
  var rowNumber = $tbody.children("tr").length;

  $tr.removeClass("new-table-row");

  var ctr = $table.attr('dynamic-table-id-ctr');
  var trId = 'dynamic-table-row-'+$table.attr("id")+'-'+ctr;
  ctr++;
  $table.attr('dynamic-table-id-ctr', ctr);
  $tr.attr('id', trId);

  $ntr.appendTo($tbody); /* insert first so suffix can be found */
  $ntr.attr('id-suffix', '-' + ctr);
  $ntr.find("*[id]").each(function(i,e) {
    $(this).triggerHandler("id-suffix-changed");
  });

  $ntr.attr('dynamic-table-row-number', rowNumber);
  $ntr.triggerHandler("row-number-changed");
  $table.children(".store-row-count").val($table.children("tbody").children("tr:not(.new-table-row)").length);

  $ntr.find("*").each(function (i, e) { $(e).triggerHandler("cloned"); });
  $tr.find("*").each(function (i, e) { $(e).triggerHandler("clone-post"); });

  $tr.find("td.dynamic-table-column-title input").off("change.row-title");
  $tr.find("td.dynamic-table-column-title input").each(function (i, e) {
    if ($(e).attr("type") == "file") { return; }
    $(e).on("change.row-title init-display-text", function(evt) {
      var $td = $(this).closest("td");
      var $tr = $td.closest("tr");
      $td.data("display-text", $(this).val());
      $tr.triggerHandler("row-changed");
    }).triggerHandler("init-display-text");
  });

  /* update references select */
  $table.parents().each(function (i, p) {
    var $ref = $(p).find('select[data-references='+tableId+']');
    if ($ref.length == 0) {
      return;
    }

    $ref.each(function(i, sel) {
      var $sel = $(sel);
      var $opt = $("<option/>");
      $opt.text("some new row");
      $opt.attr("value", "*new*");
      $opt.attr("data-references", trId);
      $opt.appendTo($sel);
      if ($sel.is(".selectpicker")) {
        $sel.selectpicker("refresh");
      }
    });

    $tr.parents("tr").on("pre-row-delete.ref-field-extra", function(evt) {
      var $tr = $(this);
      $tr.find("tr").each(function (i, tr) {
        $(tr).triggerHandler("pre-row-delete");
      });
    });

    $tr.on("pre-row-delete.ref-field", function (evt) {
      var $tr = $(this);
      var trId = $tr.attr('id');
      var $opts = $("option[data-references="+trId+"]");
      $opts.each(function(i, opt) {
        var $opt = $(opt);
        var $sel = $opt.closest("select");
        $opt.remove();
        if ($sel.is(".selectpicker")) {
          $sel.selectpicker("refresh");
        }
      });
    });

    $tr.parents("tr").off("row-changed.ref-field-cascade row-number-changed.ref-field-cascade");
    $tr.parents("tr").on("row-changed.ref-field-cascade row-number-changed.ref-field-cascade", function(evt) {
      var $tr = $(this);
      $tr.find("tr").each(function (i, tr) {
        $(tr).triggerHandler("row-changed");
      });
    });

    $tr.on("row-changed.ref-field row-number-changed.ref-field name-changed.ref-field", function(evt) {
      var $tr = $(this);
      var trId = $tr.attr('id');
      var $opts = $("option[data-references="+trId+"]");
      var $table = $tr.closest("table");
      var rowIdx = $tr.attr('dynamic-table-row-number');
      var newValue = extractFieldName( $table.attr("name") + "["+rowIdx+"]" );
      var trText = getTrText($tr);

      $opts.each(function(i, opt) {
        var $opt = $(opt);
        var $sel = $opt.closest("select");
        var selValue;
        if ($sel.is(".selectpicker")) {
          selValue = $sel.selectpicker("val");
        } else {
          selValue = $sel.val();
        }
        if ($opt.attr("value") == selValue) {
          selValue = newValue;
        }
        $opt.text(trText);
        $opt.attr("value", newValue);
        if ($sel.is(".selectpicker")) {
          $sel.selectpicker("refresh");
          $sel.selectpicker("val", selValue);
          $sel.triggerHandler("change");
        } else { /* not selectpicker */
          $sel.val(selValue);
        }
      });
    });
    $tr.triggerHandler("row-changed");

    return false;
  });

}

function getTrText($tr) {
  var $table = $tr.closest("table.dynamic-table");

  var trList = [ $tr ];
  $table.parents("tr[dynamic-table-row-number]").each(function (i, tr) {
    trList.unshift($(tr));
  });

  var trText = "";
  $.each( trList, function (i, $ptr) {
    if (trText.length > 0)
      trText = trText + " ";

    var rowNumber = $ptr.attr('dynamic-table-row-number');
    rowNumber++;
    trText = trText + "["+rowNumber+"]";

    $ptr.children("td.dynamic-table-column-title").each(function (j, td) {
      var $td = $(td);
      if (j > 0) {
        trText = trText + ",";
      }
      trText = trText + " ";
      var txt = $td.data("display-text");
      if (txt == null) {
        txt = $td.text();
      }
      trText = trText + txt;
    });
  });

  return trText;

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

function handleSubmitForm($form, evt) {
  var action = $form.attr("action");
  if ($form.find("input[name=action]").length + $form.find("select[name=action]").length == 0) { return true; }
  var data = new FormData($form[0]);
  data.append("ajax", 1);
  $('.new-table-row *[name]').each(function (i,e) {
    var $e = $(e);
    var name = $e.attr("name");
    if (name.substr(0, 8) == "formdata" && name.indexOf("[]") == -1) {
      data.delete(name);
    }
  });
  $form.find(".multi-file-container-with-destination .multi-file").each(function (i, mf) {
    var $mf = $(mf);
    if (!$mf.data("fileinput")) return;
    var name = $mf.attr("name");
    if (data.has(name)) {
      data.delete(name);
    }
  });
  $form.find(".multi-file-container-without-destination .multi-file").each(function (i, mf) {
    var $mf = $(mf);
    if (!$mf.data("fileinput")) return;
    var fileList = $mf.fileinput("getFileStack");
    var name = $mf.attr("name");
    if (data.has(name)) {
      data.delete(name);
    }
    for (i = 0; i < fileList.length; i++) {
      var newName = name;
      newName = newName.replaceAll("[]", "");
      newName = newName + "["+i+"]";
      data.append(newName, fileList[i]);
    }
  });
  $form.find(".form-files").each(function (i, sf) {
    var $sf = $(sf);
    var name = $sf.attr("name");
    var file = $sf.data("file");
    if (!file) { return; }
    data.append(name, file);
  });
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
       $("#server-question-close-window").off("click");
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
       $("#server-question-dlg").off('hidden.bs.modal');
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
  evt.preventDefault();
  return false;
}

function getSizeText(size) {
  i = Math.floor(Math.log(size) / Math.log(1024));
  sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  out = (size / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + sizes[i];
  return out;
}

function extractFieldName(name) {
  var re = /^formdata\[([^\]]*)\](.*)/;
  var m = name.match(re);
  if (!m)
    return false;
  return m[1]+m[2];
}

function getFormdataName(fieldname) {
  var re = /^([^\[\]]*)(\[.*)?$/;
  var m = fieldname.match(re);
  if (!m)
    return false;
  var name = "formdata["+m[1]+"]";
  if (m[2]) {
    name += m[2];
  }
  return name;
}

function updateInvRef(targetTableId, $sel, newRef) {
  $("tr[invref-sel-id="+ $sel.attr("id")).each(function (i,tr) {
    var $tr = $(tr);
    var $table = $tr.closest("table");
    $tr.remove();
    $table.triggerHandler("update-invref-table");
  });


  if (newRef === false || newRef === "")
    return;

//  var selValue = extractFieldName($table.attr("name") + "["+$tr.attr('dynamic-table-row-number')+"]");
  var re = /^(.*)\[([0-9]*)\]$/;
  var m = newRef.match(re);
  if (!m) {
    console.log("reference invalid: "+newRef);
    return; // invalid reference
  }
  tableName = m[1];
  rowNumber = m[2];

  var $table = $("table[name]").filter(function() { return extractFieldName($(this).attr("name")) == tableName; });
  if ($table.length != 1) {
    console.log("cannot find table instance referenced");
    return;
  }
  var $tr = $table.children("tbody").children("tr[dynamic-table-row-number]").filter(function() { return $(this).attr("dynamic-table-row-number") == rowNumber; });
  var $invrefTable = $tr.children("td[data-formItemType=invref]").find("table.invref");

  var $templateRow = $invrefTable.children("tbody").children("tr.invref-template");
  var $ntr = $templateRow.clone(true);

  $ntr.removeClass("invref-template");
  $ntr.removeClass("summing-skip");
  $ntr.insertBefore($templateRow);

  $selTr = $sel.closest("tr.dynamic-table-row");

  $ntr.attr("invref-sel-id", $sel.attr("id"));
  $ntr.children("td.invref-rowTxt").text(getTrText($selTr));
  $ntr.children("td.cell-has-printSum").find("*[data-printSum]").each(function (i, e) {
    var $e = $(e);
    $e.data("print-sum-region", $selTr);
    $e.triggerHandler("update-print-sum");
  });

  $invrefTable.triggerHandler("update-invref-table");

}
