
$(document).ready(function() {
    $('.selectpicker').selectpicker({
        style: 'btn-default',
        size: false
    });

    $('.column-sum').each(function (i, e) {
       var $e = $(e);
       var colId = $e.data('col-id');
       $e.addClass(colId);
       $('.'+colId+' input').each(function() {
         $(this).on('change.column-sum', null, colId, function (evt) {
            var val= parseFloat($(this).val());
            $(this).val(val.toFixed(2));
           updateColumnSum(evt.data);
         });
       });
       updateColumnSum(colId);
    });

    $('.dynamic-table tbody').each(function (i, tbody) {
     var $tbody = $(tbody);
     var $tr = $tbody.find('tr.new-table-row').last();
     $tr.find("*").each(function(i,e) {
       var rowCount = 0;
       var $e = $(e);
       var id = $e.attr('id');
       if (!id) { return; }
       $e.data('orig-id', id);
       $e.attr('id',id+'-'+rowCount);
     });
     enableNewRowClock($tr, $tbody);
   }); /* each tbody */
});

function enableNewRowClock($tr, $tbody) {
  $tr.find("*").on('focus.dynamic-table', function (evt) {
    onClickNewRow($tr, $tbody);
  });
}

function onClickNewRow($tr, $tbody) {
  $tr.find("*").off('focus.dynamic-table');
  var $ntr = $tr.clone(true);
  var rowCount = $tbody.children("tr").length;
  $ntr.appendTo($tbody);
  $ntr.find(".row-number").text((rowCount+1)+".");
  $ntr.find("*").each(function(i,e) {
    var $e = $(e);
    var id = $e.data('orig-id');
    if (!id) { return; }
    $e.attr('id',id+'-'+rowCount);
  }); /* update id attribute of new row */
  enableNewRowClock($ntr, $tbody);
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

